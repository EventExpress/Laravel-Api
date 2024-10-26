<?php

namespace App\Enums;

enum AnuncioStatus: string
{
    case D = "Disponivel";
    case A = "Aprovado";
    case C = "Cancelado";
    case P = "Pendente";
    case I = "Indisponivel";

    public function descricao() : string{
        return match ($this){
            self::D => "Disponivel",
            self::A => "Aprovado",
            self::C => "Cancelado",
            self::P => "Pendente",
            self::I => "Indisponivel",
        };
    }

}
