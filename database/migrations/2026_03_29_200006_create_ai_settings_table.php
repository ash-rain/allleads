<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30)->default('openrouter');
            $table->string('model', 100)->nullable();
            $table->string('language', 50)->default('English');
            $table->enum('tone', ['professional', 'friendly', 'casual', 'persuasive', 'consultative'])->default('professional');
            $table->enum('length', ['short', 'medium', 'long'])->default('medium');
            $table->enum('personalisation', ['low', 'medium', 'high'])->default('medium');
            $table->enum('opener_style', ['question', 'compliment', 'observation', 'direct'])->default('question');
            $table->boolean('include_portfolio')->default(false);
            $table->boolean('include_audit')->default(false);
            $table->boolean('include_cta')->default(true);
            $table->boolean('include_ps')->default(false);
            $table->text('custom_system_prompt')->nullable();
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->unsignedSmallInteger('max_tokens')->default(1024);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
