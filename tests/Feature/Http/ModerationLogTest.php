<?php

namespace Tests\Feature\Http;

use App\Models\Group;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ModerationLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_the_log_is_open_to_a_guest_and_to_a_member(): void
    {
        $group = Group::factory()->create();

        $this->get(route('groups.moderation_log', $group->slug))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Groups/ModerationLog')->where('role', null));

        $this->actingAs(User::factory()->create())->get(route('groups.moderation_log', $group->slug))->assertOk();
    }

    public function test_an_unknown_group_is_not_found(): void
    {
        $this->get(route('groups.moderation_log', 'no-such-group'))->assertNotFound();
    }
}
