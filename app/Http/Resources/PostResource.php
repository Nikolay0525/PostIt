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

    /**
     * @return array{state: string, verdict?: array<string, mixed>|null}
     */
    private function moderationForAuthor(Request $request): array
    {
        if ($this->isHidden()) {
            return ['state' => 'pending'];
        }

        $verdict = $this->relationLoaded('moderationCase') ? $this->getRelation('moderationCase')?->verdict : null;

        return [
            'state' => 'removed',
            'verdict' => $verdict === null ? null : (new AuthorVerdictResource($verdict))->toArray($request),
        ];
    }

    public function toArray(Request $request): array
    {
        // Under a moderation case, or removed by its verdict: the text and the author never leave the server, so that a moderator
        // judging it cannot tell whose it is, and the page shows only that it is hidden.
        $hidden = $this->isConcealed();
        // Its own author is the exception: they see their text, and where it stands (`moderation`), but
        // nobody else does. Editing, voting and reporting stay closed to everyone (PostPolicy).
        $own = $hidden && $request->user()?->id === $this->user_id;
        $concealed = $hidden && ! $own;

        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'slug' => $this->slug,
            'is_hidden' => $hidden,
            // Taken down by a verdict rather than waiting for one; the page says so.
            'is_removed' => $this->isRemoved(),
            'title' => $concealed ? null : $this->title,
            // Raw Markdown source, kept for a future edit form; display uses the two below.
            'article' => $concealed ? '' : $this->article,
            'article_html' => $concealed ? '' : $this->markdownToHtml($this->article),
            'article_text' => $concealed ? '' : $this->markdownToPlainText($this->article),
            'created_at' => $this->created_at,
            // When the author last changed the text; null if never.
            'edited_at' => $concealed ? null : $this->edited_at,
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
                'id' => $concealed ? null : $this->author->id,
                'username' => $concealed ? 'Hidden' : $this->author->username,
                'avatar_url' => $concealed ? null : ImageService::url($this->author->avatar_url),
            ],
            // For the author only: waiting for the moderators' decision ('pending'), or taken down
            // ('removed'). The verdict is there where the page loaded it (PostController::show()).
            'moderation' => $own ? $this->moderationForAuthor($request) : null,
            'group' => [
                'id' => $this->group->id,
                'slug' => $this->group->slug,
                'name' => $this->group->name,
                'language_code' => $this->group->language_code,
            ],
        ];
    }
}
