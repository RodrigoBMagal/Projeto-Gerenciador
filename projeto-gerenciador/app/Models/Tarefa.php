<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarefa extends Model
{
    use HasFactory;

    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_EM_ANDAMENTO = 'em_andamento';
    public const STATUS_CONCLUIDA = 'concluida';

    public const STATUSES = [
        self::STATUS_PENDENTE,
        self::STATUS_EM_ANDAMENTO,
        self::STATUS_CONCLUIDA,
    ];

    public const PRIORIDADE_BAIXA = 'baixa';
    public const PRIORIDADE_MEDIA = 'media';
    public const PRIORIDADE_ALTA = 'alta';

    public const PRIORIDADES = [
        self::PRIORIDADE_BAIXA,
        self::PRIORIDADE_MEDIA,
        self::PRIORIDADE_ALTA,
    ];

    protected $fillable = [
        'nome',
        'data',
        'descricao',
        'status',
        'prioridade',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
        ];
    }

    /**
     * Membros da equipe atualmente designados para esta tarefa.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    /**
     * Uma tarefa esta atrasada quando sua data ja passou e ela ainda nao
     * foi concluida. E calculado (nao persistido) para nunca ficar
     * desatualizado em relacao ao relogio atual.
     */
    public function estaAtrasada(): bool
    {
        return $this->status !== self::STATUS_CONCLUIDA
            && $this->data !== null
            && $this->data->lt(now()->startOfDay());
    }
}
