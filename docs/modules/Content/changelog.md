# Content — Changelog

Append-only. Newest entries first. Format: `## [YYYY-MM-DD] [TICKET] Title`.

## [2026-09-26] [DOCS] Corrected controversy scope and formula

- The controversy indicator was documented as comment-only with a 0–100% value rounded to the nearest 10%; that was the original design, not what shipped. Corrected: the shared `App\Support\Concerns\ComputesControversy` trait computes the score for **both posts and comments** identically (used by `PostResource`, `CommentResource` and `VoteController`), using Reddit's own unbounded formula `(upvotes + downvotes) ^ (min/max)` rounded to 2 significant figures — not a percentage.
- Clarified that the **Controversial** comment-sort order (FR-CON-012) and the `CommentThreadService`/Best-order feature are still unimplemented — only the score/badge itself, and the plain **Newest**/**Top** sorts, actually exist today. The score does not depend on that service.
- FR-CON-013 updated to Done and reworded to match the implemented behaviour (rounded score, posts and comments, not a percentage).

## [2026-09-26] [DOCS] Vote casting, self-vote rule and viewer-vote highlighting

- Documented the `POST /votes` endpoint built today: `VoteController`/`VoteService` cast a new vote, change an existing one, or remove it if the same direction is resubmitted, one vote per user per target, inside a DB transaction.
- Documented that voting on your own post or comment is forbidden (`PostPolicy::vote()`/`CommentPolicy::vote()`), resolving the previously-open "may a user vote on their own content" question — no separate `VotePolicy` was introduced.
- Documented today's fix for an unclear-vote-state complaint ("не зрозуміло за що ти голосував"): every read of a post/comment and every vote response now reports the viewer's own vote (`viewer_vote`: true/false/null), computed server-side via a correlated subquery so it costs no extra query per list item; `VoteButtons` uses it to keep the correct arrow highlighted both right after clicking and after a full reload.
- Documented that the vote endpoint is a plain JSON `fetch()` call, not routed through Inertia, to avoid resetting `<InfiniteScroll>` pagination on post/comment lists.
- Noted the composite-primary-key workaround already required for `Vote` (`EloquentVoteRepository` mutates via explicit WHERE-scoped queries, never instance `update()`/`delete()`).
- Flagged that `VoteCast` is still not emitted (no karma reaction yet), and that `POST /votes`'s `throttle:60,1` middleware needs a working cache store while `.env` is set to `CACHE_STORE=redis` — unresolved, tracked as tech debt, not silently changed.
- Updated FR-CON-006 to Done in the requirements specification (0.1.3).
- Raised `phpunit.xml`'s `memory_limit` to 512M — unrelated to voting logic itself, but a pre-existing fragility (`Faker::realText()` in `GroupFactory`) that the added vote tests pushed over the default 128M limit.

## [2026-09-26] [DOCS] Comment controversy indicator and Controversial sort

- Added a third comment sort, **Controversial**, alongside Best and Top: `100 × (1 − |upvotes − downvotes| / (upvotes + downvotes))` — 100% at an even split, near 0% for a one-sided vote.
- Decided to show this to viewers as a rounded percentage (nearest 10%), gated by a minimum-votes-on-both-sides threshold, so a fresh unvoted comment and a genuinely contested one are never visually identical (both currently net to a score of 0).
- Rounding is deliberate, not cosmetic: an exact percentage next to the already-public net score would let the exact upvote/downvote split be reconstructed by algebra, which is exactly what showing raw counts separately was rejected for.
- The threshold is a fixed constant, not scaled to group size, to avoid pulling group population into the sort query; exact value left open pending real vote-volume data.
- Added FR-CON-013, extended FR-CON-012 (see requirements specification 0.1.2).
- 
## [2026-09-26] [DOCS] Corrected stale dummy-data references

- `resources/js/data/dummyPosts.js`, `dummyGroups.js`, `dummyComments.js` were deleted and the read paths (post page, home feed, group post list) moved to `PostController`/`HomeController` behind `PostResource`/`CommentResource` some time ago; the docs still described them as dummy-backed. Corrected in *Key Flow* and the *UI* infrastructure note.
- Replaced the resolved "replace dummy data" tech-debt item with the debt that actually remains: there is still no write endpoint for comments or votes.

## [2026-09-26] [DOCS] Best (time-decayed) comment ordering

- Decided that a post's top-level comments default to a time-decayed **Best** score instead of a raw vote count, so a good new comment is not permanently buried under older, heavily-voted ones; **Top** (no decay) stays available as an alternative.
- This applies to comment ordering only; post ordering (group page, feed) is unchanged.
- Added FR-CON-012 (see requirements specification 0.1.1).

## [2026-09-21] [INIT] Initial module documentation

- Documented posts, comments, votes and images with invariants.
- Documented the read/vote flow and planned services and events.
