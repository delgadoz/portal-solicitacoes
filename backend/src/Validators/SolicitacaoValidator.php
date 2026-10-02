<?php

declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\ValidationException;
use App\Repositories\CategoriaRepository;

final class SolicitacaoValidator
{
    public const TITULO_MIN = 5;
    public const TITULO_MAX = 150;
    public const DESCRICAO_MIN = 10;

    public function __construct(private readonly CategoriaRepository $categorias)
    {
    }

    /**
     * Valida os dados enviados pelo cliente e devolve só os campos aceitos, já normalizados.
     * Campos como status, solicitante e datas são ignorados: quem define é o servidor.
     *
     * @return array{titulo: string, descricao: string, categoria_id: int}
     */
    public function validar(array $dados): array
    {
        $erros = [];

        $titulo = is_string($dados['titulo'] ?? null) ? trim($dados['titulo']) : '';
        $descricao = is_string($dados['descricao'] ?? null) ? trim($dados['descricao']) : '';
        $categoriaId = filter_var($dados['categoria_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        $tamanhoTitulo = mb_strlen($titulo);
        if ($tamanhoTitulo < self::TITULO_MIN || $tamanhoTitulo > self::TITULO_MAX) {
            $erros['titulo'] = sprintf(
                'O título deve ter entre %d e %d caracteres.',
                self::TITULO_MIN,
                self::TITULO_MAX
            );
        }

        if (mb_strlen($descricao) < self::DESCRICAO_MIN) {
            $erros['descricao'] = sprintf('A descrição deve ter pelo menos %d caracteres.', self::DESCRICAO_MIN);
        }

        if ($categoriaId === false || !$this->categorias->existe($categoriaId)) {
            $erros['categoria_id'] = 'Selecione uma categoria válida.';
        }

        if ($erros !== []) {
            throw new ValidationException($erros);
        }

        return [
            'titulo' => $titulo,
            'descricao' => $descricao,
            'categoria_id' => $categoriaId,
        ];
    }
}
