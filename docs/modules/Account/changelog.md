# Account — Changelog

Append-only. Newest entries first. Format: `## [YYYY-MM-DD] [TICKET] Title`.

## [2026-10-04] [FEAT] Avatars shown everywhere, not only on the profile

- New `UserAvatar.vue`: the picture, or the first letter when there is none or it fails to load. Used by the post card (`.avatar`), comments (new `.avatar-sm`) and the navbar account button (replacing a placeholder that pointed at a non-existent default image).
- The shared `auth.user` prop now carries `avatar_url` (a ready link or null). Uploading or removing the avatar on the profile updates the navbar button at once, without a page visit.

## [2026-10-03] [FEAT] Profile: status, bio, groups, achievements; edited in place

- New optional `users.status_emoji` (≤ 16), `status_text` (≤ 100) and `bio` (≤ 500), by migration `2026_10_03_180000_add_profile_fields_to_users_table`. The emoji is picked from a fixed set (`UpdateProfileRequest::STATUS_EMOJIS`, 24 options), not typed, so the field can't carry arbitrary text.
- **The owner edits the profile right on its page** (decided over a separate edit page or a settings section): `PATCH /profile` (partial update; `''`/null clears a field), `POST`/`DELETE /profile/avatar`. All JSON, so the scrolled post list isn't reset. The avatar endpoints moved here from `/settings/avatar` (same day, never released) and now answer `{avatar_url}` instead of redirecting; the settings page no longer gets avatar props.
- Page layout: one header card with avatar, username, status, followers/join date and the main action (Follow, or Edit for the owner); an "About me" card only when there is a bio; then Achievements, Groups, Posts. For the owner the avatar itself is the upload button (hover/focus: darkened edges and a "+"); "Remove photo" is in the edit form. Long text wraps anywhere (`overflow-wrap: anywhere`, `min-w-0`) instead of widening the card; the bio textarea resizes vertically only.
- **Groups list**: the user's groups by name — public ones, and private ones only when the viewer is a member too (same rule as their posts). `GroupRepositoryInterface::membershipsVisibleTo()`.
- **Achievements**: unlocked ones only (`AchievementRepositoryInterface::unlockedBy()`, `AchievementService::getUnlocked()`). Nothing awards achievements yet, so the block shows "No achievements yet" for everyone.
- `http.js` gained `postForm()` for multipart uploads (no `Content-Type` set by hand, the browser adds the boundary).
- `ProfileControllerTest` now 13 cases (status/bio set, partial update, clear, validation; groups visibility and order; achievements; avatar JSON endpoints; guest).

## [2026-10-03] [FEAT] Profile page, following, avatar upload

- **Profile page** `GET /users/{username}` (`ProfileController::show`, `Pages/Users/Show.vue`), public like a group page: avatar (or initial), username, followers count, join month, and the user's posts via `PostService::getAuthorPosts()` with `<InfiniteScroll>` (private-group posts only for that group's members). `UserProfileResource` exposes public fields only — never email, date of birth or settings. The username segment accepts any characters but `/` (usernames have no format rule yet).
- **Follow / unfollow** (FR-ACC-010 → Done): `POST`/`DELETE /users/{id}/follow` (`FollowController`, JSON `{is_following, followers_count}`, outside Inertia so the scrolled post list isn't reset — same as group subscribe). `UserPolicy::follow()`: verified, not self (403). Unfollowing is never gated.
- **Avatar endpoints**: `POST /settings/avatar` (POST, since PHP only parses multipart uploads on POST) and `DELETE /settings/avatar`, throttled 10/min. `UpdateAvatarRequest`: jpg/png/webp checked by contents, ≤ 2 MB, ≤ 4096×4096 (no resizing — see `Content` image notes). The settings page receives `avatar_url` and `max_avatar_size_kb`; the upload control itself is not built yet.
- `author.avatar_url` (a ready link, `null` for none) added to `PostResource` and `CommentResource`; author names in posts and comments now link to the profile (were `href="#"`), and the account menu has "Profile".
- Added `ProfileControllerTest` (9 cases) and `FollowControllerTest` (6 cases).

## [2026-10-03] [FEAT] Avatar — service layer

- New `ProfileService::updateAvatar()` / `removeAvatar()` on top of `ImageService` (see `Content`): the file goes to `avatars/` with an `images` row (`owner_type = User`), and `users.avatar_url` mirrors its path so post/comment lists can show avatars without joining `images`. Despite the column name, it holds a disk path; render it with `ImageService::url()`.
- Replacing an avatar deletes the old file and row only after the new one is saved, so a failed upload keeps the previous avatar. Removing resets `avatar_url` to null (default picture).
- No route or UI yet — they come with the profile edit page. Added `AvatarTest` (6 cases).

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
