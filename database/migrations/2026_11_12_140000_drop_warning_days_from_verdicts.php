<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // How long a warning's strike lasts is no longer chosen by the moderator: it follows from how many
    // strikes the member already has (see config/moderation.php).
    public function up(): void
    {
        Schema::table('moderation_verdicts', function (Blueprint $table) {
            $table->dropColumn('warning_days');
        });
    }

    public function down(): void
    {
        Schema::table('moderation_verdicts', function (Blueprint $table) {
            $table->unsignedSmallInteger('warning_days')->nullable()->after('reason');
        });
    }
};
