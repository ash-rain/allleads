<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospecting_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('territory_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('search_query');
            $table->json('filters')->nullable();
            $table->enum('status', ['pending', 'searching', 'completed', 'failed'])->default('pending');
            $table->json('sources_used')->nullable();
            $table->unsignedInteger('result_count')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('dismissed_count')->default(0);
            $table->dateTime('searched_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'territory_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospecting_sessions');
    }
};
