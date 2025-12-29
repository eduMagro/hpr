<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('entrada_import_logs')) {
            Schema::create('entrada_import_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('entrada_id')->nullable()->constrained('entradas')->nullOnDelete();
                $table->string('file_path')->nullable();
                $table->longText('raw_text')->nullable();
                $table->json('parsed_payload')->nullable();
                $table->json('applied_payload')->nullable();
                $table->string('status', 50)->default('parsed'); // parsed | applied | rejected
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrada_import_logs');
    }
};
