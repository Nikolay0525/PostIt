# ====== Content BUSINESS LOGIC ======

## Purpose

`Content` owns:
- Posts published inside groups.
- Nested comment threads under posts.
- Votes on posts and comments (up/down).
- Images attached to content, including adult-image flag and moderation status.
- Feed and group post ordering (Newest / Top).
- *(0.1.1)* Comment ordering within a post: **Best** (time-decayed score) by default, **Top** (raw score) or **Controversial** (balance between opposing votes) as alternatives — a separate concern from post ordering, because a comment thread keeps growing under the reader's eyes and a purely count-based order buries every new, good comment under old ones that simply had more time to accumulate votes.
- *(0.1.2, corrected)* A **post's or comment's** controversy score — shown as a small badge next to its votes for both, not just comments — see *Key Flow* below. The **Controversial** comment-sort order named above remains unimplemented; only the score/badge itself ships today.
- *(0.1.3)* Casting, changing and removing a vote end-to-end (`POST /votes`), including who may vote and showing the viewer their own vote back so the pressed arrow stays highlighted.

**NOT here:**
- Deciding if a user may see a private group — `Community`.
- Handling reports, bans and deletions by moderators — `Moderation` (Content only stores the soft-delete flags).
- Karma/achievement calculations — `Engagement` / `Account` react to Content events.

## Entities

| Entity | Basic Fields | Description | Invariants |
|---|---|---|---|
| `Post` *(Aggregate Root, extends `BaseEntity`)* | id, group_id, user_id, title, article, slug, is_deleted, deleted_at | An article published in a group by a user. | - Belongs to exactly one group and one author.<br>- `title` optional, ≤ 100 characters.<br>- `article` required.<br>- `slug` required.<br>- Deletion is soft (`is_deleted`, `deleted_at`); a deleted post is not shown.<br>- Cannot be created by a banned user or in a group the user has no right to post in. |
| `Comment` *(extends `BaseEntity`)* | id, post_id, parent_id, user_id, text, is_deleted, deleted_at | A comment on a post or a reply to another comment. | - `text` required, ≤ 500 characters.<br>- `parent_id` null = top-level comment; otherwise the parent must belong to the same post.<br>- Soft-deleted comments stay in the tree and are rendered as "deleted" so replies keep their context. |
| `Vote` *(link)* | parent_id, user_id, parent_type, positive | One user's vote on one post or comment. | - PK `(parent_id, user_id)`: one vote per user per target.<br>- `parent_type`: 1 = Post, 2 = Comment.<br>- `positive` true = upvote, false = downvote.<br>- Changing a vote updates the same row.<br>- *(0.1.3)* Submitting the same direction again removes the row (toggle-off) instead of leaving a duplicate.<br>- *(0.1.3)* An author cannot vote on their own post or comment. |
| `Image` | id, uploader_id, owner_type, owner_id, file_name, file_extension, url, is_adult_image, moderation_status | An uploaded image attached to an owner (post, …). | - Has an uploader.<br>- Adult images are shown only to users with `show_adult_content` enabled and age ≥ 18.<br>- `moderation_status` defaults to 0 (not reviewed). |

## Key Flow — Reading and voting

- Anyone can read a post and its comments (public groups); writing a comment or voting requires login (guests see a "log in or sign up" prompt).
- Comments are loaded flat and assembled into a tree by `parent_id`.
- Score of a post is derived from its votes, computed server-side (`upvotes_count`/`downvotes_count` aggregates in `EloquentPostRepository`); **Top** sort orders by score, **Newest** by `created_at`.
- *(0.1.1)* Top-level comments default to **Best** order: `score / (age_in_hours + 2) ^ gravity` (Hacker-News-style time decay; `gravity` ≈ 1.5–1.8, exact value open), so a new comment with few votes can outrank an old one that has merely had longer to collect them. **Top** (raw `upvotes − downvotes`, no decay) stays available as an explicit alternative, so a purely popularity-ranked view is never lost.
- *(0.1.2, corrected)* The controversy indicator — for **both posts and comments** — is computed by the shared `ComputesControversy` trait (used by `PostResource`, `CommentResource` and `VoteController` alike), not by a separate comment-only service. It follows Reddit's own controversial-sort formula, `(upvotes + downvotes) ^ (min/max)`, rounded to 2 significant figures: total vote count is the base (so real engagement counts), and the ratio between the smaller and larger side is the exponent, so a lopsided vote collapses towards 1 no matter how large the total is, while an even split keeps the full magnitude. This is **not** a 0–100% balance ratio — a small, evenly-split item and a huge, evenly-split item show different numbers, which is intentional (a pure ratio would score a 5/5 tie the same as a 500/500 tie). It is `null` (hidden) below a minimum-votes-per-side threshold (currently 3, see tech notes) so a fresh, unvoted item never shows a score indistinguishable from a genuinely contested one. The rounding is deliberate, not cosmetic: an exact value, combined with the already-public net score (upvotes − downvotes), would let the exact vote split be reconstructed by algebra — the same reason raw vote counts are not shown separately (see *Key Flow — Reading and voting* above). *(The original design here specified a 0–100% value rounded to the nearest 10%; that design was superseded by the above during implementation and is corrected here, not carried forward.)*
- Users the viewer has blocked, and content of banned users, are hidden *(planned)*.
- *(0.1.3)* Voting is authenticated-only (a guest gets a 401 and the same "log in or sign up" prompt as commenting) and is a plain JSON `fetch()` call to `POST /votes`, deliberately outside the Inertia visit cycle: an Inertia visit would only return page one of a paginated list, discarding whatever the reader has already scrolled past via `<InfiniteScroll>`.
- *(0.1.3)* Every vote response (and every read of a post/comment) reports the viewer's own current vote (`true` = upvoted, `false` = downvoted, `null` = no vote), computed per-viewer, so the UI can keep the matching arrow highlighted after voting and after a page reload, not only right after the click.

**Boundary:** `Content` stores and orders content; visibility rules for private groups are asked from `Community`.

## Domain Policies

| Domain Policy | Description |
|---|---|
| `PostPolicy` | Create: authenticated, verified, allowed in group (member, or group public) and not banned. Delete: author or group moderator. *(0.1.3)* `vote()`: not the post's own author, and the post is not deleted. |
| `CommentPolicy` | Create: authenticated, verified, not banned in the group. Delete: author or moderator. *(0.1.3)* `vote()`: not the comment's own author, and the comment is not deleted. |

*(0.1.3)* There is no separate `VotePolicy` — voting authorization lives on the target's own policy (`PostPolicy::vote()` / `CommentPolicy::vote()`), invoked via `$this->authorize('vote', $target)` in `VoteController`, since "who may vote" only depends on the target, not on any vote-specific state.

## Domain Services

| Service | Operation |
|---|---|
| `PostService` | Create post (generates slug), soft-delete post, emits `PostCreated`. |
| `CommentService` | Add comment / reply, soft-delete, emits `CommentCreated`. |
| `VoteService` | *(0.1.3, implemented)* `castVote()`: creates a vote, changes its direction, or deletes it if the same direction is resubmitted (one vote per user per target); runs in a DB transaction and returns the updated `upvotes`/`downvotes` counts plus the viewer's resulting `viewer_vote` (`true`/`false`/`null`). Does not yet emit `VoteCast`. |
| `FeedService` | Build feed for a viewer / group with sort (Newest, Top) and pagination. |
| `CommentThreadService` *(0.1.1, planned — not built yet)* | Would build a post's top-level comment page ordered by Best, Top or Controversial; replies stay ordered oldest-first under their parent. *(0.1.2, corrected)* The controversy score itself does **not** wait on this service — it is already live today via the shared `ComputesControversy` trait, independent of comment ordering. |

## Domain Events *(planned)*

| Event | Carries | Notes |
|---|---|---|
| `PostCreated` | post id, author id, group id | Increments `posts_created`. |
| `CommentCreated` | comment id, post id, author id | Increments `comments_created`; may notify post author. |
| `VoteCast` | target id, target type, voter id, positive | Updates vote counters and karma of the target's author. Not yet emitted — `VoteService::castVote()` updates counts by re-querying rather than by event, so per-user karma from votes is still open (see tech notes). |

## Application Commands & Queries

**Commands:** create post, delete post, add comment, delete comment, *(0.1.3, implemented)* cast/change/remove vote (`VoteController::store`, `POST /votes`).
**Queries:** home feed (Trending for guests / Your feed for members), group feed, post with comment tree — *(0.1.3)* each now optionally scoped to a viewer to attach that viewer's own vote per item.

## Infrastructure

### Models
- `Post` — relations `group`, `author`, `comments`, `votes` (filtered by `parent_type = 1`).
- `Comment` — relations `post`, `author`, `parent`, `replies`, `votes` (filtered by `parent_type = 2`).
- `Vote` — composite PK `(parent_id, user_id)`; casts `parent_type` int, `positive` bool.

### Support
- *(0.1.2, corrected)* `App\Support\Concerns\ComputesControversy` — the single implementation of the controversy score, shared by `PostResource`, `CommentResource` and `VoteController`. Covers posts and comments identically; there is no per-module or comment-only variant.

### Repositories
- *(0.1.3)* `EloquentPostRepository`/`EloquentCommentRepository` accept an optional `?string $viewerId` on every read method (`findWithStats`, `paginateForGroup`, `paginateTrending`, `paginateThreadsForPost`) and, when given one, attach a `viewer_vote` column via a correlated scalar subquery (`addSelect`) scoped to that viewer — the same no-N+1 technique already used for the `withCount` vote aggregates, rather than a second query per item.
- *(0.1.3)* `EloquentVoteRepository` never calls `update()`/`delete()` on a fetched `Vote` instance, because the composite primary key means Eloquent cannot target the right row that way; `updateDirection()` and `delete()` instead build an explicit `where('parent_id', …)->where('parent_type', …)->where('user_id', …)` query.

### UI
- `Pages/Posts/Show.vue` (post + comment form + comment tree), `PostFeed`, `PostCard`, `CommentNode`, `VoteButtons`. Posts and comments are read from the server (`PostController`, `HomeController`, and the post list on `Groups/Show.vue`) via `PostResource`/`CommentResource`, paginated through `Inertia::scroll`.
- *(0.1.3)* `VoteButtons` is no longer a stub: it posts to `/votes` via `fetch()` (CSRF token read from the `XSRF-TOKEN` cookie and sent as `X-XSRF-TOKEN`, since this is a plain JSON call outside Inertia), and highlights whichever arrow matches `viewer_vote` — seeded from the page props on load and updated locally from the response after each click, so the highlight survives both a click and a full reload without waiting on a round-trip through `PostResource`. The comment form is still a visual stub — no write endpoint exists yet for commenting (tracked in *Tech debt*).
