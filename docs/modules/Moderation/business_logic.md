# ====== Moderation BUSINESS LOGIC ======

## Purpose

`Moderation` owns:
- Reports: users flag content or users for review, with a review workflow and escalation.
- Group bans: a Guardian or Owner restricts a user inside one group, temporarily or permanently.
- Platform bans: an administrator restricts a user across the whole platform.
- *(0.1.1)* Appeals: a user challenges a Guardian's own moderation action, reviewed by an independent cross-group jury.
- *(0.1.1)* The pattern-of-appeals review that backstops an Owner's accountability (Owner is not covered by the jury process — see below).

**NOT here:**
- Who holds the Guardian/Owner role and why (eligibility, retention, decay) — `Community`.
- Storage and soft-delete flags of posts and comments — `Content` (Moderation triggers deletion through Content services).
- User accounts — `Account`.

## Entities

| Entity | Basic Fields | Description | Invariants |
|---|---|---|---|
| `Report` *(Aggregate Root, extends `BaseEntity`)* | id, reporter_id, target_type, target_id, group_id, text, status, reviewed_by, resolution_note, escalated_at | A user's report about a target (post, comment, …). | - `text` ≤ 100 characters.<br>- `status` defaults to 0 (open).<br>- `group_id` nullable: set when the target belongs to a group, so group moderators can review it; `null` reports go to platform administrators.<br>- `resolution_note` ≤ 500 characters, required when the report is closed.<br>- `escalated_at` is set when a group-level report is passed to the platform level.<br>- Reporter is deleted ⇒ report cascades; reviewer deleted ⇒ `reviewed_by` becomes null. |
| `GroupBan` *(link)* | group_id, blamed_user_id, moderator_id, reason, expires_at, timestamps | A user is restricted in one group. | - PK `(group_id, blamed_user_id)`: at most one ban record per user per group.<br>- `reason` required, ≤ 250 characters.<br>- `expires_at = null` ⇒ permanent.<br>- `isActive()` is true while `expires_at` is null or in the future. |
| `PlatformBan` *(extends `BaseEntity`)* | id, banned_user_id, admin_id, reason, expires_at, timestamps | A user is restricted across the platform. | - `reason` required, ≤ 250 characters.<br>- `expires_at = null` ⇒ permanent.<br>- Created only by a platform administrator. |
| `ModerationAppeal` *(0.1.1, planned — not yet in schema)* | id, action_reference, appellant_id, case_summary, panel_size, verdict, resolved_at | A user's challenge to one specific Guardian action. | - Created only by the user directly affected by the action, not by onlookers.<br>- `case_summary` is assembled neutrally: the rule cited (at its version at the time, `Group.rules` history), the Guardian's stated reasoning, the appellant's statement — no framing that primes guilt or innocence.<br>- `panel_size` is 3 for a standard action, 5 for a severe one (e.g. a platform-visible ban).<br>- `verdict`: `upheld` \| `overturned` \| `insufficient_evidence`. |
| `AppealVote` *(0.1.1, planned — not yet in schema)* | appeal_id, juror_id (pseudonymous), decision | One juror's independent vote on one appeal. | - Jurors are drawn at random from active Guardians **platform-wide, excluding the group the action took place in**.<br>- A juror never sees another juror's vote before the case resolves (independent, blind voting — deliberately not a discussion thread).<br>- A non-unanimous result does not resolve by simple majority: the appeal is automatically escalated to a larger panel instead. |

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

## Key Flow — Appealing a Guardian action *(0.1.1, designed; not yet in schema/code)*

Ordinary Guardian actions are never put to a direct crowd vote — see `Community` tech notes for why. Instead:

- The affected user opens the specific action and files an appeal (not a general complaint against the Guardian).
- A neutral `case_summary` is assembled: the group rule cited, **as it read at the time of the action** (FR-MOD-011 — rules are versioned by `Community`), the Guardian's stated reasoning, and the appellant's statement. No side is framed as the accuser.
- A random panel of active Guardians is drawn platform-wide, deliberately excluding Guardians of the same group (avoids local social bias) and filtered to a minimum activity/reliability bar.
- Each juror votes independently — `upheld`, `overturned`, or `insufficient_evidence` — without seeing the others' votes.
- Unanimous ⇒ resolved. Non-unanimous ⇒ automatically escalated to a larger panel (`escalated_at`-style pattern, reused from the `Report` lifecycle) rather than settled by a thin majority.
- Resolution: the content decision is enforced (restored if overturned, kept if upheld); the Guardian's private accuracy record and each juror's private reliability record are updated. Neither record is shown publicly; the reliability record is used to gradually exclude careless jurors from future panels.

**Boundary:** `Moderation` runs the appeal/jury mechanism; `Community` owns what that verdict does to the Guardian's `standing_score`.

## Key Flow — Owner accountability backstop *(0.1.1, designed; not yet in schema/code)*

The Owner does not decay and is not reviewed by the Guardian appeal/jury process (that process is for Guardians, drawn from the Guardian pool). Instead: a *recurring pattern* of appeals against the same Owner's own moderation actions (not a single appeal) is flagged for a platform administrator, who may issue a visible warning and, if the pattern continues, strip the Owner's rights and transfer the group to another member (`Community` `OwnershipTransferred`, `reason = admin_ordered`). This mirrors, ahead of time, the last-resort intervention real communities eventually need for abandoned or abusive founders.

## Domain Policies *(planned)*

| Domain Policy | Description |
|---|---|
| `ReportPolicy` | Any verified user may report; only the Guardian(s)/Owner of `group_id` (or admins) may review; only admins review escalated reports. |
| `BanPolicy` | A Guardian or Owner may ban only within their group; only admins issue platform bans; nobody bans an administrator. |
| `AppealPolicy` *(0.1.1)* | Only the user directly affected by an action may appeal it. Jury eligibility: active Guardians above a reliability/activity bar, excluding the appealed action's own group. |
| `OwnerConductPolicy` *(0.1.1)* | Only a platform administrator reviews an Owner-conduct pattern and orders a warning or ownership transfer. |

## Domain Services *(planned)*

| Service | Operation |
|---|---|
| `ReportService` | Submit, review, resolve, dismiss, escalate; emits `ReportResolved`. |
| `GroupBanService` | Issue and lift group bans; emits `UserBannedFromGroup`. |
| `PlatformBanService` | Issue and lift platform bans; emits `UserBannedFromPlatform`. |
| `AppealService` *(0.1.1)* | File an appeal, assemble the neutral case file, draw the random jury, collect blind votes, resolve or escalate, update accuracy/reliability records. |
| `OwnerConductReviewService` *(0.1.1)* | Detect a qualifying pattern of appeals against one Owner, notify a platform administrator, record the warning, trigger a forced transfer. |

## Domain Events *(planned)*

| Event | Carries | Notes |
|---|---|---|
| `ReportResolved` | report id, decision | Notifies reporter. |
| `UserBannedFromGroup` | group id, user id, expires_at | Notifies the user; no reason text in the payload. |
| `UserBannedFromPlatform` | user id, expires_at | Notifies the user. |
| `AppealFiled` | appeal id, action reference | *(0.1.1)* Triggers jury selection. |
| `AppealEscalated` | appeal id, new panel size | *(0.1.1)* Non-unanimous verdict. |
| `AppealResolved` | appeal id, verdict | *(0.1.1)* Notifies both sides; updates `Community` standing/accuracy records. |
| `OwnerConductWarned` | group id, owner id | *(0.1.1)* Visible warning before a forced transfer is even considered. |

## Infrastructure

### Models
- `Report` — casts `target_type`, `status` int, `escalated_at` datetime; relations `reporter`, `reviewer`, `group`.
- `GroupBan` — composite PK `(group_id, blamed_user_id)`; cast `expires_at`; relations `blamedUser`, `moderator`, `group`; method `isActive()`.
- `PlatformBan` — table `platform_bans`; columns `banned_user_id`, `admin_id`, `reason`, `expires_at`.
- `ModerationAppeal`, `AppealVote` — 0.1.1, planned; no migration yet.
