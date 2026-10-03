<?php

namespace App\Http\Resources;

use App\Models\Post;
use App\Support\Concerns\RendersMarkdown;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * A post as one line of a short list (search suggestions): its title — or, for a post without
 * one, the start of its text — and where it lives. Expects the group loaded.
 *
 * @mixin Post
 */
class PostListItemResource extends JsonResource
{
    use RendersMarkdown;

    private const PREVIEW_LENGTH = 80;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'preview' => $this->title ? null : Str::limit($this->markdownToPlainText($this->article), self::PREVIEW_LENGTH),
            'group' => ['slug' => $this->group->slug, 'name' => $this->group->name],
        ];
    }
}
