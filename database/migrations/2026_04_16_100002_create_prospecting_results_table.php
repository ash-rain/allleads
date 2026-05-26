<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospecting_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospecting_session_id')->constrained()->cascadeOnDelete();
            $table->enum('source', ['foursquare', 'osm', 'google_places']);
            $table->string('source_id')->nullable();
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->decimal('review_rating', 2, 1)->nullable();
            $table->unsignedInteger('review_count')->nullable();
            $table->json('signals')->nullable();
            $table->json('raw_data')->nullable();
            $table->enum('status', ['new', 'selected', 'dismissed', 'imported', 'duplicate'])->default('new');
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['prospecting_session_id', 'status']);
            $table->unique(['source', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospecting_results');
    }
};
