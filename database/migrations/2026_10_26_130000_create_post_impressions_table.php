<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Posts a user has seen in a list, whole card on screen, without opening them. Kept apart from
     * `post_views` (opened the post's page): scrolling past is not the same as reading, and a
     * view count must not grow from it. Both make a post "seen" (ExcludesSeenPosts).
     */
    public function up(): void
    {
        Schema::create('post_impressions', function (Blueprint $table) {
            $table->foreignUuid('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('seen_at')->useCurrent();

            $table->primary(['post_id', 'user_id']);
            // "Not seen by this user" lookups go by user first.
            $table->index(['user_id', 'post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_impressions');
    }
};
