<?php

namespace Tests\Feature\Services;

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Models\UserGroupSubscription;
use App\Services\PostService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorPostsTest extends TestCase
{
    use RefreshDatabase;

    private PostService $posts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->posts = $this->app->make(PostService::class);
    }

    public function test_only_the_authors_own_posts_are_listed_newest_first(): void
    {
        $author = User::factory()->create();
        $older = Post::factory()->create(['user_id' => $author->id, 'created_at' => now()->subDay()]);
        $newer = Post::factory()->create(['user_id' => $author->id, 'created_at' => now()]);
        Post::factory()->create(); // someone else's

        $ids = $this->postIds($this->posts->getAuthorPosts($author->id));

        $this->assertSame([$newer->id, $older->id], $ids);
    }

    public function test_posts_in_a_private_group_are_hidden_from_guests_and_non_members(): void
    {
        $author = User::factory()->create();
        $stranger = User::factory()->create();
        $public = Post::factory()->create(['user_id' => $author->id]);
        Post::factory()->create(['user_id' => $author->id, 'group_id' => Group::factory()->private()]);

        $this->assertSame([$public->id], $this->postIds($this->posts->getAuthorPosts($author->id)));
        $this->assertSame([$public->id], $this->postIds($this->posts->getAuthorPosts($author->id, $stranger->id)));
    }

    public function test_a_member_of_the_private_group_sees_its_posts(): void
    {
        $author = User::factory()->create();
        $member = User::factory()->create();
        $privateGroup = Group::factory()->private()->create();
        $otherPrivateGroup = Group::factory()->private()->create();
        $inGroup = Post::factory()->create(['user_id' => $author->id, 'group_id' => $privateGroup->id, 'created_at' => now()]);
        $public = Post::factory()->create(['user_id' => $author->id, 'created_at' => now()->subDay()]);
        Post::factory()->create(['user_id' => $author->id, 'group_id' => $otherPrivateGroup->id]);
        UserGroupSubscription::query()->insert(['user_id' => $member->id, 'group_id' => $privateGroup->id, 'created_at' => now()]);

        $ids = $this->postIds($this->posts->getAuthorPosts($author->id, $member->id));

        // Membership opens that one private group, not every private group.
        $this->assertSame([$inGroup->id, $public->id], $ids);
    }

    public function test_deleted_posts_are_not_listed(): void
    {
        $author = User::factory()->create();
        Post::factory()->create(['user_id' => $author->id, 'is_deleted' => true]);

        $this->assertSame([], $this->postIds($this->posts->getAuthorPosts($author->id)));
    }

    /** @return list<string> */
    private function postIds($paginator): array
    {
        return collect($paginator->items())->pluck('id')->all();
    }
}
