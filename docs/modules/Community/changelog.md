# Community — Changelog

Append-only. Newest entries first. Format: `## [YYYY-MM-DD] [TICKET] Title`.

## [2026-09-29] [FEAT] Group creation — repository and service

- Added `groups.slug` to the initial migration (unique, ≤ 30): the slug decision from 0.1.7 is now in the schema, since group creation needs it as an input and adding it later would mean changing these signatures again. Routing still uses the UUID — switching URLs to the slug is a separate step. Seeded groups got hand-picked slugs (`laravel`, `hiking`, `home-cooking`, `retro-gaming`, `book-club`); the factory generates random valid ones.
- `GroupRepositoryInterface`: `slugExists()`, `create()`, `addRuleVersion()`, `addModerator()` (composite PK → query-builder `insert()`, like `subscribe()`). `GroupRuleVersion` and `GroupModerator` live in this repository because they only exist under a group (repository per aggregate).
- `GroupService::createGroup()` validates the slug (`SLUG_PATTERN`, `SLUG_MAX_LENGTH` — public constants so the upcoming form request can reuse them instead of duplicating the regex) and wraps group + first rule version + Owner + membership in one DB transaction. Verified the rollback: a failure on the Owner insert leaves no group behind.
- The service-level slug checks are an invariant guard, not the user-facing validation: the form request (next step) is expected to catch a bad/taken slug first and return a 422, and the unique index catches a race between two simultaneous creations.
- Icon upload is left out (`icon_url` stays null) — there is no image upload yet.

## [2026-09-29] [FEAT] Versioned group rules — step 3 (examples on the group page)

- The group page shows a rule's `example`, when it has one, on its own line under the rule as smaller muted italic text (`Example: …`, `.group-rule-example`) — always visible rather than collapsed, since an example is a single line and hiding it behind a click would defeat its purpose of removing ambiguity. A rule without an example renders as before.
- Remaining: moderation actions citing `(rule_version_id, index)` — deferred until moderation actions exist; editing rules (creating a new version) comes with group management (FR-COM-014).

## [2026-09-29] [FEAT] Versioned group rules — step 2 (rules live only in versions)

- Removed `groups.rules` from the initial migration and the `Group` model: a group's rules now exist only in `group_rule_versions`, so there is one source of truth instead of a column that could drift from the newest version.
- `GroupSeeder` creates each seeded group's first rule version, with examples on the rules that benefit from one; `GroupFactory` no longer produces rules (a factory group has no rule version unless a test adds one).
- Renamed `GroupRepositoryInterface::findWithMembersCount()` → `findForGroupPage()`, since it now also eager-loads `currentRuleVersion`; `GroupResource` returns `rules` as that version's `[{text, example}]` list, or `[]` for a group without one.
- The group page shows only each rule's `text` for now — rendering `example` is step 3.

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
