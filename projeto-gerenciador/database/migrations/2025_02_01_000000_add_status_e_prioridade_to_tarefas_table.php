<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            // Guardados como string (nao enum nativo do banco) para manter a
            // migration portavel entre MySQL e SQLite (usado nos testes).
            // A validacao da lista de valores permitidos fica nos Form
            // Requests (StoreTarefaRequest/UpdateTarefaRequest).
            $table->string('status', 20)->default('pendente')->after('nome');
            $table->string('prioridade', 10)->default('media')->after('status');

            $table->index('status');
            $table->index('prioridade');
        });
    }

    public function down(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['prioridade']);
            $table->dropColumn(['status', 'prioridade']);
        });
    }
};
