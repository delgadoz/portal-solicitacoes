<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status de uma solicitação. Os valores são os ids da tabela `status`.
 *
 * Fluxo permitido (máquina de estados): Aberto → Em Atendimento → Concluído.
 * Não há como pular etapas, voltar, nem sair de Concluído.
 */
enum StatusSolicitacao: int
{
    case Aberto = 1;
    case EmAtendimento = 2;
    case Concluido = 3;

    /**
     * Próximo estado permitido na máquina de estados. Retorna null caso não
     * tenha próximo estado.
     */
    public function proximo(): ?self
    {
        return match ($this) {
            self::Aberto => self::EmAtendimento,
            self::EmAtendimento => self::Concluido,
            self::Concluido => null,
        };
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::Aberto => 'Aberto',
            self::EmAtendimento => 'Em Atendimento',
            self::Concluido => 'Concluído',
        };
    }
}
