<?php

namespace Tests\Feature\Http;

use App\Enums\GroupModeratorRole;
use App\Http\Requests\StoreGroupRequest;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Services\GroupService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class GroupControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // UserObserver assigns a default UI/speaking language on user creation, and the form
        // needs languages to pick from.
        $this->seed(LanguageSeeder::class);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'slug' => 'retro-gaming',
            'name' => 'Ретро ігри',
            'description' => 'Old consoles and the games we still love.',
            'language_code' => 'uk',
            'is_private' => false,
            'rules' => [
                ['text' => 'No piracy links.', 'example' => 'ROM download sites'],
                ['text' => 'Be kind.', 'example' => null],
            ],
        ], $overrides);
    }

    public function test_a_guest_is_sent_to_login_from_the_create_page(): void
    {
        $this->get('/groups/-/create')->assertRedirect('/login');
    }

    public function test_an_unverified_user_cannot_open_the_create_page(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/groups/-/create')->assertForbidden();
    }

    public function test_a_verified_user_gets_the_create_page_with_languages_and_limits(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/groups/-/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Groups/Create')
                ->has('languages', 183)
                ->where('languages.0.name', 'Abkhazian') // ordered by English name
                // A factory user speaks no language yet, so the configured default is pre-selected.
                ->where('default_language_code', config('app.default_speaking_language_code'))
                ->where('limits.slug', GroupService::SLUG_MAX_LENGTH)
                ->where('limits.rules', StoreGroupRequest::MAX_RULES)
                ->where('limits.rule_text', StoreGroupRequest::RULE_TEXT_MAX_LENGTH)
                ->where('limits.rule_example', StoreGroupRequest::RULE_EXAMPLE_MAX_LENGTH));
    }

    public function test_the_create_page_pre_selects_a_language_the_user_speaks(): void
    {
        $user = User::factory()->create();
        DB::table('user_speaking_languages')->insert(['user_id' => $user->id, 'language_code' => 'de']);

        $this->actingAs($user)->get('/groups/-/create')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('default_language_code', 'de'));
    }

    public function test_a_verified_user_can_create_a_group_and_becomes_its_owner_and_member(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/groups', $this->payload());

        $group = Group::where('slug', 'retro-gaming')->firstOrFail();
        $response->assertRedirect(route('groups.show', $group->slug));

        $this->assertSame('Ретро ігри', $group->name);
        $this->assertSame('uk', $group->language_code);
        $this->assertFalse($group->is_private);
        $this->assertSame([
            ['text' => 'No piracy links.', 'example' => 'ROM download sites'],
            ['text' => 'Be kind.', 'example' => null],
        ], $group->currentRuleVersion->rules);
        $this->assertDatabaseHas('group_moderators', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => GroupModeratorRole::Owner->value,
        ]);
        $this->assertDatabaseHas('user_group_subscriptions', ['group_id' => $group->id, 'user_id' => $user->id]);
    }

    public function test_the_new_group_page_shows_its_rules(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/groups', $this->payload());
        $group = Group::where('slug', 'retro-gaming')->firstOrFail();

        $this->actingAs($user)->get(route('groups.show', $group->slug))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('group.rules.0.text', 'No piracy links.')
                ->where('group.rules.0.example', 'ROM download sites')
                ->where('is_member', true));
    }

    public function test_the_group_page_names_the_language_in_that_language(): void
    {
        $owner = User::factory()->create();
        $this->groupOwnedBy($owner);

        $this->actingAs($owner)->get('/groups/retro-gaming')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('group.language_code', 'uk')
                ->where('group.language_name', 'українська'));
    }

    public function test_a_post_in_the_feed_carries_its_groups_language_code(): void
    {
        $owner = User::factory()->create();
        $group = $this->groupOwnedBy($owner);
        $this->actingAs($owner)->post('/posts', ['group_id' => $group->id, 'title' => 'Hello', 'article' => 'World']);

        $this->actingAs($owner)->get('/groups/retro-gaming')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('posts.data.0.group.language_code', 'uk'));
    }

    public function test_a_posts_page_carries_the_groups_card_and_who_may_edit_it(): void
    {
        $owner = User::factory()->create();
        $group = $this->groupOwnedBy($owner);
        $post = Post::factory()->create(['group_id' => $group->id]);
        $member = User::factory()->create();
        app(GroupRepositoryInterface::class)->subscribe($group->id, $member->id);

        $this->actingAs($owner)->get("/groups/retro-gaming/posts/{$post->slug}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('group.slug', 'retro-gaming')
                ->where('group.language_name', 'українська')
                ->where('group.rules.0.text', 'No piracy links.')
                ->has('group.members_count')
                ->where('is_member', true)
                ->where('can_edit', true));
        $this->actingAs($member)->get("/groups/retro-gaming/posts/{$post->slug}")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('is_member', true)->where('can_edit', false));

        auth()->logout();
        $this->get("/groups/retro-gaming/posts/{$post->slug}")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('is_member', false)->where('can_edit', false));
    }

    public function test_the_slug_is_trimmed_and_lowercased_before_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/groups', $this->payload(['slug' => '  Retro-Gaming ']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('groups', ['slug' => 'retro-gaming']);
    }

    public function test_a_group_can_be_private(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/groups', $this->payload(['is_private' => true]));

        $this->assertTrue(Group::where('slug', 'retro-gaming')->firstOrFail()->is_private);
    }

    public function test_rules_are_optional_and_still_produce_a_first_rule_version(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/groups', $this->payload(['rules' => null]))
            ->assertSessionHasNoErrors();

        $group = Group::where('slug', 'retro-gaming')->firstOrFail();
        $this->assertSame(1, $group->ruleVersions()->count());
        $this->assertSame([], $group->currentRuleVersion->rules);
    }

    public function test_a_taken_slug_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/groups', $this->payload());

        $this->actingAs($user)->post('/groups', $this->payload(['name' => 'Another']))
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, Group::where('slug', 'retro-gaming')->count());
    }

    public function test_a_slug_outside_the_latin_pattern_is_rejected(): void
    {
        $user = User::factory()->create();

        foreach (['ретро', 'retro--gaming', '-retro', 'retro_gaming', str_repeat('a', GroupService::SLUG_MAX_LENGTH + 1)] as $slug) {
            $this->actingAs($user)->post('/groups', $this->payload(['slug' => $slug]))
                ->assertSessionHasErrors('slug');
        }

        $this->assertSame(0, Group::count());
    }

    public function test_required_fields_and_an_unknown_language_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/groups', $this->payload(['name' => '', 'description' => '', 'language_code' => 'xx']))
            ->assertSessionHasErrors(['name', 'description', 'language_code']);

        $this->assertSame(0, Group::count());
    }

    public function test_a_rule_needs_text_and_the_rule_limits_are_enforced(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/groups', $this->payload(['rules' => [['text' => '', 'example' => 'only an example']]]))
            ->assertSessionHasErrors('rules.0.text');

        $this->actingAs($user)
            ->post('/groups', $this->payload(['rules' => array_fill(0, StoreGroupRequest::MAX_RULES + 1, ['text' => 'Rule'])]))
            ->assertSessionHasErrors('rules');

        $this->actingAs($user)
            ->post('/groups', $this->payload(['rules' => [['text' => 'Rule', 'example' => str_repeat('e', StoreGroupRequest::RULE_EXAMPLE_MAX_LENGTH + 1)]]]))
            ->assertSessionHasErrors('rules.0.example');

        $this->assertSame(0, Group::count());
    }

    public function test_a_guest_cannot_create_a_group(): void
    {
        $this->post('/groups', $this->payload())->assertRedirect('/login');

        $this->assertSame(0, Group::count());
    }

    public function test_an_unverified_user_cannot_create_a_group(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post('/groups', $this->payload())->assertForbidden();

        $this->assertSame(0, Group::count());
    }

    /** The owner's group, made the way the site makes one. */
    private function groupOwnedBy(User $owner): Group
    {
        $this->actingAs($owner)->post('/groups', $this->payload());

        return Group::where('slug', 'retro-gaming')->firstOrFail();
    }

    private function editPayload(array $overrides = []): array
    {
        return array_merge(
            Arr::only($this->payload(), ['name', 'description', 'language_code', 'rules']),
            $overrides
        );
    }

    public function test_the_owner_gets_the_edit_page_with_the_current_values(): void
    {
        $owner = User::factory()->create();
        $this->groupOwnedBy($owner);

        $this->actingAs($owner)->get('/groups/retro-gaming/-/edit')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Groups/Edit')
                ->where('group.name', 'Ретро ігри')
                ->where('group.language_code', 'uk')
                ->where('group.rules.0.text', 'No piracy links.')
                ->has('languages', 183)
                ->where('limits.rules', StoreGroupRequest::MAX_RULES));
    }

    public function test_only_the_owner_is_offered_the_edit_link(): void
    {
        $owner = User::factory()->create();
        $group = $this->groupOwnedBy($owner);
        $member = User::factory()->create();
        app(GroupRepositoryInterface::class)->subscribe($group->id, $member->id);

        $this->actingAs($owner)->get('/groups/retro-gaming')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('can_edit', true));
        $this->actingAs($member)->get('/groups/retro-gaming')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('can_edit', false));
    }

    public function test_a_guest_cannot_edit_a_group(): void
    {
        $this->groupOwnedBy(User::factory()->create());
        auth()->logout();

        $this->get('/groups/retro-gaming/-/edit')->assertRedirect('/login');
        $this->put('/groups/retro-gaming', $this->editPayload(['name' => 'Hijacked']))->assertRedirect('/login');

        $this->assertSame('Ретро ігри', Group::where('slug', 'retro-gaming')->firstOrFail()->name);
    }

    public function test_a_member_and_a_moderator_cannot_edit_the_group(): void
    {
        $group = $this->groupOwnedBy(User::factory()->create());
        $member = User::factory()->create();
        $moderator = User::factory()->create();
        $groups = app(GroupRepositoryInterface::class);
        $groups->subscribe($group->id, $member->id);
        $groups->subscribe($group->id, $moderator->id);
        $groups->addModerator($group->id, $moderator->id, GroupModeratorRole::Moderator);

        foreach ([$member, $moderator] as $user) {
            $this->actingAs($user)->get('/groups/retro-gaming/-/edit')->assertForbidden();
            $this->actingAs($user)->put('/groups/retro-gaming', $this->editPayload(['name' => 'Hijacked']))->assertForbidden();
        }

        $this->assertSame('Ретро ігри', $group->fresh()->name);
        $this->assertSame(1, $group->ruleVersions()->count());
    }

    public function test_the_owner_of_one_group_cannot_edit_another(): void
    {
        $this->groupOwnedBy(User::factory()->create());
        $other = User::factory()->create();
        $this->actingAs($other)->post('/groups', $this->payload(['slug' => 'other-group']));

        $this->actingAs($other)->put('/groups/retro-gaming', $this->editPayload(['name' => 'Hijacked']))->assertForbidden();
    }

    public function test_editing_a_missing_group_is_a_404(): void
    {
        $this->actingAs(User::factory()->create())->get('/groups/nope/-/edit')->assertNotFound();
    }

    public function test_the_owner_can_change_the_name_description_and_language_but_not_the_address_or_privacy(): void
    {
        $owner = User::factory()->create();
        $group = $this->groupOwnedBy($owner);

        $this->actingAs($owner)
            ->put('/groups/retro-gaming', $this->editPayload([
                'name' => 'Retro',
                'description' => 'A new description.',
                'language_code' => 'de',
                // Not editable here: ignored, not applied.
                'slug' => 'something-else',
                'is_private' => true,
            ]))
            ->assertRedirect(route('groups.show', 'retro-gaming'))
            ->assertSessionHasNoErrors();

        $group->refresh();
        $this->assertSame('Retro', $group->name);
        $this->assertSame('A new description.', $group->description);
        $this->assertSame('de', $group->language_code);
        $this->assertSame('retro-gaming', $group->slug);
        $this->assertFalse($group->is_private);
    }

    public function test_changed_rules_become_a_new_version_and_the_old_one_is_kept(): void
    {
        $owner = User::factory()->create();
        $group = $this->groupOwnedBy($owner);
        $firstVersion = $group->currentRuleVersion;

        $this->actingAs($owner)->put('/groups/retro-gaming', $this->editPayload([
            'rules' => [['text' => 'Be kind.', 'example' => null]],
        ]))->assertSessionHasNoErrors();

        $group = Group::with('currentRuleVersion')->findOrFail($group->id);
        $this->assertSame(2, $group->ruleVersions()->count());
        $this->assertSame([['text' => 'Be kind.', 'example' => null]], $group->currentRuleVersion->rules);
        $this->assertSame(
            [
                ['text' => 'No piracy links.', 'example' => 'ROM download sites'],
                ['text' => 'Be kind.', 'example' => null],
            ],
            $firstVersion->fresh()->rules,
        );
    }

    public function test_rules_that_did_not_change_add_no_version(): void
    {
        $owner = User::factory()->create();
        $group = $this->groupOwnedBy($owner);

        $this->actingAs($owner)->put('/groups/retro-gaming', $this->editPayload(['name' => 'Retro']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $group->ruleVersions()->count());
    }

    public function test_the_owner_can_remove_all_the_rules(): void
    {
        $owner = User::factory()->create();
        $group = $this->groupOwnedBy($owner);

        $this->actingAs($owner)->put('/groups/retro-gaming', $this->editPayload(['rules' => null]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $group->ruleVersions()->count());
        $this->assertSame([], Group::with('currentRuleVersion')->findOrFail($group->id)->currentRuleVersion->rules);
    }

    public function test_an_edit_is_validated_like_a_new_group(): void
    {
        $owner = User::factory()->create();
        $group = $this->groupOwnedBy($owner);

        $this->actingAs($owner)
            ->put('/groups/retro-gaming', $this->editPayload(['name' => '', 'description' => '', 'language_code' => 'xx']))
            ->assertSessionHasErrors(['name', 'description', 'language_code']);

        $this->actingAs($owner)
            ->put('/groups/retro-gaming', $this->editPayload(['rules' => [['text' => '', 'example' => 'only an example']]]))
            ->assertSessionHasErrors('rules.0.text');

        $this->assertSame('Ретро ігри', $group->fresh()->name);
        $this->assertSame(1, $group->ruleVersions()->count());
    }
}
