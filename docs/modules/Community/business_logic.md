# ====== Community BUSINESS LOGIC ======

## Purpose

`Community` owns:
- Groups: name, description, rules, language, icon, public/private visibility.
- Membership: subscribing to public groups (`UserGroupSubscription`).
- Join requests for private groups (`GroupJoinRequest`) and their decision.
- Moderator assignment and roles inside a group (`GroupModerator`).

**NOT here:**
- Posts and comments inside a group — `Content`.
- Bans, reports and their resolution — `Moderation`.
- Who the user is / authentication — `Account`.

## Entities

| Entity | Basic Fields | Description | Invariants |
|---|---|---|---|
| `Group` *(Aggregate Root, extends `BaseEntity`)* | id, name, description, rules, group_language_id, icon_url, is_private | A topic community. | - `name` ≤ 50, `description` ≤ 250, `rules` ≤ 250 characters.<br>- Has exactly one language (`group_language_id`).<br>- Private groups hide posts from non-members.<br>- Deleting a group cascades to memberships, moderators, posts, bans. |
| `UserGroupSubscription` *(link)* | user_id, group_id, created_at | User is a member/subscriber of a group. | - Unique (user, group).<br>- No `updated_at`.<br>- Public groups: created immediately on subscribe. Private groups: created only after an approved join request. |
| `GroupJoinRequest` *(link)* | user_id, group_id, status, timestamps | Request to join a private group. | - Unique (user, group) — one request per pair.<br>- `status` defaults to pending (0).<br>- Only moderators of the group decide the request. |
| `GroupModerator` *(link)* | user_id, group_id, role, timestamps | Moderator assignment with a role. | - Unique (user, group).<br>- `role` is an integer role code, default 0.<br>- A moderator should also be a member of the group. |

## Status Lifecycle — `GroupJoinRequest.status` *(proposed enum `JoinRequestStatus`; DB column exists, values to be confirmed)*

- A new request starts as `PENDING` (0).

| From | Self-initiated | Moderator-initiated |
|---|---|---|
| `PENDING` | → withdrawn (row removed) | → `APPROVED`, `REJECTED` |
| `APPROVED` | — (terminal; membership created) | — |
| `REJECTED` | → new request allowed after cool-down *(open decision)* | — |

## Key Flow — Joining a group

- Guest opens the group page → can read public groups; sees a "log in or sign up" prompt when trying to join.
- Public group: button **Subscribe** → membership created.
- Private group: button **Request to join** → `GroupJoinRequest` created (`PENDING`) → moderator approves → membership created; posts stay hidden until then.
- Banned users (see `Moderation`) cannot join or post in the group.

**Boundary:** `Community` decides *who is a member/moderator*; `Content` and `Moderation` ask `Community` before allowing actions.

## Domain Policies *(planned)*

| Domain Policy | Description |
|---|---|
| `GroupPolicy` (Laravel) | Who can view a group's posts (public: anyone; private: members and moderators), who can edit group settings (moderators with sufficient role), who can decide join requests. |

## Domain Services *(planned)*

| Service | Operation |
|---|---|
| `GroupService` | Create/update group; enforces field limits and moderator bootstrap (creator becomes moderator). |
| `MembershipService` | Subscribe / unsubscribe; updates `user_counters.groups_connected`. |
| `JoinRequestService` | Create request, approve, reject; on approve creates membership. |

## Domain Events *(planned)*

| Event | Carries | Notes |
|---|---|---|
| `GroupJoined` | user id, group id | Updates counters/achievements. |
| `JoinRequestDecided` | user id, group id, decision | Triggers a notification to the requester. |

## Application Commands & Queries *(planned)*

**Commands:** subscribe, unsubscribe, request join, decide join request, assign/remove moderator, update group.
**Queries:** group page (details + `members_count` via `withCount('members')`), list groups, list join requests for a group.

## Infrastructure

### Models
- `Group` — relations: `language`, `posts`, `bans`, `reports`, `members`, `moderators`, `joinRequests`; cast `is_private` boolean.
- `UserGroupSubscription`, `GroupJoinRequest`, `GroupModerator` — composite PK `(user_id, group_id)`; casts for `status`/`role` integers.

### UI
- `Pages/Groups/Show.vue`: group header (avatar initial, members count, private badge), rules, join/subscribe button, Newest/Top sort tabs, private-group notice. Currently uses `dummyGroups`.
