<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->string('provider', 30)->default('openrouter'); // openrouter | groq | gemini
            $table->string('model', 100)->nullable();
            $table->json('style_settings')->nullable(); // per-campaign style overrides
            $table->unsignedInteger('lead_count')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('email_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('thread_key', 64)->unique(); // matches Reply-To address component
            $table->enum('status', ['open', 'replied', 'closed'])->default('open');
            $table->timestamps();
        });

        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('email_threads')->cascadeOnDelete();
            $table->enum('role', ['ai_draft', 'outbound', 'lead_reply', 'manual'])->default('outbound');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('message_id', 255)->nullable(); // Brevo returned message ID
            $table->string('sender')->nullable();
            $table->string('source', 30)->default('system'); // brevo | manual | system
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('email_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('email_campaigns')->nullOnDelete();
            $table->foreignId('thread_id')->nullable()->constrained('email_threads')->nullOnDelete();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->enum('status', ['draft', 'queued_for_send', 'sent', 'failed'])->default('draft');
            $table->timestamp('send_at')->nullable();
            $table->text('error')->nullable();
            $table->unsignedSmallInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('email_draft_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draft_id')->constrained('email_drafts')->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_draft_versions');
        Schema::dropIfExists('email_drafts');
        Schema::dropIfExists('email_messages');
        Schema::dropIfExists('email_threads');
        Schema::dropIfExists('email_campaigns');
    }
};
