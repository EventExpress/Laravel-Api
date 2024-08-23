<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nome extends Model
{
    use HasFactory;

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
