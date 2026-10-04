<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\UsuarioAutenticado;
use App\Enums\Perfil;
use App\Exceptions\TooManyRequestsException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Repositories\LoginTentativaRepository;
use App\Repositories\UsuarioRepository;
use App\Validators\SenhaValidator;

final class AuthService
{
    /**
     * Hash de uma senha qualquer. Usado quando o usuário não existe, para que o password_verify
     * rode mesmo assim: a resposta leva o mesmo tempo e não revela quais usuários existem.
     */
    private const HASH_FICTICIO = '$2y$10$PL.p/jKEJJnSKg4VCiqvjuXp4U2xN1c9UwlivlogJGZRQJQDEcgXu';

    public function __construct(
        private readonly UsuarioRepository $usuarios,
        private readonly LoginTentativaRepository $tentativas,
        private readonly SenhaValidator $senhaValidator,
        private readonly int $maxTentativas,
        private readonly int $janelaMinutos
    ) {
    }

    /**
     * Troca a senha do usuário logado.
     *
     * A senha atual é exigida para que uma sessão esquecida aberta não baste para tomar a conta.
     * Erros de senha atual contam no mesmo limite do login (usuário + IP): com a sessão de outra
     * pessoa, ninguém consegue testar senhas à vontade por esta rota.
     */
    public function alterarSenha(UsuarioAutenticado $usuario, array $dados, string $ip, ?string $userAgent): void
    {
        $this->bloquearSeExcedeuTentativas($usuario->usuario, $ip);

        $senhas = $this->senhaValidator->validarTroca($dados);

        $hashAtual = $this->usuarios->buscarSenhaHash($usuario->id);
        if ($hashAtual === null || !password_verify($senhas['senha_atual'], $hashAtual)) {
            $this->tentativas->registrar($usuario->usuario, $ip, false, $userAgent);
            // 422 no campo, e não 401: o usuário está logado, só errou a senha atual
            throw new ValidationException(['senha_atual' => 'Senha atual incorreta.']);
        }

        $this->usuarios->atualizarSenha($usuario->id, password_hash($senhas['nova_senha'], PASSWORD_DEFAULT));
    }

    public function login(string $usuario, string $senha, string $ip, ?string $userAgent): UsuarioAutenticado
    {
        // 1. Bloqueio ANTES de conferir a senha: um atacante bloqueado não consegue mais testar senhas
        $this->bloquearSeExcedeuTentativas($usuario, $ip);

        // 2. Confere usuário, situação e senha
        $registro = $this->usuarios->buscarPorUsuario($usuario);
        $senhaConfere = password_verify($senha, $registro['senha_hash'] ?? self::HASH_FICTICIO);
        $valido = $registro !== null && (bool) $registro['ativo'] && $senhaConfere;

        // 3. Toda tentativa é registrada, válida ou não
        $this->tentativas->registrar($usuario, $ip, $valido, $userAgent);

        if (!$valido) {
            // Mensagem única para usuário inexistente, inativo ou senha errada
            throw new UnauthorizedException('Usuário ou senha inválidos.');
        }

        return new UsuarioAutenticado(
            (int) $registro['id'],
            $registro['nome'],
            $registro['usuario'],
            Perfil::from($registro['perfil'])
        );
    }

    private function bloquearSeExcedeuTentativas(string $usuario, string $ip): void
    {
        $falhas = $this->tentativas->contarFalhasRecentes($usuario, $ip, $this->janelaMinutos);

        if ($falhas >= $this->maxTentativas) {
            throw new TooManyRequestsException(
                "Muitas tentativas de login. Aguarde {$this->janelaMinutos} minutos e tente novamente."
            );
        }
    }
}
