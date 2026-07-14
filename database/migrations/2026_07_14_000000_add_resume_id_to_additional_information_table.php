<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('additional_information', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });

        Schema::table('additional_information', function (Blueprint $table) {
            $table->foreignId('resume_id')->nullable()->constrained()->cascadeOnDelete();
        });

        $now = now();

        DB::table('additional_information')->orderBy('id')->each(function ($info) use ($now) {
            $resumeIds = DB::table('resumes')
                ->where('user_id', $info->user_id)
                ->orderBy('id')
                ->pluck('id');

            if ($resumeIds->isEmpty()) {
                return;
            }

            DB::table('additional_information')
                ->where('id', $info->id)
                ->update(['resume_id' => $resumeIds->first()]);

            foreach ($resumeIds->skip(1) as $resumeId) {
                DB::table('additional_information')->insert([
                    'user_id' => $info->user_id,
                    'resume_id' => $resumeId,
                    'languages' => $info->languages,
                    'certifications' => $info->certifications,
                    'interests' => $info->interests,
                    'notes' => $info->notes,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        Schema::table('additional_information', function (Blueprint $table) {
            $table->unique('resume_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('additional_information', function (Blueprint $table) {
            $table->dropUnique(['resume_id']);
            $table->dropConstrainedForeignId('resume_id');
        });

        Schema::table('additional_information', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }
};
