<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A new subscription is attached to the side panel until the user detaches it; there is no
     * cap on how many are attached. `amount_of_attached_subscriptions` only sets how many of the
     * attached groups and people (each) the panel shows before its "show all" link.
     */
    public function up(): void
    {
        Schema::table('user_user_subscriptions', function (Blueprint $table) {
            $table->boolean('attached_on_feed')->default(true)->after('user_author_id');
        });

        Schema::table('user_group_subscriptions', function (Blueprint $table) {
            $table->boolean('attached_on_feed')->default(true)->after('group_id');
        });

        Schema::table('user_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('amount_of_attached_subscriptions')->default(10)->after('allow_messages');
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

        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn('amount_of_attached_subscriptions');
        });
    }
};
