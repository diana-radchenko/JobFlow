<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('salary_start', 12, 2)->nullable();
            $table->decimal('salary_end', 12, 2)->nullable();
            $table->string('company');
            $table->text('description');
            $table->string('contacts');
            $table->string('location');
            $table->json('technologies');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_jobs');
    }
};
