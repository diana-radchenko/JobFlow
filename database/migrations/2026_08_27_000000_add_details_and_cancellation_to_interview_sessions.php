<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->string('interview_format')->nullable()->after('duration_minutes');
            $table->string('meeting_link', 2048)->nullable()->after('interview_format');
            $table->string('location', 500)->nullable()->after('meeting_link');
            $table->timestamp('cancelled_at')->nullable()->after('employer_note');
        });
    }

    public function down(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->dropColumn(['interview_format', 'meeting_link', 'location', 'cancelled_at']);
        });
    }
};
