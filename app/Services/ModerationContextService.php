<?php

namespace App\Services;

use App\Enums\ReportTargetType;
use App\Models\Comment;
use App\Models\ModerationCase;
use App\Models\Post;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * The conversation a case's content stands in, so that it is judged in it rather than torn out of it:
 * for a comment, the post it is under, the comments it answers up the thread and the replies to it;
 * for a post, the post. Only that, never the whole thread.
 *
 * Everyone in it is given a label instead of a name (`context_label`): "author" for every piece by the
 * person reported, "participant:N" for the others, numbered as they first appear. The models keep
 * their authors' ids for that; CaseContextResource sends none of it to the page.
 */
class ModerationContextService
{
    // Enough to follow a conversation; a thread deeper or busier than this is cut, nearest kept.
    public const MAX_ANCESTORS = 20;

    public const MAX_REPLIES = 20;

    public function __construct(
        protected PostRepositoryInterface $postRepository,
        protected CommentRepositoryInterface $commentRepository
    ) {}

    /**
     * @return array{post: ?Post, ancestors: Collection<int, Comment>, comment: ?Comment, replies: Collection<int, Comment>}
     */
    public function forCase(ModerationCase $case): array
    {
        $context = ['post' => null, 'ancestors' => new Collection, 'comment' => null, 'replies' => new Collection];
        $authorId = null;

        if ($case->target_type === ReportTargetType::Post) {
            $context['post'] = $this->postRepository->find($case->target_id);
            $authorId = $context['post']?->user_id;
        } elseif ($case->target_type === ReportTargetType::Comment) {
            $comment = $this->commentRepository->find($case->target_id);

            if ($comment !== null) {
                $context = [
                    'post' => $this->postRepository->find($comment->post_id),
                    'ancestors' => $this->commentRepository->ancestorsOf($comment, self::MAX_ANCESTORS),
                    'comment' => $comment,
                    'replies' => $this->commentRepository->repliesTo($comment->id, self::MAX_REPLIES),
                ];
                $authorId = $comment->user_id;
            }
        }

        $this->label($context, $authorId);

        return $context;
    }

    /**
     * In reading order, so that "Participant 1" is whoever comes first: the post, the thread down to
     * the comment, then the replies.
     *
     * @param  array{post: ?Post, ancestors: Collection<int, Comment>, comment: ?Comment, replies: Collection<int, Comment>}  $context
     */
    private function label(array $context, ?string $authorId): void
    {
        $numbers = [];
        $items = array_filter([$context['post'], ...$context['ancestors'], $context['comment'], ...$context['replies']]);

        foreach ($items as $item) {
            /** @var Model $item */
            if ($authorId !== null && $item->user_id === $authorId) {
                $item->setAttribute('context_label', 'author');

                continue;
            }

            $numbers[$item->user_id] ??= count($numbers) + 1;
            $item->setAttribute('context_label', 'participant:'.$numbers[$item->user_id]);
        }
    }
}
