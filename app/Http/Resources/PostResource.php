<?php

namespace App\Http\Resources;

use App\Models\Post;
use App\Services\ImageService;
use App\Support\Concerns\ComputesControversy;
use App\Support\Concerns\RendersMarkdown;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expects a post loaded with author, group and the upvotes_count / downvotes_count / comments_count
 * aggregates, which is what PostRepositoryInterface returns.
 *
 * @mixin Post
 */
class PostResource extends JsonResource
{
    use ComputesControversy;
    use RendersMarkdown;

    public function toArray(Request $request): array
    {
        // Under a moderation case: the text and the author never leave the server, so that a moderator
        // judging it cannot tell whose it is, and the page shows only that it is hidden.
        $hidden = $this->isHidden();

        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'slug' => $this->slug,
            'is_hidden' => $hidden,
            'title' => $hidden ? null : $this->title,
            // Raw Markdown source, kept for a future edit form; display uses the two below.
            'article' => $hidden ? '' : $this->article,
            'article_html' => $hidden ? '' : $this->markdownToHtml($this->article),
            'article_text' => $hidden ? '' : $this->markdownToPlainText($this->article),
            'created_at' => $this->created_at,
            // When the author last changed the text; null if never.
            'edited_at' => $hidden ? null : $this->edited_at,
            // Offered to the author only; PostPolicy::update() decides again on the server.
            'can_edit' => ! $hidden && $request->user()?->id === $this->user_id,
            'upvotes' => $this->upvotes_count,
            'downvotes' => $this->downvotes_count,
            'controversy' => $this->controversyScore($this->upvotes_count, $this->downvotes_count),
            'comments_count' => $this->comments_count,
            'shares_count' => $this->shares_count,
            // true = viewer upvoted, false = downvoted, null = no vote (or a guest).
            'viewer_vote' => $this->viewer_vote === null ? null : (bool) $this->viewer_vote,
            'author' => [
                'id' => $hidden ? null : $this->author->id,
                'username' => $hidden ? 'Hidden' : $this->author->username,
                'avatar_url' => $hidden ? null : ImageService::url($this->author->avatar_url),
            ],
            'group' => [
                'id' => $this->group->id,
                'slug' => $this->group->slug,
                'name' => $this->group->name,
                'language_code' => $this->group->language_code,
            ],
        ];
    }
}
