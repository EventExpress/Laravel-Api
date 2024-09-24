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
        'user_id', // Adicione isso para permitir o preenchimento em massa
        'nota',
        'comentario',
    ];

    public function anuncios() {
        return $this->belongsToMany(Anuncio::class, 'avaliacao_anuncio', 'avaliacao_id', 'anuncio_id');
    }

    public function users() {
        return $this->belongsTo(User::class, 'user_id'); // Relacionamento com o User
    }

    public function servico() {
        return $this->belongsToMany(User::class, 'avaliacao_servico', 'avaliacao_id', 'servico_id');
    }
}
