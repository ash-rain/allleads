<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->enum('opener_style', ['question', 'compliment', 'observation', 'direct'])
                ->default('question')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->enum('opener_style', ['question', 'statement', 'compliment', 'statistic'])
                ->default('question')
                ->change();
        });
    }
};
