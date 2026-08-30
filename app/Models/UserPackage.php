<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class UserPackage extends Model
{
    protected $fillable = ['user_id', 'package_id', 'status', 'starts_at', 'ends_at', 'assigned_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }
}
