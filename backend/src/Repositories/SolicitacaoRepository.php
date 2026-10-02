<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class SolicitacaoRepository
{
    /**
     * Colunas e junções comuns à listagem e aos detalhes: traz os nomes de
     * categoria, status, solicitante e atendente junto com os ids.
     */
    private const SELECT_BASE = '
        SELECT s.id, s.titulo, s.descricao,
               s.categoria_id, c.nome AS categoria,
               s.status_id, st.nome AS status,
               s.solicitante_id, us.nome AS solicitante,
               s.atendente_id, ua.nome AS atendente,
               s.criado_em, s.atualizado_em, s.concluido_em
          FROM solicitacoes s
          JOIN categorias c ON c.id = s.categoria_id
          JOIN status st ON st.id = s.status_id
          JOIN usuarios us ON us.id = s.solicitante_id
          LEFT JOIN usuarios ua ON ua.id = s.atendente_id';

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @param int|null $solicitanteId quando informado, lista só as solicitações desse solicitante
     */
    public function listar(?int $solicitanteId = null): array
    {
        $sql = self::SELECT_BASE;
        $params = [];

        if ($solicitanteId !== null) {
            $sql .= ' WHERE s.solicitante_id = :solicitante_id';
            $params['solicitante_id'] = $solicitanteId;
        }

        $sql .= ' ORDER BY s.criado_em DESC, s.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @param int|null $solicitanteId quando informado, só encontra a solicitação se pertencer a ele
     */
    public function buscarPorId(int $id, ?int $solicitanteId = null): ?array
    {
        $sql = self::SELECT_BASE . ' WHERE s.id = :id';
        $params = ['id' => $id];

        if ($solicitanteId !== null) {
            $sql .= ' AND s.solicitante_id = :solicitante_id';
            $params['solicitante_id'] = $solicitanteId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $registro = $stmt->fetch();

        return $registro === false ? null : $registro;
    }

    public function criar(string $titulo, string $descricao, int $categoriaId, int $statusId, int $solicitanteId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO solicitacoes (titulo, descricao, categoria_id, status_id, solicitante_id)
             VALUES (:titulo, :descricao, :categoria_id, :status_id, :solicitante_id)'
        );

        $stmt->execute([
            'titulo' => $titulo,
            'descricao' => $descricao,
            'categoria_id' => $categoriaId,
            'status_id' => $statusId,
            'solicitante_id' => $solicitanteId,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, string $titulo, string $descricao, int $categoriaId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE solicitacoes
                SET titulo = :titulo, descricao = :descricao, categoria_id = :categoria_id
              WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'categoria_id' => $categoriaId,
        ]);
    }

    public function excluir(int $id): void
    {
        // O histórico é removido junto pelo ON DELETE CASCADE
        $stmt = $this->pdo->prepare('DELETE FROM solicitacoes WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
