<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
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
        return $this->organizations()->wherePivot('is_active', true);
    }
}
