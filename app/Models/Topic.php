<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Topic extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'organization_id',
        'is_global',
    ];

    protected function casts(): array
    {
        return [
            'is_global' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($topic) {
            if (empty($topic->slug)) {
                $topic->slug = Str::slug($topic->name);
            }
        });
    }

    /**
     * Get the organization that owns the topic.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
