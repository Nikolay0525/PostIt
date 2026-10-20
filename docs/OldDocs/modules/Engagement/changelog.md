# Engagement — Changelog

Append-only. Newest entries first. Format: `## [YYYY-MM-DD] [TICKET] Title`.

## [2026-10-03] [FEAT] Unlocked achievements shown on profiles

- Read side only: `AchievementRepositoryInterface::unlockedBy()` (completed rows, most recent first, with `unlocked_at` = the progress row's `updated_at`, since `is_completed` never reverts) and `AchievementService::getUnlocked()`, shown in the profile's Achievements block. Nothing awards achievements yet — progress tracking against `user_counters` is still planned.

## [2026-09-21] [INIT] Initial module documentation

- Documented achievements, notifications and direct messages.
- Documented the achievement progress flow.
