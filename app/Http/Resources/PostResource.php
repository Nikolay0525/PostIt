<?php

namespace App\Http\Resources;

use App\Models\Post;
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
        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'slug' => $this->slug,
            'title' => $this->title,
            // Raw Markdown source, kept for a future edit form; display uses the two below.
            'article' => $this->article,
            'article_html' => $this->markdownToHtml($this->article),
            'article_text' => $this->markdownToPlainText($this->article),
            'created_at' => $this->created_at,
            'upvotes' => $this->upvotes_count,
            'downvotes' => $this->downvotes_count,
            'controversy' => $this->controversyScore($this->upvotes_count, $this->downvotes_count),
            'comments_count' => $this->comments_count,
            // true = viewer upvoted, false = downvoted, null = no vote (or a guest).
            'viewer_vote' => $this->viewer_vote === null ? null : (bool) $this->viewer_vote,
            'author' => ['id' => $this->author->id, 'username' => $this->author->username],
            'group' => ['id' => $this->group->id, 'slug' => $this->group->slug, 'name' => $this->group->name],
        ];
    }
}
