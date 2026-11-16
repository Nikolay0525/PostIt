<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The conversation as the moderator saw it when they decided — the post, the comments up the thread,
    // the replies — kept with the verdict, as the rule version is: the log and the jury show that, not
    // what was written later. Already labelled and without anyone's name or id (CaseContextResource).
    public function up(): void
    {
        Schema::table('moderation_verdicts', function (Blueprint $table) {
            $table->json('context')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('moderation_verdicts', function (Blueprint $table) {
            $table->dropColumn('context');
        });
    }
};
