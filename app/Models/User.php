<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the connection name for the model.
     * Users are stored in the landlord database.
     */
    public function getConnectionName(): ?string
    {
        return 'landlord';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
     * Get the hospitals (tenants) that this resident belongs to.
     */
    public function hospitals(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')
            ->withTimestamps();
    }

    /**
     * Check if the resident belongs to a specific hospital.
     */
    public function belongsToHospital(Tenant $hospital): bool
    {
        return $this->hospitals()->where('tenant_id', $hospital->id)->exists();
    }

    /**
     * Check if the resident belongs to the current tenant.
     */
    public function belongsToCurrentTenant(): bool
    {
        $currentTenant = Tenant::current();

        if (! $currentTenant) {
            return false;
        }

        return $this->belongsToHospital($currentTenant);
    }

    /**
     * Get enrollments for this user in the current tenant context.
     * Note: Enrollments are stored in single database with tenant_id.
     */
    public function enrollments()
    {
        $currentTenant = Tenant::current();

        if (! $currentTenant) {
            return collect();
        }

        // Query enrollments scoped to current tenant
        return Enrollment::where('user_id', $this->id)->get();
    }

    /**
     * Get courses this user is enrolled in for the current tenant.
     */
    public function enrolledCourses()
    {
        $enrollmentIds = $this->enrollments()->pluck('course_id');

        return Course::whereIn('id', $enrollmentIds)->get();
    }
}
