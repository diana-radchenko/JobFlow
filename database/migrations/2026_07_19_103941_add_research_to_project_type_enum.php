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
        DB::statement('alter table projects drop constraint projects_type_check');
        DB::statement("alter table projects add constraint projects_type_check check (type in ('project', 'achievement', 'research'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('alter table projects drop constraint projects_type_check');
        DB::statement("alter table projects add constraint projects_type_check check (type in ('project', 'achievement'))");
    }
};
