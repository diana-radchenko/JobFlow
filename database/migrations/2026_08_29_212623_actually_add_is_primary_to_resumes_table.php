<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('resumes', 'is_primary')) {
            Schema::table('resumes', function (Blueprint $table) {
                $table->boolean('is_primary')
                    ->default(false)
                    ->after('title')
                    ->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('resumes', 'is_primary')) {
            Schema::table('resumes', function (Blueprint $table) {
                $table->dropIndex(['is_primary']);
                $table->dropColumn('is_primary');
            });
        }
    }
};
