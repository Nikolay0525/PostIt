<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    private const TEXTS = [
        'Great write-up, thanks for sharing this.',
        'I ran into the same problem last month. What worked for me was taking a break and coming back to it later.',
        'This is exactly what I needed to read today.',
        'Congrats, that looks really impressive!',
        'Could you share more details about how you got started?',
        'Agreed, the part about keeping things simple is spot on.',
        'I have a slightly different opinion, but I can see where you are coming from.',
        'Thanks, this helped me a lot.',
        'Has anyone else tried this? I would love to hear how it went.',
        'Bookmarked for later. Please keep posting updates!',
        'Honestly, I would have done the same thing.',
        'That is a fair point, I had not thought about it that way.',
        'Welcome to the community! Feel free to ask if you need anything.',
        'This made my day, thank you for posting.',
        'I disagree a bit, but I appreciate you explaining your reasoning.',
        'Nice work! What are you planning to do next?',
        'Not sure this works for everyone, but it is worth a try.',
        'Same here. It took me a while to figure it out too.',
        'Thanks for the honest review, that is really useful.',
        'Do you have any recommendations for beginners?',
        'Well said. More people should be talking about this.',
        'I learned something new today, thanks!',
        'This is a common mistake, do not be too hard on yourself.',
        'Can you post an update in a few weeks?',
        'Interesting, I will give it a try this weekend.',
        'Looks great. The details really make the difference.',
        'Totally agree. Consistency matters more than talent.',
        'Thanks for the tips, I will definitely use them.',
        'Ha, that sounds familiar!',
        'Good luck, you are on the right track.',
    ];

    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'post_id' => Post::factory(),
            'parent_id' => null,
            'user_id' => User::factory(),
            'text' => fake()->randomElement(self::TEXTS),
            'is_deleted' => false,
            'deleted_at' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    public function replyTo(Comment $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'post_id' => $parent->post_id,
            'parent_id' => $parent->id,
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_deleted' => true,
            'deleted_at' => $attributes['created_at'],
        ]);
    }
}
