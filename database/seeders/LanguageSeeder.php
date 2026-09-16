<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ui_languages')->upsert([
            ['id' => (string) Str::uuid(), 'code' => 'uk', 'name' => 'Ukrainian', 'is_active' => true],
            ['id' => (string) Str::uuid(), 'code' => 'en', 'name' => 'English', 'is_active' => true],
        ], ['code']);

        DB::table('speaking_languages')->upsert([
            ['id' => (string) Str::uuid(), 'name' => 'Ukrainian'],
            ['id' => (string) Str::uuid(), 'name' => 'English'],
        ], ['name']);
    }
}