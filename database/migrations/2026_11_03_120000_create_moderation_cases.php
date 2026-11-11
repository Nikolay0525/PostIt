<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A group's case about one post or comment, opened when enough members report it. Cases wait
        // in the group's queue; nobody is assigned one.
        Schema::create('moderation_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
            $table->integer('target_type');
            $table->uuid('target_id');
            $table->integer('status')->default(0);
            // "type:id" while the case is open, null once it is closed: the unique index lets only one
            // case be open for a piece of content, even if two reports tip the count at the same moment,
            // and still lets it have a new case after an old one was closed (nulls do not clash).
            $table->string('open_key')->nullable()->unique();
            $table->timestamps();

            $table->index(['group_id', 'status']);
        });

        // Hidden: a moderation case is open on it. Not deleted: the content is still there, and it is
        // shown as "hidden" until a decision is made.
        Schema::table('posts', function (Blueprint $table) {
            $table->timestamp('hidden_at')->nullable()->after('edited_at');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->timestamp('hidden_at')->nullable()->after('edited_at');
        });

        // The reports that opened the case, for the moderator who takes it.
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignUuid('moderation_case_id')->nullable()->after('group_id')
                ->constrained('moderation_cases')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderation_case_id');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('hidden_at');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('hidden_at');
        });

        Schema::dropIfExists('moderation_cases');
    }
};
