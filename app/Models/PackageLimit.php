<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PackageLimit extends Model
{
    protected $fillable = ['package_id', 'key', 'value'];
}
