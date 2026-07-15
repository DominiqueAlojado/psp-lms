<?php

namespace App\Actions\Fortify;

use App\Models\Resident;
use App\Models\ResidentOrganizationMembership;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Permitted year-level options for residents.
     *
     * @var array<int, string>
     */
    public const YEAR_LEVELS = [
        'Pre Resident',
        'First Year',
        'Second Year',
        'Third Year',
        'Fourth Year',
        'Graduate',
    ];

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $validated = Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'contact_number' => ['required', 'string', 'regex:/^(\+63|0)?9\d{9}$/'],
            'course' => ['required', 'string', 'max:255'],
            'year_level' => ['required', 'string', Rule::in(self::YEAR_LEVELS)],
            'password' => $this->passwordRules(),
        ], [
            'organization_id.required' => 'Please select your institution.',
            'contact_number.regex' => 'Contact number must be a valid Philippine mobile number (e.g., 09123456789 or +639123456789).',
        ])->validate();

        return DB::transaction(function () use ($validated) {
            $fullName = trim(
                Arr::join(
                    array_filter([
                        $validated['first_name'],
                        $validated['middle_name'],
                        $validated['last_name'],
                    ]),
                    ' ',
                ),
            );

            $user = User::create([
                'name' => $fullName,
                'email' => $validated['email'],
                'password' => $validated['password'],
                'current_organization_id' => $validated['organization_id'],
            ]);

            $resident = Resident::create([
                'user_id' => $user->id,
                'organization_id' => $validated['organization_id'],
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'contact_number' => $validated['contact_number'],
                'course' => $validated['course'],
                'year_level' => $validated['year_level'],
                'status' => 'active',
            ]);

            $user->organizations()->attach($validated['organization_id'], [
                'joined_at' => now(),
                'is_active' => true,
            ]);

            ResidentOrganizationMembership::create([
                'resident_id' => $resident->id,
                'organization_id' => $validated['organization_id'],
                'started_at' => now(),
                'ended_at' => null,
                'is_primary' => true,
                'year_level' => $resident->year_level,
                'status' => $resident->status,
            ]);

            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($validated['organization_id']);
            }

            $user->assignRole('Resident');

            return $user;
        });
    }
}
