# Moderation — Tech Notes

## Tech debt
- `Report.status`, `Report.target_type` are raw integers; create `ReportStatus` and reuse `TargetType`. `reports.target_id` is a polymorphic uuid without FK.
- `GroupBan` has an array `$primaryKey` (`group_id`, `blamed_user_id`) — composite-key limitation, see Community notes.
- No service was found that enforces bans: `GroupBan::isActive()` exists, but no check of it was found when posting, commenting or joining.
- Check that `PlatformBan` has an `isActive()` method like `GroupBan`; add one if missing.
- Confirm how platform administrators are represented (no admin flag or role was found in the reviewed files); it is needed for platform bans and escalated reports.

## Non-obvious decisions
- One ban record per (group, user): re-banning updates the existing row (extend `expires_at`) instead of inserting a new one.
- Escalation is modelled by `escalated_at` on `Report`; whether it also needs its own status value is an open question.

## Open questions
- Appeal process for bans.
- Retention period for resolved reports.
