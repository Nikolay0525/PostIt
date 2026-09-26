# Content — Changelog

Append-only. Newest entries first. Format: `## [YYYY-MM-DD] [TICKET] Title`.

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
