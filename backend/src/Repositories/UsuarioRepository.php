<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UsuarioRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function buscarPorUsuario(string $usuario): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, usuario, senha_hash, perfil, ativo
               FROM usuarios
              WHERE usuario = :usuario'
        );
        $stmt->execute(['usuario' => $usuario]);

        $registro = $stmt->fetch();

        return $registro === false ? null : $registro;
    }

    public function buscarSenhaHash(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT senha_hash FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $hash = $stmt->fetchColumn();

        return $hash === false ? null : (string) $hash;
    }

    public function atualizarSenha(int $id, string $senhaHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :id');
        $stmt->execute(['senha_hash' => $senhaHash, 'id' => $id]);
    }
}
