<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $title = ucfirst(fake()->words(fake()->numberBetween(1, 3), true)) . ' ' . fake()->randomNumber(4, true);

        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(6),
            'description' => fake()->optional(0.8)->paragraph(),
        ];
    }
}
