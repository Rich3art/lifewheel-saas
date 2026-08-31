<?php

namespace Tests\Unit;

use LifeWheel\Plugins\LifeWheel\LifeWheelScoring;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../plugins/LifeWheel/src/LifeWheelScoring.php';

final class LifeWheelScoringTest extends TestCase
{
    public function test_overall_score_is_average_rounded_to_two_decimals(): void
    {
        $this->assertSame(6.67, LifeWheelScoring::overall([
            'body' => 6,
            'mind' => 7,
            'soul' => 7,
        ]));
    }

    public function test_scores_rank_from_weakest_to_strongest(): void
    {
        $this->assertSame([
            'money' => 3,
            'body' => 5,
            'mind' => 8,
        ], LifeWheelScoring::weakestToStrongest([
            'mind' => 8,
            'money' => 3,
            'body' => 5,
        ]));
    }
}
