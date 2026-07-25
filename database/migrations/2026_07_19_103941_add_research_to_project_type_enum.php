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
        // SQLite bakes the enum's check constraint in at table-creation time (no named,
        // alterable constraint), and the projects table migration already builds it from
        // the current ProjectType cases — so there's nothing to alter here on SQLite.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('alter table projects drop constraint projects_type_check');
        DB::statement("alter table projects add constraint projects_type_check check (type in ('project', 'achievement', 'research'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('alter table projects drop constraint projects_type_check');
        DB::statement("alter table projects add constraint projects_type_check check (type in ('project', 'achievement'))");
    }
};
