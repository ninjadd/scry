<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->words(fake()->numberBetween(1, 2), true)) . ' ' . fake()->randomNumber(4, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
        ];
    }
}
