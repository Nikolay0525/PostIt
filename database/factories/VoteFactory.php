<?php

namespace Database\Factories;

use App\Enums\VoteParentType;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'parent_id' => Post::factory(),
            'parent_type' => VoteParentType::Post,
            'user_id' => User::factory(),
            'positive' => random_int(1, 10) <= 8, // mostly upvotes
        ];
    }

    public function onPost(Post $post): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $post->id,
            'parent_type' => VoteParentType::Post,
        ]);
    }

    public function onComment(Comment $comment): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $comment->id,
            'parent_type' => VoteParentType::Comment,
        ]);
    }
}
