# Project Policy — PostIt

| | |
| --- | --- |
| Status | Draft |
| Last updated | 2026-10-21 |
|Stack| Laravel + Inertia + Vue · |
| Owner | Nikolay0525 |

> This policy defines the mandatory requirements for the code of PostIt — a community discussion web application (see [business_logic.md](business_logic.md)).

## Contents

1. Scope
2. Technologies
3. Fundamental Principles
4. Architecture and Structure
5. Domain Events
6. Naming Policy
7. Annotation Policy (PHPDoc)
8. Exceptions
9. NULL-less Policy
10. Testing Policy
11. Git Workflow
12. Development Documentation Policy
13. Anti-patterns

## Scope

This policy applies to all of PostIt's source code — `app/`, `routes/`, `database/`, `config/`, `lang/`, `resources/` — and to its tests (`tests/`). It is binding for everyone who creates or changes this code — developers and AI agents.

## Technologies

- **Backend:** PHP 8.3, Laravel 13, Composer
- **Frontend:** Inertia 3, Vue 3, Tailwind CSS 4, Vite, Ziggy (named routes in JS), laravel-vue-i18n (translations in JS)
- **Database:** SQLite by default; tests run on in-memory SQLite
- **Code style:** Laravel Pint
- **Tests:** PHPUnit 12 (backend), Playwright (end-to-end)
- **Version control:** Git, GitHub

## Fundamental Principles

- **Use the framework, don't reinvent it.** We use what Laravel, Inertia and Vue already provide instead of writing it from scratch.
- **Low coupling, high cohesion.** Each part of the code does one thing well and depends on as few other parts as possible. This is achieved by reducing dependencies between the layers of the application.

## Architecture and Structure

The backend is layered; calls go only from top to bottom.

```
Controller → Service → RepositoryInterface → EloquentRepository → Model
```

| Layer | Location | Role |
|---|---|---|
| Request | `app/Http/Requests/` | Validates input before it reaches the controller |
| Controller | `app/Http/Controllers/` | Accepts the request, calls a service, returns a response |
| Policy | `app/Policies/` | Decides whether a user may perform an action |
| Resource | `app/Http/Resources/` | Shapes a model into the JSON the frontend receives |
| Service | `app/Services/` | Business operations |
| Repository | `app/Repositories/Contracts/` (interfaces), `app/Repositories/Eloquent/` (implementations) | Database access; interfaces are bound to implementations in `AppServiceProvider` |
| Model | `app/Models/` | Eloquent models |
| Events, listeners, notifications | `app/Events/`, `app/Listeners/`, `app/Notifications/` | See Domain Events |

Frontend: Inertia pages in `resources/js/Pages/`, reusable components in `resources/js/Pages/Components/`, layouts in `resources/js/Layouts/`.

**Rules:**
- A controller may call a repository directly only for a simple read with no business rules — fetching one entity by id or slug, or a reference list (e.g. languages). Anything that changes data or applies business rules goes through a service. A service that would only pass a call through to a repository is not created.
- A repository is per aggregate, not per model: it may work with another model if that model only makes sense under its aggregate (e.g. `GroupRepository` handles `UserGroupSubscription`).
- Business rules are preferably checked in both the model and the service. A rule that concerns a specific user or entity lives on its model (e.g. `User::isAdult()`).

## Domain Events

Domain events use Laravel's built-in events and listeners; there is no custom event infrastructure.

- An event class lives in `app/Events/` and is named after what happened, in the past tense (`CommentCreated`, `PostCreated`). It only says what happened — whoever needs to react listens for it.
- Events are dispatched from services (`CommentCreated::dispatch($comment)` in `CommentService`).
- Listeners live in `app/Listeners/` (`FollowOwnComment`, `SendCommentNotifications`).
- A listener that does slow work (e.g. sending notifications) is queued and implements `ShouldQueueAfterCommit`, so it runs only after the database transaction has been committed.

## Naming Policy

Naming follows the standards built into PHP (PSR-1), Laravel and Vue. There are no custom conventions on top of them.

**Principle:** `snake_case` is for names that Laravel reads by name (database, routes, translations, request fields); `camelCase` is for names we give ourselves in PHP and JS.

**Casing:**

| What | Style | Example | Source |
|---|---|---|---|
| Classes, interfaces, traits, enums | `PascalCase` | `PostService`, `VoteParentType` | PSR-1 |
| Enum cases | `PascalCase` | `JoinRequestStatus::Waiting` | Laravel |
| Methods | `camelCase` | `castVote()` | PSR-1 |
| Variables, parameters, properties | `camelCase` | `$viewerId` | Laravel |
| Constants | `UPPER_SNAKE_CASE` | `SLUG_MAX_LENGTH` | PSR-1 |
| Database tables | `snake_case`, plural | `posts`, `user_group_subscriptions` | Eloquent |
| Database columns, model attributes | `snake_case` | `user_id`, `is_private` | Eloquent |
| Request fields and JSON keys | `snake_case` | `date_of_birth`, `viewer_vote` | Laravel |
| Route names | `snake_case`, dot-separated: `entity.action` | `posts.show`, `groups.random_post` | Laravel |
| Translation and config keys | `snake_case`, dot-separated | `auth.login.forgot_password` | Laravel |
| URLs | `kebab-case` | `/forgot-password` | web practice |
| JS variables and functions | `camelCase` | `localUpvotes`, `isOwnContent` | JS |
| Vue components and their files | `PascalCase` | `VoteButtons.vue` | Vue |
| Vue props | `camelCase` in the declaration, `kebab-case` in templates | `targetType` / `target-type="post"` | Vue |

**Suffixes and prefixes (PHP):**

| Type | Convention | Example |
|---|---|---|
| Model | singular, no suffix | `Post`, `GroupRuleVersion` |
| Controller | `Controller` suffix | `PostController` |
| Form Request | verb + entity + `Request` | `StorePostRequest`, `CastVoteRequest` |
| API Resource | `Resource` suffix; `ListItemResource` for a list item | `PostResource`, `PostListItemResource` |
| Service | `Service` suffix | `VoteService` |
| Repository interface | `RepositoryInterface` suffix | `VoteRepositoryInterface` |
| Repository implementation | technology prefix + `Repository` | `EloquentVoteRepository` |
| Policy | model + `Policy` | `PostPolicy` |
| Observer | model + `Observer` | `UserObserver` |
| Middleware | an action, as a verb phrase | `SetLocale`, `HandleInertiaRequests` |
| Trait | third-person verb (`Checks…`, `Computes…`) or `Has…`; lives in `Concerns/` next to its users | `ComputesControversy`, `HasEntityAttributes` |
| Enum | no suffix | `PostSort` |
| Factory / Seeder | model + `Factory` / `Seeder` | `PostFactory`, `PostSeeder` |
| Test | class: `Test` suffix; method: `test` + description in `camelCase` | `PrivateGroupAccessTest::testRandomPostIs404ForGuestsAndOutsiders` |
| Exception | `Exception` suffix | `InvalidArgumentException` |

> Existing test methods named `test_` + `snake_case` are not renamed; every new test method uses `camelCase`.

**Imports:** external classes/interfaces/traits/enums are imported with `use` at the top of the file (FQCNs in code are discouraged); PHPDoc uses the same short `use`-imported names as the code; an FQCN with a leading `\` only for types that are not imported; name collisions are resolved with `as` aliases that are logical and concise.

**Directories and files:**
- PHP: `PascalCase` directories following the namespace (PSR-4); file name = class name.
- Vue pages: `Pages/<Entity>/<Action>.vue` (`Posts/Show.vue`); reusable components in `Pages/Components/`; layouts in `Layouts/`.
- Helper JS modules (not components): lowercase directory and file names (`utils/format.js`).

## Annotation Policy (PHPDoc)

Annotations are not required on every method. A comment is written where a method is complex: it explains why the code does what it does, not retells the code.

## Exceptions

## NULL-less Policy

`null` is allowed. Avoiding it is good practice in languages like C#, but PostIt is written in PHP, where `null` is a normal way to express absence.

## Testing Policy

We try to write tests for every feature we build, so that over time the tests cover most of the code and keep builds stable.

| Kind | Location | Purpose |
|---|---|---|
| Feature tests | `tests/Feature/` | The main kind. They go through HTTP and the database and check that data actually passes through — broader and more objective than unit tests. Run on in-memory SQLite. |
| Unit tests | `tests/Unit/` | Only for isolated logic such as formulas (e.g. the controversy score). |
| End-to-end tests | `tests/Playwright/` | Far fewer; only for checking the frontend. |

- Negative scenarios (forbidden, not found, invalid data) are tested where the place is problematic.
- Before merging into `main`, all tests should pass: `main` is the fully working branch.

## Git Workflow

### Branches

| Branch | Purpose |
|---|---|
| `main` | Working state, without errors |
| Other branches | Where features are created and tested; named in free form |

### Commit messages

Free form.

## Development Documentation Policy

Documentation lives in `docs/` as a few flat files, without per-module folders: one person cannot review and maintain a module tree.

Changes to the documentation are recorded in a changelog. Changes to the code are not — Git already tracks them, and there is no need to fill in two places.

## Anti-patterns
