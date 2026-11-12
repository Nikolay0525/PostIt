<?php

namespace Database\Seeders;

use App\Enums\GroupModeratorRole;
use App\Enums\ModerationVerdictType;
use App\Enums\ReportTargetType;
use App\Enums\SanctionKind;
use App\Models\Group;
use App\Models\Post;
use App\Models\Punishment;
use App\Models\SpeakingLanguage;
use App\Models\Strike;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Services\ModerationVerdictService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Four small groups where the regular account (see StaticAccountsSeeder) has strikes and punishments,
 * to try them by hand. They are made the way the real thing is — a post, a case, a "Warning" verdict —
 * so what the account sees (notifications, a muted form, a banned group page, the "removed by
 * moderators" posts) is what a real member would:
 *
 *  - Demo: warned — two mute strikes, one short of a mute.
 *  - Demo: muted  — a mute that is running.
 *  - Demo: banned — a ban that is running.
 *  - Demo: ended  — a mute that ended an hour ago and a strike that ran out, both waiting for the
 *    scheduled `moderation:settle-punishments` (run it by hand to see the "over" notifications).
 *
 * The mute and the ban last a day from the seed, so seed again to see them running.
 *
 * Safe to run again: it does nothing for an account that already has strikes.
 */
class SanctionSeeder extends Seeder
{
    private const INSULT = 'You are a complete idiot and nobody wants you here.';

    private const THREAT = 'Say that once more and you will regret it badly.';

    // Rule 0 gives mute strikes, rule 1 ban strikes.
    private const RULES = [
        ['text' => 'Be respectful: no insults or name-calling.', 'example' => null, 'kind' => 'mute'],
        ['text' => 'No threats or harassment.', 'example' => null, 'kind' => 'ban'],
    ];

    public function run(): void
    {
        $user = User::where('email', StaticAccountsSeeder::emails()[0])->first();

        if ($user === null || Strike::where('user_id', $user->id)->exists()) {
            return;
        }

        // Anyone else: owns the demo groups and is who "decided" the verdicts (never shown to anyone).
        $other = User::where('id', '!=', $user->id)->first();

        if ($other === null) {
            return;
        }

        $warned = $this->group('Demo: warned', 'demo-warned', 'Two mute strikes for the regular account: one more is a mute.', $user, $other);
        $muted = $this->group('Demo: muted', 'demo-muted', 'The regular account is muted here: it can read, but not post, comment or vote.', $user, $other);
        $banned = $this->group('Demo: banned', 'demo-banned', 'The regular account is banned from this group: its content is closed to it.', $user, $other);
        $ended = $this->group('Demo: ended', 'demo-ended', 'A mute and a strike here have run out and wait to be settled.', $user, $other);

        $this->warn($warned, $user, $other, 0, self::INSULT, 'complete idiot', 2);
        $this->warn($muted, $user, $other, 0, self::INSULT, 'complete idiot', 3);
        $this->warn($banned, $user, $other, 1, self::THREAT, 'you will regret it', 3);

        $this->warn($ended, $user, $other, 0, self::INSULT, 'complete idiot', 3);
        $this->warn($ended, $user, $other, 1, self::THREAT, 'you will regret it', 1);
        $this->backdate($ended, $user);
    }

    private function group(string $name, string $slug, string $description, User $user, User $owner): Group
    {
        $group = Group::where('slug', $slug)->first() ?? Group::factory()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'is_private' => false,
            'language_code' => SpeakingLanguage::inRandomOrder()->value('code'),
        ]);

        $groups = app(GroupRepositoryInterface::class);
        $groups->addRuleVersion($group->id, self::RULES);
        $groups->addModerator($group->id, $owner->id, GroupModeratorRole::Owner);

        DB::table('user_group_subscriptions')->insertOrIgnore([
            ['user_id' => $user->id, 'group_id' => $group->id, 'created_at' => now()->subDay()],
            ['user_id' => $owner->id, 'group_id' => $group->id, 'created_at' => now()->subDay()],
        ]);

        return $group;
    }

    /**
     * $times warnings, each for a post of the account, under the rule with this index.
     */
    private function warn(Group $group, User $user, User $moderator, int $rule, string $text, string $quote, int $times): void
    {
        $versionId = $group->ruleVersions()->latest('created_at')->value('id');
        $start = (int) strpos($text, $quote);

        for ($i = 0; $i < $times; $i++) {
            $post = Post::factory()->hidden()->create([
                'group_id' => $group->id,
                'user_id' => $user->id,
                'title' => null,
                'article' => $text,
                'created_at' => now()->subHours(2),
            ]);
            $case = app(ModerationCaseRepositoryInterface::class)->open($group->id, ReportTargetType::Post, $post->id, $versionId);

            app(ModerationVerdictService::class)->decide(
                $case,
                $moderator->id,
                ModerationVerdictType::Warning,
                $rule,
                [['start' => $start, 'end' => $start + strlen($quote), 'quote' => $quote]],
                $rule === 0 ? 'An insult instead of an argument.' : 'A threat against another member.',
            );
        }
    }

    // Makes the group's mute end an hour ago and its ban strike run out an hour ago, so that both wait
    // for the scheduled settling instead of being in force.
    private function backdate(Group $group, User $user): void
    {
        $ends = now()->subHour();

        $punishment = Punishment::where('user_id', $user->id)->where('group_id', $group->id)->where('kind', SanctionKind::Mute->value)->first();
        $punishment?->update(['starts_at' => $ends->copy()->subDay(), 'ends_at' => $ends]);

        Strike::where('user_id', $user->id)->where('group_id', $group->id)->where('kind', SanctionKind::Mute->value)
            ->update(['created_at' => $ends->copy()->subDay()]);
        Strike::where('user_id', $user->id)->where('group_id', $group->id)->where('kind', SanctionKind::Ban->value)
            ->update(['expires_at' => $ends]);
    }
}
