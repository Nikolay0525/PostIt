# Content — Changelog

Append-only. Newest entries first. Format: `## [YYYY-MM-DD] [TICKET] Title`.

## [2026-10-04] [FEAT] Feed filters, shared by every tab: only new, only my languages, period

- `HomeController` reads `new`, `langs` and `period` (`FeedPeriod`: `all` default, `month`, `week`) into an `App\Support\FeedFilters` and returns them as the `filters` prop; `new`/`langs` are forced off for guests whatever the URL says, an unknown period falls back to `all`.
- One `EloquentPostRepository::applyFilters()` used by `paginateRecommended()`, `paginateForSubscriber()` and `paginateForFollower()`: *only new* excludes posts in the user's `post_views` or `votes`; *only my languages* is a strict `WHERE EXISTS` (same SQL as Recommended's language priority, shared as `IN_USER_LANGUAGE`); the period adds a `created_at` lower bound. Filters only narrow — each feed keeps its order. `paginateTrending()` takes the period for guests. Own posts and joined groups stay out of Recommended regardless.
- Decided (same day): the filters are shared by every tab and kept when switching — first built for Recommended only, which felt unintuitive.
- `Home.vue`: a Filters button on the tab line (every tab except a guest's Following, with a count of active filters) opening a panel with two checkboxes (members) and the period radios; a change reloads only the posts. With filters on, an empty list says to loosen them. The tab underline moved to the row so it runs under the button.
- Added `FeedFiltersTest` (11 cases, including both Following lists).

## [2026-10-03] [FIX] Private-group posts closed to outsiders everywhere (FR-COM-006 → Done)

- Before, only lists hid a private group's posts; by direct URL or id an outsider could read a post, get one from "I'm feeling lucky", comment, vote and share. `CommentPolicy` even assumed visibility had been checked elsewhere — it hadn't.
- New `PostPolicy::view()` (guests included via `?User`) on `ChecksGroupVisibility::canSeePostsOf()`. Checked in `PostController::show`/`share`, and first thing in `PostPolicy::vote`, `CommentPolicy::create`/`vote`; `PostController::random` uses `GroupService::canViewPosts()`, like the group page. **The private group itself stays open to everyone** (name, description, rules, join request) — only its posts are closed; `GroupPolicy` deliberately has no visibility rule. Outsiders get 404 rather than 403, so the post's existence isn't revealed; their failed visits aren't recorded as views.
- Added `PrivateGroupAccessTest` (7 cases: the private group page stays open with posts hidden; post page, random post, comment, vote on post and comment, share; public group stays open).

## [2026-10-03] [FEAT] Post views and copy-link sharing

- New tables `post_views` and `post_shares` (migration `2026_10_03_210000_create_post_views_and_post_shares_tables`), composite key `(user_id, post_id)`, written with `insertOrIgnore` — repeats are no-ops and can't race into duplicate-key errors.
- `PostController::show` records a member's view (`PostService::recordView()`); guests aren't recorded.
- `POST /posts/{id}/share` (`PostController::share`, JSON `{shares_count}`, 404 for a missing/deleted post) counts a member's share once. `shares_count` added to the post aggregates (`Post::sharedBy()`), `PostResource` and so to every feed.
- `ShareButton.vue` in the post footer: copies the absolute post URL for everyone (falls back to a prompt without clipboard access), shows "Link copied" for 2 s, and for members updates the count.
- Added `PostViewsAndSharesTest` (6 cases).

## [2026-10-03] [FEAT] Recommendations v1.2: freshness buckets instead of a 7-day cut-off

- The Recommended tab (guests' trending and members' recommendations) no longer drops posts older than 7 days. It orders by freshness bucket — this week, this month, older (`PostService::FRESHNESS_DAYS = [7, 30]`, `EloquentPostRepository::orderByFreshness()`, a portable `CASE WHEN`) — then by score; for members the language priority still comes first. The list now only runs out when the site does.
- `paginateTrending()`/`paginateRecommended()` take the bucket bounds instead of `$days`. Tab hints and the empty text no longer say "this week".
- `RecommendationsTest`: old posts follow fresh ones instead of being excluded; guest ordering covered (6 cases).

## [2026-10-03] [FEAT] Recommendations v1.1: new to you, your languages first

- `PostRepositoryInterface::paginateRecommended()` / `PostService::getRecommendedPosts()`: for a member, the Recommended tab skips their own posts and groups they're already in, and orders posts in groups of a language they speak first (an `EXISTS` on `user_speaking_languages`, same on MySQL and sqlite), then by score. Guests still get plain trending.
- Decided: languages are a **priority, not a filter** — a strict filter would empty the tab on a small site (and in dev, where seed groups get random languages). One condition to change later.
- `PostService::getTrendingPosts()` removed (replaced). The hint under the tab now differs for guests and members; an empty list says to check back or look at Following.
- Added `RecommendationsTest` (5 cases).

## [2026-10-03] [FEAT] Home feed tabs: Recommended / Following (Groups, People)

- `HomeController` reads `feed` (`FeedType`: `recommended` default, `following`) and `source` (`FeedSource`: `groups` default, `people`) from the query; unknown values fall back to the defaults. A guest on Following gets `posts: null` and a log-in prompt. Decided: Recommended is the default for everyone, members included — previously members with group subscriptions landed on their feed.
- New `PostRepositoryInterface::paginateForFollower()` / `PostService::getFollowedAuthorsPosts()` for the People list; the private-group rule is shared with `paginateForAuthor()` (`visibleTo()`).
- `Home.vue`: underlined tabs plus a Groups/People switch, switching via a partial reload that resets the post list (like a group's sort). `PostFeed` got an optional title and an `empty` text.
- Removed `GroupService`/`GroupRepositoryInterface::hasSubscriptions()` — only the old default-feed choice used it. `FeedType::Subscriptions`/`Trending` replaced.
- Added `HomeControllerTest` (7 cases). Recommendations are still trending; the roadmap is in business logic.

## [2026-10-03] [FEAT] Image storage — service layer

- New `ImageRepositoryInterface` (`create`, `findForOwner`, `delete`) and `ImageService`: stores an uploaded file on the `public` disk under a random UUID name (extension guessed from the contents, not the client's file name) and records it in `images` with uploader and owner. If the row can't be saved, the file is deleted; deleting an image removes both.
- `images.url` holds the **path on the disk** (e.g. `avatars/{uuid}.png`), not an absolute URL, so changing `APP_URL` or the disk leaves no stale links; `ImageService::url()` builds the link when rendering.
- Files are stored as uploaded, without resizing: the GD extension isn't enabled. Validation of type/size is the caller's job (the upcoming avatar endpoint).
- First user: avatars (see `Account`). Post images (FR-CON-010) and group icons can reuse it.

## [2026-10-03] [FEAT] An author's posts — service layer

- `PostRepositoryInterface::paginateForAuthor()` / `PostService::getAuthorPosts()`: an author's posts, newest first, 20 per page, for the upcoming profile page.
- Visibility: posts in public groups for everyone; a post in a private group only for viewers who are members of **that** group (guests and non-members don't see it). Decided over "hide private-group posts from everyone": a member already sees these posts in the group itself, so hiding them on the profile would only be inconsistent.
- Added `AuthorPostsTest` (4 cases: own posts only and order, private group hidden from guest/non-member, member sees only their private group, deleted posts excluded).

## [2026-09-29] [FEAT] Post URLs use the post slug

- A post's URL is now `/groups/{group slug}/posts/{post slug}` instead of `/posts/{uuid}`; the post slug keeps its random 6-char suffix (decided: no "-2, -3" numbering), e.g. `/groups/home-cooking/posts/борщ-з-пампушками-a1b2c3`.
- Added `unique(group_id, slug)` to `posts` (initial migration): the slug now identifies the post in its group, so it must be unique there. `PostService::createPost()` retries with a new suffix if the index ever rejects a clash, instead of querying first.
- `PostRepositoryInterface`: `findWithStats($id)` → `findWithStatsBySlug($groupId, $slug)`, `randomIdForGroup()` → `randomSlugForGroup()`; `PostService::getPost()` → `getPostBySlug()`, `getRandomPostId()` → `getRandomPostSlug()`. `PostResource` exposes `slug` and `group.slug`; all post links in `PostCard`/`Posts/Show`/`Posts/Create` build URLs from them.
- The create-post page moved to `/groups/{slug}/-/create-post` (service pages under `/-/`, see `Community` changelog).

## [2026-09-28] [FIX] Post slugs keep their own script instead of transliterating

- Replaced `Str::slug()` in `PostService::generateSlug()` with a hand-written `unicodeSlug()`: verified `Str::slug()` returns an empty string for Hebrew/Chinese/Japanese/Korean titles, and a near-unreadable transliteration for Arabic. The replacement keeps any Unicode letter/number as-is and only turns whitespace/punctuation into `-` — the same technique Reddit uses for non-Latin post slugs, relying on URL paths supporting non-ASCII text (RFC 3987) rather than trying to force everything into `[a-z0-9-]`.
- Decided this is specifically a **post** behaviour (auto-generated, keeps the post's own language) — a future group slug is a deliberately different decision, see `Community` changelog.

## [2026-09-27] [FEAT] Create-post page and Markdown formatting

- Wired the "+ Create post" button on the group page to a real form: `GET /groups/{id}/posts/create` (`PostController::create()`, gated by the same `PostPolicy::create()` as the submit) renders `Pages/Posts/Create.vue` — a title field and an article `<textarea>` with Bold/Italic toolbar buttons that wrap the current selection in `**`/`*`.
- **Decided the article's storage format is Markdown**, not plain text (the prior state) or a rich-text/WYSIWYG document: no client editor library, no schema change, a toolbar button is just "insert `**` around the selection." Trade-off: no live WYSIWYG feedback — the author sees `**bold**` while typing, not bold text.
- Added `App\Support\Concerns\RendersMarkdown` (`Str::markdown()`, `html_input: strip`, `allow_unsafe_links: false`) and wired it into `PostResource`: `article` (raw Markdown, kept for a future edit form), `article_html` (rendered, used via `v-html` for the full post view), `article_text` (plain, tags stripped, used for the feed preview/excerpt so a preview never shows raw `**`/`*` syntax).
- The HTML-stripping and unsafe-link options are a deliberate XSS defense, not defaults left untouched — this is the only place user-authored text becomes markup rendered with `v-html`.
- Corrected two stale doc lines found while touching this area: the comment form was documented as "still a visual stub" (it hasn't been since comments were wired up earlier) and FR-CON-002 (slug generation) was still marked Planned despite `PostService::generateSlug()` already existing.
- Verified live end-to-end (headless-browser login → group page → create-post form → Bold/Italic toolbar → submit → rendered `<strong>`/`<em>` on the resulting post), not just by reading the code.

## [2026-09-27] [FEAT] Post creation (repository → service → policy → controller/route)

- Built `PostRepositoryInterface::create()`/`EloquentPostRepository`, `PostService::createPost()` (generates the slug — closes FR-CON-002, previously Planned), `PostPolicy::create()`, `StorePostRequest`, and `PostController::store()` behind `POST /posts` — the same repo → interface → service → policy → FormRequest → controller sequence used for comments.
- Unlike `VoteController`/`CommentController`, `store()` is a plain Inertia redirect to the new post, not a JSON endpoint — there's no already-scrolled list on the "create post" page to preserve, and the natural next step is a full navigation to the new post anyway.
- Extracted `ChecksGroupBans` (`app/Policies/Concerns`), shared by `PostPolicy` and `CommentPolicy`, instead of duplicating the same group-ban query in both.
- **[POLICY]** Decided that `PostPolicy::create()` requires group membership always, public or private — superseding the originally-planned "member, or group public" rule from 0.1.3 (a public group's readability was letting anyone post in it without joining, which turned out not to be the intended behaviour). See business logic 0.1.4.
- Added `PostSort::Controversy` and a portable (`+ - * / abs nullif`, no `POW`/`GREATEST`) proxy for `ComputesControversy`'s formula in `EloquentPostRepository::orderByControversy()`, so post sorting works identically on MySQL (prod) and sqlite (tests) without reproducing the exact displayed score.
- Added `PostRepositoryInterface::randomIdForGroup()` / `PostController::random()` behind `GET /groups/{id}/random-post` for an "I'm feeling lucky" action — public like `posts.show`, and, like `posts.show`, does not yet check private-group visibility (FR-COM-006 is still Partial; this isn't a new gap).
- Root cause found for a real report of "nothing on the page reacts to clicks at all": a stale `public/hot` file (left behind by a `npm run dev` that was no longer running) was pointing every page at an unreachable Vite dev server, so the whole Vue/Inertia app silently failed to mount. Removed; unrelated to any of the above, but discovered while investigating the group-page buttons.

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
