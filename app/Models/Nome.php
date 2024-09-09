<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Nome extends Model
{
    use HasApiTokens,HasFactory, Notifiable;

    protected $fillable = [
        'nome',
        'sobrenome',

    ];

    public function getNomeCompletoAttribute()
    {
        return "{$this->nome} {$this->sobrenome}";
    }

    public function user() {
        return $this->Hasmany(User::class);
    }
}
