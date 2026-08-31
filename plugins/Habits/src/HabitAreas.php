<?php

namespace LifeWheel\Plugins\Habits;

final class HabitAreas
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
