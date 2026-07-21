<?php

namespace Database\Factories;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Staff> */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->name(),
            'cargo' => fake()->jobTitle(),
            'local' => fake()->city(),
            'idade' => fake()->numberBetween(18, 65),
            'contrato' => fake()->randomElement(['CLT', 'PJ', 'Estagio', 'Temporario']),
            'salario' => fake()->randomFloat(2, 1500, 15000),
            'tarefa_id' => null,
        ];
    }
}
