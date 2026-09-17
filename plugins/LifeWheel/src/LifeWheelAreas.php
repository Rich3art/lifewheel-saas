<?php

namespace LifeWheel\Plugins\LifeWheel;

final class LifeWheelAreas
{
    public static function all(): array
    {
        return [
            ['key' => 'physical_health', 'name' => 'Physical Health', 'group' => 'Health'],
            ['key' => 'mental_wellness', 'name' => 'Mental Wellness', 'group' => 'Health'],
            ['key' => 'faith_purpose', 'name' => 'Faith & Purpose', 'group' => 'Purpose'],
            ['key' => 'marriage_relationships', 'name' => 'Marriage / Relationships', 'group' => 'Relationships'],
            ['key' => 'family_friends', 'name' => 'Family & Friends', 'group' => 'Relationships'],
            ['key' => 'career', 'name' => 'Career', 'group' => 'Work'],
            ['key' => 'business', 'name' => 'Business', 'group' => 'Work'],
            ['key' => 'money_finance', 'name' => 'Money & Finance', 'group' => 'Money'],
            ['key' => 'learning', 'name' => 'Learning', 'group' => 'Growth'],
            ['key' => 'personal_growth', 'name' => 'Personal Growth', 'group' => 'Growth'],
            ['key' => 'lifestyle', 'name' => 'Lifestyle', 'group' => 'Life'],
        ];
    }

    public static function keys(): array
    {
        return array_column(self::all(), 'key');
    }
}
