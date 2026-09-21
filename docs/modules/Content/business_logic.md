# ====== Content BUSINESS LOGIC ======

## Purpose

`Content` owns:
- Posts published inside groups.
- Nested comment threads under posts.
- Votes on posts and comments (up/down).
- Images attached to content, including adult-image flag and moderation status.
- Feed construction and ordering (Newest / Top).

**NOT here:**
- Deciding if a user may see a private group — `Community`.
- Handling reports, bans and deletions by moderators — `Moderation` (Content only stores the soft-delete flags).
- Karma/achievement calculations — `Engagement` / `Account` react to Content events.

## Entities

| Entity | Basic Fields | Description | Invariants |
|---|---|---|---|
| `Post` *(Aggregate Root, extends `BaseEntity`)* | id, group_id, user_id, title, article, slug, is_deleted, deleted_at | An article published in a group by a user. | - Belongs to exactly one group and one author.<br>- `title` optional, ≤ 100 characters.<br>- `article` required.<br>- `slug` required.<br>- Deletion is soft (`is_deleted`, `deleted_at`); a deleted post is not shown.<br>- Cannot be created by a banned user or in a group the user has no right to post in. |
| `Comment` *(extends `BaseEntity`)* | id, post_id, parent_id, user_id, text, is_deleted, deleted_at | A comment on a post or a reply to another comment. | - `text` required, ≤ 500 characters.<br>- `parent_id` null = top-level comment; otherwise the parent must belong to the same post.<br>- Soft-deleted comments stay in the tree and are rendered as "deleted" so replies keep their context. |
| `Vote` *(link)* | parent_id, user_id, parent_type, positive | One user's vote on one post or comment. | - PK `(parent_id, user_id)`: one vote per user per target.<br>- `parent_type`: 1 = Post, 2 = Comment.<br>- `positive` true = upvote, false = downvote.<br>- Changing a vote updates the same row. |
| `Image` | id, uploader_id, owner_type, owner_id, file_name, file_extension, url, is_adult_image, moderation_status | An uploaded image attached to an owner (post, …). | - Has an uploader.<br>- Adult images are shown only to users with `show_adult_content` enabled and age ≥ 18.<br>- `moderation_status` defaults to 0 (not reviewed). |

## Key Flow — Reading and voting

- Anyone can read a post and its comments (public groups); writing a comment or voting requires login (guests see a "log in or sign up" prompt).
- Comments are loaded flat and assembled into a tree by `parent_id`.
- Score of a post is derived from its votes (currently computed by a frontend dummy helper); **Top** sort orders by score, **Newest** by `created_at`.
- Users the viewer has blocked, and content of banned users, are hidden *(planned)*.

**Boundary:** `Content` stores and orders content; visibility rules for private groups are asked from `Community`.

## Domain Policies *(planned)*

| Domain Policy | Description |
|---|---|
| `PostPolicy` | Create: authenticated, verified, allowed in group (member, or group public) and not banned. Delete: author or group moderator. |
| `CommentPolicy` | Create: authenticated, verified, not banned in the group. Delete: author or moderator. |
| `VotePolicy` | One vote per target; whether a user may vote on their own content is an open decision. |

## Domain Services *(planned)*

| Service | Operation |
|---|---|
| `PostService` | Create post (generates slug), soft-delete post, emits `PostCreated`. |
| `CommentService` | Add comment / reply, soft-delete, emits `CommentCreated`. |
| `VoteService` | Cast, change or remove a vote; emits `VoteCast`. |
| `FeedService` | Build feed for a viewer / group with sort (Newest, Top) and pagination. |

## Domain Events *(planned)*

| Event | Carries | Notes |
|---|---|---|
| `PostCreated` | post id, author id, group id | Increments `posts_created`. |
| `CommentCreated` | comment id, post id, author id | Increments `comments_created`; may notify post author. |
| `VoteCast` | target id, target type, voter id, positive | Updates vote counters and karma of the target's author. |

## Application Commands & Queries *(planned)*

**Commands:** create post, delete post, add comment, delete comment, cast/remove vote.
**Queries:** home feed (Trending for guests / Your feed for members), group feed, post with comment tree.

## Infrastructure

### Models
- `Post` — relations `group`, `author`, `comments`, `votes` (filtered by `parent_type = 1`).
- `Comment` — relations `post`, `author`, `parent`, `replies`, `votes` (filtered by `parent_type = 2`).
- `Vote` — composite PK `(parent_id, user_id)`; casts `parent_type` int, `positive` bool.

### UI
- `Pages/Posts/Show.vue` (post + comment form + comment tree), `PostFeed`, `PostCard`, `CommentNode`, `VoteButtons`. Currently backed by `dummyPosts` / `dummyComments`.
