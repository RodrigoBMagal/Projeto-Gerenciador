<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'nome',
        'cargo',
        'local',
        'idade',
        'contrato',
        'salario',
        'tarefa_id',
    ];

    protected function casts(): array
    {
        return [
            'idade' => 'integer',
            'salario' => 'decimal:2',
        ];
    }

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class);
    }
}
