<?php

namespace App\Repositories\Eloquent;

use App\Enums\ModerationCaseStatus;
use App\Enums\ModerationVerdictType;
use App\Enums\ReportTargetType;
use App\Models\Comment;
use App\Models\ModerationCase;
use App\Models\ModerationVerdict;
use App\Models\Post;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

class EloquentModerationCaseRepository implements ModerationCaseRepositoryInterface
{
    public function findOpen(ReportTargetType $targetType, string $targetId): ?ModerationCase
    {
        return ModerationCase::where('open_key', $this->openKey($targetType, $targetId))->first();
    }

    public function open(string $groupId, ReportTargetType $targetType, string $targetId, ?string $ruleVersionId = null): ModerationCase
    {
        return ModerationCase::create([
            'group_id' => $groupId,
            'rule_version_id' => $ruleVersionId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'status' => ModerationCaseStatus::Open,
            'open_key' => $this->openKey($targetType, $targetId),
        ]);
    }

    public function createVerdict(
        string $caseId,
        ModerationVerdictType $type,
        string $decidedBy,
        ?string $ruleVersionId,
        ?int $ruleIndex,
        ?array $marks,
        ?string $reason,
    ): ModerationVerdict {
        return ModerationVerdict::create([
            'moderation_case_id' => $caseId,
            'type' => $type,
            'decided_by' => $decidedBy,
            'rule_version_id' => $ruleVersionId,
            'rule_index' => $ruleIndex,
            'marks' => $marks,
            'reason' => $reason,
        ]);
    }

    public function close(string $caseId): void
    {
        ModerationCase::whereKey($caseId)->update([
            'status' => ModerationCaseStatus::Closed,
            'open_key' => null,
            'closed_at' => now(),
        ]);
    }

    public function openInGroup(string $groupId, int $perPage): LengthAwarePaginator
    {
        $cases = $this->forModeration()
            ->where('group_id', $groupId)
            ->where('status', ModerationCaseStatus::Open)
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate($perPage);

        $this->loadTargets($cases->getCollection());

        return $cases;
    }

    public function findLatestFor(ReportTargetType $targetType, string $targetId): ?ModerationCase
    {
        return ModerationCase::where('target_type', $targetType->value)
            ->where('target_id', $targetId)
            ->with('verdict.ruleVersion')
            ->latest('created_at')
            ->latest('id')
            ->first();
    }

    public function findForModeration(string $id): ?ModerationCase
    {
        $case = $this->forModeration()->whereKey($id)->first();

        if ($case !== null) {
            $this->loadTargets(new Collection([$case]));
        }

        return $case;
    }

    private function forModeration(): Builder
    {
        // No `reporter_id`: whoever decides the case does not learn who reported.
        return ModerationCase::query()
            ->withCount('reports')
            ->with(['reports' => fn (Relation $reports) => $reports
                ->select(['id', 'moderation_case_id', 'rule_version_id', 'rule_index', 'breaks_platform_rules', 'text', 'created_at'])
                ->with('ruleVersion:id,rules')
                ->orderBy('created_at')]);
    }

    /**
     * Sets the `target` relation of each case: two queries however many cases there are. The
     * author's id is left out of the columns on purpose.
     *
     * @param  Collection<int, ModerationCase>  $cases
     */
    private function loadTargets(Collection $cases): void
    {
        $posts = Post::whereIn('id', $this->targetIds($cases, ReportTargetType::Post))
            ->get(['id', 'group_id', 'title', 'article', 'slug', 'is_deleted', 'edited_at', 'hidden_at', 'created_at'])
            ->keyBy('id');

        $comments = Comment::whereIn('id', $this->targetIds($cases, ReportTargetType::Comment))
            ->with('post:id,title,slug')
            ->get(['id', 'post_id', 'parent_id', 'text', 'is_deleted', 'edited_at', 'hidden_at', 'created_at'])
            ->keyBy('id');

        foreach ($cases as $case) {
            $case->setRelation('target', match ($case->target_type) {
                ReportTargetType::Post => $posts->get($case->target_id),
                ReportTargetType::Comment => $comments->get($case->target_id),
                default => null,
            });
        }
    }

    /**
     * @param  Collection<int, ModerationCase>  $cases
     * @return list<string>
     */
    private function targetIds(Collection $cases, ReportTargetType $type): array
    {
        return $cases->where('target_type', $type)->pluck('target_id')->all();
    }

    private function openKey(ReportTargetType $targetType, string $targetId): string
    {
        return $targetType->value.':'.$targetId;
    }
}
