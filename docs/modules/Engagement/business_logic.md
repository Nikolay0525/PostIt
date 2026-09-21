# ====== Engagement BUSINESS LOGIC ======

## Purpose

`Engagement` owns:
- Achievements: definitions and per-user progress (`Achievement`, `UserAchievement`).
- Notifications shown in the notifications menu (`Notification`).
- Direct messages between users, shown in the inbox (`Message`).

**NOT here:**
- The counters that achievements are measured against — `Account` (`UserCounters`).
- Events that cause notifications (votes, comments, decisions) — emitted by `Content`, `Community`, `Moderation`.

## Entities

| Entity | Basic Fields | Description | Invariants |
|---|---|---|---|
| `Achievement` *(extends `BaseEntity`)* | id, title, description, target_property, target_value, comparison_type, icon_url | Definition of an achievement: "when `target_property` compares to `target_value` by `comparison_type`". | - No timestamps.<br>- `target_property` names a tracked metric (expected to map to a `user_counters` field).<br>- Definitions are managed by administrators. |
| `UserAchievement` *(link)* | user_id, achievement_id, current_value, is_completed, timestamps | A user's progress towards one achievement. | - PK `(user_id, achievement_id)`.<br>- `is_completed` becomes true once and never reverts.<br>- `current_value` mirrors the tracked metric (stored as string ≤ 100). |
| `Notification` *(extends `BaseEntity`)* | id, user_id, text, url, is_read, type | A short message for one user about an event. | - `text` ≤ 100 characters, `url` optional ≤ 100.<br>- `is_read` defaults to false and only changes false → true.<br>- `type` is an integer type code. |
| `Message` | id, sender_id, receiver_id, group_id, head, body, read_at, replied_at | A direct message. `group_id` optionally links it to a group context. | - `head` ≤ 500, `body` ≤ 500 characters.<br>- Sender ≠ receiver.<br>- Delivered only if the receiver's `allow_messages` is true and the receiver has not blocked the sender.<br>- `read_at` / `replied_at` are set once. |

## Key Flow — Achievement progress

- An activity event (post created, vote received, …) updates the matching `user_counters` value.
- `Engagement` re-evaluates achievements whose `target_property` matches: `current_value` is updated; when the comparison with `target_value` is satisfied, `is_completed` is set and a `Notification` is created.

**Boundary:** `Engagement` reacts to events; it never changes content, membership or sanctions.

## Domain Services *(planned)*

| Service | Operation |
|---|---|
| `AchievementService` | Recalculate progress for a user and metric; complete achievements; emits `AchievementUnlocked`. |
| `NotificationService` | Create, list unread, mark as read. |
| `MessageService` | Send message (checks `allow_messages` and blocks), mark read, mark replied. |

## Domain Events *(planned)*

| Event | Carries | Notes |
|---|---|---|
| `AchievementUnlocked` | user id, achievement id | Creates a notification. |

## Application Commands & Queries *(planned)*

**Commands:** mark notification read, send message, mark message read/replied.
**Queries:** notifications list (unread count for the menu), inbox, user achievements.

## Infrastructure

### Models
- `Achievement` — `hasMany(UserAchievement)`, `belongsToMany(User)` with pivot `current_value`, `is_completed`.
- `UserAchievement` — composite PK, cast `is_completed` boolean.
- `Notification` — casts `is_read` boolean, `type` int.

### UI
- `NotificationsMenu`, `InboxMenu` in the top navigation (visible for authenticated users; currently stubs).
