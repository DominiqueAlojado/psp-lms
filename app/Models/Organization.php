<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Organization extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'logo',
        'is_active',
        'settings',
        'training_officers',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
            'training_officers' => 'array',
        ];
    }

    /**
     * Get the users that belong to this organization.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['joined_at', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get the active users that belong to this organization.
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('is_active', true);
    }

    /**
     * Get the training officer User models.
     */
    public function getTrainingOfficerUsersAttribute()
    {
        if (empty($this->training_officers)) {
            return collect([]);
        }

        return User::whereIn('id', $this->training_officers)->get();
    }

    /**
     * Add a training officer to the organization.
     */
    public function addTrainingOfficer(User $user): bool
    {
        $officers = $this->training_officers ?? [];

        // Check if user is already a training officer
        if (in_array($user->id, $officers)) {
            return false;
        }

        $officers[] = $user->id;
        $this->update(['training_officers' => $officers]);

        return true;
    }

    /**
     * Remove a training officer from the organization.
     */
    public function removeTrainingOfficer(User $user): bool
    {
        $officers = $this->training_officers ?? [];

        $key = array_search($user->id, $officers);

        if ($key === false) {
            return false;
        }

        unset($officers[$key]);
        $this->update(['training_officers' => array_values($officers)]);

        return true;
    }

    /**
     * Sync training officers (replaces all existing with new list).
     */
    public function syncTrainingOfficers(array $userIds): void
    {
        $this->update(['training_officers' => array_values(array_unique($userIds))]);
    }

    /**
     * Check if a user is a training officer of this organization.
     */
    public function hasTrainingOfficer(User $user): bool
    {
        return in_array($user->id, $this->training_officers ?? []);
    }

    /**
     * Get the count of training officers.
     */
    public function getTrainingOfficersCountAttribute(): int
    {
        return count($this->training_officers ?? []);
    }

    /**
     * Get the residents in this organization.
     */
    public function residents()
    {
        return $this->hasMany(Resident::class);
    }

    /**
     * Get the active residents in this organization.
     */
    public function activeResidents()
    {
        return $this->residents()->where('status', 'active');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'type', 'is_active', 'training_officers'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Organization created',
                'updated' => 'Organization updated',
                'deleted' => 'Organization deleted',
                default => "Organization {$eventName}",
            })
            ->useLogName('organizations');
    }
}
