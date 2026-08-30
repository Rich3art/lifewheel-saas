<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PluginPermissionRegistration extends Model
{
    protected $fillable = ['plugin_id', 'slug', 'manifest'];

    protected function casts(): array
    {
        return ['manifest' => 'array'];
    }
}
