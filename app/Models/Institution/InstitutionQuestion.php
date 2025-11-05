<?php

namespace App\Models\Institution;

use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstitutionQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assessment_id',
        'topic_id',
        'question_type',
        'question_text',
        'points',
        'explanation',
        'image_path',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'order' => 'integer',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(InstitutionAssessment::class, 'assessment_id');
    }

    public function choices(): HasMany
    {
        return $this->hasMany(InstitutionQuestionChoice::class, 'question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(InstitutionAnswer::class, 'question_id');
    }

    public function requiresChoices(): bool
    {
        return in_array($this->question_type, ['multiple_choice', 'multiple_select', 'true_false']);
    }

    public function isAutoGradable(): bool
    {
        return in_array($this->question_type, ['multiple_choice', 'multiple_select', 'true_false', 'fill_blank']);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
