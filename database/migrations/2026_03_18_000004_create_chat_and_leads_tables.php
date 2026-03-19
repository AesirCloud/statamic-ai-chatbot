<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_profile_id')->constrained('bot_profiles')->cascadeOnDelete();
            $table->string('site')->nullable()->index();
            $table->string('locale')->nullable()->index();
            $table->string('session_id')->index();
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->string('role')->index();
            $table->longText('content');
            $table->json('structured_output')->nullable();
            $table->json('citations')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('lead_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_profile_id')->constrained('bot_profiles')->cascadeOnDelete();
            $table->foreignId('chat_conversation_id')->nullable()->constrained('chat_conversations')->nullOnDelete();
            $table->string('site')->nullable()->index();
            $table->string('locale')->nullable()->index();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new');
            $table->json('payload')->nullable();
            $table->json('delivery_log')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_submissions');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
    }
};
