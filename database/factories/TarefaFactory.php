<?php

namespace Database\Factories;

use App\Models\Tarefa;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tarefa> */
class TarefaFactory extends Factory
{
    protected $model = Tarefa::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->words(3, true),
            'data' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'descricao' => fake()->sentence(12),
        ];
    }
}
