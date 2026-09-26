# Content — Tech Notes

## Tech debt
- No write endpoint exists yet for commenting: the comment form is still a visual stub. *(0.1.3)* Voting's own gap is closed — `POST /votes` (`VoteController`/`VoteService`) now handles cast, change and remove.
- `Vote` has an array `$primaryKey` (`parent_id`, `user_id`) — same composite-key limitation as in Community. *(0.1.3)* `EloquentVoteRepository::updateDirection()`/`delete()` work around it with explicit WHERE-scoped queries instead of instance `update()`/`delete()`; keep that pattern for any future mutation on `Vote`.
- `Vote.parent_type` (1 = Post, 2 = Comment) is a magic integer and `parent_id` has **no foreign key** (polymorphic). Introduce the shared `TargetType` enum and consider referential integrity checks in the Service.
- Post score is computed in the frontend helper `score()`; move to the server (aggregate query) before pagination is added.
- `posts.slug` is required but has no uniqueness rule or generator yet.
- Verify that an `Image` model exists: the `images` table is in the migrations, but the model was not among the reviewed files.
- Comment tree is built on the client (`buildCommentTree`); for big threads move to the server and paginate.
- *(0.1.3)* `VoteService::castVote()` does not emit `VoteCast`; per-user karma from votes (`FR-CON-006`/`Engagement`/`Account`) still has nothing to react to.
- *(0.1.3)* `POST /votes` is rate-limited with `throttle:60,1`, which needs a working cache store. `.env` currently has `CACHE_STORE=redis` (a deliberate earlier choice — do not change without asking); if Redis is not running locally, voting will fail even though the feature code itself is correct. Confirm Redis is up, or ask before switching the store.

## Non-obvious decisions
- Posts and comments are soft-deleted with their own `is_deleted` / `deleted_at` fields instead of Laravel's `SoftDeletes`. Queries must filter `is_deleted = false` explicitly, or the model should be switched to `SoftDeletes`.
- Comment text is limited to 500 characters (DB and textarea `maxlength`).
- (0.1.2, corrected) The controversy **score** — not a percentage — covers both posts and comments via the shared `ComputesControversy` trait: `(upvotes + downvotes) ^ (min/max)`, rounded to 2 significant figures, unbounded (a bigger contested item reads as a bigger number; it is not a 0–100 balance ratio). It is always rounded before it reaches the client, on purpose — an exact value next to the already-public net score would let the exact upvote/downvote split be reconstructed algebraically. Do not "fix" this rounding as a display nicety; it is load-bearing for vote-split privacy. (Earlier docs described a 0–100% value rounded to the nearest 10%; that design was never built — the trait above is what actually shipped.)
- *(0.1.3)* Voting on your own content is forbidden (`PostPolicy::vote()`/`CommentPolicy::vote()`), resolving what used to be an open decision — self-votes would trivially inflate score and controversy.
- *(0.1.3)* The vote endpoint is a plain `fetch()`/JSON call, not an Inertia form/link, specifically so it never triggers a full Inertia visit — that would reset `<InfiniteScroll>` back to page one. Do not "simplify" this to `router.post()` or `useForm()` without re-checking that constraint.
- *(0.1.3)* `viewer_vote` is computed via a correlated subquery (`addSelect`) rather than loading the viewer's votes separately and matching them in PHP, so it stays a single query per page regardless of list length — same reasoning as the existing `withCount` vote aggregates.

## Edge cases
- A deleted comment with replies must stay in the tree (rendered as "deleted").
- Posts without a title are shown with the article preview as the link text.

## Open questions
- (0.1.1) Exact `gravity` constant for the Best-comment decay formula; needs tuning once there is real usage data, not guessed upfront.
- (0.1.1) Whether Best decay risks rewarding a low-effort new comment placed in an already-hot thread purely for being new; consider bounding how early a comment can rank without any votes at all.
- (0.1.2) Exact minimum-votes-per-side threshold before the controversy score is shown at all; implemented as `ComputesControversy::MIN_VOTES_PER_SIDE = 3` (single digits per side, not 50-100 — the seeded dataset averages ~5-6 votes per item) rather than scaled to group size, and needs tuning once there is real vote-volume data.
