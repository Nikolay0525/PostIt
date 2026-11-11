<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Set once a punishment has run its course and the strikes that led to it have been deleted, so
        // that doing it again never deletes strikes given afterwards.
        Schema::table('punishments', function (Blueprint $table) {
            $table->timestamp('settled_at')->nullable()->after('lifted_at');
        });
    }

    public function down(): void
    {
        Schema::table('punishments', function (Blueprint $table) {
            $table->dropColumn('settled_at');
        });
    }
};
