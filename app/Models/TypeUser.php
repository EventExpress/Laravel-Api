<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeUser extends Model
{
    use HasFactory;

    protected $fillable = ['titulo'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'tipo_usuario', 'typeusers_id', 'user_id');
    }
}
