<?php

namespace Tests\Unit;

use LifeWheel\Plugins\Projects\ProjectStats;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../plugins/Projects/src/ProjectStats.php';

final class ProjectStatsTest extends TestCase
{
    public function test_completion_is_completed_tasks_over_total_tasks(): void
    {
        $this->assertSame(40, ProjectStats::completion(2, 5));
    }

    public function test_completion_is_clamped(): void
    {
        $this->assertSame(100, ProjectStats::completion(8, 5));
        $this->assertSame(0, ProjectStats::completion(-1, 5));
    }

    public function test_completion_is_unknown_without_tasks(): void
    {
        $this->assertNull(ProjectStats::completion(0, 0));
    }
}
