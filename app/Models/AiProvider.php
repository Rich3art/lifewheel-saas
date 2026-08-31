<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AiProvider extends Model
{
    protected $fillable = [
        'key',
        'name',
        'enabled',
        'mock_mode',
        'encrypted_api_key',
        'base_url',
        'settings',
    ];

    protected $hidden = ['encrypted_api_key'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'mock_mode' => 'boolean',
            'encrypted_api_key' => 'encrypted',
            'settings' => 'array',
        ];
    }

    public function routes(): HasMany
    {
        return $this->hasMany(AiModelRoute::class);
    }
}
