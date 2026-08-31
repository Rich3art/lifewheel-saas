<?php

namespace LifeWheel\Plugins\Gamification;

final class XpCalculator
{
    public static function levelFor(int $xp): int
    {
        if ($xp <= 0) {
            return 1;
        }

        return max(1, (int) floor(sqrt($xp / 100)) + 1);
    }

    public static function nextLevelXp(int $level): int
    {
        return max(0, ($level ** 2) * 100);
    }
}
