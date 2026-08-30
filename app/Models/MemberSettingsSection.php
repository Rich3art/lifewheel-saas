<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class MemberSettingsSection extends Model
{
    protected $fillable = [
        'key',
        'label',
        'description',
        'source',
        'enabled',
        'required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'required' => 'boolean',
        ];
    }
}
