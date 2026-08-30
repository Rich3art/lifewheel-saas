<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Feature extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'active', 'source'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class)->withPivot('enabled')->withTimestamps();
    }
}
