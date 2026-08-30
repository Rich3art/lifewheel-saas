<?php

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    public function update(User $actor, User $subject): bool
    {
        return $actor->is($subject);
    }

    public function manageSecurity(User $actor, User $subject): bool
    {
        return $actor->is($subject);
    }
}
