# ====== Account BUSINESS LOGIC ======

## Purpose

`Account` owns:
- Registration, login, logout, email verification and password reset.
- The user identity and profile (`User`), including age check (`isAdult()`).
- User preferences (`UserSettings`): languages, dark theme, content filters, messaging permission.
- Per-user activity counters and karma (`UserCounters`).
- Social graph: following authors (`UserUserSubscription`) and blocking users (`BlockedUser`).

**NOT here:**
- Group membership and moderator roles — `Community`.
- Bans of users (platform and group) — `Moderation`.
- Achievements earned by a user — `Engagement`.

## Entities

| Entity | Basic Fields | Description | Invariants |
|---|---|---|---|
| `User` *(Aggregate Root, extends `BaseEntity`)* | id, username, email, password, email_verified_at, date_of_birth, … | A registered account holder. | - Username is a unique public handle (max 50); no real name is stored.<br>- Email is unique and must be verified before verified-only actions.<br>- Password is stored hashed only.<br>- `isAdult()` is true when age ≥ 18 (from `date_of_birth`).<br>- Deleting a user cascades to owned data (settings, counters, subscriptions). |
| `UserSettings` *(owned by `User`, PK = `user_id`)* | user_id, ui_language_code, dark_theme, theme_mode, show_swear_words, show_adult_content, enable_cookies, allow_messages | Personal preferences. Exactly one per user. | - One row per user.<br>- `ui_language_code` must reference an active UI language (default `config('app.default_ui_language_code')`).<br>- `show_adult_content` may only be enabled for adult users — *(0.1.8)* enforced by `UpdateSettingsRequest` (422) and `SettingsService` (invariant guard).<br>- `theme_mode` (`ThemeMode`: `clock` — dark 20:00–07:00 by the browser's local time; `browser` — follows the system setting; `manual` — `dark_theme`, switched with the navbar button). Default `browser`, same as guests. The theme is picked in the browser (`partials/theme-script.blade.php`); in `clock`/`browser` the navbar button only sets a temporary override in `localStorage` and saves nothing.<br>- *(0.1.8)* Swear-word filter, cookies and direct messages are **stored but not yet acted on** — the features that read them don't exist yet. |
| User speaking languages *(0.1.8, link `user_speaking_languages`)* | user_id, language_code | Languages the user speaks — used to recommend groups in them. | - Optional list, 0 … 10 languages, no duplicates, each must exist in `speaking_languages`.<br>- At registration pre-filled from the browser's `Accept-Language` (regions collapse onto the language: `en-US` → `en`; unknown codes skipped; at most 5), falling back to `config('app.default_speaking_language_code')`. Users created any other way (seeders, factories) start with none.<br>- Replaced as a whole on save. |
| `UserCounters` *(owned by `User`, PK = `user_id`)* | user_id, posts_created, comments_created, groups_connected, reports_sent, positive_votes, negative_votes, karma | Denormalised activity statistics used for karma and achievements. Exactly one per user. | - One row per user.<br>- Counters are non-negative integers.<br>- Counters change only through application services reacting to activity. |
| `UserUserSubscription` *(link)* | user_follower_id, user_author_id, created_at | "Follower follows author". | - Unique pair (follower, author).<br>- A user cannot follow themselves.<br>- No `updated_at`. |
| `BlockedUser` *(link)* | user_id, blocked_user_id, created_at | "User blocks another user". | - Unique pair.<br>- A user cannot block themselves.<br>- Blocked user's content is hidden from the blocker. |

> The rules "`show_adult_content` ⇒ adult only" and "no self follow/block" are business rules of this module; they are not yet enforced in code (see tech notes).

## Authentication Flow

- **Register:** validated input → `AuthService::register` → user created via `UserRepositoryInterface` → `Registered` event (sends verification mail) → user logged in → redirect to verification notice.
- **Login:** credentials validated → `attemptLogin` (with "remember me") → session regenerated → redirect to intended page; failure returns a generic "Invalid credentials" error.
- **Logout:** logout → session invalidated → CSRF token regenerated → redirect to home.
- **Verify email:** signed link → `EmailVerificationRequest::fulfill()`; resend is throttled (6/min).
- **Forgot / reset password:** reset link request throttled (5/min); the response is identical whether or not the email exists (no account enumeration); reset updates the password via the repository and fires `PasswordReset`.

**Boundary:** this module authenticates and identifies the user; what the user may do inside a group or against content is decided by `Community` and `Content`.

## Value Objects / Enums

| Item | Description | Invariants |
|---|---|---|
| Email | Login identifier. | - Valid format, unique. |
| Password | Secret credential. | - Hashed, never returned to the client or logged. |

## Domain Services

| Service | Operation |
|---|---|
| `AuthService` | `register`, `attemptLogin`, `logout`, `sendResetLink`, `resetPassword`, `resendVerification`. Depends on `UserRepositoryInterface`. |
| `UserRepositoryInterface` *(contract)* | `create`, `updatePassword`; *(0.1.8)* `findForSettings`, `updateSettings`, `syncSpeakingLanguages` (persistence contract implemented in `Repositories/`). |
| `SettingsService` *(0.1.8)* | `getSettings`, `updateSettings` — saves preferences and replaces speaking languages in one transaction; rejects adult content for a minor. Behind `GET`/`PATCH /settings` (`SettingsController`, `UpdateSettingsRequest`), page `Pages/Settings/Edit.vue`, reachable from the account menu. |

## Domain Events

| Event | Carries | Notes |
|---|---|---|
| `Registered` *(Laravel)* | user | Triggers the email verification notification. |
| `PasswordReset` *(Laravel)* | user | Fired after a successful reset. |

## Application Commands & Queries

Currently implemented as controller actions on `AuthController`: `register`, `login`, `logout`, `sendResetLink`, `showResetForm`, `resetPassword`, `verificationNotice`, `verifyEmail`, `resendVerification`. Validation is in Form Requests (`RegisterRequest`, `LoginRequest`, `ForgotPasswordRequest`, `ResetPasswordRequest`).

## Infrastructure

### Models
- `User` — relations: `followers`, `achievements`, `subscribedGroups`, `moderatedGroups`, `posts`, `comments`, `platformBans`, `groupBans`, `submittedReports`, `groupJoinRequests`.
- `UserSettings` — PK `user_id` (string, non-incrementing); belongs to `UiLanguage`; boolean casts on the toggles. *(0.1.8)* No speaking-language column — see `User::speakingLanguages()` (`user_speaking_languages`).
- `UserCounters` — PK `user_id`.
- `UserUserSubscription`, `BlockedUser` — link tables, composite PK, `created_at` only.

### Routing & middleware
- `guest` middleware: login, register, forgot/reset password. `auth` middleware: verification, logout. Throttling on reset and verification mails.
