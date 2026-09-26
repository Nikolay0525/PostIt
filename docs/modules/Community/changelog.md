# Community — Changelog

Append-only. Newest entries first. Format: `## [YYYY-MM-DD] [TICKET] Title`.

## [2026-09-26] [DOCS] Corrected stale dummy-data reference

- `members_count` on the group page has come from `withCount('members')` via `GroupController`/`GroupResource` for a while; the docs still called it dummy data. Corrected in the *UI* infrastructure note and removed the matching tech-debt item. The join/subscribe button is still a genuine local-only toggle — that tech-debt item stays.

## [2026-09-26] [DOCS] Guardian & Owner role model; group title/slug split

- Replaced the "creator assigns moderators" design with a community-elevated **Guardian** role (per-group `contribution_score`, threshold-gated candidate pool, random offer, opt-in, pseudonymous identity) and a separate, permanent **Owner** role (rules/title/topic, voluntary transfer, fallback moderation only while the group has no active Guardian).
- Guardian retention (`standing_score`) is decoupled from `contribution_score` on purpose: it tracks responsiveness to actual workload and appeal-verdict accuracy, not the group's opinion of the Guardian.
- Added non-binding community votes on rule/topic changes with a public Owner response, and a platform-admin backstop for a repeated pattern of appeals against the Owner.
- Added `Group.slug` (planned) separate from the existing `name`, which becomes the display title in any language.
- Superseded FR-COM-007; added FR-COM-009 … 016 (see requirements specification 0.1.1).

## [2026-09-21] [INIT] Initial module documentation

- Documented groups, membership, join requests and moderator roles.
- Proposed the `JoinRequestStatus` lifecycle.
