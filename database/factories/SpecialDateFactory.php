<?php

namespace Database\Factories;

use App\Enums\SpecialDateType;
use App\Models\Space;
use App\Models\SpecialDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpecialDate>
 */
class SpecialDateFactory extends Factory
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
            'type' => SpecialDateType::Anniversary,
            'title' => 'Hari jadi kita',
            'date' => '2023-10-29',
            'created_by' => fn (array $attributes) => Space::query()->findOrFail($attributes['space_id'])->owner_id,
        ];
    }
}
