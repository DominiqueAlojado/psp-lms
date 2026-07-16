<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FeedbackEntry extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'organization_id',
        'user_id',
        'overall_rating',
        'content_rating',
        'support_rating',
        'usability_rating',
        'context',
        'module_name',
        'page_url',
        'comment',
        'would_recommend',
    ];

    protected function casts(): array
    {
        return [
            'would_recommend' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'organization_id',
                'user_id',
                'overall_rating',
                'content_rating',
                'support_rating',
                'usability_rating',
                'context',
                'module_name',
                'page_url',
                'comment',
                'would_recommend',
            ])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Feedback submitted',
                'updated' => 'Feedback updated',
                'deleted' => 'Feedback deleted',
                default => "Feedback {$eventName}",
            })
            ->useLogName('feedback');
    }
}
