<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The side panel shows every attached group and person: the user decides what is attached,
     * so a separate cap on how many are shown only repeated that choice.
     */
    public function up(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn('amount_of_attached_subscriptions');
        });
    }

    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('amount_of_attached_subscriptions')->default(10)->after('allow_messages');
        });
    }
};
