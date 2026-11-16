<?php

namespace Database\Seeders\Concerns;

use App\Models\ModerationCase;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * For a seeder that opens a moderation case by hand: the reports a real one would have been opened by,
 * so that the case page shows what the content was reported for. From people other than the author:
 * two name a rule of the group, one says it breaks the platform's rules, with details.
 */
trait FilesReports
{
    private const SEEDED_DETAILS = ['Rude to other members', 'Looks like spam', 'Off-topic and provocative'];

    /**
     * @param  Collection<int, string>  $userIds  who may have reported it
     */
    private function fileReports(ModerationCase $case, string $authorId, Collection $userIds): void
    {
        $reports = app(ReportRepositoryInterface::class);
        $rules = $case->ruleVersion?->rules ?? [];
        // Without rules to name, a report can only be about the platform's rules.
        $kinds = $rules === [] ? ['platform', 'platform'] : ['rule', 'rule', 'platform'];

        $reporters = $userIds->reject(fn (string $id) => $id === $authorId)->shuffle()->take(count($kinds))->values();

        foreach ($reporters as $i => $reporterId) {
            $kind = $kinds[$i];

            $reports->create(
                $reporterId,
                $case->target_type,
                $case->target_id,
                $case->group_id,
                $kind === 'rule' ? $case->rule_version_id : null,
                $kind === 'rule' ? array_rand($rules) : null,
                $kind === 'platform',
                $kind === 'platform' ? self::SEEDED_DETAILS[array_rand(self::SEEDED_DETAILS)] : null,
            );
        }

        $reports->attachToCase($case->target_type, $case->target_id, $case->id);
    }
}
