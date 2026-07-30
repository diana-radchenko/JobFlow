<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->foreignId('resume_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('work_job_id')->nullable()->after('resume_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resume_id');
            $table->dropConstrainedForeignId('work_job_id');
        });
    }
};
