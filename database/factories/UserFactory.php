<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            'remember_token' => Str::random(10),
            'role' => UserRole::Technician,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the user is a clinical pharmacist.
     */
    public function pharmacist(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Pharmacist,
        ]);
    }

    /**
     * Indicate that the user is a physician.
     */
    public function physician(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Physician,
        ]);
    }

    /**
     * Indicate that the user is a nurse.
     */
    public function nurse(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Nurse,
        ]);
    }

    /**
     * Indicate that the user is a pharmacy manager.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Manager,
        ]);
    }

    /**
     * Indicate that the user is a system administrator.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
        ]);
    }

    /**
     * Indicate that the user is a superuser (bypasses all policy checks).
     */
    public function superuser(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Superuser,
        ]);
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

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
