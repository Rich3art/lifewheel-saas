<?php

namespace LifeWheel\Plugins\LifeWheel\Events;

use App\Models\User;

final readonly class LifeWheelAssessmentCompleted
{
    public function __construct(
        public User $user,
        public int $assessmentId,
        public float $overallScore,
    ) {
    }
}
