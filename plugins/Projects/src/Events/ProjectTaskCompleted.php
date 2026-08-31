<?php

namespace LifeWheel\Plugins\Projects\Events;

use App\Models\User;

final readonly class ProjectTaskCompleted
{
    public function __construct(
        public User $user,
        public int $projectId,
        public int $taskId,
    ) {
    }
}
