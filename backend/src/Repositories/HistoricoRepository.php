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

    /**
     * Linha do tempo da solicitação, da criação até o status atual.
     */
    public function listarPorSolicitacao(int $solicitacaoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT h.id,
                    h.status_anterior_id, sa.nome AS status_anterior,
                    h.status_novo_id, sn.nome AS status_novo,
                    h.usuario_id, u.nome AS usuario,
                    h.observacao, h.criado_em
               FROM solicitacao_historico h
               LEFT JOIN status sa ON sa.id = h.status_anterior_id
               JOIN status sn ON sn.id = h.status_novo_id
               JOIN usuarios u ON u.id = h.usuario_id
              WHERE h.solicitacao_id = :id
              ORDER BY h.criado_em, h.id'
        );
        $stmt->execute(['id' => $solicitacaoId]);

        return $stmt->fetchAll();
    }
}
