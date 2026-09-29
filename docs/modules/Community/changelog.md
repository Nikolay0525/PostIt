# Community — Changelog

Append-only. Newest entries first. Format: `## [YYYY-MM-DD] [TICKET] Title`.

## [2026-09-29] [FEAT] Page URLs use slugs; service pages under `/-/`

- Groups are now addressed by slug: `/groups/home-cooking`, `/groups/home-cooking/random-post`; posts by group slug + post slug: `/groups/home-cooking/posts/борщ-з-пампушками-a1b2c3` (see `Content` changelog). Old `/groups/{uuid}` and `/posts/{uuid}` URLs are gone (404) — pre-release, no external links to preserve.
- **Service pages live under a `-` segment**: `/groups/-/create` (create group), `/groups/{slug}/-/create-post`. A group slug can never be a bare `-` (`SLUG_PATTERN` requires a letter/digit at both ends), so a service page can't collide with a group *whatever* it is named — including a group literally called `create`, which now works at `/groups/create`. Chosen over a list of reserved slugs (easy to forget one) and over `/groups/actions/create` (`actions` itself is a valid slug, so that only postpones the collision). Same idea as GitLab's `/-/` separator.
- The `{groupSlug}` route constraint is `GroupService::SLUG_ROUTE_PATTERN` — the same pattern the validation uses, so routing and validation can't drift apart.
- Background JSON endpoints keep the UUID (`POST`/`DELETE /groups/{id}/subscribe`): they never appear in the address bar.
- `GroupRepositoryInterface`: added `findBySlug()`; `findForGroupPage()` now takes the slug. `GroupService::getGroup($id)` → `getGroupBySlug($slug)`. `GroupResource` exposes `slug`.
- Added `SlugRoutingTest` (9 cases): group/post pages by slug, unknown slug and old UUID URL → 404, a group named `create` vs. `/groups/-/create`, a Cyrillic post slug through a percent-encoded URL, a post slug only resolving inside its own group, deleted post → 404, random-post and post-creation redirects to slug URLs, the per-group uniqueness of post slugs. Updated `GroupControllerTest`/`ViewerVoteTest` to the new URLs (and fixed `ViewerVoteTest`'s guest check, which ran after `actingAs()` and so was never actually a guest).
- Verified in a headless browser: post links on the group page carry the Cyrillic slug, clicking one opens the post, back link, sort and "I'm feeling lucky" all stay on slug URLs.

## [2026-09-29] [FEAT] Group creation — controller, routes and two-step page

- `GroupController::create()`/`store()` behind `GET /groups/create` and `POST /groups` (`auth`, `throttle:10,1`), both gated by `GroupPolicy::create()`. `store()` is a regular Inertia redirect to the new group, like `PostController::store()`. Entry point: "Create group" in the account menu.
- Added `LanguageRepositoryInterface`/`EloquentLanguageRepository` (`speakingLanguages()`) for the language picker, bound in `AppServiceProvider`. The page also receives the limits (slug length, rule count/lengths) from the backend constants, so the form's `maxlength`s can't drift from validation.
- `Pages/Groups/Create.vue` is a two-step form on **one page**: step 1 = slug, name, description, language, private toggle; step 2 = rules (text + optional example, add/remove, up to 15). Both steps are rendered side by side in a track that slides left/right (`.wizard*` classes); nothing is reloaded between steps and the whole form is submitted once at the end.
  - "Next" checks step 1 locally (required fields, slug pattern) before sliding. If the server still rejects a step-1 field — e.g. a slug taken meanwhile — the form slides back to step 1 so the error is visible.
  - The hidden step is `inert` (not tabbable/announced) and collapses to zero height after the slide, so a long rules list doesn't leave blank space under step 1. The slide respects "reduce motion"; a timer (not `transitionend`, which never fires with transitions disabled) ends it.
  - Focus moves into the step that just arrived; "+ Add rule" focuses the new rule. Completely empty rule rows are dropped on submit.
- Verified in a headless browser against a throwaway DB: empty step 1 is blocked with errors, slide to step 2 and back, rules with examples saved and shown on the new group page, taken slug slides back with the server error, no horizontal overflow at phone width.
- FR-COM-001 → Done (icon upload aside).
- Added `GroupControllerTest` (14 cases): create page for guest/unverified/verified (with languages and limits), successful creation (Owner, membership, first rule version, redirect, rules shown on the group page), slug trimming/lowercasing, private flag, optional rules, taken slug, slugs outside the Latin pattern or over 30 characters, required fields and unknown language, rule text/count/example limits, guest and unverified rejections. The limits are asserted through the same constants the code uses.

## [2026-09-29] [REFACTOR] Group language is a code

- `groups.group_language_id` → `groups.language_code` (FK to `speaking_languages.code`, restrict on delete) — see `SharedKernel` changelog. `StoreGroupRequest` now validates `language_code` (must exist) instead of a UUID `group_language_id`; `GroupService::createGroup()`/`GroupRepositoryInterface::create()` take `$languageCode`.

## [2026-09-29] [FEAT] Group creation — policy and form request

- `GroupPolicy::create()`: any verified user. Platform-wide bans (`platform_bans`) aren't enforced by this or any other policy yet — a general gap, not specific to groups.
- `StoreGroupRequest`: name ≤ 50, description ≤ 250, slug (required, ≤ 30, `GroupService::SLUG_PATTERN`, unique), `group_language_id` must exist, `is_private` boolean, `rules` optional list of `{text, example?}`. Like `StorePostRequest`, `authorize()` is shape-only; the controller will call the policy.
- The slug is trimmed and lowercased before validation, so `" Retro-Gaming "` becomes `retro-gaming` instead of being rejected for casing; anything else outside the pattern (Cyrillic, `a--b`, leading hyphen) is still rejected.
- **Provisional rule limits** (the "set with FR-COM-001" decision from earlier today): up to 15 rules, text ≤ 100, example ≤ 300 characters — 15 rules and a 100-character rule title match Reddit's own limits. Easy to change; they're constants on the request.

## [2026-09-29] [DOCS] How a member actually becomes a Guardian

- Recorded the wiring behind the 0.1.1 Guardian design as three separate stages: `VoteCast` → `contribution_score`/pool; a **scheduled** random draw → `GuardianOffer`; acceptance → `GuardianshipService::acceptOffer()` → `addModerator(Guardian)`. Points never grant the role directly.
- Decided the offer is made by a scheduled job, not when a member crosses the threshold: offering on crossing would reward whoever crosses first (the popularity race the random draw is meant to prevent) and would offer the role even to groups that don't need more Guardians.
- Added the planned `GuardianOffer` entity — the design needed a place to keep an offer's expiry and outcome, which also makes the open "re-offer after a decline?" question answerable later.
- Guardianship data gets its own `GuardianshipRepositoryInterface`; `GroupRepositoryInterface::addModerator()` stays the only way a role row is written, with three intended callers (create group → Owner, transfer → Owner, accept offer → Guardian).
- Noted the dependency: none of this can start until `VoteService` emits `VoteCast`.

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
