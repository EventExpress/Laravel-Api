<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agendado extends Model
{
    use HasFactory;
    protected $fillable = ['anuncio_id', 'usuario_id', 'comprovante_id', 'formapagamento', 'data_inicio', 'data_fim'];

    public function anuncio()
    {
        return $this->belongsTo(Anuncio::class);
    }
    public function adicional()
    {
        return $this->belongsToMany(Servico::class, 'agendado_servico','agendado_id','servico_id');
    }
    public function usuario() {
        return $this->belongsTo(Usuario::class);
    }
    public function comprovante() {
        return $this->belongsTo(Comprovante::class);
    }
}
