<?php

namespace Tests\Unit;

use App\Data\TemplateConfiguration;
use App\Services\Templates\StyleResolver;
use Tests\TestCase;

class StyleResolverTest extends TestCase
{
    private function config(): TemplateConfiguration
    {
        return TemplateConfiguration::fromArray([
            'layout' => 'single-column',
            'header' => ['style' => 'classic'],
            'defaults' => ['font_size' => 10.5, 'color_primary' => '#123456'],
            'allowed' => ['font_size' => [9, 12], 'layouts' => ['single-column'], 'fonts' => ['lato', 'ptserif'], 'photo' => false],
        ]);
    }

    public function test_defaults_come_from_the_template(): void
    {
        $style = app(StyleResolver::class)->resolve($this->config());

        $this->assertSame(10.5, $style->fontSize);
        $this->assertSame('#123456', $style->colorPrimary);
        $this->assertSame('single-column', $style->layout);
    }

    public function test_invalid_values_are_clamped_or_rejected(): void
    {
        $style = app(StyleResolver::class)->resolve($this->config(), [
            'font_size' => 99,
            'color_primary' => 'red; background:url(x)',
            'font_body' => '"><script>',
            'layout' => 'sidebar-left',
            'show_photo' => true,
            'unknown_key' => 'ignored',
        ]);

        $this->assertSame(12.0, $style->fontSize);
        $this->assertSame('#123456', $style->colorPrimary);
        $this->assertSame('lato', $style->fontBody);
        $this->assertSame('single-column', $style->layout);
        $this->assertFalse($style->showPhoto);
    }

    public function test_valid_overrides_apply(): void
    {
        $style = app(StyleResolver::class)->resolve($this->config(), ['font_size' => 9.5, 'color_primary' => '#ABCDEF', 'font_body' => 'ptserif']);

        $this->assertSame(9.5, $style->fontSize);
        $this->assertSame('#abcdef', $style->colorPrimary);
        $this->assertSame('ptserif', $style->fontBody);
    }
}
