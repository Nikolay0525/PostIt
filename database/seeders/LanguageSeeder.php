<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ui_languages')->upsert([
            ['code' => 'uk', 'name' => 'Ukrainian', 'is_active' => true],
            ['code' => 'en', 'name' => 'English', 'is_active' => true],
        ], ['code']);

        DB::table('speaking_languages')->upsert([
            ['code' => 'uk', 'name' => 'Ukrainian'],
            ['code' => 'en', 'name' => 'English'],
        ], ['code']);
    }
}
