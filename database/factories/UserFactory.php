<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'is_vip' => false,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function requester(): static
    {
        return $this->withRole('requester');
    }

    public function agent(): static
    {
        return $this->withRole('agent');
    }

    public function lead(): static
    {
        return $this->withRole('lead');
    }

    public function admin(): static
    {
        return $this->withRole('admin');
    }

    private function withRole(string $role): static
    {
        return $this->afterCreating(function (User $user) use ($role): void {
            if (Role::query()->where('name', $role)->where('guard_name', 'web')->exists()) {
                $user->assignRole($role);
            }
        });
    }
}
