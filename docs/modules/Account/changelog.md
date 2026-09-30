# Account — Changelog

Append-only. Newest entries first. Format: `## [YYYY-MM-DD] [TICKET] Title`.

## [2026-09-30] [FIX] Interface language kept at sign-up

- Registration now saves the interface language the guest was seeing (the locale `SetLocale` picked from `Accept-Language` among active interface languages) instead of `default_ui_language_code`. Before, a guest with e.g. a Russian browser saw English (the first supported language in their browser list) and was switched to Ukrainian the moment they signed up. `default_ui_language_code` still applies to users created any other way (seeders, tinker).

## [2026-09-29] [FEAT] Settings page and several speaking languages

- Added `GET`/`PATCH /settings` (`SettingsController`, `UpdateSettingsRequest`, `SettingsService`) and `Pages/Settings/Edit.vue`, linked from the account menu (was `href="#"`). Sections: Languages (interface language; languages you speak — searchable picker over all 183 languages, matching the English name, the native name or the code, best match first), Content, Privacy, Appearance.
- **A user now speaks a list of languages (0–10)** instead of exactly one: new link table `user_speaking_languages`, `user_settings.speaking_language_code` removed (initial migration). Saving replaces the list together with the other settings in one transaction.
- At registration the list is pre-filled from the browser's `Accept-Language` (`en-US` → `en`, unknown codes skipped, at most 5), falling back to the configured default. Decided over "always the config default" and "empty" so recommendations work from the first visit without asking.
- **Adult content (FR-ACC-009) is now enforced**: a minor gets a validation error and the toggle is disabled on the page; `SettingsService` also refuses it as an invariant.
- Decided to show and store every setting now, including ones nothing reads yet (dark theme, swear-word filter, cookies, messages) — the features will start reading the stored values when they exist.
- The group-creation page pre-selects a language the creator speaks instead of whatever sorts first (with 183 languages that would have been Abkhazian).
- Fixed two latent bugs in `User`: `counters()` pointed at a non-existent `UserCounter` class (would fail on first use), and `subscribedGroups()` declared `withTimestamps()` on a pivot that has no `updated_at` (would fail on attach).
- Added `SettingsControllerTest` (11 cases: seeding of all languages, guest redirect, page props, save, replacing/clearing languages, adult/minor rule, unknown/duplicate/too many languages and inactive UI language, registration from `Accept-Language` and its fallback). Verified in a headless browser: menu → settings, search and add languages, Enter picks the best match, save message, values survive a reload.

## [2026-09-21] [INIT] Initial module documentation

- Documented purpose, boundaries, entities and invariants (`User`, `UserSettings`, `UserCounters`, follows, blocks).
- Documented the implemented authentication flow (register, login, logout, email verification, password reset).
