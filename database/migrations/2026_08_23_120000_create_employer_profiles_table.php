<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name')->nullable();
            $table->timestamps();
        });

        $employerRoleId = DB::table('roles')->where('name', 'employer')->value('id');

        if ($employerRoleId) {
            $now = now();
            $profiles = DB::table('model_has_roles')
                ->where('role_id', $employerRoleId)
                ->where('model_type', App\Models\User::class)
                ->pluck('model_id')
                ->map(fn ($userId) => [
                    'user_id' => $userId,
                    'company_name' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

            if ($profiles !== []) {
                DB::table('employer_profiles')->insertOrIgnore($profiles);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_profiles');
    }
};
