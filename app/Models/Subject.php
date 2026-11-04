<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'tint'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->orderBy('sort');
    }
}
