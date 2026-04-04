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
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();

            // Company Identity
            $table->string('business_name')->nullable();
            $table->string('website_url')->nullable();
            $table->string('industry')->nullable();
            $table->string('company_size')->nullable();
            $table->string('year_founded')->nullable();

            // What We Do
            $table->text('business_description')->nullable();
            $table->text('key_services')->nullable();
            $table->text('unique_selling_points')->nullable();

            // Target Market
            $table->text('target_audience')->nullable();
            $table->string('geographic_focus')->nullable();

            // Sales Context
            $table->text('value_proposition')->nullable();
            $table->text('common_pain_points')->nullable();
            $table->text('call_to_action')->nullable();
            $table->text('social_proof')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_settings');
    }
};
