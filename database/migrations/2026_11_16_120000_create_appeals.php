<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // An author's appeal against a verdict that punished. One per verdict: the unique key is what
        // keeps a second one out, even if two are sent at the same moment. Judged by a jury of people
        // from other groups; until then (`status` pending) the removed content stays removed.
        Schema::create('appeals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('moderation_verdict_id')->unique()->constrained('moderation_verdicts')->cascadeOnDelete();
            $table->foreignUuid('appellant_id')->constrained('users')->cascadeOnDelete();
            // Why the author thinks the verdict is wrong: the only thing the jury hears from them.
            $table->text('statement');
            $table->integer('status')->default(0);
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appeals');
    }
};
