<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\UsuarioAutenticado;
use App\Enums\StatusSolicitacao;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\HistoricoRepository;
use App\Repositories\SolicitacaoRepository;
use App\Validators\FiltrosSolicitacao;
use App\Validators\SolicitacaoValidator;
use PDO;
use Throwable;

final class SolicitacaoService
{
    private const OBSERVACAO_MAX = 500;

    public function __construct(
        private readonly PDO $pdo,
        private readonly SolicitacaoRepository $solicitacoes,
        private readonly HistoricoRepository $historico,
        private readonly SolicitacaoValidator $validator
    ) {
    }

    /**
     * @return array{itens: array, meta: array{page: int, per_page: int, total: int, total_pages: int}}
     */
    public function listar(FiltrosSolicitacao $filtros, UsuarioAutenticado $usuario): array
    {
        // Para o solicitante, o filtro de dono é sempre ele mesmo, ignorando qualquer solicitante_id enviado
        $resultado = $this->solicitacoes->listar($filtros, $this->filtroDeDono($usuario));

        return [
            'itens' => $resultado['itens'],
            'meta' => [
                'page' => $filtros->pagina,
                'per_page' => $filtros->porPagina,
                'total' => $resultado['total'],
                'total_pages' => (int) ceil($resultado['total'] / $filtros->porPagina),
            ],
        ];
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

    /**
     * Detalhes da solicitação com a linha do tempo de mudanças de status.
     */
    public function buscarComHistorico(int $id, UsuarioAutenticado $usuario): array
    {
        $solicitacao = $this->buscar($id, $usuario);
        $solicitacao['historico'] = $this->historico->listarPorSolicitacao($id);

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
     * Move a solicitação para o status de destino informado, seguindo a máquina de estados.
     *
     * O cliente informa o destino (e não "avance uma etapa"): assim, um clique duplo em
     * "Iniciar atendimento" não conclui a solicitação por engano — a segunda requisição
     * pede uma transição que já não é válida e recebe 409.
     */
    public function alterarStatus(int $id, array $dados, UsuarioAutenticado $usuario): array
    {
        if (!$usuario->isAtendente()) {
            throw new ForbiddenException('Apenas atendentes podem alterar o status.');
        }

        $destino = StatusSolicitacao::tryFrom((int) ($dados['status_id'] ?? 0));
        if ($destino === null) {
            throw new ValidationException(['status_id' => 'Informe um status válido.']);
        }

        $observacao = $dados['observacao'] ?? null;
        $observacao = is_string($observacao) && trim($observacao) !== '' ? trim($observacao) : null;
        if ($observacao !== null && mb_strlen($observacao) > self::OBSERVACAO_MAX) {
            throw new ValidationException([
                'observacao' => sprintf('A observação pode ter no máximo %d caracteres.', self::OBSERVACAO_MAX),
            ]);
        }

        $solicitacao = $this->buscar($id, $usuario);
        $atual = StatusSolicitacao::from((int) $solicitacao['status_id']);
        $permitido = $atual->proximo();

        if ($permitido === null) {
            throw new ConflictException('Solicitações concluídas não podem mudar de status.');
        }

        if ($destino !== $permitido) {
            throw new ConflictException(sprintf(
                'Transição inválida: de "%s" só é possível ir para "%s".',
                $atual->rotulo(),
                $permitido->rotulo()
            ));
        }

        $this->pdo->beginTransaction();

        try {
            $alterou = $this->solicitacoes->mudarStatus(
                $id,
                $atual->value,
                $destino->value,
                $usuario->id,
                $destino === StatusSolicitacao::Concluido
            );

            if (!$alterou) {
                throw new ConflictException('A solicitação foi alterada por outra pessoa. Atualize a página.');
            }

            $this->historico->registrar($id, $atual->value, $destino->value, $usuario->id, $observacao);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $this->buscarComHistorico($id, $usuario);
    }

    /**
     * Regras para editar ou excluir uma solicitação: só o solicitante dono, e só enquanto Aberta.
     */
    private function garantirQuePodeAlterar(array $solicitacao, UsuarioAutenticado $usuario): void
    {
        if ($usuario->isAtendente()) {
            throw new ForbiddenException('Você não tem permissão para executar esta ação.');
        }

        if ((int) $solicitacao['solicitante_id'] !== $usuario->id) {
            throw new ForbiddenException('Você não tem permissão para executar esta ação.');
        }

        if ((int) $solicitacao['status_id'] !== StatusSolicitacao::Aberto->value) {
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
