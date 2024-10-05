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
        'usuario_id',
        'valor',
        'agenda',
    ];

    public function categoria()
    {
        return $this->belongsToMany(Categoria::class, 'servico_categoria', 'servico_id', 'categoria_id');
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
