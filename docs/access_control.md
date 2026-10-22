# Identification, Authentication and Authorization — PostIt

| | |
| --- | --- |
| Status | Active |
| Last updated | 2026-10-21 |
|Stack| Laravel + Inertia + Vue · |
| Owner | Nikolay0525 |

This document records the identification, authentication and access-control
decisions for PostIt. It is intentionally concise; rules it refers to are
enforced in code (routes, Form Requests, the `AuthService`, Eloquent casts and
Policies).

## 1. Identification

| Identifier | Scope | Used when |
|------------|-------|-----------|
| `id` (UUID) | Internal, immutable | Primary key, session subject, foreign keys, signed URLs |
| `email` | Login credential | The identifier the user types at `/login`; unique, not public |
| `username` | Public handle | Profile URLs (`/users/{username}`), mentions; unique, user-visible |

Email and username are unique and validated at registration. The UUID is the
stable internal reference, so renaming a username never breaks relations or
sessions. Email and username are never exposed interchangeably: the public site
shows `username`, authentication uses `email`.

## 2. Authentication

Factor: **knowledge (password)**, single-factor by default.

| Method | Status | Notes |
|--------|--------|-------|
| Email + password | Mandatory | `Auth::attempt`; password stored only as a bcrypt hash (`'password' => 'hashed'` cast). |
| Email verification | Mandatory before protected actions | `MustVerifyEmail`; signed verification links. |
| "Remember me" | Optional | Long-lived remember-token cookie. |
| Password reset | Optional | Tokened link, throttled, same response whether or not the email exists (no account enumeration). |
| MFA (TOTP second factor) | **Planned / optional** | Knowledge + possession; recommended for Admin and, later, Guardian accounts. Secret stored as protected data, not in plaintext. |

Password storage uses bcrypt (Laravel default), which embeds its own per-user
salt and cost. Login failures return one generic message (`auth.failed`) and do
not reveal whether the email or the password was wrong. Login is rate-limited
(`throttle:5,1` on `POST /login`) to bound online guessing; session id is
regenerated on success and invalidated on logout.

## 3. Authorization

Model: **hybrid — RBAC + resource ownership (DAC-like) + attribute checks**, with
deny-by-default. A single model does not fit, so the parts are combined:

- **RBAC** — platform role `UserRole` (`User` / `Admin`); Admin gets
  platform-wide moderation. Group-level roles (Owner, Guardian — planned) scope
  moderation to one group.
- **Ownership (DAC-like)** — the author of a post/comment controls their own
  content (edit/delete), enforced in `PostPolicy`, `CommentPolicy`,
  `UserPolicy`, `GroupPolicy`.
- **Attributes / context** — email-verified status, group membership, block
  relationships and content privacy gate specific actions.

Rationale: PostIt is a community platform where most rights follow from *who
owns a resource* and *membership in a group*, not from a fixed clearance level —
so a mandatory (MAC) model is unsuitable, pure RBAC is too coarse for
per-object ownership, and pure DAC cannot express platform/group moderation.
The hybrid matches the business logic.

Enforcement rules:
- Every protected action is authorized in server-side logic (Policies / Form
  Requests) immediately before it runs. Hiding UI elements is not authorization.
- Least privilege: a role/membership grants only the rights its function needs.
- Private content returns 404 rather than 403, so existence is not disclosed.
