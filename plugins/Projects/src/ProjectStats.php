<?php

namespace LifeWheel\Plugins\Projects;

final class ProjectStats
{
    public static function completion(int $completedTasks, int $totalTasks): ?int
    {
        if ($totalTasks <= 0) {
            return null;
        }

        return max(0, min(100, (int) round(($completedTasks / $totalTasks) * 100)));
    }
}
