<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_messages', function (Blueprint $table) {
            $table->string('type')->default('user')->after('body');
            $table->json('metadata')->nullable()->after('type');
            $table->index(['job_conversation_id', 'created_at'], 'job_messages_conversation_created_index');
            $table->index(['job_conversation_id', 'read_at', 'sender_id'], 'job_messages_unread_index');
        });

        $now = now();
        DB::table('user_work_job_applications as applications')
            ->join('work_jobs as jobs', 'jobs.id', '=', 'applications.work_job_id')
            ->whereNotNull('jobs.user_id')
            ->select([
                'applications.id as application_id',
                'applications.work_job_id',
                'applications.user_id as candidate_user_id',
                'jobs.user_id as employer_user_id',
            ])
            ->orderBy('applications.id')
            ->each(function (object $application) use ($now): void {
                DB::table('job_conversations')->insertOrIgnore([
                    'application_id' => $application->application_id,
                    'work_job_id' => $application->work_job_id,
                    'candidate_user_id' => $application->candidate_user_id,
                    'employer_user_id' => $application->employer_user_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('job_messages', function (Blueprint $table) {
            $table->dropIndex('job_messages_conversation_created_index');
            $table->dropIndex('job_messages_unread_index');
            $table->dropColumn(['type', 'metadata']);
        });
    }
};

