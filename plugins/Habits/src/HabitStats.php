<?php

namespace LifeWheel\Plugins\Habits;

final class HabitStats
{
    public static function adherence(int $completed, int $expected): ?int
    {
        if ($expected <= 0) {
            return null;
        }

        return max(0, min(100, (int) round(($completed / $expected) * 100)));
    }
}
