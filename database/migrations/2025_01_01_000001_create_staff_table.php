<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cargo')->nullable();
            $table->string('local')->nullable();
            $table->unsignedTinyInteger('idade')->nullable();
            $table->string('contrato')->nullable();
            $table->decimal('salario', 12, 2)->nullable();

            // Substitui o antigo design onde staff.tarefa_id guardava o NOME da
            // tarefa (string) em vez de uma chave estrangeira de verdade.
            $table->foreignId('tarefa_id')
                ->nullable()
                ->constrained('tarefas')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
