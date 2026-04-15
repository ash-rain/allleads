<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('name');
            $table->string('website_url')->nullable();
            $table->string('industry')->nullable();
            $table->string('company_size')->nullable();
            $table->string('year_founded')->nullable();

            // What we do
            $table->text('description')->nullable();
            $table->text('key_services')->nullable();
            $table->text('unique_selling_points')->nullable();

            // Target market
            $table->text('target_audience')->nullable();
            $table->string('geographic_focus')->nullable();

            // Sales context
            $table->text('value_proposition')->nullable();
            $table->text('common_pain_points')->nullable();
            $table->text('call_to_action')->nullable();
            $table->text('social_proof')->nullable();

            $table->string('tag_color', 7)->default('#3b82f6');

            $table->timestamps();
        });

        Schema::create('business_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamps();

            $table->unique(['business_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_user');
        Schema::dropIfExists('businesses');
    }
};
