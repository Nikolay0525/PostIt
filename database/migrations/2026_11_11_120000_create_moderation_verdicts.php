<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // How a moderation case was decided. One per case: a case is decided once, and a verdict is
        // changed only by an appeal.
        Schema::create('moderation_verdicts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('moderation_case_id')->unique()->constrained('moderation_cases')->cascadeOnDelete();
            $table->integer('type');
            // Who decided. Kept because an appeal that succeeds lowers this moderator's reliability;
            // never sent to anyone, not even in the log. Null if the account is gone.
            $table->foreignUuid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            // The rule that was broken, as (the group's rule version at that moment, the rule's index
            // in it), like a report names it: an appeal is judged against that version. Both null for
            // "no violation".
            $table->foreignUuid('rule_version_id')->nullable()->constrained('group_rule_versions')->nullOnDelete();
            $table->unsignedSmallInteger('rule_index')->nullable();
            // Where in the content the rule was broken: a list of {start, end, quote}, the positions
            // counted in characters of the text the moderator read. The content cannot be edited while
            // its case is open, so they stay valid. Required for a punishing verdict.
            $table->json('marks')->nullable();
            // The moderator's own words on how they read the content against the rule. The notice to the
            // author and the entry in the moderation log are built from it.
            $table->text('reason')->nullable();
            // For a warning: how many days it lasts before it goes away by itself.
            $table->unsignedSmallInteger('warning_days')->nullable();
            $table->timestamps();
        });

        // When the verdict closed the case; the log shows the entry a day later.
        Schema::table('moderation_cases', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('open_key');
        });
    }

    public function down(): void
    {
        Schema::table('moderation_cases', function (Blueprint $table) {
            $table->dropColumn('closed_at');
        });

        Schema::dropIfExists('moderation_verdicts');
    }
};
