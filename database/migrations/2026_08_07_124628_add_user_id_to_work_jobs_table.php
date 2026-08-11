<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Owner of the job posting. Nullable: seeded platform jobs have no employer
     * behind them and must stay visible to candidates.
     */
    public function up(): void
    {
        Schema::table('work_jobs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
