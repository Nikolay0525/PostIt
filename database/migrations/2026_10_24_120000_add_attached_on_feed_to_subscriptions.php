<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which of the user's groups and people are on the side panel. A new subscription is attached
     * until the user unticks it in Settings; there is no cap, the panel shows every attached one.
     */
    public function up(): void
    {
        Schema::table('user_user_subscriptions', function (Blueprint $table) {
            $table->boolean('attached_on_feed')->default(true)->after('user_author_id');
        });

        Schema::table('user_group_subscriptions', function (Blueprint $table) {
            $table->boolean('attached_on_feed')->default(true)->after('group_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_user_subscriptions', function (Blueprint $table) {
            $table->dropColumn('attached_on_feed');
        });

        Schema::table('user_group_subscriptions', function (Blueprint $table) {
            $table->dropColumn('attached_on_feed');
        });
    }
};
