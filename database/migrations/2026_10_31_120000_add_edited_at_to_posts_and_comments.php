<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // When the author last changed the text. Not `updated_at`: that moves with anything written to
    // the row (a moderator hiding it, a deletion), and "(edited)" must mean only that the text changed.
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('deleted_at');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('edited_at');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('edited_at');
        });
    }
};
