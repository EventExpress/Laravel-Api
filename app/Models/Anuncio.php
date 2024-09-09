<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Anuncio extends Model
{
    use HasApiTokens,HasFactory, Notifiable, SoftDeletes;
    protected $fillable =
    [
        'titulo',
        'endereco_id',
        'capacidade',
        'descricao',
        'usuario_id',
        'valor',
        'agenda',
    ];

    public function endereco() {
        return $this->belongsTo(Endereco::class);
    }

    public function categoria()
    {
        return $this->belongsToMany(Categoria::class, 'anuncio_categoria', 'anuncio_id', 'categoria_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function nome() {
        return $this->hasMany(Nome::class);
    }

    public function avaliacao() {
        return $this->hasMany(Avaliacao::class);
    }

}
