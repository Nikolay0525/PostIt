# Security Policy — PostIt

| | |
|---|---|
| Name | Security Policy — PostIt |
| Version | 0.1.0 |
| Status | Draft |
| Classification | Internal |
| Last update | 2026-10-05 |
| Owner | Project owner (Mykola Poberezhnyi) |

> This policy defines mandatory security rules for developing, releasing and maintaining PostIt — a community discussion platform built as a Laravel 13 + Inertia 3 + Vue 3 web application. It complements the [project policy](../project_policy.md) (code and architecture rules) and is the basis for the [threat model](threat_model.md). The handling of personal data is defined separately in the [privacy policy](privacy_policy.md).

## Policy change rules

- The policy changes only through a dedicated task, never as a side effect of another task.
- Every change is agreed with the project owner, goes into a separate commit prefixed `[POLICY]` and increments the version.
- A new threat found in the threat model that needs a new rule is added here first, then implemented.

## Contents

1. Purpose
2. Scope
3. Roles and responsibilities
4. Security principles
5. Secure development environment
6. Software protection
7. Secure implementation
8. Security verification
9. Vulnerability management
10. Privacy
11. Exceptions
12. Related documents
13. Final provisions

## 1. Purpose

The policy sets one set of security rules for the code, data, configuration, secrets, development environment and maintenance of PostIt, so that the application provides:

- **Confidentiality** — private-group posts, emails, dates of birth and settings are visible only to whom they belong to.
- **Integrity** — content, votes, memberships and sanctions are changed only by users who have the right to change them.
- **Availability** — one user or bot cannot make the site unusable for others (rate limits, pagination).
- **Accountability** — sanctions and moderation decisions are recorded with who made them and why.

## 2. Scope

**In scope:**

| Item | Details |
|---|---|
| Source code | `app/`, `routes/`, `config/`, `database/`, `resources/`, `lang/`, `bootstrap/`, `tests/` of the PostIt repository |
| Configuration | `.env` (per environment), `config/*.php`, `composer.json`/`composer.lock`, `package.json`/`package-lock.json` |
| Data stores | Relational database (MySQL in production, SQLite in tests), Redis cache, `storage/app/public` (uploaded images), `storage/logs` |
| Environments | Local development machines, test runs, the future production server |
| Repository | GitHub repository of PostIt, its branches and pull requests |
| Users of the system | Guests, registered users, group Owners/Guardians (planned), platform administrators (planned) |

**Out of scope:** native mobile apps, a public third-party API and real-time chat (not planned, see project policy); security of the user's own device and browser; physical security of the hosting provider.

## 3. Roles and responsibilities

| Role | Responsibility |
|---|---|
| Project owner | Owns this policy, approves changes, reviews every pull request, decides on exceptions and on fixing reported vulnerabilities, manages secrets and production access. |
| Developer (incl. AI coding agents) | Follows this policy and the project policy, writes tests for access rules, never commits secrets, records new risks in module `tech_notes.md`. Code written by an AI agent is reviewed by the owner like any other code. |
| Operator (server maintainer) | Keeps the server, PHP, database and Redis updated, keeps backups, watches logs, applies migrations on release. |
| Group Owner / Guardian *(planned)* | Moderates only inside their own group, within the rights given by `Community` and `Moderation`. |
| Platform administrator *(planned)* | Reviews escalated reports and issues platform bans; never has access to passwords. |

In the current stage one person (the project owner) holds the owner, developer and operator roles.

## 4. Security principles

- **Server is the only protection.** Every access rule (private groups, membership, bans, age, ownership) is checked on the server in Policies/Services. UI checks are a convenience only.
- **Least privilege.** A user gets only the rights of their role; a database user of the application has no rights beyond the application schema; developers get production access only when needed.
- **Deny by default.** Routes that change state are inside the `auth` middleware group; an action is allowed only if a Policy explicitly allows it. Unknown values (sort, period, search type) fall back to a safe default.
- **Defense in depth.** Validation (Form Request) + authorization (Policy) + database constraints (unique indexes, foreign keys) + rate limits.
- **Secure defaults.** New settings default to the safer value (adult content off, `APP_DEBUG=false` in production, cookies `HttpOnly`, `SameSite=Lax`).
- **Minimal attack surface.** No public API, no file types except the allowed images, no features that are not specified.
- **No secrets in code.** Keys, passwords and tokens live only in `.env` / server environment.
- **Don't reveal existence.** Private posts answer 404 to outsiders; login and password reset answer the same way for existing and unknown emails.

## 5. Secure development environment

**Repository and accounts**

- The repository is hosted on GitHub. Developers authenticate with SSH keys (ed25519, private key never leaves the developer's machine) and have two-factor authentication on their GitHub account.
- `main` is protected: changes reach it only through pull requests reviewed by the owner; force-push to `main` is forbidden.
- Branches follow the project policy: `feature/<name>`, `fix/<name>`; security fixes use `fix/` and are merged with priority.

**Secrets**

- `.env` is listed in `.gitignore` and is never committed; `.env.example` contains only names and non-secret defaults.
- `APP_KEY`, database, Redis and mail credentials are different for every environment.
- A secret that was committed or shown in a screenshot/log is considered leaked: it is rotated immediately, not only deleted from history.

**Dependencies**

- Only Composer and npm packages from the official registries are used; versions are pinned by the committed `composer.lock` and `package-lock.json`.
- A new dependency is added only when needed, after checking that it is maintained and widely used; this is noted in the PR.
- `composer audit` and `npm audit` are run before every release and after updating dependencies; GitHub Dependabot alerts are enabled for the repository.

**Workstation**

- Development machines have an updated OS, antivirus, and disk encryption where available.
- Local databases use seed/factory data only — no copies of production data.
- Production uses `APP_ENV=production`, `APP_DEBUG=false`; development settings (`MAIL_MAILER=log`, SQLite) are never deployed.

**Automated checks**

- Before merge: `./vendor/bin/pint --test` and `composer test` must pass (project policy §11). A CI pipeline running the same checks on every pull request is planned.

## 6. Software protection

- **Source code** — stored only in the GitHub repository; access is given personally and removed when no longer needed.
- **Configuration** — `config/*.php` read values from the environment; production configuration is cached (`php artisan config:cache`) and is not readable from the web (document root is `public/` only).
- **Build artefacts** — front-end assets are built from the locked dependencies with `npm run build`; only `public/build` is served. `vendor/` and `node_modules/` are installed on the server from lock files, not copied from a developer machine.
- **Releases** — every release is a tagged commit with semantic version (`[RELEASE]` commit); the deployed version is always a commit from `main`.
- **Uploaded files** — stored under random UUID names on the `public` disk; the original client file name is never used as a path.
- **Backups** — the database and `storage/app/public` are backed up regularly; backups are stored encrypted and access to them is limited to the operator.

## 7. Secure implementation

**Input validation**

- Every request with input has a Form Request with explicit rules (type, length, format, existence). Lengths follow the specification (e.g. post title ≤ 100, comment ≤ 500, report text ≤ 100).
- Only `$request->validated()` data is passed to services; mass assignment is limited by `$fillable`.
- Raw SQL is allowed only with bound parameters; search terms escape `LIKE` wildcards (`SearchesText::escapeLike()`).
- File uploads accept only `jpg, jpeg, png, webp`, checked by content (`mimes`), max 2 MB and 4096×4096 px (`UpdateAvatarRequest`). Metadata (EXIF, including GPS location) is removed before the file is stored.

**Authentication and sessions**

- Passwords are hashed with bcrypt (`BCRYPT_ROUNDS=12`), minimum 8 characters, never logged or returned.
- Email must be verified before any action that creates content (`hasVerifiedEmail()` in Policies).
- On login the session id is regenerated; on logout the session is invalidated and the CSRF token regenerated.
- Login, registration, password reset and verification endpoints are rate limited (see exceptions for the current gap).
- Session cookies are `HttpOnly`, `SameSite=Lax`, and `Secure` in production (`SESSION_SECURE_COOKIE=true`); the site is served only over HTTPS.
- Password reset tokens expire after 60 minutes; verification links are signed.

**Authorization**

- Every mutation calls a Policy (`PostPolicy`, `CommentPolicy`, `GroupPolicy`, `UserPolicy`) or an equivalent service check.
- Private-group rule lives in one place (`ChecksGroupVisibility::canSeePostsOf()`); an outsider gets 404, not 403.
- Group bans are checked through `ChecksGroupBans`; platform bans must be checked by every Policy once they are enforced.
- Users cannot vote on their own content, follow themselves, or enable adult content while under 18.

**Output and XSS**

- Vue templates escape output by default; `v-html` is allowed only for post Markdown rendered by `RendersMarkdown` with `html_input: strip` and `allow_unsafe_links: false`. Any new `v-html` needs owner approval.
- Inertia shares only whitelisted user fields (`id`, `username`, `avatar_url`); emails, dates of birth, tokens and other users' private data are never sent to the client.

**CSRF and HTTP**

- All state-changing routes are in the `web` middleware group with CSRF protection; JSON helpers send the `X-XSRF-TOKEN` header.
- State changes are never done by `GET`.
- Rate limits (`throttle`) are set on every write endpoint and on search.
- Every response carries security headers: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, a Content Security Policy limited to own origin, and HSTS in production.

**Cryptography**

- Only the framework's primitives are used (bcrypt hashing, `APP_KEY` encryption, signed URLs, `Str::random` for tokens). Own cryptographic algorithms are forbidden.
- TLS 1.2+ is required between the browser and the server, and to the mail provider.

**Errors and logging**

- In production errors show a generic page; stack traces and exception messages are only in logs.
- Production logs use the `daily` channel with 14 days of retention; log files are readable only by the application user.
- Logs never contain passwords, tokens, session ids or full emails (NFR-SEC-004); event payloads carry ids only (project policy §5).
- Security-relevant events are logged: failed logins over the limit, authorization failures on mutations, bans issued/lifted, administrator actions (planned).

**External systems**

- The only external system is the mail transport (verification and reset emails). Its credentials are in `.env`; emails contain only a signed link, never a password.
- Any new integration (image moderation API, search engine) is placed behind an application interface and requires an update of the threat model first.

## 8. Security verification

- **Code review** — every PR is reviewed by the owner with attention to: validation, Policy call on each mutation, new `v-html`, raw SQL, new routes outside `auth`, secrets.
- **Automated tests** — every route that changes state has feature tests for the happy path and for the forbidden cases: guest, unverified, non-member, banned, foreign object, invalid input (project policy §10). Access-control rules from the threat model have their own tests.
- **Static analysis** — Laravel Pint for style; a PHP static analyser (Larastan) is planned.
- **Dependency check** — `composer audit` and `npm audit` with no high/critical findings.
- **Release criteria** — a release is allowed only if: all tests pass; no open threat with status *Open* and high risk in the threat model; no known high/critical vulnerable dependency; `APP_DEBUG=false` and `SESSION_SECURE_COOKIE=true` in production config.

## 9. Vulnerability management

1. **Report** — a vulnerability found by a developer or reported by anyone is sent privately to the owner (GitHub private security advisory), not as a public issue.
2. **Register** — it is recorded with date, affected component, how it was found.
3. **Assess** — severity is rated Critical / High / Medium / Low by impact and ease of use (CVSS can be used as a guide).
4. **Fix** — target time: Critical — 48 h, High — 7 days, Medium — 30 days, Low — next planned release. The fix goes to a `fix/` branch with a test that reproduces the problem.
5. **Verify** — the test passes, the threat model status is updated, leaked secrets are rotated.
6. **Close** — the fix is noted in the module `changelog.md`; users are informed if their data could be affected.

## 10. Privacy

Collection, use, storage and deletion of personal data is defined in the separate [privacy policy](privacy_policy.md), because it is meant to be shown to users. This policy requires that:

- only data listed in the privacy policy is collected (data minimisation);
- any new personal data field is first added to the privacy policy;
- personal data is not written to logs and not used in test fixtures.

## 11. Exceptions

An exception is allowed only temporarily, approved by the owner, recorded in this table and linked to a threat. Current exceptions (development stage, no production release yet):

| # | Rule not yet met | Risk / threat | Deadline |
|---|---|---|---|
| EX-1 | `POST /login` and `POST /register` have no rate limit | Password brute force, mass registration (T-01, T-14) | Before first release |
| EX-2 | Platform bans are stored but not checked by any Policy; `users.role` (0 = user, 1 = admin) exists but no feature or Policy uses it yet | Banned user keeps acting (T-33) | Before Moderation release |
| EX-3 | Uploaded images are not screened for illegal/adult content (FR-MOD-013 planned) | Illegal content in avatars (T-37) | Before public registration |
| EX-4 | No self-service account deletion endpoint (NFR-PRV-001) | User cannot remove their data themselves (privacy) | Before first release |
| EX-5 | `SESSION_SECURE_COOKIE` and HTTPS are not configured locally | Session theft on an open network (T-07, T-52) — only production is affected | At deployment |
| EX-6 | No CI pipeline; checks run locally | A PR merged without tests or with a vulnerable dependency (T-16) | Before second developer joins |
| EX-7 | Security headers are not set yet | Clickjacking, content sniffing (T-10, T-54) | Before first release |
| EX-8 | Image metadata is not removed (no image library enabled: GD/Imagick) | Location leak from photos (T-21) | Before first release |

## 12. Related documents

- [Project policy](../project_policy.md) — code, architecture, testing and Git rules.
- [Privacy policy](privacy_policy.md) — personal data handling.
- [Threat model](threat_model.md) — STRIDE analysis and derived security requirements.
- [Requirements specification](../specification/requirements_specification.md) — NFR-SEC-001…005, NFR-PRV-001.
- [Architecture description](../architecture/architecture_description.md) — §7 Security view.
- NIST SP 800-218 (SSDF), OWASP Top 10, Law of Ukraine "On Personal Data Protection", GDPR principles.

## 13. Final provisions

- The policy is mandatory for everyone who changes PostIt code, configuration or infrastructure, including AI agents.
- The policy is reviewed at least every six months, and after every security incident or major architecture change.
- Violations found in review are fixed before merge; violations found after merge are handled as vulnerabilities (§9).
