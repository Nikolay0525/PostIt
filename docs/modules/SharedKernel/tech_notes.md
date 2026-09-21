# SharedKernel — Tech Notes

## Tech debt
- `SpeakingLanguage` declares `use HasUuids` and `HasFactory` although `BaseEntity` already uses `HasUuids`; the duplicate import can be removed.
- Several models (`UserSettings`, `UserCounters`, link tables) extend plain `Model` instead of `BaseEntity` because their keys are not their own UUID. This is intentional for link/owned tables.
- `TargetType` enum (Post = 1, Comment = 2) is not created yet; three tables use its values as raw integers.

## Non-obvious decisions
- All primary keys are UUIDs to keep identifiers non-guessable and safe to expose in URLs (`whereUuid` on routes).
