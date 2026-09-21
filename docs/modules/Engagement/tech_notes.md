# Engagement — Tech Notes

## Tech debt
- `NotificationsMenu` and `InboxMenu` are UI stubs; no controllers or queries exist.
- `Notification.type` is a raw integer; define `NotificationType` enum.
- `UserAchievement.current_value` is a string (≤ 100) while counters are integers — decide on a comparison strategy for `Achievement.comparison_type` (e.g. `>=`, `==`) and its enum.
- `UserAchievement` has `$incrementing = false` but no `$primaryKey` declared for the composite key.
- Verify that a `Message` model exists: the `messages` table is in the migrations, but the model was not among the reviewed files.

## Non-obvious decisions
- Notification text is limited to 100 characters, so it must be a short template (e.g. "Your post got a new comment") with the details behind `url`.
- Direct messages respect `UserSettings.allow_messages` and user blocks.

## Open questions
- Should achievements be recalculated by events (real time) or by a scheduled job?
