<?php

namespace Tests\Unit;

use App\Data\FeatureAllowance;
use App\Enums\Feature;
use PHPUnit\Framework\TestCase;

class FeatureAllowanceTest extends TestCase
{
    public function test_limited_allowance(): void
    {
        $allowance = new FeatureAllowance(Feature::PdfExport, true, 3, 2);

        $this->assertTrue($allowance->allows());
        $this->assertFalse($allowance->allows(2));
        $this->assertSame(1, $allowance->remaining());
        $this->assertSame(67, $allowance->percentUsed());
    }

    public function test_unlimited_allowance(): void
    {
        $allowance = new FeatureAllowance(Feature::PdfExport, true, null, 500);

        $this->assertTrue($allowance->isUnlimited());
        $this->assertTrue($allowance->allows(1000));
        $this->assertNull($allowance->remaining());
        $this->assertNull($allowance->percentUsed());
    }

    public function test_disabled_allowance_never_allows(): void
    {
        $allowance = new FeatureAllowance(Feature::DocxExport, false, 0, 0);

        $this->assertFalse($allowance->allows());
        $this->assertSame(0, $allowance->remaining());
    }

    public function test_boolean_feature_ignores_limits(): void
    {
        $allowance = new FeatureAllowance(Feature::PremiumTemplates, true, null, 0);

        $this->assertTrue($allowance->allows());
        $this->assertNull($allowance->percentUsed());
    }
}
