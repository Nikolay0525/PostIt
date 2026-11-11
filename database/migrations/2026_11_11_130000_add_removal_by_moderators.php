<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Removed by a moderation verdict. Not `is_deleted`: that is the author deleting their own
        // content, which leaves nothing to show. A removed post keeps its text, so that its author can
        // see what was marked in it and why, and so that the moderation log can show it.
        Schema::table('posts', function (Blueprint $table) {
            $table->timestamp('removed_at')->nullable()->after('hidden_at');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->timestamp('removed_at')->nullable()->after('hidden_at');
        });

        // The group's rules as they were when the case opened: a verdict names the rule that was broken
        // from this version, even if the owner changes the rules while the case waits.
        Schema::table('moderation_cases', function (Blueprint $table) {
            $table->foreignUuid('rule_version_id')->nullable()->after('group_id')
                ->constrained('group_rule_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('moderation_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rule_version_id');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('removed_at');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('removed_at');
        });
    }
};
