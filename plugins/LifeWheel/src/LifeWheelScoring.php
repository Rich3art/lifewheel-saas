<?php

namespace LifeWheel\Plugins\LifeWheel;

final class LifeWheelScoring
{
    public static function overall(array $scores): float
    {
        if ($scores === []) {
            return 0.0;
        }

        return round(array_sum($scores) / count($scores), 2);
    }

    public static function weakestToStrongest(array $scores): array
    {
        asort($scores);

        return $scores;
    }
}
