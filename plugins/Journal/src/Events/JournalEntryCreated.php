<?php

namespace LifeWheel\Plugins\Journal\Events;

use App\Models\User;

final readonly class JournalEntryCreated
{
    public function __construct(
        public User $user,
        public int $entryId,
    ) {
    }
}
