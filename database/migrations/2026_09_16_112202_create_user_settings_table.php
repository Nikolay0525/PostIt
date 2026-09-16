<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_languages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 10);
            $table->string('name', 50);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('speaking_languages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 50);
        });

        Schema::create('user_settings', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->foreignUuid('ui_language_id')->constrained('ui_languages');
            $table->foreignUuid('speaking_language_id')->constrained('speaking_languages');

            $table->boolean('dark_theme')->default(false);
            $table->boolean('show_swear_words')->default(false);
            $table->boolean('show_adult_content')->default(false);
            $table->boolean('enable_cookies')->default(false);
            $table->boolean('allow_messages')->default(true);

            $table->timestamp('date_of_creation')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('speaking_languages');
        Schema::dropIfExists('ui_languages');
    }
};
