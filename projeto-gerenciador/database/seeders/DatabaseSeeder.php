<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\Tarefa;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@gerenciador.local',
        ]);

        // Massa de dados variada (status/prioridade/atrasadas) para o
        // dashboard e a paginacao terem algo interessante para mostrar.
        $tarefas = Tarefa::factory(18)->create()
            ->merge(Tarefa::factory(5)->atrasada()->create())
            ->merge(Tarefa::factory(4)->concluida()->create());

        Staff::factory(30)->create()->each(function (Staff $staff) use ($tarefas) {
            if (fake()->boolean(60)) {
                $staff->update(['tarefa_id' => $tarefas->random()->id]);
            }
        });
    }
}
