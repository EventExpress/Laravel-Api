<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Avaliacao extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nota',
        'comentario',
    ];

    public function anuncio() {
        return $this->belongsToMany(User::class, 'avaliacao_anuncio', 'avaliacao_id', 'anuncio_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'avaliacao_users', 'avaliacao_id', 'user_id');
    }

    public function servico() {
        return $this->belongsToMany(User::class, 'avaliacao_servico', 'avaliacao_id', 'servico_id');
    }
}
