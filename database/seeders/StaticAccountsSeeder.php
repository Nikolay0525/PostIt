<?php

namespace Database\Seeders;

use App\Enums\GroupModeratorRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Two fixed accounts to log in with by hand, the same on every seed: a regular user and an admin.
 * The regular user owns the first public group and moderates the next two, to see those roles.
 * Each is in every public group and follows a few people, from a day ago, so the posts
 * UnreadPostsSeeder adds count as new for them (the side panel's counters).
 *
 * Safe to run again: an account that exists is left as it is, memberships and follows are
 * insertOrIgnore.
 */
class StaticAccountsSeeder extends Seeder
{
    private const PASSWORD = '123123123';

    private const ACCOUNTS = [
        ['username' => 'user', 'email' => 'user@gmail.com', 'admin' => false],
        ['username' => 'admin', 'email' => 'admin@gmail.com', 'admin' => true],
    ];

    private const FOLLOWS = 3;

    public function run(): void
    {
        $publicGroupIds = Group::where('is_private', false)->pluck('id');
        $since = now()->subDay();

        foreach (self::ACCOUNTS as $account) {
            $user = User::where('email', $account['email'])->first()
                ?? ($account['admin'] ? User::factory()->admin() : User::factory())->create([
                    'username' => $account['username'],
                    'email' => $account['email'],
                    'password' => Hash::make(self::PASSWORD),
                ]);

            DB::table('user_group_subscriptions')->insertOrIgnore($publicGroupIds->map(fn (string $groupId) => [
                'user_id' => $user->id,
                'group_id' => $groupId,
                'created_at' => $since,
            ])->all());

            // People who post in public groups, so their new posts are visible to this account.
            $authorIds = DB::table('user_group_subscriptions')
                ->whereIn('group_id', $publicGroupIds)
                ->whereNotIn('user_id', User::whereIn('email', array_column(self::ACCOUNTS, 'email'))->pluck('id'))
                ->distinct()
                ->pluck('user_id')
                ->shuffle()
                ->take(self::FOLLOWS);

            DB::table('user_user_subscriptions')->insertOrIgnore($authorIds->map(fn (string $authorId) => [
                'user_follower_id' => $user->id,
                'user_author_id' => $authorId,
                'created_at' => $since,
            ])->all());

            if (! $account['admin']) {
                $this->giveRoles($user);
            }
        }
    }

    // The first public group (by name) is owned by this account — taking the place of the seeded
    // owner, so the group still has just one — and the next two are moderated by it.
    private function giveRoles(User $user): void
    {
        $groupIds = Group::where('is_private', false)->orderBy('name')->pluck('id');

        if ($groupIds->isEmpty()) {
            return;
        }

        DB::table('group_moderators')
            ->where('group_id', $groupIds->first())
            ->where('role', GroupModeratorRole::Owner->value)
            ->update(['user_id' => $user->id]);

        DB::table('group_moderators')->insertOrIgnore($groupIds->slice(1, 2)->map(fn (string $groupId) => [
            'user_id' => $user->id,
            'group_id' => $groupId,
            'role' => GroupModeratorRole::Moderator->value,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }

    /**
     * @return list<string>
     */
    public static function emails(): array
    {
        return array_column(self::ACCOUNTS, 'email');
    }
}
