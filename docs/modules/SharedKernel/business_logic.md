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
| `SpeakingLanguage` *(extends `BaseEntity`)* | id, name | Language a user speaks or a group is written in. Reference data. | - No timestamps.<br>- `name` is required.<br>- Referenced by `groups.group_language_id` and `user_settings.speaking_language_id`. |
| `UILanguage` | id, name | Language of the interface. Reference data. | - Referenced by `user_settings.ui_language_id`. |

## Shared Enums *(planned — currently raw integers, see tech notes)*

| Enum | Values | Used by |
|---|---|---|
| `TargetType` | `POST = 1`, `COMMENT = 2` (extendable) | `votes.parent_type`, `reports.target_type`, `images.owner_type` |

## Infrastructure

### Models
- `BaseEntity` — abstract, `HasUuids`.
- `SpeakingLanguage` — table `speaking_languages`, no timestamps, `hasMany(UserSettings)`.
- `UILanguage` — table `ui_languages`.
