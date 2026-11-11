<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // How alive a group is, as of the last time it was counted (a scheduled job refreshes it): who
    // voted in it, and how many votes there were, over the counting window (a week). Counting this on every
    // report or every pool check would mean a heavy query over all the group's votes each time, so
    // the thresholds that depend on it read this row instead. A group with no row has not been
    // counted yet, which is the same as no activity.
    public function up(): void
    {
        Schema::create('group_activity', function (Blueprint $table) {
            $table->foreignUuid('group_id')->primary()->constrained('groups')->cascadeOnDelete();
            $table->unsignedInteger('voters_count')->default(0);
            $table->unsignedInteger('votes_count')->default(0);
            $table->timestamp('computed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_activity');
    }
};
