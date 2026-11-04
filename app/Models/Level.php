<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    protected $fillable = ['name', 'short_name', 'sort'];

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->orderBy('sort');
    }
}
