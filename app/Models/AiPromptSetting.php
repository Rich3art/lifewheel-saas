<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AiPromptSetting extends Model
{
    protected $fillable = [
        'key',
        'label',
        'prompt',
    ];
}
