<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // A report names the rule it says was broken, as (the group's rule version at that moment, the
    // rule's index in it): rules change, and an appeal is judged against the version in force.
    // No rule means "other": the reason is then the text, which is why the text is no longer always there.
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignUuid('rule_version_id')->nullable()->after('group_id')
                ->constrained('group_rule_versions')->nullOnDelete();
            $table->unsignedSmallInteger('rule_index')->nullable()->after('rule_version_id');
            $table->string('text', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rule_version_id');
            $table->dropColumn('rule_index');
            $table->string('text', 100)->nullable(false)->change();
        });
    }
};
