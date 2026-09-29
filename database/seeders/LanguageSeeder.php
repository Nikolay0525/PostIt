<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        // Languages the interface is translated into — intentionally a short list.
        DB::table('ui_languages')->upsert([
            ['code' => 'uk', 'name' => 'Ukrainian', 'is_active' => true],
            ['code' => 'en', 'name' => 'English', 'is_active' => true],
        ], ['code']);

        // Every ISO 639-1 language a user may speak or a group may be written in.
        $languages = json_decode(
            file_get_contents(database_path('data/languages.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        DB::table('speaking_languages')->upsert($languages, ['code'], ['name', 'native_name']);
    }
}
