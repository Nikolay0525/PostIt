<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // One report per person per post or comment: a group's reports on a piece of content are counted
    // by distinct reporters, so the same person cannot raise the count by reporting it again.
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->unique(['reporter_id', 'target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropUnique(['reporter_id', 'target_type', 'target_id']);
        });
    }
};
