<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class CategoriaRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listar(): array
    {
        return $this->pdo
            ->query('SELECT id, nome, sla_horas FROM categorias ORDER BY nome')
            ->fetchAll();
    }

    public function existe(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM categorias WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetchColumn() !== false;
    }
}
