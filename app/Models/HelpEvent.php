<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpEvent extends Model
{
    protected $fillable = ['tutor_session_id', 'kind', 'source', 'student_text', 'content'];
}
