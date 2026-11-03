<?php

namespace Database\Factories;

use App\Enums\VerificationPurpose;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificationCode>
 */
class VerificationCodeFactory extends Factory
{
    /**
     * The code every factory row stands for, unless a test sets another.
     */
    public const CODE = '482913';

    /**
     * Define the model's default state: a fresh code to reset the password.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'purpose' => VerificationPurpose::PasswordReset,
            'code_hash' => VerificationCode::hash(self::CODE),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(VerificationCode::VALID_FOR_MINUTES),
        ];
    }

    /**
     * Indicate that the code is for verifying the email.
     */
    public function forEmailVerification(): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => VerificationPurpose::EmailVerification,
        ]);
    }

    /**
     * Indicate that the code is for changing the email to the given address.
     */
    public function forEmailChange(string $newEmail = 'baru@email.com'): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => VerificationPurpose::EmailChange,
            'new_email' => $newEmail,
        ]);
    }

    /**
     * Indicate that the code is past its expiry time.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subSecond(),
        ]);
    }
}
