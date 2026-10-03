# Account — Changelog

Append-only. Newest entries first. Format: `## [YYYY-MM-DD] [TICKET] Title`.

## [2026-10-03] [FEAT] Following authors — service layer

- `UserRepositoryInterface`: `follow`, `unfollow`, `isFollowing`, `followersCount` over `user_user_subscriptions` (insertOrIgnore / WHERE-scoped delete, as for group subscriptions — composite key).
- New `FollowService`: refuses following oneself (`InvalidArgumentException`); a guest follows no one. No routes or UI yet — first step towards profile pages and authors in the home feed (FR-ACC-010 stays Partial).
- Added `FollowServiceTest` (6 cases).

## [2026-10-03] [REFACTOR] `users.name` renamed to `username`

- The field was a unique handle in practice (`RegisterRequest` already required `unique:users`), but its name suggested a real name, which PostIt does not collect. Renamed to `username` everywhere: model, registration form and validation, `auth.user` shared prop, `author` in `PostResource`/`CommentResource`, lang keys (`auth.fields.username`, `attributes.user.username`), factory and tests.
- New migration `2026_10_03_120000_rename_name_to_username_on_users_table` renames the column and adds a **unique index**, so uniqueness no longer relies on validation alone. Prepares profile pages addressed by username.
- Registration validation `max:255` → `max:50` to match the column.

## [2026-09-30] [FEAT] Dark theme with three modes

- New `user_settings.theme_mode` (`App\Enums\ThemeMode`: `clock`, `browser`, `manual`; default `browser`), added by its own migration `2026_09_30_202436_add_theme_mode_to_user_settings_table` rather than in the initial one, so existing local databases keep their data. `dark_theme` stays as the choice shown in `manual`.
- Settings: the "Dark theme" checkbox is replaced by the three modes (Appearance). The navbar button saves via `PATCH /settings/theme` (`{dark}`, 204) only in `manual`; in `clock`/`browser` it sets a temporary browser-side override — until the next clock switch, or 12 hours / a system theme change.
- The mode and manual choice reach the page twice: as `<html data-theme-mode data-theme-manual>` for the inline script that sets the theme before first paint, and as the shared `theme` prop (`null` for guests) that `app.js` applies after each visit, so a saved change or logging in/out takes effect without a reload.

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
