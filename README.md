# PostIt

PostIt is a community discussion platform. Users join topic groups (public or private), publish posts, discuss them in nested comment threads, vote, follow each other and earn achievements. Group moderators and platform administrators keep the community safe through reports and bans.

| | |
|---|---|
| Status | Early development (backend models and auth done; feed, group and post pages run on dummy data) |
| Stack | PHP 8.3+, Laravel 13, Inertia 3, Vue 3, Tailwind 4, Vite |
| Tooling | Laravel Pint (style), PHPUnit 12 (tests), Ziggy (named routes in JS) |

## Quick start

```bash
composer setup     # install deps, create .env, generate key, migrate, build assets
composer dev       # run the dev environment
composer test      # run the test suite
```

## Documentation

All project documentation lives in [`docs/`](docs/) and is versioned together with the code.

| Document | Purpose |
|---|---|
| [Project policy](docs/project_policy.md) | Mandatory rules for code, architecture, naming, testing, Git and docs |
| [Module registry](docs/modules.md) | Map of all bounded contexts (modules) with boundaries and dependencies |
| [Requirements specification](docs/specification/requirements_specification.md) | What the system shall do (ISO/IEC/IEEE 29148 style) |
| [Architecture description](docs/architecture/architecture_description.md) | How the system is structured (ISO/IEC/IEEE 42010 style) |
| `docs/modules/<Module>/` | Per-module `business_logic.md`, `changelog.md`, `tech_notes.md` |
