<?php

namespace LifeWheel\Plugins\LifeWheel;

final class LifeWheelAreas
{
    public static function all(): array
    {
        return [
            ['key' => 'body', 'name' => 'Body', 'group' => 'Health'],
            ['key' => 'mind', 'name' => 'Mind', 'group' => 'Health'],
            ['key' => 'soul', 'name' => 'Soul', 'group' => 'Health'],
            ['key' => 'romance', 'name' => 'Romance', 'group' => 'Relationships'],
            ['key' => 'family', 'name' => 'Family', 'group' => 'Relationships'],
            ['key' => 'friends', 'name' => 'Friends', 'group' => 'Relationships'],
            ['key' => 'mission', 'name' => 'Mission', 'group' => 'Work'],
            ['key' => 'money', 'name' => 'Money', 'group' => 'Work'],
            ['key' => 'growth', 'name' => 'Growth', 'group' => 'Work'],
        ];
    }

    public static function keys(): array
    {
        return array_column(self::all(), 'key');
    }
}
