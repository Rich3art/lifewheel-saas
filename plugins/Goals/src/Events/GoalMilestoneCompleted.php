<?php

namespace LifeWheel\Plugins\Goals\Events;

use App\Models\User;

final readonly class GoalMilestoneCompleted
{
    public function __construct(
        public User $user,
        public int $goalId,
        public int $milestoneId,
    ) {
    }
}
