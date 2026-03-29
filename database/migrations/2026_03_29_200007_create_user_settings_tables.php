<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_email_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('default_cc')->nullable();
            $table->string('default_bcc')->nullable();
            $table->string('header_image_path')->nullable();
            $table->text('signature')->nullable();
            $table->timestamps();
        });

        Schema::create('user_filter_presets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('filters'); // serialised filter state
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_filter_presets');
        Schema::dropIfExists('user_email_settings');
    }
};
