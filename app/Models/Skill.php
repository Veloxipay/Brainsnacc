<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable = ['code', 'name', 'year', 'strand', 'objective', 'curriculum_code', 'mapping_status'];
}
