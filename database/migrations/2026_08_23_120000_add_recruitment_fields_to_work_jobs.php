<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_jobs', function (Blueprint $table) {
            $table->string('industry')->nullable()->after('location');
            $table->string('position_level')->nullable()->after('industry');
            $table->string('employment_type')->nullable()->after('position_level');
            $table->string('workplace_type')->nullable()->after('employment_type');
            $table->text('responsibilities')->nullable()->after('description');
            $table->text('requirements')->nullable()->after('responsibilities');
            $table->text('benefits')->nullable()->after('requirements');
            $table->string('salary_currency', 3)->nullable()->after('salary_end');
            $table->string('salary_period')->nullable()->after('salary_currency');
            $table->string('status')->default('published')->after('technologies');
            $table->timestamp('published_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('work_jobs', function (Blueprint $table) {
            $table->dropColumn(['industry', 'position_level', 'employment_type', 'workplace_type',
                'responsibilities', 'requirements', 'benefits', 'salary_currency', 'salary_period',
                'status', 'published_at']);
        });
    }
};
