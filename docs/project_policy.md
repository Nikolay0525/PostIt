# Project Policy — PostIt

| | |
|---|---|
| Name | Project Policy — PostIt |
| Version | 0.1.0 |
| Status | Draft |
| Classification | Internal |
| Last update | 2026-09-21 |
| Owner | Project owner (Mykola Poberezhnyi) |

> This policy defines mandatory requirements for the code and architecture of `PostIt` — a community discussion platform built as a Laravel + Inertia + Vue web application. Key requirements: strictly typed OOP following SOLID, clean code, a layered architecture with enforced boundaries between layers, and test coverage.
>
> Out of scope: native mobile applications, a public third-party API, real-time chat.

## Policy change rules

- **No changes in passing** — the policy changes only through a dedicated policy-update task, never as a side effect of another task.
- **Mandatory approval** — every change is agreed with the project owner beforehand.
- **Separate commit + version bump** — policy changes go into a separate Git commit prefixed `[POLICY]`, and the document version is incremented.

## Contents

1. Scope
2. Technologies
3. Fundamental principles
4. Architecture and structure
5. Domain events policy
6. Naming policy
7. Annotation policy (PHPDoc)
8. Exceptions policy
9. NULL-less policy
10. Testing policy
11. Git workflow
12. Development documentation policy
13. Anti-patterns

## 1. Scope

This policy applies to all PostIt source code (`app/`, `routes/`, `database/`, `resources/js/`) and tests (`tests/`). It is mandatory for everyone who creates or changes this code — developers and AI agents.

## 2. Technologies

- **PHP 8.3+**
- **Laravel 13**, **Inertia.js 3** (server side: `inertiajs/inertia-laravel`), **Ziggy** (named routes in JS)
- **Vue 3**, **Tailwind CSS 4**, **Vite**
- **Composer**, **npm**
- **Laravel Pint** (code style)
- **PHPUnit 12** (tests)
- **Git**

## 3. Fundamental principles

- **Layer separation.** HTTP/transport code, application (use-case) logic, business rules and persistence are kept in separate layers. No layer leaks into another (see §4).
- **Business rules live in one place.** Rules such as "private group posts are visible to members only" are implemented on the server (services/policies), not only in the UI. UI checks are a convenience, never the protection.
- **Thin controllers.** Controllers accept a validated request, call a service, and return a response.
- **Strictly typed OOP, SOLID, clean code.** Every PHP file declares parameter and return types.
- **Module boundaries.** Code belongs to exactly one module (bounded context) from the [module registry](modules.md). Cross-module access is done through identifiers and service calls.
- **Secure by default.** Validation on every input, authorization on every mutation, no user enumeration in auth responses, secrets only in `.env`.
- **Policy is inviolable.** No circumstances justify breaking this policy. Any deviation is corrected immediately.

## 4. Architecture and structure

Calls go top-down only; lower layers never depend on upper ones.

```
Presentation (Http, Inertia pages) → Application (Services) → Domain (Models, Enums, Policies) ←[interfaces]→ Infrastructure (Repositories)
```

Backend structure:

```
app/
├── Http/
│   ├── Controllers/     # Presentation: thin, one controller per resource
│   ├── Middleware/
│   └── Requests/        # Form Requests: validation (grouped by module, e.g. Requests/Auth/)
├── Services/            # Application: use-case orchestration, transactions, events
├── Repositories/
│   ├── Contracts/       # Interfaces used by Services
│   └── *.php            # Eloquent implementations
├── Models/              # Domain: Eloquent entities (BaseEntity with UUID identity)
├── Enums/               # Domain: backed enums for statuses, roles, types
├── Policies/            # Domain: Laravel authorization policies
└── Exceptions/          # Domain/Application exceptions
```

Frontend structure:

```
resources/js/
├── Pages/               # Inertia pages, one folder per module area (Groups/, Posts/, Auth/)
│   └── Components/      # Reusable components (PostCard, VoteButtons, ...)
├── Layouts/
├── data/                # TEMPORARY dummy data — must be replaced by server props (see tech notes)
└── utils/
```

Layer rules:

- **Presentation** validates input (Form Requests), authorizes (Policies/middleware), calls one Service and returns an Inertia/redirect response. No business rules, no direct queries beyond simple read models.
- **Application (Services)** orchestrates repositories and models, opens transactions, dispatches Laravel events. Contains no HTTP or Inertia code.
- **Domain (Models/Enums/Policies)** holds entities, their relations, casts, invariants and authorization rules. Domain code does not use `Request`, `Inertia`, `Auth` facade or the session.
- **Infrastructure (Repositories)** implements persistence behind `Repositories/Contracts` interfaces; Services depend on the interface, never on the implementation.

Modules: each module (Account, Community, Content, Moderation, Engagement, SharedKernel) is described in `docs/modules/<Module>/`. Physical grouping of classes into per-module subfolders (e.g. `app/Services/Community/`) is allowed and preferred once a module has more than a few classes; the layer folders stay the top level.

Database rules:

- Primary keys are UUIDs (models extend `BaseEntity`, which uses `HasUuids`).
- Join tables use composite primary keys; pivot access goes through relations (`belongsToMany`) or a dedicated model with explicit handling of the composite key.
- Type/status/role integers are always backed by a PHP `Enum` (see anti-patterns).
- Every schema change is a new migration; existing migrations are not edited after being shared.

## 5. Domain events policy

- Significant business changes emit a Laravel event (e.g. `Registered`, `PasswordReset` already in use; planned: post created, comment created, vote cast, report resolved).
- Events are dispatched from Services after the state is persisted, never from controllers.
- Event payloads carry identifiers only (UUIDs), never passwords, tokens, emails or other sensitive data.
- Listeners are idempotent and must not trigger event cycles.

## 6. Naming policy

**General:** classes, interfaces, traits, enums — `PascalCase`; methods, variables, parameters — `camelCase`; constants — `UPPER_SNAKE_CASE`; file name = class name; PHP directories — `PascalCase`; database tables and columns — `snake_case`, tables in plural (`user_settings`).

| Type | Convention | Example |
|---|---|---|
| Model | singular noun, no suffix | `Post`, `GroupBan` |
| Abstract base class | prefix `Base` | `BaseEntity` |
| Interface | suffix `Interface` | `UserRepositoryInterface` |
| Enum | singular noun, no suffix | `ReportStatus` |
| Service | suffix `Service` | `AuthService`, `PostService` |
| Repository | suffix `Repository` | `EloquentUserRepository` |
| Controller | suffix `Controller` | `AuthController` |
| Form Request | `<Action><Subject>Request` | `RegisterRequest` |
| Authorization policy | suffix `Policy` | `PostPolicy` |
| Event | past tense | `PostCreated` |
| Exception | suffix `Exception` | `GroupBannedException` |
| Vue page | `PascalCase.vue` in a module folder | `Groups/Show.vue` |
| Vue component | `PascalCase.vue` | `PostCard.vue` |
| Route name | dot notation, `resource.action` | `posts.show` |

**Imports:** external classes are imported with `use` at the top of the file; inline fully-qualified names are discouraged; name collisions are solved with short `as` aliases.

## 7. Annotation policy (PHPDoc)

- Every class, public/protected method and property has a docblock unless the native type declaration fully expresses its meaning (e.g. a typed constructor-promoted property).
- Method docblock: short purpose description (business meaning, not a code retelling), `@param` where the type needs refinement (e.g. `array<string, mixed>`), `@throws` for every exception the method may throw.
- Class docblock for models and services: purpose and, for entities, key **invariants** (business rules the entity keeps).
- Eloquent relations are documented by their return type (`BelongsTo`, `HasMany`, ...).

```php
/**
 * Group ban: a temporary or permanent restriction of a user inside one group.
 *
 * Invariants:
 * - One ban per (group, user) pair.
 * - `expires_at = null` means a permanent ban.
 */
class GroupBan extends Model { ... }
```

## 8. Exceptions policy

- Business-rule violations are reported with dedicated exceptions (e.g. `UserBannedFromGroupException`), not generic ones and not `RuntimeException` with a free-text message.
- Third-party and framework exceptions are caught at the boundary where they occur and either handled or wrapped into an application exception before leaving the Service layer.
- Exception messages shown to users never contain internal details (SQL, paths, tokens).
- Authentication flows return the same response whether or not an account exists (no user enumeration), as already implemented for password reset.
- Rendering of exceptions is centralised in `bootstrap/app.php`; JSON is returned for `api/*` and JSON-expecting requests.

## 9. NULL-less policy

Minimise `null` in business logic: express absence explicitly (empty collection, default value, dedicated "none" state) instead of using `null` as a logical value. This is a recommendation, not a hard ban: where avoiding `null` makes code unreasonably complex it is allowed, ideally with a short justification. Nullable columns that model real optionality (e.g. `posts.title`, `group_bans.expires_at`) are allowed and documented in `business_logic.md`. Empty arrays and collections are not `null`.

## 10. Testing policy

- **Framework:** PHPUnit 12 via `composer test`.
- **Structure:** `tests/` mirrors `app/` (`tests/Unit/Services/...`, `tests/Feature/Http/...`).
- **Unit tests first:** Services and model logic (e.g. `GroupBan::isActive()`, `User::isAdult()`) are covered by fast unit tests with repository doubles.
- **Feature tests:** every route with a state-changing effect has a feature test for the happy path, validation failures, and authorization failures (guest, unverified, banned, non-member).
- **Negative scenarios are mandatory:** every test class covers invalid input and rule violations, asserting the *expected* exception or response.
- **No real external calls:** mail is faked (`MAIL_MAILER=log` / `Mail::fake()`), no network in tests.
- **Frontend:** pages are verified manually until a frontend test runner is adopted; adopting one requires a policy update.

## 11. Git workflow

**Branches**

| Branch | Purpose |
|---|---|
| `main` | Stable branch; always deployable |
| `feature/<short-name>` | One branch per task; deleted after merge |
| `fix/<short-name>` | Bug-fix branches |

Changes reach `main` through pull requests only.

**Commit messages**

```
[type] short summary
```

| Type | Meaning |
|---|---|
| `INIT` | Initial project setup, initial documentation |
| `DEV` | Feature development |
| `FIX` | Bug fix |
| `DOCS` | Documentation only |
| `POLICY` | Policy change (separate commit) |
| `RELEASE` | Release |

**Versioning:** semantic versioning `MAJOR.MINOR.PATCH`, incremented before every release.

**Checks before merge:** `./vendor/bin/pint --test` and `composer test` must pass. Any warning or failure rejects the PR.

## 12. Development documentation policy

Documentation is a deliverable. Outdated or missing documentation is a defect.

```
docs/
├── project_policy.md
├── modules.md                     # module registry
├── specification/requirements_specification.md
├── architecture/architecture_description.md
└── modules/<Module>/
    ├── business_logic.md
    ├── changelog.md
    └── tech_notes.md
```

- **`business_logic.md`** — stable conceptual content of a module: purpose, boundaries ("NOT here"), entities with invariants, lifecycles, services, events. Always reflects the current state.
- **`changelog.md`** — append-only chronological log, newest first. Entry format: `## [YYYY-MM-DD] [TICKET] Title` followed by a list of changes.
- **`tech_notes.md`** — everything a developer must know before touching the module beyond the code and `business_logic.md`: tech debt, known issues, non-obvious decisions, edge cases.
- Whoever changes a module adds a changelog entry, updates `business_logic.md` if the concept changed, and records new debt in `tech_notes.md`.
- Requirements changes are made in the specification **first**, then in code and tests (specification-driven development).
- Style: concise, lists, exhaustive but without noise.

## 13. Anti-patterns

| # | Anti-pattern | Essence |
|---|---|---|
| 1 | Layer leakage | Business rules in controllers or Vue components; `Request`/`Inertia` in Services or Models. |
| 2 | UI-only protection | Access rules (private groups, bans, adult content) enforced only in the frontend. |
| 3 | Fat controller | Controller contains queries, transactions or rules instead of calling a Service. |
| 4 | Bypassing the repository | Services depending on a concrete repository or querying Eloquent directly when a contract exists. |
| 5 | Magic integers | Raw `1`/`2` for `parent_type`, `status`, `role`, `type` instead of a backed Enum. |
| 6 | Vague exception | Generic exception or free-text `RuntimeException` instead of a specific one. |
| 7 | Unvalidated input | Reading `$request->all()` or unvalidated input in a Service or model. |
| 8 | Sensitive data leak | Emails, tokens or hashes in events, logs, or Inertia shared props. |
| 9 | God module | One module absorbing unrelated business areas (e.g. moderation logic inside Content). |
| 10 | Editing shared migrations | Changing a migration that others already ran instead of adding a new one. |
| 11 | Dummy data in production paths | Pages reading `resources/js/data/*` after the matching backend endpoint exists. |
| 12 | Happy-path-only tests | No negative scenarios, or real network/mail calls in tests. |
| 13 | Stale documentation | Code changed without updating `business_logic.md`, `changelog.md` or the specification. |
