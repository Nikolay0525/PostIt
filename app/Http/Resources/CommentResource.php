<?php

namespace App\Http\Resources;

use App\Models\Comment;
use App\Http\Resources\Concerns\DescribesModerationForAuthor;
use App\Services\ImageService;
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
    use DescribesModerationForAuthor;

    public function toArray(Request $request): array
    {
        // A hidden comment (under a moderation case) is like a deleted one for the page: it stays in
        // the thread so its replies keep their place, but only as a placeholder.
        $hidden = $this->isConcealed();
        // Its own author is the exception to hiding: they see their text and where it stands
        // (`moderation`), nobody else does. A comment its author deleted themselves is gone for all.
        $own = $hidden && ! $this->is_deleted && $request->user()?->id === $this->user_id;
        $concealed = $this->is_deleted || ($hidden && ! $own);

        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'parent_id' => $this->parent_id,
            'is_deleted' => $this->is_deleted,
            'is_hidden' => $hidden,
            'is_removed' => $this->isRemoved(),
            'text' => $concealed ? '' : $this->text,
            'author' => [
                'id' => $concealed ? null : $this->author->id,
                'username' => $this->is_deleted ? 'Removed' : ($concealed ? 'Hidden' : $this->author->username),
                'avatar_url' => $concealed ? null : ImageService::url($this->author->avatar_url),
            ],
            'created_at' => $this->created_at,
            // When the author last changed the text; null if never.
            'edited_at' => $concealed ? null : $this->edited_at,
            // Offered to the author only; CommentPolicy::update() decides again on the server.
            'can_edit' => ! $this->is_deleted && ! $hidden && $request->user()?->id === $this->user_id,
            'upvotes' => $this->upvotes_count,
            'downvotes' => $this->downvotes_count,
            'controversy' => $this->controversyScore($this->upvotes_count, $this->downvotes_count),
            // true = viewer upvoted, false = downvoted, null = no vote (or a guest).
            'viewer_vote' => $this->viewer_vote === null ? null : (bool) $this->viewer_vote,
            // For the author only: waiting for the moderators' decision, or taken down, and why.
            'moderation' => $own ? $this->moderationForAuthor($request) : null,
            'replies' => self::collection($this->whenLoaded('replies')),
        ];
    }
}
