<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // "Breaks the platform's rules", as opposed to one of the group's (rule_version_id / rule_index)
    // or "something else" (neither, with a reason in `text`).
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->boolean('breaks_platform_rules')->default(false)->after('rule_index');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('breaks_platform_rules');
        });
    }
};
