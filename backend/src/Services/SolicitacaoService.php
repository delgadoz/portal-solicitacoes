<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\UsuarioAutenticado;
use App\Enums\StatusSolicitacao;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Repositories\HistoricoRepository;
use App\Repositories\SolicitacaoRepository;
use App\Validators\SolicitacaoValidator;
use PDO;
use Throwable;

final class SolicitacaoService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly SolicitacaoRepository $solicitacoes,
        private readonly HistoricoRepository $historico,
        private readonly SolicitacaoValidator $validator
    ) {
    }

    public function listar(UsuarioAutenticado $usuario): array
    {
        return $this->solicitacoes->listar($this->filtroDeDono($usuario));
    }

    /**
     * Solicitante só encontra as próprias; para ele, a solicitação de outro "não existe" (404).
     */
    public function buscar(int $id, UsuarioAutenticado $usuario): array
    {
        $solicitacao = $this->solicitacoes->buscarPorId($id, $this->filtroDeDono($usuario));

        if ($solicitacao === null) {
            throw new NotFoundException('Solicitação não encontrada.');
        }

        return $solicitacao;
    }

    public function criar(array $dados, UsuarioAutenticado $usuario): array
    {
        if ($usuario->isAtendente()) {
            throw new ForbiddenException('Apenas solicitantes podem abrir solicitações.');
        }

        $valido = $this->validator->validar($dados);

        // Solicitação e primeiro registro do histórico são gravados juntos, ou nenhum dos dois
        $this->pdo->beginTransaction();

        try {
            $id = $this->solicitacoes->criar(
                $valido['titulo'],
                $valido['descricao'],
                $valido['categoria_id'],
                StatusSolicitacao::Aberto->value,
                $usuario->id
            );

            $this->historico->registrar(
                $id,
                null,
                StatusSolicitacao::Aberto->value,
                $usuario->id,
                'Solicitação criada.'
            );

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $this->buscar($id, $usuario);
    }

    public function atualizar(int $id, array $dados, UsuarioAutenticado $usuario): array
    {
        $solicitacao = $this->buscar($id, $usuario);
        $this->garantirQuePodeAlterar($solicitacao, $usuario);

        $valido = $this->validator->validar($dados);
        $this->solicitacoes->atualizar($id, $valido['titulo'], $valido['descricao'], $valido['categoria_id']);

        return $this->buscar($id, $usuario);
    }

    public function excluir(int $id, UsuarioAutenticado $usuario): void
    {
        $solicitacao = $this->buscar($id, $usuario);
        $this->garantirQuePodeAlterar($solicitacao, $usuario);

        $this->solicitacoes->excluir($id);
    }

    /**
     * EXERCÍCIO: regras para editar ou excluir uma solicitação.
     *
     * - Atendente não edita nem exclui solicitações → ForbiddenException (403)
     * - Só o próprio solicitante (dono) pode alterar  → ForbiddenException (403)
     * - Só é possível alterar enquanto o status for Aberto → ConflictException (409),
     *   com uma mensagem que explique o motivo ao usuário
     *
     * Dicas: $solicitacao['solicitante_id'] e $solicitacao['status_id'] vêm do banco;
     * compare com $usuario->id e com StatusSolicitacao::Aberto->value.
     */
    private function garantirQuePodeAlterar(array $solicitacao, UsuarioAutenticado $usuario): void
    {
        // TODO
        if ($usuario->isAtendente()) {
            throw new ForbiddenException('Você não tem permissão para executar esta ação.');
        }

        if ($usuario->id !== $solicitacao['solicitante_id']) {
            throw new ForbiddenException('Você não tem permissão para executar esta ação.');
        }

        if ($solicitacao['status_id'] !== StatusSolicitacao::Aberto->value) {
            throw new ConflictException('A solicitação precisa estar aberta para que a ação seja executada.');
        }
    }

    /**
     * Atendente vê todas (null = sem filtro); solicitante vê só as próprias.
     */
    private function filtroDeDono(UsuarioAutenticado $usuario): ?int
    {
        return $usuario->isAtendente() ? null : $usuario->id;
    }
}
