<?php

namespace Database\Factories;

use App\Models\SpaceInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpaceInvitation>
 */
class SpaceInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('['.SpaceInvitation::ALPHABET.']{'.SpaceInvitation::CODE_LENGTH.'}'),
            'created_by' => User::factory(),
            'expires_at' => now()->addHours(SpaceInvitation::VALID_FOR_HOURS),
        ];
    }

    /**
     * Indicate that the invitation is past its expiry time.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    /**
     * Indicate that the creator has revoked the invitation.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
        ]);
    }

    /**
     * Indicate that the invitation has already been used.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepted_at' => now(),
        ]);
    }
}
