<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed the two roles the application distinguishes and backfill every
     * existing user as a candidate, since the app was candidate-only until now.
     *
     * This lives in a migration rather than a seeder so `RefreshDatabase` tests
     * always have the roles available.
     */
    public function up(): void
    {
        $rolesTable = config('permission.table_names.roles');
        $pivotTable = config('permission.table_names.model_has_roles');
        $roleForeignKey = config('permission.column_names.role_pivot_key') ?? 'role_id';
        $modelForeignKey = config('permission.column_names.model_morph_key') ?? 'model_id';

        foreach (UserRole::cases() as $role) {
            DB::table($rolesTable)->updateOrInsert(
                ['name' => $role->value, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()],
            );
        }

        $candidateRoleId = DB::table($rolesTable)
            ->where('name', UserRole::Candidate->value)
            ->where('guard_name', 'web')
            ->value('id');

        DB::table('users')->orderBy('id')->select('id')->chunk(500, function ($users) use ($pivotTable, $roleForeignKey, $modelForeignKey, $candidateRoleId) {
            DB::table($pivotTable)->insertOrIgnore(
                $users->map(fn ($user) => [
                    $roleForeignKey => $candidateRoleId,
                    'model_type' => User::class,
                    $modelForeignKey => $user->id,
                ])->all()
            );
        });
    }

    public function down(): void
    {
        DB::table(config('permission.table_names.roles'))
            ->whereIn('name', array_column(UserRole::cases(), 'value'))
            ->delete();
    }
};
