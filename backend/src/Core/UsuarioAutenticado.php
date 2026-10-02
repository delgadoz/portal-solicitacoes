<?php

declare(strict_types=1);

namespace App\Core;

use App\Enums\Perfil;

/**
 * Dados do usuário logado, guardados na sessão e repassados às rotas protegidas.
 */
final readonly class UsuarioAutenticado
{
    public function __construct(
        public int $id,
        public string $nome,
        public string $usuario,
        public Perfil $perfil
    ) {
    }

    public function isAtendente(): bool
    {
        return $this->perfil === Perfil::Atendente;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'usuario' => $this->usuario,
            'perfil' => $this->perfil->value,
        ];
    }

    public static function fromArray(array $dados): self
    {
        return new self(
            (int) $dados['id'],
            (string) $dados['nome'],
            (string) $dados['usuario'],
            Perfil::from((string) $dados['perfil'])
        );
    }
}
