<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name', 50);
            $table->string('description', 250);
            $table->string('rules', 250);
            $table->string('icon_url', 100)->nullable();
            $table->boolean('is_private')->default(false);

            $table->foreignUuid('group_language_id')->constrained('speaking_languages')->cascadeOnDelete();
            
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('receiver_id')->constrained('users')->cascadeOnDelete();
            
            $table->uuid('group_id')->nullable();
            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();

            $table->string('head', 500);
            $table->string('body', 500);
            $table->timestamps();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
