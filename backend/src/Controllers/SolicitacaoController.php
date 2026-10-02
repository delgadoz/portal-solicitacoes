<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\UsuarioAutenticado;
use App\Exceptions\NotFoundException;
use App\Repositories\CategoriaRepository;
use App\Repositories\HistoricoRepository;
use App\Repositories\SolicitacaoRepository;
use App\Services\SolicitacaoService;
use App\Validators\SolicitacaoValidator;

final class SolicitacaoController
{
    public function __construct(private readonly SolicitacaoService $service)
    {
    }

    public static function criar(): self
    {
        $pdo = Database::connection();

        return new self(new SolicitacaoService(
            $pdo,
            new SolicitacaoRepository($pdo),
            new HistoricoRepository($pdo),
            new SolicitacaoValidator(new CategoriaRepository($pdo))
        ));
    }

    public function index(UsuarioAutenticado $usuario): never
    {
        Response::success($this->service->listar($usuario));
    }

    public function show(array $params, UsuarioAutenticado $usuario): never
    {
        Response::success($this->service->buscar(self::id($params), $usuario));
    }

    public function store(Request $request, UsuarioAutenticado $usuario): never
    {
        Response::success($this->service->criar($request->body, $usuario), 201);
    }

    public function update(Request $request, array $params, UsuarioAutenticado $usuario): never
    {
        Response::success($this->service->atualizar(self::id($params), $request->body, $usuario));
    }

    public function destroy(array $params, UsuarioAutenticado $usuario): never
    {
        $this->service->excluir(self::id($params), $usuario);
        Response::noContent();
    }

    /**
     * O {id} da URL chega como texto; só aceita inteiros positivos.
     */
    private static function id(array $params): int
    {
        $id = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === false) {
            throw new NotFoundException('Solicitação não encontrada.');
        }

        return $id;
    }
}
