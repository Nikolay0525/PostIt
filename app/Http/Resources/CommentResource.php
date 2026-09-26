<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ComputesControversy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expects a comment loaded with author and the upvotes_count / downvotes_count aggregates.
 * Nested replies are included when the `replies` relation is loaded.
 *
 * The text and author of a deleted comment never leave the server: the comment stays in the
 * thread so its replies keep their place, but only as an empty "Removed" placeholder.
 *
 * @mixin \App\Models\Comment
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
            'replies' => self::collection($this->whenLoaded('replies')),
        ];
    }
}
