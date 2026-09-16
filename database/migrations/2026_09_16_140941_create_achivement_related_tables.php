<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 100);
            $table->string('description', 100);
            $table->string('target_property', 100);
            $table->string('target_value', 100);
            $table->string('comparison_type', 100);
            $table->string('icon_url', 100);
        });

        Schema::create('user_achievements', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('achievement_id')->constrained('achievements')->cascadeOnDelete();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->primary(['user_id', 'achievement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
    }
};
