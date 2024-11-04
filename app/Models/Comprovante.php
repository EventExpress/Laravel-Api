<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Comprovante extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['user_id', 'anuncios_id', 'servicos_id'];

    protected $casts = [
        'servicos_id' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function anuncio()
    {
        return $this->belongsTo(Anuncio::class, 'anuncios_id');
    }

    public function servico()
    {
        return $this->belongsTo(Servico::class, 'servicos_id');
    }

    public function agendado()
    {
        return $this->belongsTo(Agendado::class);
    }
}

