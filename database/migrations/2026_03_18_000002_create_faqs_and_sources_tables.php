<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_profile_id')->constrained('bot_profiles')->cascadeOnDelete();
            $table->string('site')->nullable()->index();
            $table->string('locale')->nullable()->index();
            $table->string('question');
            $table->json('question_variants')->nullable();
            $table->longText('answer');
            $table->unsignedInteger('priority')->default(0);
            $table->json('cta_actions')->nullable();
            $table->json('lead_capture_fields')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('source_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_profile_id')->constrained('bot_profiles')->cascadeOnDelete();
            $table->string('driver')->index();
            $table->string('name');
            $table->json('config')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_connections');
        Schema::dropIfExists('faq_items');
    }
};
