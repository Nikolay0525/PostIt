<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A moderator passing a case up to the platform's administrators: not a verdict — the case's
        // status changes and its one verdict will be an administrator's — but the hint they start from:
        // the platform rule the moderator thinks was broken, where, and why. One per case.
        Schema::create('moderation_pass_ups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('moderation_case_id')->unique()->constrained('moderation_cases')->cascadeOnDelete();
            // Who passed it up; never shown, like a verdict's moderator. Null if the account is gone.
            $table->foreignUuid('passed_up_by')->nullable()->constrained('users')->nullOnDelete();
            // The platform rule, as (version, index), like a group's rule in a verdict.
            $table->foreignUuid('platform_rule_version_id')->nullable()->constrained('platform_rule_versions')->nullOnDelete();
            $table->unsignedSmallInteger('rule_index');
            // [{field, start, end, quote}], as in a verdict.
            $table->json('marks');
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_pass_ups');
    }
};
