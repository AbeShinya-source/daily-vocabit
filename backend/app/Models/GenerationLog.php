<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GenerationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'generated_date',
        'type',
        'difficulty',
        'questions_count',
        'ai_model',
        'prompt_tokens',
        'completion_tokens',
        'total_cost',
        'status',
        'error_message',
    ];

    protected $casts = [
        'generated_date' => 'date',
        'difficulty' => 'integer',
        'questions_count' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_cost' => 'decimal:6',
    ];
}
