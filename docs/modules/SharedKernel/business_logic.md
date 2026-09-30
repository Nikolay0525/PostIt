# ====== SharedKernel BUSINESS LOGIC ======

## Purpose

`SharedKernel` owns:
- Base entity abstraction with UUID identity, inherited by all domain entities.
- Language reference data used by several modules (speaking languages, UI languages).
- Shared enumerations used by more than one module (e.g. target/parent type for votes, reports and images).

**NOT here:**
- Any module-specific business rule — lives in the owning module (`Account`, `Community`, `Content`, `Moderation`, `Engagement`).
- Anything used by only one module. New items require project-owner approval; the kernel must not grow into a dumping ground.

## Entities

| Entity | Basic Fields | Description | Invariants |
|---|---|---|---|
| `BaseEntity` *(abstract)* | id (uuid) | Abstract Eloquent base (`HasUuids`) for all entities with own identity. Not instantiated directly. | - `id` is a UUID generated on creation.<br>- Identity is immutable. |
| `SpeakingLanguage` *(plain `Model`, not `BaseEntity` — 0.1.8)* | code, name, native_name *(0.1.8; all 183 ISO 639-1 languages, seeded from `database/data/languages.json`)* | Language a user speaks or a group is written in. Seeded reference data. | - *(0.1.8)* Primary key is the ISO 639 `code` (`string(3)`: 2 letters where ISO 639-1 has one, 3 letters from ISO 639-3 otherwise), not a generated UUID.<br>- No timestamps.<br>- `name` is required and unique.<br>- Referenced by `groups.language_code` and `user_speaking_languages.language_code` (*0.1.8:* a list per user, replacing `user_settings.speaking_language_code`); deleting a language used by a group is blocked (`restrictOnDelete`). |
| `UiLanguage` *(plain `Model`, not `BaseEntity` — 0.1.8)* | code, name, is_active | Language of the interface. Seeded reference data. | - *(0.1.8)* Primary key is the locale `code` (`string(10)`), not a generated UUID.<br>- Only languages the interface is actually translated into; intentionally a short list.<br>- Referenced by `user_settings.ui_language_code`.<br>- Every active code needs a `lang/{code}/` directory — `SetLocale` may pick any active language for a guest. |

## Shared Enums *(planned — currently raw integers, see tech notes)*

| Enum | Values | Used by |
|---|---|---|
| `TargetType` | `POST = 1`, `COMMENT = 2` (extendable) | `votes.parent_type`, `reports.target_type`, `images.owner_type` |

## Infrastructure

### Models
- `BaseEntity` — abstract, `HasUuids`. *(0.1.8)* Used by every entity with its own identity, but **not** by seeded reference data keyed by a standard code (`SpeakingLanguage`, `UiLanguage`).
- `SpeakingLanguage` — table `speaking_languages`, no timestamps, `hasMany(UserSettings)`.
- `UILanguage` — table `ui_languages`.
