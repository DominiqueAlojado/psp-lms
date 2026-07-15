<?php

namespace App\Actions\Residents;

use App\Models\Organization;
use App\Models\Resident;
use App\Models\ResidentOrganizationMembership;

class TransferResidentOrganizationAction
{
    public function execute(Resident $resident, Organization $destinationOrganization): bool
    {
        if ($resident->organization_id === $destinationOrganization->id) {
            return false;
        }

        $resident->loadMissing(['user.organizations', 'memberships']);

        $now = now();
        $previousOrganizationId = $resident->organization_id;

        ResidentOrganizationMembership::query()
            ->where('resident_id', $resident->id)
            ->where('organization_id', $previousOrganizationId)
            ->whereNull('ended_at')
            ->update([
                'ended_at' => $now,
                'is_primary' => false,
            ]);

        $destinationMembership = ResidentOrganizationMembership::query()
            ->where('resident_id', $resident->id)
            ->where('organization_id', $destinationOrganization->id)
            ->orderByDesc('id')
            ->first();

        if ($destinationMembership) {
            $destinationMembership->update([
                'started_at' => $destinationMembership->started_at ?? $now,
                'ended_at' => null,
                'is_primary' => true,
                'year_level' => $resident->year_level,
                'status' => $resident->status,
            ]);
        } else {
            ResidentOrganizationMembership::create([
                'resident_id' => $resident->id,
                'organization_id' => $destinationOrganization->id,
                'started_at' => $now,
                'ended_at' => null,
                'is_primary' => true,
                'year_level' => $resident->year_level,
                'status' => $resident->status,
            ]);
        }

        $resident->update(['organization_id' => $destinationOrganization->id]);

        if ($resident->user) {
            if ($previousOrganizationId) {
                if ($resident->user->organizations()->where('organizations.id', $previousOrganizationId)->exists()) {
                    $resident->user->organizations()->updateExistingPivot($previousOrganizationId, [
                        'is_active' => false,
                    ]);
                } else {
                    $resident->user->organizations()->attach($previousOrganizationId, [
                        'joined_at' => $now,
                        'is_active' => false,
                    ]);
                }
            }

            if ($resident->user->organizations()->where('organizations.id', $destinationOrganization->id)->exists()) {
                $resident->user->organizations()->updateExistingPivot($destinationOrganization->id, [
                    'is_active' => true,
                ]);
            } else {
                $resident->user->organizations()->attach($destinationOrganization->id, [
                    'joined_at' => $now,
                    'is_active' => true,
                ]);
            }

            $resident->user->current_organization_id = $destinationOrganization->id;
            $resident->user->save();
        }

        return true;
    }
}
