<?php

namespace Tests\Feature\Http;

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Models\UserGroupSubscription;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_an_empty_or_too_short_term_searches_nothing(): void
    {
        Group::factory()->create(['name' => 'a']);

        foreach (['/search', '/search?q=a', '/search?q=%20%20'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->component('Search')
                    ->where('posts', null)
                    ->where('groups', null)
                    ->where('people', null));
        }
    }

    public function test_the_all_tab_finds_groups_people_and_posts(): void
    {
        $group = Group::factory()->create(['name' => 'Retro gaming']);
        $person = User::factory()->create(['username' => 'retro_fan']);
        $post = Post::factory()->create(['title' => 'My retro collection']);
        Post::factory()->create(['title' => 'Something else', 'article' => 'Nothing here']);

        $this->get('/search?q=retro')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('type', 'all')
                ->where('groups.data.0.id', $group->id)
                ->where('people.data.0.id', $person->id)
                ->has('posts.data', 1)
                ->where('posts.data.0.id', $post->id));
    }

    public function test_posts_match_in_the_text_too_but_title_matches_come_first(): void
    {
        $inText = Post::factory()->create(['title' => 'A story', 'article' => 'I found an old dendy console.', 'created_at' => now()]);
        $inTitle = Post::factory()->create(['title' => 'Dendy repair', 'created_at' => now()->subDay()]);

        $this->get('/search?q=dendy&type=posts')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('posts.data.0.id', $inTitle->id)
                ->where('posts.data.1.id', $inText->id)
                ->where('groups', null)
                ->where('people', null));
    }

    public function test_an_exact_name_beats_starts_with_beats_contains(): void
    {
        $contains = Group::factory()->create(['name' => 'Old retro']);
        $exact = Group::factory()->create(['name' => 'Retro']);
        $startsWith = Group::factory()->create(['name' => 'Retro games']);

        $this->get('/search?q=retro&type=groups')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('groups.data.0.id', $exact->id)
                ->where('groups.data.1.id', $startsWith->id)
                ->where('groups.data.2.id', $contains->id));
    }

    public function test_private_groups_are_findable_but_their_posts_only_by_members(): void
    {
        // The group must stay findable so people can ask to join; only its posts are closed.
        $group = Group::factory()->private()->create(['name' => 'Secret club']);
        Post::factory()->create(['group_id' => $group->id, 'title' => 'Secret meeting']);
        $member = User::factory()->create();
        UserGroupSubscription::query()->insert(['user_id' => $member->id, 'group_id' => $group->id, 'created_at' => now()]);

        $this->get('/search?q=secret')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('groups.data.0.id', $group->id)
                ->where('groups.data.0.is_private', true)
                ->has('posts.data', 0));

        $this->actingAs($member)->get('/search?q=secret')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('posts.data', 1));
    }

    public function test_deleted_posts_are_never_found(): void
    {
        Post::factory()->create(['title' => 'Deleted dendy', 'is_deleted' => true]);

        $this->get('/search?q=dendy')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('posts.data', 0));
    }

    public function test_like_wildcards_in_the_term_are_literal(): void
    {
        Group::factory()->create(['name' => 'Fifty percent off']);
        $literal = Group::factory()->create(['name' => 'Save 50% now']);

        // "%" matches only an actual percent sign, not "anything".
        $this->get('/search?q='.rawurlencode('50%').'&type=groups')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('groups.data', 1)
                ->where('groups.data.0.id', $literal->id));
    }

    public function test_the_all_tab_previews_a_few_and_reports_the_total(): void
    {
        Group::factory()->count(6)->sequence(fn ($sequence) => ['name' => 'Retro '.$sequence->index])->create();

        $this->get('/search?q=retro')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('groups.data', 4)
                ->where('groups.meta.total', 6));
    }

    public function test_suggestions_return_the_best_three_of_each_kind(): void
    {
        Group::factory()->count(5)->sequence(fn ($sequence) => ['name' => 'Retro '.$sequence->index])->create();
        User::factory()->create(['username' => 'retro_fan']);
        $post = Post::factory()->create(['title' => 'Retro console']);
        $untitled = Post::factory()->withoutTitle()->create(['article' => 'My **retro** shelf, finally sorted.']);

        $this->getJson('/search/suggest?q=retro')
            ->assertOk()
            ->assertJsonCount(3, 'groups')
            ->assertJsonCount(1, 'people')
            ->assertJsonPath('people.0.username', 'retro_fan')
            ->assertJsonPath('posts.0.id', $post->id)
            ->assertJsonPath('posts.0.title', 'Retro console')
            ->assertJsonPath('posts.0.group.slug', $post->group->slug)
            // Without a title, the start of the text — as plain text, not Markdown.
            ->assertJsonPath('posts.1.id', $untitled->id)
            ->assertJsonPath('posts.1.preview', 'My retro shelf, finally sorted.');
    }

    public function test_suggestions_for_a_too_short_term_are_empty(): void
    {
        Group::factory()->create(['name' => 'R']);

        $this->getJson('/search/suggest?q=r')
            ->assertExactJson(['groups' => [], 'people' => [], 'posts' => []]);
    }

    public function test_suggestions_keep_private_group_posts_to_members(): void
    {
        $group = Group::factory()->private()->create(['name' => 'Secret club']);
        Post::factory()->create(['group_id' => $group->id, 'title' => 'Secret meeting']);

        $this->getJson('/search/suggest?q=secret')
            ->assertJsonPath('groups.0.id', $group->id)
            ->assertJsonCount(0, 'posts');
    }

    public function test_people_show_public_fields_only(): void
    {
        User::factory()->create(['username' => 'olena', 'email' => 'olena@example.com']);

        $this->get('/search?q=olena&type=people')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('people.data.0.username', 'olena')
                ->missing('people.data.0.email')
                ->missing('people.data.0.date_of_birth'));
    }
}
