<?php

declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\ValidationException;
use DateTimeImmutable;

/**
 * Filtros, paginação e ordenação da listagem, já validados a partir da query string.
 */
final readonly class FiltrosSolicitacao
{
    public const POR_PAGINA_PADRAO = 10;
    public const POR_PAGINA_MAX = 50;

    /**
     * Ordenações aceitas → coluna SQL. Nomes de coluna não podem ser parâmetros de
     * prepared statement; por isso só valores desta lista chegam ao ORDER BY.
     */
    public const ORDENACOES = [
        'criado_em' => 's.criado_em',
        'titulo' => 's.titulo',
        'status' => 's.status_id',
        'categoria' => 'c.nome',
    ];

    public function __construct(
        public ?string $dataInicio,
        public ?string $dataFim,
        public ?int $categoriaId,
        public ?int $statusId,
        public ?int $solicitanteId,
        public ?string $busca,
        public int $pagina,
        public int $porPagina,
        public string $ordenarPor,
        public string $direcao
    ) {
    }

    public static function fromQuery(array $query): self
    {
        $erros = [];

        $dataInicio = self::data($query, 'data_inicio', $erros);
        $dataFim = self::data($query, 'data_fim', $erros);
        $categoriaId = self::inteiroOpcional($query, 'categoria_id', $erros);
        $statusId = self::inteiroOpcional($query, 'status_id', $erros);
        $solicitanteId = self::inteiroOpcional($query, 'solicitante_id', $erros);

        $busca = isset($query['q']) && is_string($query['q']) ? trim($query['q']) : '';
        $busca = $busca === '' ? null : mb_substr($busca, 0, 150);

        $pagina = self::inteiroOpcional($query, 'page', $erros) ?? 1;
        $porPagina = self::inteiroOpcional($query, 'per_page', $erros) ?? self::POR_PAGINA_PADRAO;
        $porPagina = min($porPagina, self::POR_PAGINA_MAX);

        $ordenarPor = (string) ($query['ordenar'] ?? 'criado_em');
        if (!array_key_exists($ordenarPor, self::ORDENACOES)) {
            $erros['ordenar'] = 'Ordenação inválida. Use: ' . implode(', ', array_keys(self::ORDENACOES)) . '.';
        }

        $direcao = strtolower((string) ($query['direcao'] ?? 'desc'));
        if (!in_array($direcao, ['asc', 'desc'], true)) {
            $erros['direcao'] = 'Direção inválida. Use asc ou desc.';
        }

        if (
            $dataInicio !== null &&
            $dataFim !== null &&
            $dataInicio > $dataFim
        ) {
            $erros['data_fim'] = 'A data final deve ser igual ou posterior à inicial.';
        }

        if ($erros !== []) {
            throw new ValidationException($erros, 'Filtros inválidos.');
        }

        return new self(
            $dataInicio,
            $dataFim,
            $categoriaId,
            $statusId,
            $solicitanteId,
            $busca,
            $pagina,
            $porPagina,
            $ordenarPor,
            $direcao
        );
    }

    public function offset(): int
    {
        return ($this->pagina - 1) * $this->porPagina;
    }

    private static function data(array $query, string $campo, array &$erros): ?string
    {
        $valor = $query[$campo] ?? '';
        if (!is_string($valor) || $valor === '') {
            return null;
        }

        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        if ($data === false || $data->format('Y-m-d') !== $valor) {
            $erros[$campo] = 'Data inválida. Use o formato AAAA-MM-DD.';
            return null;
        }

        return $valor;
    }

    private static function inteiroOpcional(array $query, string $campo, array &$erros): ?int
    {
        $valor = $query[$campo] ?? '';
        if ($valor === '') {
            return null;
        }

        $inteiro = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($inteiro === false) {
            $erros[$campo] = 'Informe um número inteiro positivo.';
            return null;
        }

        return $inteiro;
    }
}
