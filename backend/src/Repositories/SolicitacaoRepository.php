<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Validators\FiltrosSolicitacao;
use DateTimeImmutable;
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
     * Lista com filtros, ordenação e paginação.
     *
     * @param int|null $solicitanteId quando informado, restringe às solicitações desse solicitante
     *                                (aplicado sempre que o usuário logado for solicitante)
     * @return array{itens: array, total: int}
     */
    public function listar(FiltrosSolicitacao $filtros, ?int $solicitanteId = null): array
    {
        $condicoes = [];
        $params = [];

        if ($solicitanteId !== null) {
            $condicoes[] = 's.solicitante_id = :solicitante_id';
            $params['solicitante_id'] = $solicitanteId;
        } elseif ($filtros->solicitanteId !== null) {
            $condicoes[] = 's.solicitante_id = :solicitante_id';
            $params['solicitante_id'] = $filtros->solicitanteId;
        }

        if ($filtros->dataInicio !== null) {
            $condicoes[] = 's.criado_em >= :data_inicio';
            $params['data_inicio'] = $filtros->dataInicio . ' 00:00:00';
        }

        if ($filtros->dataFim !== null) {
            // "< dia seguinte" inclui o dia inteiro e mantém o uso do índice em criado_em
            $condicoes[] = 's.criado_em < :data_fim';
            $params['data_fim'] = (new DateTimeImmutable($filtros->dataFim))->modify('+1 day')->format('Y-m-d');
        }

        if ($filtros->categoriaId !== null) {
            $condicoes[] = 's.categoria_id = :categoria_id';
            $params['categoria_id'] = $filtros->categoriaId;
        }

        if ($filtros->statusId !== null) {
            $condicoes[] = 's.status_id = :status_id';
            $params['status_id'] = $filtros->statusId;
        }

        if ($filtros->busca !== null) {
            // Escapa % e _ digitados pelo usuário, para serem buscados como texto e não como curinga
            $condicoes[] = "s.titulo LIKE :busca ESCAPE '!'";
            $params['busca'] = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filtros->busca) . '%';
        }

        $where = $condicoes === [] ? '' : ' WHERE ' . implode(' AND ', $condicoes);

        // Total para a paginação (mesmos filtros, sem LIMIT)
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM solicitacoes s' . $where
        );
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // Coluna e direção vêm de listas fixas (FiltrosSolicitacao::ORDENACOES e asc/desc)
        $coluna = FiltrosSolicitacao::ORDENACOES[$filtros->ordenarPor];
        $direcao = $filtros->direcao === 'asc' ? 'ASC' : 'DESC';

        $stmt = $this->pdo->prepare(
            self::SELECT_BASE . $where . " ORDER BY {$coluna} {$direcao}, s.id {$direcao} LIMIT :limite OFFSET :offset"
        );

        foreach ($params as $nome => $valor) {
            $stmt->bindValue($nome, $valor);
        }
        $stmt->bindValue('limite', $filtros->porPagina, PDO::PARAM_INT);
        $stmt->bindValue('offset', $filtros->offset(), PDO::PARAM_INT);
        $stmt->execute();

        return ['itens' => $stmt->fetchAll(), 'total' => $total];
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

    /**
     * Muda o status somente se ele ainda for $statusAtual (controle otimista de concorrência).
     * Retorna false quando outra pessoa alterou a solicitação antes.
     */
    public function mudarStatus(int $id, int $statusAtual, int $statusNovo, int $atendenteId, bool $concluir): bool
    {
        $sql = 'UPDATE solicitacoes
                   SET status_id = :status_novo,
                       atendente_id = COALESCE(atendente_id, :atendente_id)';

        if ($concluir) {
            $sql .= ', concluido_em = NOW()';
        }

        // O "AND status_id = :status_atual" é o controle otimista: só altera se ninguém mudou antes
        $sql .= ' WHERE id = :id AND status_id = :status_atual';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'status_atual' => $statusAtual,
            'status_novo' => $statusNovo,
            'atendente_id' => $atendenteId,
        ]);

        return $stmt->rowCount() === 1;
    }

    public function excluir(int $id): void
    {
        // O histórico é removido junto pelo ON DELETE CASCADE
        $stmt = $this->pdo->prepare('DELETE FROM solicitacoes WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
