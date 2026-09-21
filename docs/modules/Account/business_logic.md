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
| `User` *(Aggregate Root, extends `BaseEntity`)* | id, name, email, password, email_verified_at, date_of_birth, … | A registered account holder. | - Email is unique and must be verified before verified-only actions.<br>- Password is stored hashed only.<br>- `isAdult()` is true when age ≥ 18 (from `date_of_birth`).<br>- Deleting a user cascades to owned data (settings, counters, subscriptions). |
| `UserSettings` *(owned by `User`, PK = `user_id`)* | user_id, ui_language_id, speaking_language_id, dark_theme, show_swear_words, show_adult_content, enable_cookies, allow_messages | Personal preferences. Exactly one per user. | - One row per user.<br>- Language ids must reference existing languages.<br>- `show_adult_content` may only be enabled for adult users. |
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
| `UserRepositoryInterface` *(contract)* | `create`, `updatePassword` (persistence contract implemented in `Repositories/`). |

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
- `UserSettings` — PK `user_id` (string, non-incrementing); belongs to `UILanguage`, `SpeakingLanguage`.
- `UserCounters` — PK `user_id`.
- `UserUserSubscription`, `BlockedUser` — link tables, composite PK, `created_at` only.

### Routing & middleware
- `guest` middleware: login, register, forgot/reset password. `auth` middleware: verification, logout. Throttling on reset and verification mails.
