<?php

namespace Tests\Feature;

use Illuminate\Support\Arr;
use Tests\TestCase;

/**
 * Every interface language must have the same keys, so a string added in one lang file is
 * not silently missing (and shown in the fallback language) in another.
 */
class TranslationKeysTest extends TestCase
{
    private const REFERENCE_LOCALE = 'en';

    // Laravel's own files not translated yet; they fall back to English until they are.
    private const UNTRANSLATED_FRAMEWORK_FILES = ['pagination', 'passwords', 'validation'];

    public function test_every_locale_has_the_same_keys_as_the_reference(): void
    {
        $reference = $this->keysByFile(self::REFERENCE_LOCALE);

        foreach ($this->otherLocales() as $locale) {
            $keys = $this->keysByFile($locale);

            foreach ($reference as $file => $referenceKeys) {
                if (! isset($keys[$file]) && in_array($file, self::UNTRANSLATED_FRAMEWORK_FILES, true)) {
                    continue;
                }

                $this->assertArrayHasKey($file, $keys, "lang/{$locale}/{$file}.php is missing.");
                $this->assertSame([], array_values(array_diff($referenceKeys, $keys[$file])), "Missing in lang/{$locale}/{$file}.php");
                $this->assertSame([], array_values(array_diff($keys[$file], $referenceKeys)), 'Not in lang/'.self::REFERENCE_LOCALE."/{$file}.php");
            }

            $this->assertSame([], array_values(array_diff(array_keys($keys), array_keys($reference))), "Files in lang/{$locale} without a reference file");
        }
    }

    /** @return list<string> */
    private function otherLocales(): array
    {
        return array_values(array_diff(array_map('basename', glob(lang_path('*'), GLOB_ONLYDIR)), [self::REFERENCE_LOCALE]));
    }

    /** @return array<string, list<string>> dotted keys per file name */
    private function keysByFile(string $locale): array
    {
        $files = [];

        foreach (glob(lang_path("{$locale}/*.php")) as $path) {
            $files[basename($path, '.php')] = array_keys(Arr::dot(require $path));
        }

        return $files;
    }
}
