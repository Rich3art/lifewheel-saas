<?php

namespace LifeWheel\Plugins\Habits\Events;

use App\Models\User;

final readonly class HabitCompleted
{
    public function __construct(
        public User $user,
        public int $habitId,
        public int $logId,
    ) {
    }
}
