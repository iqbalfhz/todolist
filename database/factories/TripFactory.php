<?php

namespace Database\Factories;

use App\Models\Space;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
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
            'destination' => 'Bali',
            'start_date' => '2027-06-20',
            'end_date' => '2027-06-25',
            'created_by' => fn (array $attributes) => Space::query()->findOrFail($attributes['space_id'])->owner_id,
        ];
    }
}
