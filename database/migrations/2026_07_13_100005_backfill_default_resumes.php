<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $usersWithoutResume = DB::table('users')
            ->whereNotIn('id', DB::table('resumes')->select('user_id'))
            ->get(['id']);

        foreach ($usersWithoutResume as $user) {
            $resumeId = DB::table('resumes')->insertGetId([
                'user_id' => $user->id,
                'title' => 'My Resume',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->attachAll($resumeId, 'skills', 'skill_id', 'resume_skill', $now);
            $this->attachAll($resumeId, 'projects', 'project_id', 'resume_project', $now);
            $this->attachAll($resumeId, 'education', 'education_id', 'resume_education', $now);
            $this->attachAll($resumeId, 'work_experiences', 'work_experience_id', 'resume_work_experience', $now);
        }
    }

    private function attachAll(int $resumeId, string $sourceTable, string $foreignKey, string $pivotTable, $now): void
    {
        $resume = DB::table('resumes')->where('id', $resumeId)->first(['user_id']);

        $items = DB::table($sourceTable)
            ->where('user_id', $resume->user_id)
            ->orderBy('id')
            ->get(['id']);

        $rows = $items->values()->map(fn ($item, int $index) => [
            'resume_id' => $resumeId,
            $foreignKey => $item->id,
            'order' => $index,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if (! empty($rows)) {
            DB::table($pivotTable)->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data backfill only; nothing structural to reverse.
    }
};
