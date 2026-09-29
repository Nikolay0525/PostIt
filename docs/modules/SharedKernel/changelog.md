# SharedKernel — Changelog

Append-only. Newest entries first. Format: `## [YYYY-MM-DD] [TICKET] Title`.

## [2026-09-29] [REFACTOR] Languages keyed by code instead of UUID

- `speaking_languages` and `ui_languages` now use their standard code as the primary key (`'uk'`, `'en'`) and no longer extend `BaseEntity`. The UUID added nothing — the code already identifies a language everywhere (browser `Accept-Language`, HTML `lang`, app locale) — while costing a lookup whenever code needed "the Ukrainian row" (`UserObserver` used to search by English `name`), and the seeder generated a different UUID on every `migrate:fresh`, so ids differed between machines.
- Speaking-language code is `string(3)`: ISO 639-1 (2 letters) where it exists, ISO 639-3 (3 letters) for languages without one.
- Foreign keys renamed to match: `user_settings.ui_language_code`/`speaking_language_code`, `groups.language_code` (was `group_language_id`). The group's FK changed from cascade to **restrict** on delete — removing a language used to delete all its groups.
- `config('app.default_speaking_language_name')` → `default_speaking_language_code` (`DEFAULT_SPEAKING_LANGUAGE_CODE`, default `uk`); neither was set in `.env`.
- Still only Ukrainian and English are seeded. Seeding the full ISO 639-1 list and letting a user pick several speaking languages (to recommend groups) are planned, after group creation is finished.
- Changed directly in the initial migration (pre-release; the DB is rebuilt with `migrate:fresh --seed`).

## [2026-09-21] [INIT] Initial module documentation

- Documented `BaseEntity`, language reference data and the planned shared `TargetType` enum.
