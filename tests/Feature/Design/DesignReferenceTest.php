<?php

namespace Tests\Feature\Design;

use App\Enums\ProcessingStatus;
use App\Enums\ResumeSource;
use App\Models\DesignReference;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DesignReferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCatalog();
        Storage::fake('local');
    }

    /**
     * A synthetic resume page: dark navy sidebar on the left, text lines on the right.
     */
    private function sidebarDesign(): UploadedFile
    {
        $image = imagecreatetruecolor(1240, 1754);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagefilledrectangle($image, 0, 0, 400, 1754, imagecolorallocate($image, 38, 47, 125));
        $ink = imagecolorallocate($image, 30, 30, 30);

        for ($y = 120; $y < 1600; $y += 46) {
            imagefilledrectangle($image, 470, $y, 470 + (($y * 7) % 500) + 200, $y + 12, $ink);
        }

        $path = tempnam(sys_get_temp_dir(), 'design').'.png';
        imagepng($image, $path);
        imagedestroy($image);

        return new UploadedFile($path, 'reference.png', 'image/png', null, true);
    }

    public function test_image_is_analysed_and_matched_to_a_sidebar_template(): void
    {
        $user = $this->userOnPlan('premium');

        $this->actingAs($user)->post(route('designs.store'), ['file' => $this->sidebarDesign()])->assertRedirect();

        $design = DesignReference::firstOrFail();
        $this->assertSame(ProcessingStatus::Completed, $design->status);
        $this->assertSame('sidebar-left', $design->analysis['layout']);
        $this->assertSame('modern-professional', $design->suggestedTemplate->slug);
        $this->assertArrayHasKey('color_primary', $design->generated_styling);

        $this->actingAs($user)->get(route('designs.show', $design))->assertOk();
        $this->actingAs($user)->get(route('designs.preview', $design))->assertOk();
        $this->actingAs($user)->get(route('designs.file', $design))->assertOk();
    }

    public function test_applying_a_design_creates_an_original_resume(): void
    {
        $user = $this->userOnPlan('premium');
        $this->actingAs($user)->post(route('designs.store'), ['file' => $this->sidebarDesign()]);
        $design = DesignReference::firstOrFail();

        $this->actingAs($user)->post(route('designs.apply', $design), ['target' => 'new', 'language' => 'en'])->assertRedirect();

        $resume = Resume::firstOrFail();
        $this->assertSame(ResumeSource::DesignReference, $resume->source);
        $this->assertSame($design->suggested_template_id, $resume->template_id);
        $this->assertSame($design->generated_styling['color_primary'], $resume->styling['color_primary']);
    }

    public function test_design_matching_is_a_premium_feature_and_private(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('designs.store'), ['file' => $this->sidebarDesign()])
            ->assertSessionHas('upgrade');

        $owner = $this->userOnPlan('premium');
        $this->actingAs($owner)->post(route('designs.store'), ['file' => $this->sidebarDesign()]);
        $design = DesignReference::firstOrFail();

        $this->actingAs($this->userOnPlan('premium'))->get(route('designs.file', $design))->assertForbidden();
    }
}
