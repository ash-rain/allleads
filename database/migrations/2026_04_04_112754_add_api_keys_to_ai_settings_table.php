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
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->text('openrouter_api_key')->nullable()->after('provider');
            $table->text('groq_api_key')->nullable()->after('openrouter_api_key');
            $table->text('gemini_api_key')->nullable()->after('groq_api_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->dropColumn(['openrouter_api_key', 'groq_api_key', 'gemini_api_key']);
        });
    }
};
