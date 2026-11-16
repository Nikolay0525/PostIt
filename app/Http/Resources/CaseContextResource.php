<?php

namespace App\Http\Resources;

use App\Models\Comment;
use App\Models\Post;
use App\Support\Concerns\RendersMarkdown;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A case's context (ModerationContextService::forCase()) as the moderator, the log and the jury see
 * it: the words and who answered whom, by label. No author's id and no name leaves the server.
 *
 * A comment its author deleted keeps its place, but not its text. A hidden or removed one is shown:
 * those who see a context are the ones who judge content, and it is part of the conversation.
 */
class CaseContextResource extends JsonResource
{
    use RendersMarkdown;

    public function toArray(Request $request): array
    {
        ['post' => $post, 'ancestors' => $ancestors, 'comment' => $comment, 'replies' => $replies] = $this->resource;

        return [
            'post' => $post === null ? null : $this->post($post),
            // The comments the reported one answers, the top one first.
            'ancestors' => $ancestors->map(fn (Comment $c) => $this->comment($c))->values()->all(),
            // The reported comment; null in a case on a post.
            'comment' => $comment === null ? null : $this->comment($comment),
            'replies' => $replies->map(fn (Comment $c) => $this->comment($c))->values()->all(),
        ];
    }

    private function post(Post $post): array
    {
        return [
            'id' => $post->id,
            'is_deleted' => $post->is_deleted,
            'title' => $post->is_deleted ? null : $post->title,
            // article_html for the post unfolded; article_text for its start, folded.
            'article_html' => $post->is_deleted ? '' : $this->markdownToHtml($post->article),
            'article_text' => $post->is_deleted ? '' : $this->markdownToPlainText($post->article),
            'created_at' => $post->created_at,
            'label' => $this->label($post),
        ];
    }

    private function comment(Comment $comment): array
    {
        return [
            'id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'is_deleted' => $comment->is_deleted,
            'is_hidden' => $comment->isHidden(),
            'is_removed' => $comment->isRemoved(),
            'text' => $comment->is_deleted ? '' : $comment->text,
            'created_at' => $comment->created_at,
            'label' => $this->label($comment),
        ];
    }

    /**
     * @return array{kind: string, number: ?int} "author", or "participant" with its number
     */
    private function label(Model $item): array
    {
        [$kind, $number] = array_pad(explode(':', (string) $item->getAttribute('context_label')), 2, null);

        return ['kind' => $kind, 'number' => $number === null ? null : (int) $number];
    }
}
