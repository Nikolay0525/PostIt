# ====== Moderation BUSINESS LOGIC ======

## Purpose

`Moderation` owns:
- Reports: users flag content or users for review, with a review workflow and escalation.
- Group bans: a moderator restricts a user inside one group, temporarily or permanently.
- Platform bans: an administrator restricts a user across the whole platform.

**NOT here:**
- Moderator assignment and roles — `Community`.
- Storage and soft-delete flags of posts and comments — `Content` (Moderation triggers deletion through Content services).
- User accounts — `Account`.

## Entities

| Entity | Basic Fields | Description | Invariants |
|---|---|---|---|
| `Report` *(Aggregate Root, extends `BaseEntity`)* | id, reporter_id, target_type, target_id, group_id, text, status, reviewed_by, resolution_note, escalated_at | A user's report about a target (post, comment, …). | - `text` ≤ 100 characters.<br>- `status` defaults to 0 (open).<br>- `group_id` nullable: set when the target belongs to a group, so group moderators can review it; `null` reports go to platform administrators.<br>- `resolution_note` ≤ 500 characters, required when the report is closed.<br>- `escalated_at` is set when a group-level report is passed to the platform level.<br>- Reporter is deleted ⇒ report cascades; reviewer deleted ⇒ `reviewed_by` becomes null. |
| `GroupBan` *(link)* | group_id, blamed_user_id, moderator_id, reason, expires_at, timestamps | A user is restricted in one group. | - PK `(group_id, blamed_user_id)`: at most one ban record per user per group.<br>- `reason` required, ≤ 250 characters.<br>- `expires_at = null` ⇒ permanent.<br>- `isActive()` is true while `expires_at` is null or in the future. |
| `PlatformBan` *(extends `BaseEntity`)* | id, banned_user_id, admin_id, reason, expires_at, timestamps | A user is restricted across the platform. | - `reason` required, ≤ 250 characters.<br>- `expires_at = null` ⇒ permanent.<br>- Created only by a platform administrator. |

## Status Lifecycle — `Report.status` *(proposed enum `ReportStatus`; DB column exists, values to be confirmed)*

- New reports start as `OPEN` (0).

| From | Reviewer-initiated | System-initiated |
|---|---|---|
| `OPEN` | → `RESOLVED`, `DISMISSED`, `ESCALATED` | — |
| `ESCALATED` | → `RESOLVED`, `DISMISSED` (platform admin) | — |
| `RESOLVED` | — (terminal) | — |
| `DISMISSED` | — (terminal) | — |

- Escalation sets `escalated_at`; only platform administrators decide escalated reports.
- A resolved report may lead to content deletion and/or a ban; these are separate actions with their own records.

## Key Flow — Report to sanction

- Authenticated user submits a report (target, short text) → `Report` created `OPEN`, `reports_sent` counter incremented.
- Group moderator (or admin) reviews → resolves with a note, dismisses, or escalates.
- If the target violates rules: content is soft-deleted (via `Content`) and optionally a `GroupBan` / `PlatformBan` is issued.
- The affected user is notified (via `Engagement`).

**Boundary:** `Moderation` decides sanctions and keeps the audit trail; enforcement points (can this user post/join?) query `isActive()` bans from other modules.

## Domain Policies *(planned)*

| Domain Policy | Description |
|---|---|
| `ReportPolicy` | Any verified user may report; only moderators of `group_id` (or admins) may review; only admins review escalated reports. |
| `BanPolicy` | Moderators may ban only within their group; only admins issue platform bans; nobody bans an administrator. |

## Domain Services *(planned)*

| Service | Operation |
|---|---|
| `ReportService` | Submit, review, resolve, dismiss, escalate; emits `ReportResolved`. |
| `GroupBanService` | Issue and lift group bans; emits `UserBannedFromGroup`. |
| `PlatformBanService` | Issue and lift platform bans; emits `UserBannedFromPlatform`. |

## Domain Events *(planned)*

| Event | Carries | Notes |
|---|---|---|
| `ReportResolved` | report id, decision | Notifies reporter. |
| `UserBannedFromGroup` | group id, user id, expires_at | Notifies the user; no reason text in the payload. |
| `UserBannedFromPlatform` | user id, expires_at | Notifies the user. |

## Infrastructure

### Models
- `Report` — casts `target_type`, `status` int, `escalated_at` datetime; relations `reporter`, `reviewer`, `group`.
- `GroupBan` — composite PK `(group_id, blamed_user_id)`; cast `expires_at`; relations `blamedUser`, `moderator`, `group`; method `isActive()`.
- `PlatformBan` — table `platform_bans`; columns `banned_user_id`, `admin_id`, `reason`, `expires_at`.
