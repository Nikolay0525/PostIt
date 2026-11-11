<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A user's sub-karma in a group: the votes on their posts and comments there. Not tied to
     * membership — anyone may comment in a public group — and kept when the user leaves it.
     * The account karma (`user_counters.karma`) is a separate counter, not computed from these rows.
     */
    public function up(): void
    {
        Schema::create('user_group_karma', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            // No foreign key: karma earned in a group outlives the group.
            $table->uuid('group_id');
            $table->integer('karma')->default(0);

            $table->primary(['user_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_group_karma');
    }
};
