# Content — Tech Notes

## Tech debt
- No write endpoint exists yet for commenting or voting: the comment form and `VoteButtons` are still visual stubs (`// Real voting comes with the backend.`). This is the actual remaining gap now that reading is server-backed — see `CommentService`/`VoteService` in *Domain Services*.
- `Vote` has an array `$primaryKey` (`parent_id`, `user_id`) — same composite-key limitation as in Community.
- `Vote.parent_type` (1 = Post, 2 = Comment) is a magic integer and `parent_id` has **no foreign key** (polymorphic). Introduce the shared `TargetType` enum and consider referential integrity checks in the Service.
- Post score is computed in the frontend helper `score()`; move to the server (aggregate query) before pagination is added.
- `posts.slug` is required but has no uniqueness rule or generator yet.
- Verify that an `Image` model exists: the `images` table is in the migrations, but the model was not among the reviewed files.
- Comment tree is built on the client (`buildCommentTree`); for big threads move to the server and paginate.

## Non-obvious decisions
- Posts and comments are soft-deleted with their own `is_deleted` / `deleted_at` fields instead of Laravel's `SoftDeletes`. Queries must filter `is_deleted = false` explicitly, or the model should be switched to `SoftDeletes`.
- Comment text is limited to 500 characters (DB and textarea `maxlength`).
- (0.1.2) The controversy percentage is always rounded (nearest 10%) before it reaches the client, on purpose — an exact value next to the already-public net score would let the exact upvote/downvote split be reconstructed algebraically. Do not "fix" this rounding as a display nicety; it is load-bearing for vote-split privacy.

## Edge cases
- A deleted comment with replies must stay in the tree (rendered as "deleted").
- Posts without a title are shown with the article preview as the link text.

## Open questions
- (0.1.1) Exact `gravity` constant for the Best-comment decay formula; needs tuning once there is real usage data, not guessed upfront.
- (0.1.1) Whether Best decay risks rewarding a low-effort new comment placed in an already-hot thread purely for being new; consider bounding how early a comment can rank without any votes at all.
- (0.1.2) Exact minimum-votes-per-side threshold before the controversy percentage is shown at all; started as a fixed constant (order of magnitude: single digits per side, e.g. 3, not 50-100 — the seeded dataset averages ~5-6 votes per item) rather than scaled to group size, and needs tuning once there is real vote-volume data.
