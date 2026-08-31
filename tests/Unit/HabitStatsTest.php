<?php

namespace Tests\Unit;

use LifeWheel\Plugins\Habits\HabitStats;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../plugins/Habits/src/HabitStats.php';

final class HabitStatsTest extends TestCase
{
    public function test_adherence_is_completed_over_expected_percentage(): void
    {
        $this->assertSame(75, HabitStats::adherence(3, 4));
    }

    public function test_adherence_is_clamped(): void
    {
        $this->assertSame(100, HabitStats::adherence(6, 4));
        $this->assertSame(0, HabitStats::adherence(-1, 4));
    }

    public function test_adherence_is_unknown_without_expected_count(): void
    {
        $this->assertNull(HabitStats::adherence(0, 0));
    }
}
