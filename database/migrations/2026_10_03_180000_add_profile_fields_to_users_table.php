<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What a user tells about themselves on their profile: a short status with an emoji (picked
     * from UpdateProfileRequest::STATUS_EMOJIS) and a longer bio. All optional.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status_emoji', 16)->nullable()->after('avatar_url');
            $table->string('status_text', 100)->nullable()->after('status_emoji');
            $table->string('bio', 500)->nullable()->after('status_text');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status_emoji', 'status_text', 'bio']);
        });
    }
};
