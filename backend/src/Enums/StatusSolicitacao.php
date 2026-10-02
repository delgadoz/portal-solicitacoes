<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status de uma solicitação. Os valores são os ids da tabela `status`.
 */
enum StatusSolicitacao: int
{
    case Aberto = 1;
    case EmAtendimento = 2;
    case Concluido = 3;
}
