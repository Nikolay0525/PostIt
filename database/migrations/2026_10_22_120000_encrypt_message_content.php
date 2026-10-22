<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Direct-message content is encrypted at rest (see docs/encryption_policy.md).
     * Laravel's authenticated encrypter (AES-256-CBC + HMAC) expands the value well
     * beyond the original 500 characters, so head/body must hold a longer ciphertext.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->text('head')->change();
            $table->text('body')->change();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('head', 500)->change();
            $table->string('body', 500)->change();
        });
    }
};
