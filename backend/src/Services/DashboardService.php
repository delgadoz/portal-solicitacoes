<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\UsuarioAutenticado;
use App\Repositories\DashboardRepository;

final class DashboardService
{
    public function __construct(private readonly DashboardRepository $dashboard)
    {
    }

    /**
     * Indicadores no escopo do perfil: o atendente vê o geral; o solicitante, só as próprias.
     */
    public function obter(UsuarioAutenticado $usuario): array
    {
        $solicitanteId = $usuario->isAtendente() ? null : $usuario->id;

        $ind = $this->dashboard->indicadores($solicitanteId);

        // SUM e AVG devolvem NULL quando não há linhas; os casts transformam em 0
        $concluidas = (int) $ind['concluidas'];
        $noPrazo = (int) $ind['concluidas_no_prazo'];
        $tempoMedio = $ind['tempo_medio_minutos'] !== null
            ? round((float) $ind['tempo_medio_minutos'] / 60, 1)
            : null;

        return [
            'totais' => [
                'total' => (int) $ind['total'],
                'abertas' => (int) $ind['abertas'],
                'em_atendimento' => (int) $ind['em_atendimento'],
                'concluidas' => $concluidas,
            ],
            'sla' => [
                'vencidas' => (int) $ind['sla_vencidas'],
                'concluidas_no_prazo' => $noPrazo,
                'percentual_no_prazo' => $this->percentual($noPrazo, $concluidas),
            ],
            'tempo_medio_atendimento_horas' => $tempoMedio,
            'por_categoria' => $this->dashboard->porCategoria($solicitanteId),
        ];
    }

    /**
     * Percentual calculado da parte em relacao ao total. Retorna
     * null caso o total seja zero, para evirar excecao DivisionByZeroError
     */
    private function percentual(int $parte, int $total): ?float
    {
        return $total !== 0 ? round(($parte / $total) * 100, 1) : null;
    }
}
