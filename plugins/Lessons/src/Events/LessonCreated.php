<?php

namespace LifeWheel\Plugins\Lessons\Events;

use App\Models\User;

final readonly class LessonCreated
{
    public function __construct(
        public User $user,
        public int $lessonId,
    ) {
    }
}
