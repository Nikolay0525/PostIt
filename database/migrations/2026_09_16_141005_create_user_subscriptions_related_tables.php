<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_user_subscriptions', function (Blueprint $table) {
            $table->foreignUuid('user_follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('user_author_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_follower_id', 'user_author_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_user_subscriptions');
    }
};
