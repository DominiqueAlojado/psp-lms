<?php

use App\Models\Resident;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_organization_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('year_level')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();

            $table->index(['resident_id', 'organization_id']);
            $table->index(['resident_id', 'is_primary']);
        });

        $driver = DB::getDriverName();
        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('CREATE UNIQUE INDEX resident_primary_membership_unique ON resident_organization_memberships (resident_id) WHERE is_primary = true AND ended_at IS NULL');
        }

        Resident::query()
            ->with(['user.organizations'])
            ->chunkById(100, function ($residents): void {
                foreach ($residents as $resident) {
                    if ($resident->organization_id) {
                        DB::table('resident_organization_memberships')->insert([
                            'resident_id' => $resident->id,
                            'organization_id' => $resident->organization_id,
                            'started_at' => $resident->created_at,
                            'ended_at' => null,
                            'is_primary' => true,
                            'year_level' => $resident->year_level,
                            'status' => $resident->status,
                            'created_at' => $resident->created_at ?? now(),
                            'updated_at' => $resident->updated_at ?? now(),
                        ]);
                    }

                    if (! $resident->user) {
                        continue;
                    }

                    foreach ($resident->user->organizations as $organization) {
                        if ((int) $organization->id === (int) $resident->organization_id) {
                            continue;
                        }

                        DB::table('resident_organization_memberships')->insert([
                            'resident_id' => $resident->id,
                            'organization_id' => $organization->id,
                            'started_at' => $organization->pivot->joined_at ?? $resident->created_at,
                            'ended_at' => ($organization->pivot->is_active ?? true) ? null : now(),
                            'is_primary' => false,
                            'year_level' => $resident->year_level,
                            'status' => $resident->status,
                            'created_at' => $resident->created_at ?? now(),
                            'updated_at' => $resident->updated_at ?? now(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS resident_primary_membership_unique');
        }

        Schema::dropIfExists('resident_organization_memberships');
    }
};
