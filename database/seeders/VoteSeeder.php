<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Seeder;

class VoteSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id');

        foreach (Post::all() as $post) {
            foreach ($this->voters($userIds) as $userId) {
                Vote::factory()->onPost($post)->create(['user_id' => $userId]);
            }
        }

        foreach (Comment::all() as $comment) {
            foreach ($this->voters($userIds) as $userId) {
                Vote::factory()->onComment($comment)->create(['user_id' => $userId]);
            }
        }
    }

    // Each user votes at most once per post/comment (primary key: parent_id + user_id).
    private function voters($userIds)
    {
        return $userIds->shuffle()->take(random_int(0, $userIds->count()));
    }
}
