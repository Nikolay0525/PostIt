# Content — Changelog

Append-only. Newest entries first. Format: `## [YYYY-MM-DD] [TICKET] Title`.

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
