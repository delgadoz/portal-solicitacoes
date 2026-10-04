<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\StatusSolicitacao;
use PDO;

final class DashboardRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Totais por status, situação de SLA e tempo médio de atendimento, em uma única consulta.
     *
     * @param int|null $solicitanteId quando informado, considera só as solicitações desse solicitante
     */
    public function indicadores(?int $solicitanteId): array
    {
        // Os ids de status vêm do enum (inteiros fixos do código, nunca do usuário),
        // por isso podem ser interpolados com segurança.
        $aberto = StatusSolicitacao::Aberto->value;
        $emAtendimento = StatusSolicitacao::EmAtendimento->value;
        $concluido = StatusSolicitacao::Concluido->value;

        // Prazo de cada solicitação = data de abertura + SLA (em horas) da categoria
        $prazo = 'DATE_ADD(s.criado_em, INTERVAL c.sla_horas HOUR)';

        $sql = "SELECT COUNT(*) AS total,
                       SUM(s.status_id = {$aberto}) AS abertas,
                       SUM(s.status_id = {$emAtendimento}) AS em_atendimento,
                       SUM(s.status_id = {$concluido}) AS concluidas,
                       SUM(s.status_id <> {$concluido} AND NOW() > {$prazo}) AS sla_vencidas,
                       SUM(s.status_id = {$concluido} AND s.concluido_em <= {$prazo}) AS concluidas_no_prazo,
                       AVG(CASE WHEN s.status_id = {$concluido}
                                THEN TIMESTAMPDIFF(MINUTE, s.criado_em, s.concluido_em) END) AS tempo_medio_minutos
                  FROM solicitacoes s
                  JOIN categorias c ON c.id = s.categoria_id";

        $params = [];
        if ($solicitanteId !== null) {
            $sql .= ' WHERE s.solicitante_id = :solicitante_id';
            $params['solicitante_id'] = $solicitanteId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch();
    }

    /**
     * Quantidade de solicitações por categoria (inclusive categorias sem nenhuma).
     */
    public function porCategoria(?int $solicitanteId): array
    {
        // O filtro de dono fica no ON (e não no WHERE) para o LEFT JOIN manter as categorias zeradas
        $filtro = $solicitanteId !== null ? ' AND s.solicitante_id = :solicitante_id' : '';

        $stmt = $this->pdo->prepare(
            "SELECT c.id, c.nome, COUNT(s.id) AS total
               FROM categorias c
               LEFT JOIN solicitacoes s ON s.categoria_id = c.id{$filtro}
              GROUP BY c.id, c.nome
              ORDER BY c.nome"
        );
        $stmt->execute($solicitanteId !== null ? ['solicitante_id' => $solicitanteId] : []);

        return array_map(
            static fn (array $linha): array => [
                'id' => (int) $linha['id'],
                'nome' => $linha['nome'],
                'total' => (int) $linha['total'],
            ],
            $stmt->fetchAll()
        );
    }
}
