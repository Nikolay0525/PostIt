<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expects a post loaded with author, group and the upvotes_count / downvotes_count / comments_count
 * aggregates, which is what PostRepositoryInterface returns.
 *
 * @mixin \App\Models\Post
 */
class PostResource extends JsonResource
{
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
            'comments_count' => $this->comments_count,
            'author' => ['name' => $this->author->name],
            'group' => ['id' => $this->group->id, 'name' => $this->group->name],
        ];
    }
}
