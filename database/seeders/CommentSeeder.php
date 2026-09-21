<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id');

        foreach (Post::all() as $post) {
            $created = collect();

            for ($i = 0, $count = random_int(3, 8); $i < $count; $i++) {
                $factory = Comment::factory()
                    ->for($post)
                    ->state(fn () => ['user_id' => $userIds->random()]);

                // Roughly half of the comments reply to an earlier comment on the same post.
                if ($created->isNotEmpty() && random_int(0, 1)) {
                    $factory = $factory->replyTo($created->random());
                }

                // Roughly 10% are deleted.
                if (random_int(1, 10) === 1) {
                    $factory = $factory->deleted();
                }

                $created->push($factory->create());
            }
        }
    }
}
