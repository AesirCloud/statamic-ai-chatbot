<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_profile_id')->constrained('bot_profiles')->cascadeOnDelete();
            $table->foreignId('source_connection_id')->constrained('source_connections')->cascadeOnDelete();
            $table->string('driver')->index();
            $table->string('external_id');
            $table->string('site')->nullable()->index();
            $table->string('locale')->nullable()->index();
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->text('url')->nullable();
            $table->json('metadata')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->timestamps();

            $table->unique(['source_connection_id', 'external_id']);
        });

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_document_id')->constrained('knowledge_documents')->cascadeOnDelete();
            $table->foreignId('bot_profile_id')->constrained('bot_profiles')->cascadeOnDelete();
            $table->string('site')->nullable()->index();
            $table->string('locale')->nullable()->index();
            $table->unsignedInteger('position');
            $table->longText('content');
            $table->longText('content_plain');
            $table->json('embedding')->nullable();
            $table->json('metadata')->nullable();
            $table->double('score')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('knowledge_documents');
    }
};
