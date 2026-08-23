<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->unique()->constrained('user_work_job_applications')->cascadeOnDelete();
            $table->foreignId('employer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('candidate_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('job_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_messages');
        Schema::dropIfExists('job_conversations');
    }
};
