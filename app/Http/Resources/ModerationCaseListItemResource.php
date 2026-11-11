<?php

namespace App\Http\Resources;

use App\Enums\ReportTargetType;
use App\Models\Comment;
use App\Models\ModerationCase;
use App\Models\Post;
use App\Support\Concerns\RendersMarkdown;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * A case as one card of the queue: a short start of the reported content, how many reports, and
 * how long it has waited. The whole text, and the reports themselves, are on the case's own page
 * (ModerationCaseResource): a post can be thousands of characters, too long for a list.
 *
 * Neither the author nor the reporters are in it.
 *
 * @mixin ModerationCase
 */
class ModerationCaseListItemResource extends JsonResource
{
    use RendersMarkdown;

    private const EXCERPT_LENGTH = 200;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'reports_count' => $this->reports_count,
            'target' => $this->describe($this->relationLoaded('target') ? $this->getRelation('target') : null),
        ];
    }

    private function describe(Post|Comment|null $target): ?array
    {
        return match (true) {
            $target instanceof Post => [
                'type' => ReportTargetType::Post->name,
                'title' => $target->title,
                'excerpt' => Str::limit($this->markdownToPlainText($target->article), self::EXCERPT_LENGTH),
            ],
            $target instanceof Comment => [
                'type' => ReportTargetType::Comment->name,
                'title' => null,
                'excerpt' => Str::limit($target->text, self::EXCERPT_LENGTH),
            ],
            default => null,
        };
    }
}
