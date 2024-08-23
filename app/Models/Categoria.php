<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;
    protected $fillable=['titulo', 'descricao'];

    public function anuncio()
    {
        return $this->belongsToMany(Anuncio::class, 'anuncio_categoria', 'categoria_id', 'anuncio_id');
    }
}
