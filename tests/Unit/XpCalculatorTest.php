<?php

namespace Tests\Unit;

use LifeWheel\Plugins\Gamification\XpCalculator;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../plugins/Gamification/src/XpCalculator.php';

final class XpCalculatorTest extends TestCase
{
    public function test_level_starts_at_one(): void
    {
        $this->assertSame(1, XpCalculator::levelFor(0));
    }

    public function test_level_increases_from_xp_thresholds(): void
    {
        $this->assertSame(2, XpCalculator::levelFor(100));
        $this->assertSame(3, XpCalculator::levelFor(400));
    }

    public function test_next_level_xp_uses_square_curve(): void
    {
        $this->assertSame(400, XpCalculator::nextLevelXp(2));
    }
}
