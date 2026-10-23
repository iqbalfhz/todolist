<?php

namespace Database\Factories;

use App\Models\Memory;
use App\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Memory>
 */
class MemoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'space_id' => Space::factory(),
            'title' => 'Liburan ke Bali',
            'place_name' => 'Ubud, Bali',
            'visited_on' => '2027-06-20',
            'created_by' => fn (array $attributes) => Space::query()->findOrFail($attributes['space_id'])->owner_id,
        ];
    }
}
