<?php

namespace Database\Factories;

use App\Models\Memory;
use App\Models\MemoryPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MemoryPhoto>
 */
class MemoryPhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'memory_id' => Memory::factory(),
            'space_id' => fn (array $attributes) => Memory::query()->findOrFail($attributes['memory_id'])->space_id,
            'uploaded_by' => fn (array $attributes) => Memory::query()->findOrFail($attributes['memory_id'])->created_by,
            'path' => fn (array $attributes) => MemoryPhoto::pathFor($attributes['space_id'], Str::lower((string) Str::ulid())),
            'thumb_path' => fn (array $attributes) => str_replace('.jpg', '-thumb.jpg', $attributes['path']),
            'size_bytes' => 600_000,
            'width' => 2560,
            'height' => 1920,
            'position' => 1024,
        ];
    }
}
