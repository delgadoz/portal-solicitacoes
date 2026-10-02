<?php

declare(strict_types=1);

namespace App\Enums;

enum Perfil: string
{
    case Solicitante = 'SOLICITANTE';
    case Atendente = 'ATENDENTE';
}
