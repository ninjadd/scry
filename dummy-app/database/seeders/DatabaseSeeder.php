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
        // 1. Create Sample Users
        $users = [
            [
                'name' => 'Alice Admin',
                'email' => 'alice@example.com',
                'role' => 'admin',
                'settings' => json_encode(['theme' => 'dark', 'notifications' => true]),
            ],
            [
                'name' => 'Bob Author',
                'email' => 'bob@example.com',
                'role' => 'author',
                'settings' => json_encode(['theme' => 'light', 'notifications' => false]),
            ],
            [
                'name' => 'Charlie Customer',
                'email' => 'charlie@example.com',
                'role' => 'customer',
                'settings' => json_encode(['newsletter' => true]),
            ],
        ];

        $createdUsers = [];
        foreach ($users as $userData) {
            $createdUsers[] = User::create($userData);
        }

        // 2. Create Categories
        $categories = [
            ['title' => 'Technology', 'slug' => 'technology', 'description' => 'Tech news, software engineering, and AI.'],
            ['title' => 'Design', 'slug' => 'design', 'description' => 'UI/UX design trends and glassmorphism principles.'],
            ['title' => 'Laravel', 'slug' => 'laravel', 'description' => 'Package development, artisan tools, and reverb.'],
        ];

        $createdCategories = [];
        foreach ($categories as $catData) {
            $createdCategories[] = Category::create($catData);
        }

        // 3. Create Tags
        $tags = [
            ['name' => 'Vue.js', 'slug' => 'vue-js'],
            ['name' => 'PostgreSQL', 'slug' => 'postgresql'],
            ['name' => 'MySQL', 'slug' => 'mysql'],
            ['name' => 'Docker', 'slug' => 'docker'],
        ];

        $createdTags = [];
        foreach ($tags as $tagData) {
            $createdTags[] = Tag::create($tagData);
        }

        // 4. Create Sample Posts
        $posts = [
            [
                'user_id' => $createdUsers[0]->id,
                'category_id' => $createdCategories[0]->id,
                'title' => 'Architecting High-Performance Laravel Packages',
                'slug' => 'architecting-high-performance-laravel-packages',
                'body' => 'Building enterprise Laravel packages requires clean service providers, path repositories for local development, and dynamic manager drivers.',
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'user_id' => $createdUsers[1]->id,
                'category_id' => $createdCategories[2]->id,
                'title' => 'Inspecting Multi-Database Engines with Scry',
                'slug' => 'inspecting-multi-database-engines-with-scry',
                'body' => 'Scry provides a reactive Vue 3 GUI to inspect PostgreSQL relation sizes, column types, default values, and index definitions in real-time.',
                'is_published' => true,
                'published_at' => now()->subDays(2),
            ],
            [
                'user_id' => $createdUsers[1]->id,
                'category_id' => $createdCategories[1]->id,
                'title' => 'Glassmorphism and Modern UI Aesthetics',
                'slug' => 'glassmorphism-and-modern-ui-aesthetics',
                'body' => 'Using dark modes, curated HSL palettes, and Tailwind CSS backdrop-filters creates stunning dashboard interfaces.',
                'is_published' => false,
                'published_at' => null,
            ],
        ];

        foreach ($posts as $postData) {
            $post = Post::create($postData);
            $post->tags()->attach([$createdTags[0]->id, $createdTags[1]->id]);
        }
    }
}
