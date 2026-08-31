<?php

namespace LifeWheel\Plugins\Goals\Events;

use App\Models\User;

final readonly class GoalCreated
{
    public function __construct(
        public User $user,
        public int $goalId,
    ) {
    }
}
