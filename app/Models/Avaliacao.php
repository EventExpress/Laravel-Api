<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Avaliacao extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'avaliacoes';

    protected $fillable = [
        'user_id', // Adicione isso para permitir o preenchimento em massa
        'nota',
        'comentario',
    ];

    public function avaliavel()
    {
        return $this->morphTo();
    }

}
