<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Automatically set tenant_id when creating a course
        static::creating(function ($course) {
            if (! $course->tenant_id) {
                $tenant = Tenant::current();
                if ($tenant) {
                    $course->tenant_id = $tenant->id;
                }
            }
        });

        // Scope queries to current tenant (only for single-database strategy)
        // When using multi-database, SwitchTenantDatabaseTask handles isolation
        if (config('multitenancy.database_strategy') === 'single') {
            static::addGlobalScope('tenant', function ($query) {
                $tenant = Tenant::current();
                if ($tenant) {
                    $query->where('courses.tenant_id', $tenant->id);
                }
            });
        }
    }

    /**
     * Get the connection name for the model.
     * Returns null for single-database (uses default connection).
     * For multi-database, connection is managed by SwitchTenantDatabaseTask.
     */
    public function getConnectionName(): ?string
    {
        if (config('multitenancy.database_strategy') === 'multi') {
            // Connection is automatically switched by SwitchTenantDatabaseTask
            return null;
        }

        // Single database strategy uses default connection
        return null;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'code',
        'content',
        'duration_hours',
        'credit_hours',
        'status',
        'start_date',
        'end_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_hours' => 'integer',
            'credit_hours' => 'integer',
        ];
    }

    /**
     * Get the tenant (hospital) that owns this course.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the enrollments for this course.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get enrolled residents (users) for this course.
     * Note: This returns user IDs since users are in landlord DB.
     */
    public function enrolledUserIds(): array
    {
        return $this->enrollments()->pluck('user_id')->toArray();
    }

    /**
     * Check if course is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
