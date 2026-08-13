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
        $conn = config('database.default');

        // 1. Create 120 Users
        $users = User::factory()->connection($conn)->count(120)->create();

        // 2. Create 100 Categories
        $categories = Category::factory()->connection($conn)->count(100)->create();

        // 3. Create 100 Tags
        $tags = Tag::factory()->connection($conn)->count(100)->create();

        // 4. Create 150 Posts mapped to random users & categories
        $posts = Post::factory()->connection($conn)->count(150)->make()->each(function ($post) use ($users, $categories, $conn) {
            $post->setConnection($conn);
            $post->user_id = $users->random()->id;
            $post->category_id = $categories->random()->id;
            $post->save();
        });

        // 5. Attach 1 to 4 random tags to each post (populates post_tag pivot table with 300+ rows)
        foreach ($posts as $post) {
            $randomTags = $tags->random(rand(1, 4))->pluck('id')->toArray();
            $post->tags()->attach($randomTags);
        }

        // 6. Seed 35 sample campaigns
        $campaignVariants = ['Organic Growth', 'Custom', 'Local SEO', 'Purchases'];
        for ($i = 1; $i <= 35; $i++) {
            \DB::table('campaigns')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'account_id' => (string) \Illuminate\Support\Str::uuid(),
                'service_id' => (string) \Illuminate\Support\Str::uuid(),
                'variant' => $campaignVariants[$i % 4],
                'title' => "Social Media - Organic Growth | Digital Aspect #{$i}",
                'name' => "Social Media - Organic Growth | Digital Aspect #{$i}",
                'description' => $i % 3 === 0 ? "This campaign was seeded for testing purposes." : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
