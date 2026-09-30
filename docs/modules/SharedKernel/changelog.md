# SharedKernel — Changelog

Append-only. Newest entries first. Format: `## [YYYY-MM-DD] [TICKET] Title`.

## [2026-09-30] [FEAT] Interface localization groundwork

- New `SetLocale` web middleware picks the request locale: the user's `ui_language_code`, otherwise the best active `ui_languages` match for the browser's `Accept-Language` (`uk-UA` matches `uk`), otherwise `config('app.default_ui_language_code')`. Shared to Inertia as the `locale` prop and set as `<html lang>`.
- Translations live in `lang/{locale}/*.php` with semantic keys (`auth.login.heading`, `nav.home`) — one source for `__()` in PHP and `$t()` in Vue. The frontend uses `laravel-vue-i18n`; its Vite plugin compiles the PHP files to `lang/php_{locale}.json` (git-ignored), loaded lazily per locale, and the app switches language without a reload when `locale` changes after saving settings.
- Every page and shared component is translated (lang groups `auth`, `nav`, `common`, `prompts`, `home`, `posts`, `comments`, `votes`, `groups`, `settings`), plus the failed-login message (was a hard-coded "Invalid credentials"). User-written content is not translated; the inbox/notification menus still show dummy data.
- Sentences with links inside ("Log in or sign up to vote") are one lang line with `:login`/`:register` placeholders, rendered by `TransSlots.vue`/`AuthPrompt.vue`, so each language keeps its own word order. Counts use `$tChoice` with Ukrainian's three plural forms; the formatted number goes in `:num`, since the library always overwrites `:count` with the raw number.
- The settings flash is now the key `settings-saved`, translated on the client: the PATCH request still runs in the old locale, so a server-translated message would come back in the language the user just left.
- Laravel's `validation`, `passwords` and `pagination` files are translated to Ukrainian in full. Validation messages use readable field names: entity-neutral ones in `validation.attributes`; names that differ per entity (a user's `name` is "ім’я", a group's is "назва групи") in `lang/*/attributes.php`, picked up by FormRequests using the `HasEntityAttributes` trait (`protected string $attributeEntity = 'group';`). Field names stay as they are in forms and the DB.
- `TranslationKeysTest` fails when a locale's lang file is missing keys present in `lang/en` or has extra ones.

## [2026-09-29] [FEAT] Full ISO 639-1 language list

- `speaking_languages` is now seeded from `database/data/languages.json`: all 183 ISO 639-1 languages with `code`, English `name` and `native_name` (new column — the language's own name, e.g. "українська", "עברית", "日本語", readable to its speakers whatever the interface language). Hand-written rather than a Composer package, to avoid a large dependency for one seeder; validated for 183 unique, sorted two-letter codes and unique names. Native names of rarer languages are worth a glance if anything looks off.
- `ui_languages` unchanged (Ukrainian, English) — it lists only languages the interface is translated into.

## [2026-09-29] [REFACTOR] Languages keyed by code instead of UUID

- `speaking_languages` and `ui_languages` now use their standard code as the primary key (`'uk'`, `'en'`) and no longer extend `BaseEntity`. The UUID added nothing — the code already identifies a language everywhere (browser `Accept-Language`, HTML `lang`, app locale) — while costing a lookup whenever code needed "the Ukrainian row" (`UserObserver` used to search by English `name`), and the seeder generated a different UUID on every `migrate:fresh`, so ids differed between machines.
- Speaking-language code is `string(3)`: ISO 639-1 (2 letters) where it exists, ISO 639-3 (3 letters) for languages without one.
- Foreign keys renamed to match: `user_settings.ui_language_code`/`speaking_language_code`, `groups.language_code` (was `group_language_id`). The group's FK changed from cascade to **restrict** on delete — removing a language used to delete all its groups.
- `config('app.default_speaking_language_name')` → `default_speaking_language_code` (`DEFAULT_SPEAKING_LANGUAGE_CODE`, default `uk`); neither was set in `.env`.
- Still only Ukrainian and English are seeded. Seeding the full ISO 639-1 list and letting a user pick several speaking languages (to recommend groups) are planned, after group creation is finished.
- Changed directly in the initial migration (pre-release; the DB is rebuilt with `migrate:fresh --seed`).

## [2026-09-21] [INIT] Initial module documentation

- Documented `BaseEntity`, language reference data and the planned shared `TargetType` enum.
