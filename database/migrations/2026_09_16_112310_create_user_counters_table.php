<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_counters', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unsignedInteger('posts_created')->default(0);
            $table->unsignedInteger('comments_created')->default(0);
            $table->unsignedInteger('groups_connected')->default(0);
            $table->unsignedInteger('reports_sent')->default(0);
            $table->unsignedInteger('positive_votes')->default(0);
            $table->unsignedInteger('negative_votes')->default(0);
            $table->integer('karma')->default(0);

            $table->timestamp('date_of_creation')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_counters');
    }
};