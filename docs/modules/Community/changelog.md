# Community — Changelog

Append-only. Newest entries first. Format: `## [YYYY-MM-DD] [TICKET] Title`.

## [2026-09-29] [FEAT] Versioned group rules — step 1 (table + model)

- **Correction to the entry below:** the separate `2026_09_29_000000_convert_group_rules_to_json_list` migration was deleted; `groups.rules` is now created as `json` directly in the initial migration. The project is still pre-release and the local DB gets rebuilt with `migrate:fresh --seed`, so a data-converting migration isn't needed.
- Decided how rule versioning (FR-MOD-011) is stored: a `group_rule_versions` table of **immutable** snapshots, each holding the whole list as JSON `[{text, example}]` — not one row per rule. Rules are only ever read as a whole list of one version, and immutability means every edit copies the whole list anyway, so per-rule rows would add JOINs and ordering for no benefit. A moderation action will cite `(rule_version_id, index)`, which stays valid because a snapshot never changes.
- Added an optional `example` per rule, to make a rule's intent less ambiguous.
- Built step 1 only: the table (in the initial migration), `GroupRuleVersion` model/factory, and `Group::ruleVersions()`/`currentRuleVersion()`. Nothing reads or writes versions yet — `groups.rules` is still what the group page shows. Next steps: seed versions and drop `groups.rules`; group page reads the current version (with examples); moderation actions cite a rule once moderation exists.

## [2026-09-29] [FEAT] Group rules are a list of strings

- `Group.rules` changed from a single `string(250)` to a JSON list of strings (`array` cast on the model), so each rule is its own item and the group page renders them as a numbered list instead of one run-on sentence.
- New migration `2026_09_29_000000_convert_group_rules_to_json_list` converts existing rows in place: each old string becomes a **one-item** list — deliberately not split on sentence boundaries, which would be guessing. Reversible (`down()` joins the items back into one string).
- `GroupFactory`/`GroupSeeder` now produce real lists. Seeded groups that already exist locally keep their one-item list, since the seeder skips existing groups.
- The old 250-character limit applied to the whole text; a per-rule limit and a max number of rules are left open until group creation (FR-COM-001) adds validation.

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
