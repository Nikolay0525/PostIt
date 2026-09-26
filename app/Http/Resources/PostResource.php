<?php

namespace App\Http\Resources;

use App\Models\Post;
use App\Support\Concerns\ComputesControversy;
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

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'title' => $this->title,
            'article' => $this->article,
            'created_at' => $this->created_at,
            'upvotes' => $this->upvotes_count,
            'downvotes' => $this->downvotes_count,
            'controversy' => $this->controversyScore($this->upvotes_count, $this->downvotes_count),
            'comments_count' => $this->comments_count,
            // true = viewer upvoted, false = downvoted, null = no vote (or a guest).
            'viewer_vote' => $this->viewer_vote === null ? null : (bool) $this->viewer_vote,
            'author' => ['name' => $this->author->name],
            'group' => ['id' => $this->group->id, 'name' => $this->group->name],
        ];
    }
}
