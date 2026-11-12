<?php

namespace App\Http\Resources\Concerns;

use Illuminate\Http\Request;

/**
 * For a resource of a post or comment (both have isHidden(), and a `moderationCase` relation the page
 * may have loaded): where its own author is told it stands. Waiting for the moderators' decision, or
 * taken down, and in that case the verdict, if the page loaded it. Nobody but the author gets this.
 */
trait DescribesModerationForAuthor
{
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
            'verdict' => $verdict === null ? null : (new \App\Http\Resources\AuthorVerdictResource($verdict))->toArray($request),
        ];
    }
}
