<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens,HasFactory, Notifiable,SoftDeletes;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome_id',
        'telefone',
        'email',
        'password',
        'remember_token',
        'datanasc',
        'tipousu',
        'cpf',
        'cnpj',
        'endereco_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function nome()
    {
        return $this->belongsTo(Nome::class, 'nome_id');
    }


    public function endereco() {
        return $this->belongsTo(Endereco::class, 'endereco_id');
    }

    public function anuncio() {
        return $this->hasMany(Anuncio::class);
    }

    public function typeUsers()
    {
        return $this->belongsToMany(TypeUser::class, 'tipo_usuario', 'user_id', 'typeusers_id');
    }

    public function avaliacao()
    {
        return $this->belongsToMany(User::class, 'avaliacao_users', 'users_id', 'avaliacao_id');
    }
}
