<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->foreignId('application_id')->nullable()->unique()->after('work_job_id')
                ->constrained('user_work_job_applications')->nullOnDelete();
            $table->foreignId('employer_id')->nullable()->after('application_id')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable()->after('status');
            $table->string('timezone')->nullable()->after('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('timezone');
            $table->text('employer_note')->nullable()->after('duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('application_id');
            $table->dropConstrainedForeignId('employer_id');
            $table->dropColumn(['scheduled_at', 'timezone', 'duration_minutes', 'employer_note']);
        });
    }
};
