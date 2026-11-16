<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // An echo of an earlier design, in which a moderator reviewed every report on its own. Reports now
    // gather into a moderation case, and the case is what is decided: a report's state is its case's
    // (or none yet, while too few have come in). Nothing ever wrote these past "pending".
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['status', 'resolution_note', 'escalated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->integer('status')->default(0)->after('text');
            $table->foreignUuid('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->string('resolution_note', 500)->nullable()->after('reviewed_by');
            $table->timestamp('escalated_at')->nullable()->after('resolution_note');
        });
    }
};
