<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ONLY USER RELATED TABLES

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 50);
            $table->string('email', 100)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->string('avatar_url', 100)->nullable();
            $table->date('date_of_birth');
            $table->unsignedTinyInteger('role')->default(0); // 0=user, 1=admin, ...
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('user_counters', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unsignedInteger('posts_created')->default(0);
            $table->unsignedInteger('comments_created')->default(0);
            $table->unsignedInteger('groups_connected')->default(0);
            $table->unsignedInteger('reports_sent')->default(0);
            $table->unsignedInteger('positive_votes')->default(0);
            $table->unsignedInteger('negative_votes')->default(0);
            $table->integer('karma')->default(0);

            $table->timestamps();
        });

        Schema::create('ui_languages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 10)->unique();
            $table->string('name', 50)->unique();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('speaking_languages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 50)->unique();
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

            $table->timestamps();
        });

        Schema::create('user_user_subscriptions', function (Blueprint $table) {
            $table->foreignUuid('user_follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('user_author_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['user_follower_id', 'user_author_id']);
        });

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
            $table->string('current_value', 100);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->primary(['user_id', 'achievement_id']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('text', 100);
            $table->string('url', 100)->nullable();
            $table->boolean('is_read')->default(false);
            $table->integer('type');
            $table->timestamps();
        });

        Schema::create('blocked_users', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('blocked_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['user_id', 'blocked_user_id']);
        });

        // GROUP RELATED TABLES

        Schema::create('groups', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name', 50);
            $table->string('description', 250);
            $table->string('rules', 250);
            $table->string('icon_url', 100)->nullable();
            $table->boolean('is_private')->default(false);

            $table->foreignUuid('group_language_id')->constrained('speaking_languages')->cascadeOnDelete();
            
            $table->timestamps();
        });
    
        Schema::create('images', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('uploader_id')->constrained('users')->cascadeOnDelete();

            $table->integer('owner_type');
            $table->uuid('owner_id');

            $table->string('file_name', 100);
            $table->boolean('is_adult_image')->default(false);
            $table->string('file_extension', 100);
            $table->string('url', 100);
            $table->integer('moderation_status')->default(0);

            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('receiver_id')->constrained('users')->cascadeOnDelete();
            
            $table->uuid('group_id')->nullable();
            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();

            $table->string('head', 500);
            $table->string('body', 500);
            $table->timestamps();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
        });

        Schema::create('user_group_subscriptions', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['user_id', 'group_id']);
        });

        Schema::create('group_moderators', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
            $table->integer('role')->default(0);
            $table->timestamps();

            $table->primary(['user_id', 'group_id']);
        });

        Schema::create('group_join_requests', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
            $table->integer('status')->default(0);
            $table->timestamps();

            $table->primary(['user_id', 'group_id']);
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 100)->nullable();
            $table->text('article');
            $table->string('slug');
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignUuid('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('text', 500);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('votes', function (Blueprint $table) {
            $table->uuid('parent_id');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('parent_type'); // 1 = Post, 2 = Comment (or custom enum)
            $table->boolean('positive');
            $table->timestamps();

            $table->primary(['parent_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('user_counters');
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('speaking_languages');
        Schema::dropIfExists('ui_languages');
        Schema::dropIfExists('user_user_subscriptions');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('blocked_users');
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('images');
        Schema::dropIfExists('user_group_subscriptions');
        Schema::dropIfExists('group_moderators');
        Schema::dropIfExists('group_join_requests');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('votes');
    }
};