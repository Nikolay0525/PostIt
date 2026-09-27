# Community — Changelog

Append-only. Newest entries first. Format: `## [YYYY-MM-DD] [TICKET] Title`.

## [2026-09-28] [DOCS] Group slug will be typed by the creator, not generated

- Confirmed FR-COM-009's design: a group's slug is entered **manually**, in Latin script, at group-creation time — not auto-generated from `name` the way a post's slug now is (`Content` 0.1.7). Reasoning: a group's slug is a stable, chosen identity (closer to a subreddit name than to a post's incidental one), and `name` can be in any language/script, so an auto-generated slug would either mangle it (transliteration) or need the same non-Latin-preserving approach just built for posts — deliberately not reused here, since "readable in the post's own language" and "a deliberately chosen, stable, Latin identifier" are different goals.
- Group creation itself (FR-COM-001) remains fully Planned and out of scope — this is a decision recorded ahead of building it, not new code. `Group.slug` still isn't in the schema.
- Added `MembershipControllerTest` (10 cases covering `POST`/`DELETE /groups/{id}/subscribe`: happy path, idempotent re-subscribe, unsubscribe, no-op unsubscribe, guest/unverified/banned/private-group rejections, expired-ban allowance, missing-group 404) — the endpoints from the entry below only had throwaway manual verification until now.

## [2026-09-27] [FEAT] Group subscription (repository → service → policy → controller/route)

- Built `GroupRepositoryInterface::subscribe()`/`unsubscribe()` (`EloquentGroupRepository`, working around `UserGroupSubscription`'s composite PK the same way `Vote` already does), the new `MembershipService`, `GroupPolicy::subscribe()`, and `MembershipController` behind `POST`/`DELETE /groups/{id}/subscribe` — closes FR-COM-003.
- Kept `MembershipService` as its own class rather than folding it into `GroupService`, matching how this doc already separated the two as distinct planned services.
- `GroupPolicy::subscribe()` always rejects a private group — its membership still only comes from an approved `GroupJoinRequest` (`JoinRequestService`), which isn't built. "Request to join" on a private group stays a local-only toggle.
- Like `Content`'s vote/comment endpoints, this is a plain JSON `fetch()` (`postJson`/`deleteJson`) rather than an Inertia visit, so subscribing doesn't reset the group page's `<InfiniteScroll>`-paginated post list back to page one.
- Corrected two stale tech-debt lines found while touching this area: `JoinRequestStatus`/`GroupModeratorRole` enums already existed (the debt entry calling them "raw integers" was outdated), and the join/subscribe tech-debt item was half-resolved (public groups only) rather than fully open.

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
