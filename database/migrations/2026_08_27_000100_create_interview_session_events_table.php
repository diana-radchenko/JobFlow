<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_session_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->timestamp('scheduled_at')->nullable();
            $table->string('timezone')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('interview_format')->nullable();
            $table->string('meeting_link', 2048)->nullable();
            $table->string('location', 500)->nullable();
            $table->text('employer_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_session_events');
    }
};
