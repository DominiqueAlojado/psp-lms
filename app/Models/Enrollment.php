<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory;

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Automatically set tenant_id when creating an enrollment
        static::creating(function ($enrollment) {
            if (! $enrollment->tenant_id) {
                $tenant = Tenant::current();
                if ($tenant) {
                    $enrollment->tenant_id = $tenant->id;
                }
            }
        });

        // Scope queries to current tenant (only for single-database strategy)
        // When using multi-database, SwitchTenantDatabaseTask handles isolation
        if (config('multitenancy.database_strategy') === 'single') {
            static::addGlobalScope('tenant', function ($query) {
                $tenant = Tenant::current();
                if ($tenant) {
                    $query->where('enrollments.tenant_id', $tenant->id);
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
        'user_id',
        'course_id',
        'status',
        'enrolled_at',
        'completed_at',
        'grade',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enrolled_at' => 'date',
            'completed_at' => 'date',
            'grade' => 'decimal:2',
        ];
    }

    /**
     * Get the tenant (hospital) that owns this enrollment.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the course for this enrollment.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the user (resident) for this enrollment.
     * Note: Returns a user instance from landlord database.
     */
    public function user(): ?User
    {
        // Users are in landlord DB, so we need to query across connections
        return User::on('landlord')->find($this->user_id);
    }

    /**
     * Check if enrollment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if enrollment is active (enrolled or in progress).
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['enrolled', 'in_progress']);
    }

    /**
     * Mark enrollment as completed.
     */
    public function markAsCompleted(?float $grade = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'grade' => $grade ?? $this->grade,
        ]);
    }
}
