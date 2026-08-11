<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which resume the candidate applied with. Nullable because applications
     * created before this column existed have none.
     */
    public function up(): void
    {
        Schema::table('user_work_job_applications', function (Blueprint $table) {
            $table->foreignId('resume_id')->nullable()->after('work_job_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_work_job_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resume_id');
        });
    }
};
