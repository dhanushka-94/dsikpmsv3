<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserTitle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(UserTitle::cases())->value,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'epf_number' => fake()->optional()->unique()->numerify('EPF####'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::User,
            'is_active' => true,
            'must_change_password' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
