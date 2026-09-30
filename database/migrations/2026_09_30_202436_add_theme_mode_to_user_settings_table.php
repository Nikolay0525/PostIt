<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How the theme is chosen (App\Enums\ThemeMode). `dark_theme` stays: it is the choice used in
     * the 'manual' mode. Everyone starts on 'browser', the mode guests get too.
     */
    public function up(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->string('theme_mode', 10)->default('browser')->after('dark_theme');
        });
    }

    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn('theme_mode');
        });
    }
};
