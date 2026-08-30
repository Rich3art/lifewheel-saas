<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InstalledPlugin extends Model
{
    protected $table = 'plugins';

    protected $primaryKey = 'plugin_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'plugin_id',
        'name',
        'version',
        'author',
        'description',
        'path',
        'status',
        'manifest',
        'installed_at',
        'activated_at',
        'deactivated_at',
    ];

    protected function casts(): array
    {
        return [
            'manifest' => 'array',
            'installed_at' => 'datetime',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    public function isEnabled(): bool
    {
        return $this->status === 'enabled';
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(PluginPermissionRegistration::class, 'plugin_id', 'plugin_id');
    }

    public function features(): HasMany
    {
        return $this->hasMany(PluginFeatureRegistration::class, 'plugin_id', 'plugin_id');
    }

    public function menus(): HasMany
    {
        return $this->hasMany(PluginMenuRegistration::class, 'plugin_id', 'plugin_id');
    }

    public function settingsSections(): HasMany
    {
        return $this->hasMany(PluginSettingsSectionRegistration::class, 'plugin_id', 'plugin_id');
    }
}
