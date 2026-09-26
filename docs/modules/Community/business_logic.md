# ====== Community BUSINESS LOGIC ======

## Purpose

`Community` owns:
- Groups: title, slug, description, rules, language, icon, public/private visibility.
- Membership: subscribing to public groups (`UserGroupSubscription`).
- Join requests for private groups (`GroupJoinRequest`) and their decision.
- Who holds a moderation role inside a group and why: the **Guardian** role (community-elevated, accountable, decaying) and the **Owner** role (founder/transferee, permanent) — see §*Guardian & Owner roles* below. `GroupModerator` is the shared record of "this user holds this role in this group".

**NOT here:**
- Posts and comments inside a group — `Content`.
- Bans, reports and their resolution — `Moderation`.
- Who the user is / authentication — `Account`.

## Entities

| Entity | Basic Fields | Description | Invariants |
|---|---|---|---|
| `Group` *(Aggregate Root, extends `BaseEntity`)* | id, name, slug, description, rules, group_language_id, icon_url, is_private | A topic community. | - `name` is the display **title**, in any language, used for search; ≤ 50 characters.<br>- `slug` *(0.1.1, planned — not yet in schema; the group is currently routed by UUID)* is a separate Latin-script identifier used only in the URL, so search and discovery never depend on transliteration.<br>- `description` ≤ 250, `rules` ≤ 250 characters, both **versioned by date** (0.1.1) so a past moderation action is judged against the rules in force when it happened (see `Moderation` FR-MOD-011).<br>- Has exactly one language (`group_language_id`).<br>- Private groups hide posts from non-members.<br>- Deleting a group cascades to memberships, moderators, posts, bans. |
| `UserGroupSubscription` *(link)* | user_id, group_id, created_at | User is a member/subscriber of a group. | - Unique (user, group).<br>- No `updated_at`.<br>- Public groups: created immediately on subscribe. Private groups: created only after an approved join request. |
| `GroupJoinRequest` *(link)* | user_id, group_id, status, timestamps | Request to join a private group. | - Unique (user, group) — one request per pair.<br>- `status` defaults to pending (0).<br>- Only a Guardian or the Owner of the group decides the request. |
| `GroupModerator` *(link)* | user_id, group_id, role, timestamps | Record of who holds the Guardian or Owner role in a group. | - Unique (user, group).<br>- `role`: `Owner` or `Guardian` (0.1.1 renames/repurposes the old generic "moderator role" code — see §*Guardian & Owner roles*).<br>- A Guardian/Owner is also a member of the group.<br>- Exactly one `Owner` per group; zero or more `Guardian`s. |
| `GuardianCandidacy` *(0.1.1, planned — not yet in schema)* | user_id, group_id, contribution_score, is_in_pool | A member's per-group standing towards the Guardian role. | - `contribution_score` is earned from upvoted contributions **inside this group**, separate from platform-wide karma (`Account`).<br>- Crossing a platform-defined threshold puts the member in the group's candidate pool; it does not by itself grant the role. |
| `GuardianStanding` *(0.1.1, planned — not yet in schema)* | user_id, group_id, pseudonym, standing_score, last_warned_at | An active Guardian's accountable state. | - `pseudonym` is shown instead of the member's ordinary identity (FR-COM-013).<br>- `standing_score` is separate from `contribution_score`: it reflects responsiveness to the group's *actual* pending workload and the Guardian's accuracy record from appeal verdicts (`Moderation` FR-MOD-010), not popularity.<br>- Does not decay while there is no pending work.<br>- Evaluated periodically, not continuously; a low score triggers a warning with a grace period (FR-COM-012) before automatic step-down. |

## Status Lifecycle — `GroupJoinRequest.status` *(proposed enum `JoinRequestStatus`; DB column exists, values to be confirmed)*

- A new request starts as `PENDING` (0).

| From | Self-initiated | Moderator-initiated |
|---|---|---|
| `PENDING` | → withdrawn (row removed) | → `APPROVED`, `REJECTED` |
| `APPROVED` | — (terminal; membership created) | — |
| `REJECTED` | → new request allowed after cool-down *(open decision)* | — |

## Guardian & Owner Roles *(0.1.1, designed; not yet in schema/code)*

Two roles hold moderation power in a group, and neither may appoint, remove or override the other:

- **Owner** — the group's founder or voluntary transferee. Keeper of the group's *idea*: title, description, rules and topic, plus the group's Guardian-related parameters (target Guardian ratio, response-time window) within bounds the platform sets. Permanent — does **not** decay. Moderates content directly only while the group currently has no active Guardian (a live condition, re-evaluated continuously, not a one-time switch); once a Guardian is active, the Owner steps back from content moderation. May transfer ownership to another member at will.
- **Guardian** — a member elevated by the community to keep the group's day-to-day *safety*. Entry, retention and accountability are deliberately decoupled from each other and from the Owner:
  - **Entry:** a member's per-group `contribution_score` (§Entities) crossing a threshold puts them in the group's candidate pool. The platform then **randomly** offers the role to a pool member — never to the highest-scoring member outright, to avoid turning the role into a popularity/karma race. The member may accept or decline; declining has no penalty.
  - **Identity:** on acceptance, the Guardian is shown to others only by a persistent pseudonym (`GuardianStanding.pseudonym`), decoupled from their ordinary profile, so unpopular-but-correct decisions do not spill over into their normal reputation.
  - **Retention:** kept separate from `contribution_score` on purpose, so moderating instead of posting is never penalised. `standing_score` reflects (a) responsiveness relative to the group's *actual* pending workload — no pending reports means no decay — and (b) the Guardian's long-run accuracy record from appeal verdicts (`Moderation`). It does **not** depend on the group's opinion of the Guardian, because that opinion is exactly what a Guardian sometimes has to act against.
  - **Accountability:** an individual moderation action is never put to a direct crowd vote (that would let the people just sanctioned out-vote their own sanction). It can only be challenged through the appeal/jury process owned by `Moderation`.
  - **Ending the role:** evaluated periodically. A low `standing_score` triggers a visible warning with a grace period before the role ends automatically; a Guardian may also step down voluntarily at any time.
- **Community votes:** members may hold a non-binding vote on a proposed rule or topic change. The Owner is never bound by the result, but publishes a public accept/reject statement with reasoning; the outcome and the Owner's response stay visible in the group's history, so prospective members can judge the Owner's track record before joining.
- **Owner accountability backstop:** since the Owner does not decay and is not reviewed by the Guardian appeal process, a *pattern* of appeals against the Owner's own moderation actions (not a single appeal) is escalated to platform administrators, who may warn the Owner and, if the pattern continues, strip and transfer ownership to another member (`Moderation` FR-MOD-012).

**Open (see `tech_notes.md`):** exact `contribution_score` threshold and `standing_score` decay/warning parameters; whether a declined Guardian offer can be re-offered; whether a returned long-absent Owner automatically reclaims ownership.

## Key Flow — Joining a group

- Guest opens the group page → can read public groups; sees a "log in or sign up" prompt when trying to join.
- Public group: button **Subscribe** → membership created.
- Private group: button **Request to join** → `GroupJoinRequest` created (`PENDING`) → moderator approves → membership created; posts stay hidden until then.
- Banned users (see `Moderation`) cannot join or post in the group.

**Boundary:** `Community` decides *who is a member/moderator*; `Content` and `Moderation` ask `Community` before allowing actions.

## Domain Policies *(planned)*

| Domain Policy | Description |
|---|---|
| `GroupPolicy` (Laravel) | Who can view a group's posts (public: anyone; private: members and an active Guardian/Owner), who can decide join requests (Guardian or Owner). |
| `OwnerPolicy` *(0.1.1)* | Only the Owner edits rules/description/title and the Guardian-related parameters, within platform-set bounds; only the Owner initiates a voluntary transfer. |
| `GuardianEligibilityPolicy` *(0.1.1)* | Who enters the candidate pool (`contribution_score` ≥ threshold) and who is eligible to be drawn; excludes users already active as Guardian in that group. |

## Domain Services *(planned)*

| Service | Operation |
|---|---|
| `GroupService` | Create/update group (title, slug, description, rules — versioned); creator becomes Owner. |
| `MembershipService` | Subscribe / unsubscribe; updates `user_counters.groups_connected`. |
| `JoinRequestService` | Create request, approve, reject; on approve creates membership. |
| `GuardianshipService` *(0.1.1)* | Maintain the candidate pool, make random offers, record accept/decline, evaluate `standing_score` periodically, warn, step down (auto or voluntary). |
| `OwnershipService` *(0.1.1)* | Voluntary transfer; grants/revokes the Owner's fallback moderation ability based on whether the group currently has an active Guardian; executes a platform-admin-ordered transfer (`Moderation` FR-MOD-012). |

## Domain Events *(planned)*

| Event | Carries | Notes |
|---|---|---|
| `GroupJoined` | user id, group id | Updates counters/achievements. |
| `JoinRequestDecided` | user id, group id, decision | Triggers a notification to the requester. |
| `GuardianOffered` / `GuardianAppointed` | user id, group id | *(0.1.1)* Sent when a candidate is drawn and when they accept. |
| `GuardianWarned` / `GuardianStoodDown` | user id, group id, reason | *(0.1.1)* `reason`: `decayed` \| `voluntary`. Notifies the Guardian; `GuardianStoodDown` also re-evaluates the Owner's fallback moderation ability. |
| `OwnershipTransferred` | group id, from user id, to user id, reason | *(0.1.1)* `reason`: `voluntary` \| `admin_ordered`. |
| `GroupRuleVoteRecorded` | group id, vote id, owner decision | *(0.1.1)* Keeps the public vote/response history. |

## Application Commands & Queries *(planned)*

**Commands:** subscribe, unsubscribe, request join, decide join request, assign/remove moderator, update group.
**Queries:** group page (details + `members_count` via `withCount('members')`), list groups, list join requests for a group.

## Infrastructure

### Models
- `Group` — relations: `language`, `posts`, `bans`, `reports`, `members`, `moderators`, `joinRequests`; cast `is_private` boolean. `slug` and rule/description versioning are 0.1.1 additions, not yet in the schema.
- `UserGroupSubscription`, `GroupJoinRequest`, `GroupModerator` — composite PK `(user_id, group_id)`; casts for `status`/`role` integers. `GroupModerator.role` (`Owner`/`Guardian`) is reused, unchanged in shape, for the 0.1.1 role model.
- `GuardianCandidacy`, `GuardianStanding` — 0.1.1, planned; no migration yet.

### UI
- `Pages/Groups/Show.vue`: group header (avatar initial, members count, private badge), rules, join/subscribe button, Newest/Top sort tabs, private-group notice. Currently uses `dummyGroups`.
