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

        $tarefas = Tarefa::factory(5)->create();

        Staff::factory(10)->create()->each(function (Staff $staff) use ($tarefas) {
            if (fake()->boolean(60)) {
                $staff->update(['tarefa_id' => $tarefas->random()->id]);
            }
        });
    }
}
