<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class UserFeatureOverride extends Model
{
    protected $fillable = ['user_id', 'feature_id', 'enabled', 'reason', 'created_by'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
