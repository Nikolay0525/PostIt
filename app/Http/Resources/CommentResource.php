<?php

namespace App\Http\Resources;

use App\Models\Comment;
use App\Support\Concerns\ComputesControversy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expects a comment loaded with author and the upvotes_count / downvotes_count aggregates.
 * Nested replies are included when the `replies` relation is loaded.
 *
 * The text and author of a deleted comment never leave the server: the comment stays in the
 * thread so its replies keep their place, but only as an empty "Removed" placeholder.
 *
 * @mixin Comment
 */
class CommentResource extends JsonResource
{
    use ComputesControversy;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'is_deleted' => $this->is_deleted,
            'text' => $this->is_deleted ? '' : $this->text,
            'author' => ['name' => $this->is_deleted ? 'Removed' : $this->author->name],
            'created_at' => $this->created_at,
            'upvotes' => $this->upvotes_count,
            'downvotes' => $this->downvotes_count,
            'controversy' => $this->controversyScore($this->upvotes_count, $this->downvotes_count),
            // true = viewer upvoted, false = downvoted, null = no vote (or a guest).
            'viewer_vote' => $this->viewer_vote === null ? null : (bool) $this->viewer_vote,
            'replies' => self::collection($this->whenLoaded('replies')),
        ];
    }
}
