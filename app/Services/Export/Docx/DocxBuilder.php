<?php

declare(strict_types=1);

namespace App\Services\Export\Docx;

use App\Data\DocumentSection;
use App\Data\ResumeDocument;
use App\Enums\SectionType;
use App\Services\Templates\DateFormatter;
use App\Services\Templates\FontRegistry;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Language;

/**
 * Builds an editable Word document from the resume data. Multi-column creative
 * layouts are deliberately flattened to one clean column: a DOCX that people
 * can edit beats a pixel copy that breaks when touched. Fonts map to ones every
 * Word installation has, so the file never falls back to a random substitute.
 */
final class DocxBuilder
{
    private const TWIPS_PER_POINT = 20;

    public function __construct(
        private readonly DateFormatter $dates,
        private readonly FontRegistry $fonts,
    ) {
    }

    public function build(ResumeDocument $document): string
    {
        Settings::setOutputEscapingEnabled(true);

        $style = $document->style;
        $rtl = $document->rtl;
        $word = new PhpWord();
        // PHPWord has no Arabic constant; "ar-SA" is the standard OOXML bidi language tag.
        $word->getSettings()->setThemeFontLang(new Language(Language::EN_US, null, 'ar-SA'));
        $word->getDocInfo()->setTitle($document->fullName() ?: $document->title)->setCreator($document->fullName());
        $word->setDefaultFontName($this->wordFont($style->bodyFont($rtl)));
        $word->setDefaultFontSize($style->fontSize);

        $margin = (int) Converter::cmToTwip($style->pageMarginMm / 10);
        $section = $word->addSection([
            'paperSize' => 'A4',
            'marginTop' => $margin,
            'marginBottom' => $margin,
            'marginLeft' => $margin,
            'marginRight' => $margin,
        ]);

        $this->header($section, $document);

        foreach ($document->sections as $documentSection) {
            $this->section($section, $document, $documentSection);
        }

        $temp = tempnam(sys_get_temp_dir(), 'docx');
        IOFactory::createWriter($word, 'Word2007')->save($temp);
        $bytes = (string) file_get_contents($temp);
        @unlink($temp);

        return $bytes;
    }

    private function header(Section $section, ResumeDocument $document): void
    {
        $style = $document->style;
        $paragraph = $this->paragraph($document, ['spaceAfter' => 0]);

        if ($document->fullName() !== '') {
            $section->addText($document->fullName(), $this->font($document, ['bold' => true, 'size' => round($style->fontSize * 2.1, 1), 'color' => $this->hex($style->colorText), 'name' => $this->wordFont($style->headingFont($document->rtl))]), $paragraph);
        }

        if (($document->personal['headline'] ?? '') !== '') {
            $section->addText($document->personal['headline'], $this->font($document, ['size' => round($style->fontSize * 1.15, 1), 'color' => $this->hex($style->colorPrimary)]), $paragraph);
        }

        $contacts = array_column($document->contacts(), 'text');

        if ($contacts !== []) {
            $section->addText(implode('  |  ', $contacts), $this->font($document, ['size' => round($style->fontSize * 0.9, 1), 'color' => $this->hex($style->colorSecondary)]), $this->paragraph($document, [
                'spaceBefore' => 80,
                'spaceAfter' => 0,
                'borderBottomSize' => 12,
                'borderBottomColor' => $this->hex($style->colorPrimary),
            ]));
        }
    }

    private function section(Section $section, ResumeDocument $document, DocumentSection $documentSection): void
    {
        $style = $document->style;

        $section->addText(
            $style->sectionTitleStyle === 'caps' && ! $document->rtl ? mb_strtoupper($documentSection->title) : $documentSection->title,
            $this->font($document, ['bold' => true, 'size' => round($style->fontSize * $style->headingScale, 1), 'color' => $this->hex($style->colorPrimary), 'name' => $this->wordFont($style->headingFont($document->rtl))]),
            $this->paragraph($document, [
                'spaceBefore' => (int) ($style->sectionSpacing * self::TWIPS_PER_POINT),
                'spaceAfter' => 80,
                'keepNext' => true,
                'borderBottomSize' => in_array($style->sectionTitleStyle, ['underline', 'caps'], true) ? 6 : 0,
                'borderBottomColor' => $this->hex($style->colorPrimary),
            ]),
        );

        match ($documentSection->type) {
            SectionType::Summary => $this->paragraphs($section, $document, (string) ($documentSection->items[0]['content'] ?? '')),
            SectionType::Skills => $this->skills($section, $document, $documentSection),
            SectionType::Languages => $this->languages($section, $document, $documentSection),
            default => $this->entries($section, $document, $documentSection),
        };
    }

    private function entries(Section $section, ResumeDocument $document, DocumentSection $documentSection): void
    {
        foreach ($documentSection->items as $index => $item) {
            [$title, $subtitle, $meta, $description, $highlights] = $this->entryParts($documentSection->type, $item, $document->language);
            $spaceBefore = $index === 0 ? 0 : (int) ($document->style->itemSpacing * self::TWIPS_PER_POINT);

            if ($title !== '' || $subtitle !== '') {
                $run = $section->addTextRun($this->paragraph($document, ['spaceBefore' => $spaceBefore, 'spaceAfter' => 0, 'keepNext' => true]));
                if ($title !== '') {
                    $run->addText($title, $this->font($document, ['bold' => true]));
                }
                if ($title !== '' && $subtitle !== '') {
                    $run->addText(' — ', $this->font($document));
                }
                if ($subtitle !== '') {
                    $run->addText($subtitle, $this->font($document));
                }
            }

            $meta = array_values(array_filter($meta, fn ($value) => filled($value)));

            if ($meta !== []) {
                $section->addText(implode(' · ', $meta), $this->font($document, ['size' => round($document->style->fontSize * 0.9, 1), 'color' => $this->hex($document->style->colorSecondary)]), $this->paragraph($document, ['spaceAfter' => 40]));
            }

            if ($description !== '') {
                $this->paragraphs($section, $document, $description);
            }

            foreach ($highlights as $highlight) {
                $section->addListItem($highlight, 0, $this->font($document), ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED], $this->paragraph($document, ['spaceAfter' => 20]));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{0: string, 1: string, 2: list<string>, 3: string, 4: list<string>}
     */
    private function entryParts(SectionType $type, array $item, string $language): array
    {
        $range = fn (string $start, string $end, bool $current = false): string => $this->dates->range($item[$start] ?? '', $item[$end] ?? '', $current, $language);
        $month = fn (string $key): string => $this->dates->month($item[$key] ?? '', $language);
        $url = fn (): string => (string) preg_replace('#^https?://(www\.)?#i', '', (string) ($item['url'] ?? ''));

        return match ($type) {
            SectionType::Experience => [$item['position'], $item['employer'], [$range('start_date', 'end_date', (bool) $item['is_current']), $item['location']], $item['description'], $item['highlights']],
            SectionType::Education => [trim($item['degree'].($item['degree'] !== '' && $item['field'] !== '' ? ', ' : '').$item['field']), $item['institution'], [$range('start_date', 'end_date'), $item['location'], $item['grade'] !== '' ? __('resume.grade', ['grade' => $item['grade']], $language) : ''], $item['description'], []],
            SectionType::Certifications => [$item['name'], $item['issuer'], [$range('issued_at', 'expires_at'), $item['credential_id'] !== '' ? __('resume.credential', ['id' => $item['credential_id']], $language) : '', $url()], '', []],
            SectionType::Projects => [$item['name'], $item['role'], [$range('start_date', 'end_date'), $url()], $item['description'], $item['highlights']],
            SectionType::Achievements, SectionType::Awards => [$item['title'], $item['issuer'], [$month('date')], $item['description'], []],
            SectionType::Volunteer => [$item['role'], $item['organization'], [$range('start_date', 'end_date'), $item['location']], $item['description'], []],
            SectionType::Publications => [$item['title'], $item['publisher'], [$month('date'), $url()], $item['description'], []],
            SectionType::Courses => [$item['name'], $item['institution'], [$month('date')], $item['description'], []],
            SectionType::References => [$item['name'], trim($item['position'].($item['position'] !== '' && $item['company'] !== '' ? ', ' : '').$item['company']), [$item['relationship'], $item['email'], $item['phone']], '', []],
            default => [$item['title'] ?? '', $item['subtitle'] ?? '', [$item['date'] ?? '', $url()], $item['description'] ?? '', []],
        };
    }

    private function skills(Section $section, ResumeDocument $document, DocumentSection $documentSection): void
    {
        $groups = collect($documentSection->items)->groupBy(fn (array $item) => $item['group']);

        foreach ($groups as $group => $items) {
            $run = $section->addTextRun($this->paragraph($document, ['spaceAfter' => 40]));

            if ($group !== '') {
                $run->addText($group.': ', $this->font($document, ['bold' => true]));
            }

            $run->addText($items->pluck('name')->filter()->implode(' · '), $this->font($document));
        }
    }

    private function languages(Section $section, ResumeDocument $document, DocumentSection $documentSection): void
    {
        foreach ($documentSection->items as $item) {
            $run = $section->addTextRun($this->paragraph($document, ['spaceAfter' => 20]));
            $run->addText($item['name'], $this->font($document));

            if ($item['proficiency'] !== '') {
                $run->addText(' — '.__('resume.proficiency.'.$item['proficiency'], locale: $document->language), $this->font($document, ['color' => $this->hex($document->style->colorSecondary)]));
            }
        }
    }

    private function paragraphs(Section $section, ResumeDocument $document, string $text): void
    {
        foreach (preg_split("/\n{2,}/", trim($text)) ?: [] as $block) {
            $run = $section->addTextRun($this->paragraph($document, ['spaceAfter' => 60]));

            foreach (explode("\n", $block) as $index => $line) {
                if ($index > 0) {
                    $run->addTextBreak();
                }

                $run->addText($line, $this->font($document));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function font(ResumeDocument $document, array $extra = []): array
    {
        return array_merge([
            'name' => $this->wordFont($document->style->bodyFont($document->rtl)),
            'size' => $document->style->fontSize,
            'color' => $this->hex($document->style->colorText),
            'rtl' => $document->rtl,
        ], $extra);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function paragraph(ResumeDocument $document, array $extra = []): array
    {
        return array_merge([
            'bidi' => $document->rtl,
            'lineHeight' => $document->style->lineHeight,
        ], $extra);
    }

    /**
     * Fonts every Word installation has, chosen by the template font's category.
     */
    private function wordFont(string $fontKey): string
    {
        $arabic = array_key_exists($fontKey, $this->fonts->options('arabic'));
        $serif = $this->fonts->category($fontKey) === 'serif';

        return match (true) {
            $arabic && $serif => 'Times New Roman',
            $arabic => 'Arial',
            $serif => 'Cambria',
            default => 'Calibri',
        };
    }

    private function hex(string $color): string
    {
        return ltrim($color, '#');
    }
}
