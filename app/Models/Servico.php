<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Servico extends Model
{
    use HasApiTokens,HasFactory, Notifiable, SoftDeletes;

    protected $fillable =
    [
        'cidade',
        'bairro',
        'descricao',
        'user_id',
        'valor',
        'agenda',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function avaliacoes()
    {
        return $this->morphMany(Avaliacao::class, 'avaliavel');
    }

    public function agendado()
    {
        return $this->belongsToMany(Agendado::class, 'agendado_servico')
            ->withPivot('data_inicio', 'data_fim')
            ->withTimestamps();
    }


    public function scategorias() {
        return $this->belongsToMany(Scategoria::class, 'servico_scategoria', 'servico_id', 'scategoria_id');
    }
}
