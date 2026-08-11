<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Set the first time the employer opens the application. Feeds the candidate's
     * "Percentage of Viewed Applications" chart on /request-tracker.
     */
    public function up(): void
    {
        Schema::table('user_work_job_applications', function (Blueprint $table) {
            $table->timestamp('viewed_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('user_work_job_applications', function (Blueprint $table) {
            $table->dropColumn('viewed_at');
        });
    }
};
