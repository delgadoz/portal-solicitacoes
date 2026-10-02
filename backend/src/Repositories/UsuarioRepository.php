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
}
