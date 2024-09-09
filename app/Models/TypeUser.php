<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class TypeUser extends Model
{
    use HasApiTokens,HasFactory, Notifiable;

    protected $fillable = ['titulo'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'tipo_usuario', 'typeusers_id', 'user_id');
    }
}
