<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->decimal('review_rating', 2, 1)->default(0.0); // 0.0 – 5.0
            $table->enum('status', ['new', 'contacted', 'replied', 'closed', 'disqualified'])->default('new');
            $table->enum('source', ['csv', 'json', 'manual'])->default('manual');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'review_rating']);
            $table->index(['review_rating', 'website']);
        });

        Schema::create('lead_tag', function (Blueprint $table) {
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['lead_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_tag');
        Schema::dropIfExists('leads');
    }
};
