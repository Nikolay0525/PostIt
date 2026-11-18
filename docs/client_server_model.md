# Client–Server Trust Model and Network Authentication — PostIt

| | |
| --- | --- |
| Status | Active |
| Last updated | 2026-11-19 |
|Stack| Laravel + Inertia + Vue · |
| Owner | Nikolay0525 |

This document records PostIt's client–server trust model: what runs where, how a
user is authenticated over the network, how the authenticated state is kept, and
where access rights are checked. It is intentionally concise; the rules it refers
to are enforced in code (middleware, Policies, session configuration).

## 1. What belongs on the server vs the client

The client (Vue running in the browser) is an untrusted environment: the user can
read it, modify it, or bypass it and craft requests directly. Therefore:

| Server-side (authoritative) | Client-side (presentation only) |
|------------------------------|----------------------------------|
| Authentication, session/identity | Rendering the UI, local view state |
| Authorization decisions (Policies) | Showing/hiding controls by role (convenience only, not security) |
| All business logic and validation | Optimistic UI, client-side form hints |
| The database and all persistent data | A copy of data already authorized for this user |
| Secrets and keys (`APP_KEY`, credentials) | — (no long-term secret is shipped to the client) |

Hiding a button or a menu item in the client is not an access-control mechanism;
every protected operation is re-checked on the server.

## 2. Network authentication

PostIt is a same-origin Inertia application, so it uses **session-based
authentication**, not Bearer tokens. The user authenticates once with email and
password over HTTPS (`POST /login`, rate-limited `throttle:5,1`); the password is
verified against a bcrypt hash and never stored or sent again after login.

A Bearer/token model (OAuth 2.0 Authorization Code + PKCE, or Laravel Sanctum
tokens) is deliberately **not** used for the web client, because tokens suit
cross-origin, third-party or mobile clients and add theft/replay surface that a
same-origin cookie session avoids. If a separate mobile or third-party API is
added later, it would use short-lived, scope-limited tokens validated on the
server, never the deprecated Implicit or Resource Owner Password Credentials
flows.

## 3. Keeping the authenticated state

After login the server issues a random, unguessable **session id**, stored in a
cookie that is:
- **encrypted and signed** (Laravel `EncryptCookies`) — a tampered cookie fails its MAC and is rejected;
- **HttpOnly** (`session.http_only = true`) — not readable by JavaScript, limiting theft via XSS;
- **SameSite=Lax** (`session.same_site`) — not sent on cross-site requests, limiting CSRF and leakage;
- **Secure in production** (`SESSION_SECURE_COOKIE`) — sent only over HTTPS.

The session id is only a lookup key; the real state (the authenticated user and
session data) lives **server-side** in the database session store
(`session.driver = database`) with a bounded lifetime. The client never holds the
authoritative identity, only the id that points to it.

## 4. Server-side authorization

Authentication does not grant access. Every protected route runs server-side
checks before the operation:
- `auth` middleware — a valid session is required;
- `verified` middleware — a verified email is required for content-creating actions;
- **Policies** — per-resource rights are checked with `authorize()` / `Gate::allows()` (e.g. `PostPolicy`, `UserPolicy`, `VotePolicy`), so a user can only act on resources they are entitled to.

Role, user id and other critical attributes are taken from the verified session,
never from values the client sends. Private content returns 404 rather than 403,
so its existence is not disclosed.

## 5. Protecting the channel

Credentials, the session cookie and signed links must never travel in clear.
Outside local development every generated URL uses **HTTPS**
(`URL::forceScheme('https')` in `AppServiceProvider`), and the session cookie is
marked Secure in production. TLS provides confidentiality, integrity and server
authentication; without it, interception and man-in-the-middle attacks on the
session would be possible.

## 6. Threats addressed

- Interception / MITM — HTTPS everywhere, Secure cookie.
- Session theft — HttpOnly, SameSite, encrypted+signed cookie, bounded lifetime.
- Request forgery — CSRF tokens on state-changing requests; SameSite cookie.
- Credential guessing — login rate limiting (see `access_control.md`).
- Client tampering / forged requests — all authorization re-checked server-side.
