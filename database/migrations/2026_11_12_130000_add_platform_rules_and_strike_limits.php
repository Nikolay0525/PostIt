<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The platform's rules, in versions like a group's: a platform rule is named by (version, index)
        // and an appeal is judged against that version. Edited by administrators in the database, so an
        // edit adds a row and never changes one. Each rule is {text, example, kind}.
        Schema::create('platform_rule_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('rules');
            $table->timestamp('created_at')->useCurrent();
        });

        // How many active strikes of a kind punish a member in the group. Null means the platform's
        // highest; the owner can only set a lower number, which is stricter (the check is in the request).
        Schema::table('groups', function (Blueprint $table) {
            $table->unsignedTinyInteger('mute_strike_limit')->nullable()->after('is_private');
            $table->unsignedTinyInteger('ban_strike_limit')->nullable()->after('mute_strike_limit');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['mute_strike_limit', 'ban_strike_limit']);
        });

        Schema::dropIfExists('platform_rule_versions');
    }
};
