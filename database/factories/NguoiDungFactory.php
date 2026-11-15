<?php

namespace Database\Factories;

use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<NguoiDung>
 */
class NguoiDungFactory extends Factory
{
    protected $model = NguoiDung::class;

    /**
     * Mật khẩu dùng chung cho dữ liệu mẫu.
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ten' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'dien_thoai' => fake()->numerify('09########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'dang_hoat_dong' => true,
            'la_vip' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'dang_hoat_dong' => false,
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
        return $this->afterCreating(function (NguoiDung $user) use ($role): void {
            if (Role::query()->where('name', $role)->where('guard_name', 'web')->exists()) {
                $user->assignRole($role);
            }
        });
    }
}
