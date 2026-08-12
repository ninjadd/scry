<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create 120 Users
        $users = User::factory()->count(120)->create();

        // 2. Create 100 Categories
        $categories = Category::factory()->count(100)->create();

        // 3. Create 100 Tags
        $tags = Tag::factory()->count(100)->create();

        // 4. Create 150 Posts mapped to random users & categories
        $posts = Post::factory()->count(150)->make()->each(function ($post) use ($users, $categories) {
            $post->user_id = $users->random()->id;
            $post->category_id = $categories->random()->id;
            $post->save();
        });

        // 5. Attach 1 to 4 random tags to each post (populates post_tag pivot table with 300+ rows)
        foreach ($posts as $post) {
            $randomTags = $tags->random(rand(1, 4))->pluck('id')->toArray();
            $post->tags()->attach($randomTags);
        }
    }
}
