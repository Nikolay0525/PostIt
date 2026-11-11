<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who has seen a post, and who shared (copied the link to) it — one row per user and post,
     * so repeats change nothing and a share count can't be inflated by one person clicking.
     *
     * A view is any way of seeing a post: its card whole on screen in a list, its page opened, a
     * vote on it. Views make a post "seen" (the side panel's counters, the "Only new" filter);
     * shares are shown on posts.
     */
    public function up(): void
    {
        Schema::create('post_views', function (Blueprint $table) {
            $table->foreignUuid('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('viewed_at')->useCurrent();

            $table->primary(['post_id', 'user_id']);
            // "Not seen by this user yet" lookups (ExcludesSeenPosts) go by user first.
            $table->index(['user_id', 'post_id']);
        });

        Schema::create('post_shares', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('post_id')->constrained('posts')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['user_id', 'post_id']);
            // Counting a post's shares goes by post_id, which the composite key doesn't lead with.
            $table->index('post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_shares');
        Schema::dropIfExists('post_views');
    }
};
