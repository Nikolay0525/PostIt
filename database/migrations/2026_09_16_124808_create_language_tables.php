<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_languages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 10)->unique();
            $table->string('name', 50);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('speaking_languages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 50)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speaking_languages');
        Schema::dropIfExists('ui_languages');
    }
};
