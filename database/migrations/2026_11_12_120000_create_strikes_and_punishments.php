<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A strike given by a "Warning" verdict. Its kind (mute or ban) comes from the rule that was
        // broken, and how long it lasts from how many strikes of that kind the user already had: nobody
        // picks either. Counted per user, per group and per kind; `group_id` null is a strike on the
        // account (a platform rule), decided by an administrator. Deleted when it expires, when an
        // appeal overturns it, and when the punishment it led to has run its course.
        Schema::create('strikes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('group_id')->nullable()->constrained('groups')->cascadeOnDelete();
            $table->integer('kind');
            // The action this strike came from, which is what an appeal is made against.
            $table->foreignUuid('moderation_verdict_id')->unique()->constrained('moderation_verdicts')->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'group_id', 'kind', 'expires_at']);
        });

        // A mute or a ban that the strikes led to on their own, with nobody deciding. `step` is the
        // rung of the punishments' ladder it was on. `ends_at` null is permanent. `lifted_at` is set
        // when an appeal took it back: it is then not counted towards the next, longer one.
        Schema::create('punishments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('group_id')->nullable()->constrained('groups')->cascadeOnDelete();
            $table->integer('kind');
            $table->unsignedTinyInteger('step');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('lifted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'group_id', 'kind']);
        });

        // Which verdicts a punishment was made of. The strikes are deleted once it has run its course,
        // so this is how an appeal won afterwards still finds the punishment to take out of the history.
        Schema::create('punishment_verdicts', function (Blueprint $table) {
            $table->foreignUuid('punishment_id')->constrained('punishments')->cascadeOnDelete();
            $table->foreignUuid('moderation_verdict_id')->constrained('moderation_verdicts')->cascadeOnDelete();

            $table->primary(['punishment_id', 'moderation_verdict_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('punishment_verdicts');
        Schema::dropIfExists('punishments');
        Schema::dropIfExists('strikes');
    }
};
