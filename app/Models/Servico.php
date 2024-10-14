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
        'titulo',
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

    public function avaliacao() {
        return $this->belongsToMany(Avaliacao::class, 'avaliacao_servico', 'avaliacao_id', 'servico_id');;
    }

    public function agendado()
    {
        return $this->belongsToMany(Agendado::class, 'agendado_servico','agendado_id','servico_id');
    }
}
