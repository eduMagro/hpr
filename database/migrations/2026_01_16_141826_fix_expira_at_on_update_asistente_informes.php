<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix: Change expira_at from TIMESTAMP to DATETIME to prevent
     * the ON UPDATE current_timestamp() behavior that was resetting
     * the expiration date on every update to the row.
     */
    public function up(): void
    {
        // Change from TIMESTAMP to DATETIME to remove ON UPDATE behavior
        DB::statement('ALTER TABLE asistente_informes CHANGE expira_at expira_at DATETIME NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore TIMESTAMP (will get ON UPDATE current_timestamp() by default)
        DB::statement('ALTER TABLE asistente_informes CHANGE expira_at expira_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }
};
