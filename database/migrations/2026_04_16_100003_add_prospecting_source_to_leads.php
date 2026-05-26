<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE leads DROP CONSTRAINT IF EXISTS leads_source_check');
        DB::statement("ALTER TABLE leads ADD CONSTRAINT leads_source_check CHECK (source IN ('csv', 'json', 'manual', 'prospecting'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE leads DROP CONSTRAINT IF EXISTS leads_source_check');
        DB::statement("ALTER TABLE leads ADD CONSTRAINT leads_source_check CHECK (source IN ('csv', 'json', 'manual'))");
    }
};
