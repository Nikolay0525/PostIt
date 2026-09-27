<?php

namespace Tests\Feature\Http;

use App\Models\Group;
use App\Models\GroupBan;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_a_verified_user_can_subscribe_to_a_public_group(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['is_private' => false]);

        $response = $this->actingAs($user)->postJson("/groups/{$group->id}/subscribe");

        $response->assertOk()->assertJson(['is_member' => true]);
        $this->assertDatabaseHas('user_group_subscriptions', ['group_id' => $group->id, 'user_id' => $user->id]);
    }

    public function test_subscribing_twice_does_not_duplicate_the_membership(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['is_private' => false]);

        $this->actingAs($user)->postJson("/groups/{$group->id}/subscribe");
        $response = $this->actingAs($user)->postJson("/groups/{$group->id}/subscribe");

        $response->assertOk()->assertJson(['is_member' => true]);
        $this->assertDatabaseCount('user_group_subscriptions', 1);
    }

    public function test_a_verified_user_can_unsubscribe(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['is_private' => false]);

        $this->actingAs($user)->postJson("/groups/{$group->id}/subscribe");
        $response = $this->actingAs($user)->deleteJson("/groups/{$group->id}/subscribe");

        $response->assertOk()->assertJson(['is_member' => false]);
        $this->assertDatabaseMissing('user_group_subscriptions', ['group_id' => $group->id, 'user_id' => $user->id]);
    }

    public function test_unsubscribing_without_ever_being_a_member_is_a_harmless_no_op(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['is_private' => false]);

        $response = $this->actingAs($user)->deleteJson("/groups/{$group->id}/subscribe");

        $response->assertOk()->assertJson(['is_member' => false]);
    }

    public function test_a_guest_cannot_subscribe(): void
    {
        $group = Group::factory()->create(['is_private' => false]);

        $response = $this->postJson("/groups/{$group->id}/subscribe");

        $response->assertUnauthorized();
        $this->assertDatabaseMissing('user_group_subscriptions', ['group_id' => $group->id]);
    }

    public function test_an_unverified_user_cannot_subscribe(): void
    {
        $user = User::factory()->unverified()->create();
        $group = Group::factory()->create(['is_private' => false]);

        $response = $this->actingAs($user)->postJson("/groups/{$group->id}/subscribe");

        $response->assertForbidden();
        $this->assertDatabaseMissing('user_group_subscriptions', ['group_id' => $group->id]);
    }

    public function test_a_private_group_rejects_direct_subscription(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['is_private' => true]);

        $response = $this->actingAs($user)->postJson("/groups/{$group->id}/subscribe");

        $response->assertForbidden();
        $this->assertDatabaseMissing('user_group_subscriptions', ['group_id' => $group->id]);
    }

    public function test_a_user_banned_from_the_group_cannot_subscribe(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['is_private' => false]);
        $moderator = User::factory()->create();

        GroupBan::create([
            'group_id' => $group->id,
            'blamed_user_id' => $user->id,
            'moderator_id' => $moderator->id,
            'reason' => 'Spam',
            'expires_at' => null,
        ]);

        $response = $this->actingAs($user)->postJson("/groups/{$group->id}/subscribe");

        $response->assertForbidden();
        $this->assertDatabaseMissing('user_group_subscriptions', ['group_id' => $group->id, 'user_id' => $user->id]);
    }

    public function test_an_expired_ban_does_not_block_subscribing(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['is_private' => false]);
        $moderator = User::factory()->create();

        GroupBan::create([
            'group_id' => $group->id,
            'blamed_user_id' => $user->id,
            'moderator_id' => $moderator->id,
            'reason' => 'Spam',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->postJson("/groups/{$group->id}/subscribe");

        $response->assertOk()->assertJson(['is_member' => true]);
    }

    public function test_subscribing_to_a_missing_group_is_a_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/groups/00000000-0000-4000-8000-000000000000/subscribe');

        $response->assertNotFound();
    }
}
