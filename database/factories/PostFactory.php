<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    private const TITLES = [
        'I finally finished my first big project',
        'What is the best way to get started as a beginner?',
        'Looking for feedback on my latest work',
        'Weekly discussion: what are you working on?',
        'Does anyone else struggle with staying consistent?',
        'A few lessons I learned the hard way',
        'Can someone explain this to me like I am five?',
        'Sharing my setup after two years of tweaking',
        'Unpopular opinion: simple beats clever',
        'Which tools do you actually use every day?',
        'I made a mistake and here is what I learned',
        'Question about best practices',
        'What would you do differently if you started over?',
        'Tips for someone who just joined this community',
        'Show us your latest progress',
        'Is it worth it? My honest review after six months',
        'Big thanks to everyone who helped me last week',
        'Beginner mistakes to avoid',
        'How do you organize your time?',
        'Recommendations needed, please help',
        'My step-by-step guide, with photos',
        'Why I changed my mind about this',
        'Hidden gems that deserve more attention',
        'Need advice before making a big decision',
        'Things I wish I knew a year ago',
        'What is your favorite thing about this hobby?',
        'A small win I wanted to share today',
        'Let us talk about burnout',
        'Best resources you have found so far?',
        'Progress update: month three',
    ];

    private const PARAGRAPHS = [
        'I have been working on this for a few months now, mostly in the evenings after work. It started as a small experiment, but it slowly grew into something I actually use every day.',
        'The hardest part was not the technical side but staying consistent. Some weeks I made great progress, and others I did not touch it at all. What helped most was setting a tiny daily goal.',
        'If you are just getting started, my advice is to keep things simple. Pick one thing, finish it, and only then move on to the next. It is tempting to jump between ideas, but that rarely leads anywhere.',
        'I made plenty of mistakes along the way. The biggest one was trying to do everything perfectly from the start. In the end, a rough version that works is far more useful than a perfect plan.',
        'A friend recommended this to me last year and I ignored it for months. Looking back, I wish I had listened earlier, because it would have saved me a lot of time and frustration.',
        'Here is what worked for me: I wrote down my goals, broke them into small steps, and checked them off one by one. It sounds boring, but seeing the list get shorter was surprisingly motivating.',
        'I would love to hear how others approach this. Everyone seems to have a slightly different routine, and I am always curious to learn something new from people with more experience.',
        'Some things did not go as planned, and that is fine. I ended up throwing away about half of my first attempt, but the second version was much cleaner and easier to maintain.',
        'One thing I did not expect was how helpful this community would be. Every time I got stuck, someone had already run into the same problem and was happy to share what they learned.',
        'Costs were another surprise. I thought I would spend far less, but small things add up quickly. If you are planning something similar, leave yourself some extra room in the budget.',
        'I took a lot of notes while working on this, so if anyone wants the details, just ask in the comments. I am happy to share what I have, including the things that did not work.',
        'Overall I am really happy with how it turned out. It is not perfect, and there is still plenty I want to improve, but it feels good to finally have something finished to show.',
        'Thanks for reading all the way through! I know this got a little long, but I wanted to explain the whole process instead of only showing the final result.',
        'Next month I plan to try a different approach and see whether it makes things easier. I will post an update here with the results, whether they are good or bad.',
    ];

    public function definition(): array
    {
        $title = fake()->randomElement(self::TITLES);
        $createdAt = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'group_id' => Group::factory(),
            'user_id' => User::factory(),
            'title' => $title,
            'article' => collect(fake()->randomElements(self::PARAGRAPHS, random_int(1, 4)))
                ->implode("\n\n"),
            'slug' => Str::slug($title) . '-' . Str::lower(Str::random(6)),
            'is_deleted' => false,
            'deleted_at' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    // `title` is nullable in the schema.
    public function withoutTitle(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => null,
        ]);
    }
}
