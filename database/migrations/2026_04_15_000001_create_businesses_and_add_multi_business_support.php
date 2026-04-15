<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Businesses table (replaces singleton business_settings) ────────
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

            // Tag colour for visual differentiation in lead views
            $table->string('tag_color', 7)->default('#3b82f6');

            $table->timestamps();
        });

        // ─── Pivot: users ↔ businesses ──────────────────────────────────────
        Schema::create('business_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member'); // owner, admin, member
            $table->timestamps();

            $table->unique(['business_id', 'user_id']);
        });

        // ─── Add business_id to leads ───────────────────────────────────────
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index('business_id');
        });

        // ─── Add business_id to tags ────────────────────────────────────────
        Schema::table('tags', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // ─── Add business_id to ai_settings ─────────────────────────────────
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unique('business_id');
        });

        // ─── Drop the old singleton table ───────────────────────────────────
        Schema::dropIfExists('business_settings');
    }

    public function down(): void
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_id');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_id');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_id');
        });

        Schema::dropIfExists('business_user');
        Schema::dropIfExists('businesses');

        // Recreate business_settings (simplified — real rollback would restore data)
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->nullable();
            $table->string('website_url')->nullable();
            $table->string('industry')->nullable();
            $table->string('company_size')->nullable();
            $table->string('year_founded')->nullable();
            $table->text('business_description')->nullable();
            $table->text('key_services')->nullable();
            $table->text('unique_selling_points')->nullable();
            $table->text('target_audience')->nullable();
            $table->string('geographic_focus')->nullable();
            $table->text('value_proposition')->nullable();
            $table->text('common_pain_points')->nullable();
            $table->text('call_to_action')->nullable();
            $table->text('social_proof')->nullable();
            $table->timestamps();
        });
    }
};
