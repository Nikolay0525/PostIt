<?php

namespace App\Http\Resources;

use App\Enums\ReportTargetType;
use App\Models\Comment;
use App\Models\ModerationCase;
use App\Models\Post;
use App\Support\Concerns\RendersMarkdown;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A case for the moderator: the content that was reported, in full — it is hidden from everyone
 * else, but the one who judges it must read it — and the reports against it. Neither the content's
 * author nor the reporters are in it: a moderator judges the words, not the people.
 *
 * Expects what ModerationCaseRepositoryInterface returns: `reports_count`, `reports` and `target`.
 *
 * @mixin ModerationCase
 */
class ModerationCaseResource extends JsonResource
{
    use RendersMarkdown;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'reports_count' => $this->reports_count,
            'target' => $this->describe($this->relationLoaded('target') ? $this->getRelation('target') : null),
            'reports' => ModerationReportResource::collection($this->whenLoaded('reports')),
        ];
    }

    private function describe(Post|Comment|null $target): ?array
    {
        return match (true) {
            $target instanceof Post => [
                'type' => ReportTargetType::Post->name,
                'id' => $target->id,
                'slug' => $target->slug,
                'title' => $target->title,
                'article_html' => $this->markdownToHtml($target->article),
                'created_at' => $target->created_at,
                'edited_at' => $target->edited_at,
            ],
            $target instanceof Comment => [
                'type' => ReportTargetType::Comment->name,
                'id' => $target->id,
                'text' => $target->text,
                'post' => ['slug' => $target->post->slug, 'title' => $target->post->title],
                'created_at' => $target->created_at,
                'edited_at' => $target->edited_at,
            ],
            default => null,
        };
    }
}
