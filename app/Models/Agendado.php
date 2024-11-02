<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Agendado extends Model
{
    use HasApiTokens,HasFactory, Notifiable, SoftDeletes;

    protected $fillable =
    [
        'user_id',
        'anuncio_id',
        'formapagamento',
        'data_inicio',
        'data_fim',
    ];



    protected static function booted()
    {
        static::created(function ($agendado) {
            $agendado->refresh();

            Comprovante::create([
                'user_id' => $agendado->user_id,
                'anuncios_id' => $agendado->anuncio_id,
                'servicos_id' => $agendado->servico->first()->id ?? null, // Usa o primeiro serviço, se houver
            ]);
        });
    }

    public function anuncio()
    {
        return $this->belongsTo(Anuncio::class);
    }

    public function servico()
    {
        return $this->belongsToMany(Servico::class, 'agendado_servico','agendado_id','servico_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
