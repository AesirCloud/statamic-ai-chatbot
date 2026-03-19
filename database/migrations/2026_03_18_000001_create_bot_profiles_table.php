<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('handle')->unique();
            $table->string('name');
            $table->string('site')->nullable()->index();
            $table->string('locale')->nullable()->index();
            $table->boolean('is_default')->default(false);
            $table->boolean('active')->default(true);
            $table->json('branding')->nullable();
            $table->json('provider_overrides')->nullable();
            $table->json('widget_settings')->nullable();
            $table->json('support_settings')->nullable();
            $table->json('lead_settings')->nullable();
            $table->longText('system_prompt')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_profiles');
    }
};
