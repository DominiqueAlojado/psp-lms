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
            $topic->slug = static::buildScopedSlug($topic);
        });
    }

    public static function buildScopedSlug(self $topic): string
    {
        $baseSlug = Str::slug($topic->slug ?: $topic->name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'topic';
        $slug = $baseSlug;
        $suffix = 2;

        while (static::slugExistsWithinScope($slug, $topic)) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private static function slugExistsWithinScope(string $slug, self $topic): bool
    {
        return static::query()
            ->when($topic->exists, fn ($query) => $query->whereKeyNot($topic->getKey()))
            ->where('slug', $slug)
            ->where(function ($query) use ($topic) {
                if ($topic->is_global || $topic->organization_id === null) {
                    $query->where('is_global', true);

                    return;
                }

                $query->where('is_global', false)
                    ->where('organization_id', $topic->organization_id);
            })
            ->exists();
    }

    /**
     * Get the organization that owns the topic.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
