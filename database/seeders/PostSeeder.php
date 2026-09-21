<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Group::with('members:id')->get() as $group) {
            if ($group->members->isEmpty()) {
                continue;
            }

            // Authors are picked from the group's own members.
            $author = fn () => ['user_id' => $group->members->random()->id];

            Post::factory()->count(3)->for($group)->state($author)->create();
            Post::factory()->withoutTitle()->for($group)->state($author)->create();
        }
    }
}
