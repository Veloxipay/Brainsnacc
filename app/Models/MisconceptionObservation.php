<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MisconceptionObservation extends Model
{
    protected $fillable = ['user_id', 'tutor_session_id', 'working_line_id', 'skill_code', 'code', 'confidence', 'evidence'];
}
