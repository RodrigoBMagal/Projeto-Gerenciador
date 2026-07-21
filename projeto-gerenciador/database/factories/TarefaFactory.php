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
            'data' => fake()->dateTimeBetween('-2 months', '+2 months')->format('Y-m-d'),
            'descricao' => fake()->sentence(12),
            'status' => fake()->randomElement(Tarefa::STATUSES),
            'prioridade' => fake()->randomElement(Tarefa::PRIORIDADES),
        ];
    }

    public function pendente(): static
    {
        return $this->state(fn () => ['status' => Tarefa::STATUS_PENDENTE]);
    }

    public function emAndamento(): static
    {
        return $this->state(fn () => ['status' => Tarefa::STATUS_EM_ANDAMENTO]);
    }

    public function concluida(): static
    {
        return $this->state(fn () => ['status' => Tarefa::STATUS_CONCLUIDA]);
    }

    /**
     * Tarefa com data no passado e status diferente de concluida — util
     * para testar/demonstrar o calculo de "atrasada".
     */
    public function atrasada(): static
    {
        return $this->state(fn () => [
            'data' => fake()->dateTimeBetween('-2 months', '-1 days')->format('Y-m-d'),
            'status' => fake()->randomElement([Tarefa::STATUS_PENDENTE, Tarefa::STATUS_EM_ANDAMENTO]),
        ]);
    }
}
