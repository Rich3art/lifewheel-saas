<?php

namespace Tests\Unit;

use LifeWheel\Plugins\Goals\GoalProgress;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../plugins/Goals/src/GoalProgress.php';

final class GoalProgressTest extends TestCase
{
    public function test_progress_is_calculated_from_baseline_current_and_target(): void
    {
        $this->assertSame(50, GoalProgress::percentage(10, 20, 30));
    }

    public function test_progress_is_clamped(): void
    {
        $this->assertSame(100, GoalProgress::percentage(0, 120, 100));
        $this->assertSame(0, GoalProgress::percentage(100, 120, 0));
    }

    public function test_progress_is_unknown_when_target_equals_baseline(): void
    {
        $this->assertNull(GoalProgress::percentage(10, 10, 10));
    }
}
