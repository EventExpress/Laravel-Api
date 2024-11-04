<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Scategoria extends Model
{
    use HasApiTokens,HasFactory, Notifiable;
    protected $fillable=['titulo', 'descricao'];

    public function servicos()
    {
        return $this->belongsToMany(Servico::class, 'servico_scategoria', 'scategoria_id', 'servico_id');
    }
}
