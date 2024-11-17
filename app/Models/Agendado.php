<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Agendado extends Model
{
    use HasApiTokens,HasFactory, Notifiable;

    protected $fillable =
    [
        'user_id',
        'anuncio_id',
        'formapagamento',
        'data_inicio',
        'data_fim',
        'valor_total',
        'servico_data_inicio',
        'servico_data_fim'
    ];

    public function anuncio()
    {
        return $this->belongsTo(Anuncio::class);
    }

    public function servicos()
    {
        return $this->belongsToMany(Servico::class, 'agendado_servico')->withPivot('data_inicio', 'data_fim');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
