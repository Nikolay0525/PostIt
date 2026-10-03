# Content — Changelog

Append-only. Newest entries first. Format: `## [2026-10-03] [FEAT] An author's posts — service layer

- `PostRepositoryInterface::paginateForAuthor()` / `PostService::getAuthorPosts()`: an author's posts, newest first, 20 per page, for the upcoming profile page.
- Visibility: posts in public groups for everyone; a post in a private group only for viewers who are members of **that** group (guests and non-members don't see it). Decided over "hide private-group posts from everyone": a member already sees these posts in the group itself, so hiding them on the profile would only be inconsistent.
- Added `AuthorPostsTest` (4 cases: own posts only and order, private group hidden from guest/non-member, member sees only their private group, deleted posts excluded).

## [YYYY-MM-DD] [TICKET] Title`.

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
