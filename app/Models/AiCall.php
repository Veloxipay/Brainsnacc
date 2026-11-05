<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCall extends Model
{
    protected $fillable = ['user_id', 'purpose', 'provider', 'model', 'input_tokens', 'output_tokens', 'ms', 'ok', 'error'];

    protected function casts(): array
    {
        return ['ok' => 'boolean'];
    }
}
