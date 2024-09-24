<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Anuncio extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'titulo',
        'endereco_id',
        'avaliacoes_id',
        'capacidade',
        'descricao',
        'user_id',
        'valor',
        'agenda',
    ];

    public function endereco() {
        return $this->belongsTo(Endereco::class);
    }

    public function categorias() {
        return $this->belongsToMany(Categoria::class, 'anuncio_categoria', 'anuncio_id', 'categoria_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function avaliacoes() {
        return $this->belongsToMany(Avaliacao::class, 'avaliacao_anuncio', 'anuncio_id', 'avaliacao_id'); // Ajuste para relacionamento muitos para muitos
    }
}
