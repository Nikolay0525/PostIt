<?php

namespace App\Services;

use App\Enums\PostSort;
use App\Models\Post;
use App\Repositories\Contracts\PostRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

class PostService
{
    private const PER_PAGE = 20;

    private const TRENDING_DAYS = 7;

    private const SLUG_ATTEMPTS = 3;

    public function __construct(
        protected PostRepositoryInterface $postRepository
    ) {}

    /**
     * @throws ModelNotFoundException when the group has no such post, or it was deleted
     */
    public function getPostBySlug(string $groupId, string $slug, ?string $viewerId = null): Post
    {
        return $this->postRepository->findWithStatsBySlug($groupId, $slug, $viewerId)
            ?? throw (new ModelNotFoundException)->setModel(Post::class, [$slug]);
    }

    public function getGroupPosts(string $groupId, PostSort $sort = PostSort::Newest, ?string $viewerId = null): LengthAwarePaginator
    {
        return $this->postRepository->paginateForGroup($groupId, $sort, self::PER_PAGE, $viewerId);
    }

    public function getSubscribedPosts(string $userId): LengthAwarePaginator
    {
        return $this->postRepository->paginateForSubscriber($userId, self::PER_PAGE);
    }

    public function getFollowedAuthorsPosts(string $followerId): LengthAwarePaginator
    {
        return $this->postRepository->paginateForFollower($followerId, self::PER_PAGE);
    }

    public function getAuthorPosts(string $authorId, ?string $viewerId = null): LengthAwarePaginator
    {
        return $this->postRepository->paginateForAuthor($authorId, self::PER_PAGE, $viewerId);
    }

    public function getTrendingPosts(?string $viewerId = null): LengthAwarePaginator
    {
        return $this->postRepository->paginateTrending(self::TRENDING_DAYS, self::PER_PAGE, $viewerId);
    }

    // `(group_id, slug)` is unique, since the slug addresses the post in its URL. A clash of the
    // random suffix is practically impossible (36^6 per title), so rather than checking first we
    // let the unique index reject it and simply try another suffix.
    public function createPost(string $groupId, string $userId, ?string $title, string $article): Post
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return $this->postRepository->create($groupId, $userId, $title, $article, $this->generateSlug($title, $article));
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= self::SLUG_ATTEMPTS) {
                    throw $e;
                }
            }
        }
    }

    public function getRandomPostSlug(string $groupId): ?string
    {
        return $this->postRepository->randomSlugForGroup($groupId);
    }

    // The random suffix keeps two posts with the same title in one group apart without a lookup
    // query; the fallback to 'post' covers a title/article that slugifies to nothing, e.g. one
    // written entirely in emoji.
    private function generateSlug(?string $title, string $article): string
    {
        $source = $title ?: Str::words($article, 8, '');
        $base = $this->unicodeSlug($source) ?: 'post';

        return $base.'-'.Str::lower(Str::random(6));
    }

    // Unlike Str::slug(), which transliterates to ASCII and drops anything it can't map (empty
    // result for Hebrew/Arabic/CJK, a near-unreadable mess for Arabic), this keeps any Unicode
    // letter/number as-is and only replaces whitespace/punctuation with '-' — the same approach
    // Reddit uses for non-Latin post slugs. A URL segment isn't restricted to ASCII (RFC 3987):
    // the browser displays the native script directly, percent-encoding it as UTF-8 underneath,
    // and Laravel's router decodes it back automatically.
    private function unicodeSlug(string $title): string
    {
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower($title, 'UTF-8'));

        return trim($slug, '-');
    }
}
