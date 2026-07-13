<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProdiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode_prodi' => fake()->unique()->bothify('PR##'),
            'nama_prodi' => fake()->unique()->words(3, true),
        ];
    }
}
