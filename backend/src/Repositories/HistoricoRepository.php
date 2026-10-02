<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class HistoricoRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function registrar(
        int $solicitacaoId,
        ?int $statusAnteriorId,
        int $statusNovoId,
        int $usuarioId,
        ?string $observacao
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO solicitacao_historico
                (solicitacao_id, status_anterior_id, status_novo_id, usuario_id, observacao)
             VALUES (:solicitacao_id, :status_anterior_id, :status_novo_id, :usuario_id, :observacao)'
        );

        $stmt->execute([
            'solicitacao_id' => $solicitacaoId,
            'status_anterior_id' => $statusAnteriorId,
            'status_novo_id' => $statusNovoId,
            'usuario_id' => $usuarioId,
            'observacao' => $observacao,
        ]);
    }
}
