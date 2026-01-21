<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vocabulary extends Model
{
    use HasFactory;

    protected $fillable = [
        'word',
        'type',
        'difficulty',
        'meaning',
        'part_of_speech',
        'example_sentence',
        'synonym',
        'antonym',
        'frequency',
        'tags',
    ];

    protected $casts = [
        'difficulty' => 'integer',
        'frequency' => 'integer',
    ];

    /**
     * この単語を使った問題
     */
    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
