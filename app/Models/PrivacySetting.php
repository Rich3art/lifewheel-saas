<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PrivacySetting extends Model
{
    protected $fillable = ['key', 'value', 'description'];
}
