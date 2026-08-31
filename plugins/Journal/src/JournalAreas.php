<?php

namespace LifeWheel\Plugins\Journal;

final class JournalAreas
{
    public static function all(): array
    {
        return [
            'body' => 'Body',
            'mind' => 'Mind',
            'soul' => 'Soul',
            'romance' => 'Romance',
            'family' => 'Family',
            'friends' => 'Friends',
            'mission' => 'Mission',
            'money' => 'Money',
            'growth' => 'Growth',
        ];
    }
}
