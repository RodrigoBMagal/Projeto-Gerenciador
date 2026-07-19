<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarefa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'data',
        'descricao',
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
}
