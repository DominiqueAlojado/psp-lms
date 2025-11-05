<?php

namespace App\Models\National;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NationalQuestionChoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'question_id',
        'choice_text',
        'is_correct',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(NationalQuestion::class, 'question_id');
    }
}
