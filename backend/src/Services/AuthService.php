<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\UsuarioAutenticado;
use App\Enums\Perfil;
use App\Exceptions\TooManyRequestsException;
use App\Exceptions\UnauthorizedException;
use App\Repositories\LoginTentativaRepository;
use App\Repositories\UsuarioRepository;

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
        private readonly int $maxTentativas,
        private readonly int $janelaMinutos
    ) {
    }

    public function login(string $usuario, string $senha, string $ip, ?string $userAgent): UsuarioAutenticado
    {
        // 1. Bloqueio ANTES de conferir a senha: um atacante bloqueado não consegue mais testar senhas
        $falhas = $this->tentativas->contarFalhasRecentes($usuario, $ip, $this->janelaMinutos);

        if ($falhas >= $this->maxTentativas) {
            throw new TooManyRequestsException(
                "Muitas tentativas de login. Aguarde {$this->janelaMinutos} minutos e tente novamente."
            );
        }

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
}
