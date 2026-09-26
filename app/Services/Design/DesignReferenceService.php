<?php

declare(strict_types=1);

namespace App\Services\Design;

use App\Enums\AiFeature;
use App\Enums\Feature;
use App\Enums\ProcessingStatus;
use App\Enums\ResumeSource;
use App\Exceptions\AiRequestFailed;
use App\Exceptions\FeatureLimitReached;
use App\Jobs\AnalyzeDesignReference;
use App\Models\DesignReference;
use App\Models\Resume;
use App\Models\User;
use App\Services\Ai\AiAssistant;
use App\Services\Import\Extractors\PdfTextExtractor;
use App\Services\Import\SectionHeadings;
use App\Services\Resume\ResumeService;
use App\Services\Templates\TemplateRegistry;
use App\Services\Usage\FeatureGate;
use App\Services\Usage\UsageMeter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * "Upload a CV design": analyse the structure and style of a reference and turn
 * it into an original design (closest template + inspired styling). Branding,
 * logos, photos and text of the reference are never copied.
 */
final class DesignReferenceService
{
    private const SERIF_HINTS = ['times', 'georgia', 'garamond', 'cambria', 'serif', 'merriweather', 'playfair', 'baskerville', 'didot', 'bodoni', 'palatino', 'book antiqua', 'minion', 'caslon', 'amiri', 'naskh'];

    public function __construct(
        private readonly FeatureGate $gate,
        private readonly UsageMeter $meter,
        private readonly ImageDesignAnalyzer $images,
        private readonly PdfTextExtractor $pdf,
        private readonly SectionHeadings $headings,
        private readonly TemplateMatcher $matcher,
        private readonly TemplateRegistry $templates,
        private readonly AiAssistant $ai,
        private readonly ResumeService $resumes,
    ) {
    }

    /**
     * @throws FeatureLimitReached
     */
    public function store(User $user, UploadedFile $file): DesignReference
    {
        $this->gate->authorize($user, Feature::DesignReference);

        $mime = (string) $file->getMimeType();
        $extension = match ($mime) {
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            default => 'jpg',
        };
        $disk = (string) config('cv.disks.private', 'local');

        $design = $user->designReferences()->create([
            'status' => ProcessingStatus::Pending,
            'disk' => $disk,
            'path' => $file->storeAs('designs/'.$user->id, Str::uuid().'.'.$extension, $disk),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
            'mime_type' => $mime,
            'file_size' => (int) $file->getSize(),
        ]);

        AnalyzeDesignReference::dispatch($design->id)->afterCommit();

        return $design;
    }

    public function analyze(DesignReference $design): void
    {
        $design->forceFill(['status' => ProcessingStatus::Processing])->save();
        $path = Storage::disk($design->disk)->path($design->path);

        try {
            $analysis = $design->mime_type === 'application/pdf' ? $this->analyzePdf($path) : $this->images->analyze($path);
        } catch (Throwable $exception) {
            $this->fail($design, __('designs.errors.unreadable'), $exception);

            return;
        }

        $analyzer = 'heuristic';

        // An AI vision pass refines what pixels cannot tell (serif vs sans, header style, section order).
        if ($design->mime_type !== 'application/pdf' && $this->ai->enabled() && $this->gate->allows($design->user, Feature::AiRequests)) {
            try {
                $analysis = $this->refineWithAi($design->user, $path, $design->mime_type, $analysis);
                $analyzer = 'heuristic+ai';
            } catch (AiRequestFailed|FeatureLimitReached $exception) {
                report($exception);
            }
        }

        $available = $this->templates->active()->filter(fn ($template) => $this->templates->isAvailableTo($design->user, $template));
        $match = $this->matcher->match($analysis, $available->isNotEmpty() ? $available : $this->templates->active());

        $design->forceFill([
            'status' => ProcessingStatus::Completed,
            'analysis' => $analysis,
            'suggested_template_id' => $match['template']->id ?? null,
            'similarity' => $match['similarity'] ?? null,
            'generated_styling' => $match['styling'] ?? null,
            'analyzer' => $analyzer,
            'processed_at' => now(),
        ])->save();

        $this->meter->increment($design->user, Feature::DesignReference);
    }

    /**
     * New resume from the suggested template with the inspired styling.
     */
    public function createResume(DesignReference $design, User $user, string $language): Resume
    {
        $template = $design->suggestedTemplate ?? $this->templates->default();

        $resume = $this->resumes->create(
            user: $user,
            title: __('designs.resume_title'),
            template: $template,
            language: $language,
            source: ResumeSource::DesignReference,
            styling: $design->generated_styling,
        );

        $design->forceFill(['resume_id' => $resume->id])->save();

        return $resume;
    }

    /**
     * Applies the suggested template and styling to an existing resume.
     */
    public function applyTo(DesignReference $design, User $user, Resume $resume): Resume
    {
        $template = $design->suggestedTemplate ?? $resume->template;
        $this->resumes->changeTemplate($resume, $user, $template);

        $resume->forceFill(['styling' => $design->generated_styling, 'version' => $resume->version + 1, 'last_edited_at' => now()])->save();
        $design->forceFill(['resume_id' => $resume->id])->save();

        return $resume;
    }

    public function delete(DesignReference $design): void
    {
        Storage::disk($design->disk)->delete($design->path);
        $design->delete();
    }

    public function fail(DesignReference $design, string $message, ?Throwable $exception = null): void
    {
        $design->forceFill(['status' => ProcessingStatus::Failed, 'error' => $message, 'processed_at' => now()])->save();

        if ($exception !== null) {
            report($exception);
        }
    }

    /**
     * Uploads never applied to a resume are removed after a week.
     */
    public function pruneAbandoned(): int
    {
        $count = 0;

        DesignReference::query()
            ->whereNull('resume_id')
            ->where('created_at', '<', now()->subDays(7))
            ->chunkById(200, function ($designs) use (&$count): void {
                foreach ($designs as $design) {
                    $this->delete($design);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * PDFs: fonts tell serif vs sans; heading order tells the section structure.
     * Layout and colours cannot be measured without rasterising, so they stay "unknown".
     *
     * @return array<string, mixed>
     */
    private function analyzePdf(string $path): array
    {
        $fonts = $this->pdf->fonts($path);
        $text = $this->pdf->extract($path);
        $order = [];

        foreach (explode("\n", $text) as $line) {
            $type = $this->headings->match($line);

            if ($type !== null && ! in_array($type->value, $order, true)) {
                $order[] = $type->value;
            }
        }

        $serif = collect($fonts)->contains(fn (string $font) => Str::contains(mb_strtolower($font), self::SERIF_HINTS));

        return [
            'source' => 'pdf',
            'layout' => 'unknown',
            'sidebar' => null,
            'header_style' => 'unknown',
            'banner' => null,
            'colors' => [],
            'density' => mb_strlen($text) > 3500 ? 'compact' : 'balanced',
            'typography' => $fonts === [] ? 'unknown' : ($serif ? 'serif' : 'sans'),
            'fonts' => array_slice($fonts, 0, 8),
            'section_order' => $order,
        ];
    }

    /**
     * @param  array<string, mixed>  $analysis
     * @return array<string, mixed>
     */
    private function refineWithAi(User $user, string $path, string $mime, array $analysis): array
    {
        $result = $this->ai->extract(
            user: $user,
            feature: AiFeature::AnalyzeDesign,
            instructions: 'Describe only the visual STRUCTURE and STYLE of this resume design. Do not transcribe any text, names or logos. Choose the closest values.',
            text: '',
            schema: [
                'type' => 'object',
                'properties' => [
                    'layout' => ['type' => 'string', 'enum' => ['single-column', 'sidebar-left', 'sidebar-right']],
                    'header_style' => ['type' => 'string', 'enum' => ['classic', 'centered', 'accent', 'split', 'compact', 'banner']],
                    'typography' => ['type' => 'string', 'enum' => ['serif', 'sans']],
                    'has_photo' => ['type' => 'boolean'],
                    'section_order' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['summary', 'experience', 'education', 'skills', 'languages', 'certifications', 'projects', 'achievements', 'awards', 'volunteer', 'publications', 'courses', 'references']]],
                ],
                'required' => ['layout', 'header_style', 'typography', 'has_photo', 'section_order'],
                'additionalProperties' => false,
            ],
            images: [['mime' => $mime, 'data' => base64_encode((string) file_get_contents($path))]],
        );

        // Pixel measurements win for layout when they found a clear band; the AI fills the gaps.
        return array_merge($analysis, array_filter([
            'layout' => $analysis['sidebar'] === null ? ($result['layout'] ?? null) : null,
            'header_style' => $analysis['header_style'] === 'banner' ? null : ($result['header_style'] ?? null),
            'typography' => $result['typography'] ?? null,
            'has_photo' => $result['has_photo'] ?? null,
            'section_order' => $result['section_order'] ?? null,
        ], fn ($value) => $value !== null));
    }
}
