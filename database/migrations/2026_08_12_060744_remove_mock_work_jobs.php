<?php

use Database\Factories\WorkJobFactory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $mockJobIds = collect(WorkJobFactory::mockReadyData())
            ->map(fn (array $data) => DB::table('work_jobs')
                ->where('title', $data['title'])
                ->where('company', $data['company'])
                ->value('id'))
            ->filter()
            ->all();

        if (empty($mockJobIds)) {
            return;
        }

        // user_work_job_applications.work_job_id cascades on delete, so
        // applications against these mock jobs are removed automatically.
        DB::table('work_jobs')->whereIn('id', $mockJobIds)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data cleanup only; re-run WorkJobSeeder to restore the mock jobs.
    }
};
