<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'current_organization_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the organizations that the user belongs to.
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot(['joined_at', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get the user's current organization.
     */
    public function currentOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'current_organization_id');
    }

    /**
     * Switch the user's current organization.
     */
    public function switchOrganization(Organization $organization): bool
    {
        // Verify user belongs to this organization
        if (! $this->organizations()->where('organizations.id', $organization->id)->exists()) {
            return false;
        }

        $this->update(['current_organization_id' => $organization->id]);

        // Set the permission team context
        setPermissionsTeamId($organization->id);

        // Clear cached roles and permissions
        $this->unsetRelation('roles')->unsetRelation('permissions');

        return true;
    }

    /**
     * Get the user's active organizations.
     */
    public function activeOrganizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot(['joined_at', 'is_active'])
            ->wherePivot('is_active', true)
            ->withTimestamps();
    }

    /**
     * Get all organizations where this user is a training officer.
     */
    public function organizationsAsTrainingOfficer()
    {
        return Organization::whereJsonContains('training_officers', $this->id)->get();
    }

    /**
     * Check if the user is a training officer in a specific organization.
     */
    public function isTrainingOfficerOf(Organization $organization): bool
    {
        return $organization->hasTrainingOfficer($this);
    }

    /**
     * Check if the user is a training officer in their current organization.
     */
    public function isTrainingOfficer(): bool
    {
        if (! $this->currentOrganization) {
            return false;
        }

        return $this->isTrainingOfficerOf($this->currentOrganization);
    }

    /**
     * Get the resident profile associated with this user.
     */
    public function resident()
    {
        return $this->hasOne(Resident::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'current_organization_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'User created',
                'updated' => 'User updated',
                'deleted' => 'User deleted',
                default => "User {$eventName}",
            })
            ->useLogName('users');
    }
}
