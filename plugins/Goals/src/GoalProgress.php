<?php

namespace LifeWheel\Plugins\Goals;

final class GoalProgress
{
    public static function percentage(float $baseline, float $current, float $target): ?int
    {
        if ($target === $baseline) {
            return null;
        }

        $progress = (($current - $baseline) / ($target - $baseline)) * 100;

        return max(0, min(100, (int) round($progress)));
    }
}
