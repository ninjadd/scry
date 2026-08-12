<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $roles = ['admin', 'author', 'editor', 'customer', 'subscriber'];

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => fake()->randomElement($roles),
            'settings' => [
                'theme' => fake()->randomElement(['dark', 'light', 'system']),
                'notifications' => fake()->boolean(),
                'newsletter' => fake()->boolean(),
                'language' => fake()->randomElement(['en', 'es', 'fr', 'de']),
            ],
            'email_verified_at' => fake()->optional(0.8)->dateTimeThisYear(),
            'password' => '$2y$10$92IXUNPKjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
        ];
    }
}
