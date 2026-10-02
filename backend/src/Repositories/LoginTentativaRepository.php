<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Registros de tentativas de login, usado no rate limiting
 * e na auditoria de acesso.
 */

final class LoginTentativaRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Quantas tentativas FALHAS este usuário teve, a partir deste IP, nos últimos $minutos.
     */
    public function contarFalhasRecentes(string $usuario, string $ip, int $minutos): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total_tentativas
            FROM login_tentativas
            WHERE usuario_informado = :usuario
            AND sucesso = 0
            AND ip = :ip
            AND criado_em >= DATE_SUB(NOW(), INTERVAL :minutos MINUTE)'
        );

        $stmt->execute([
            'usuario' => $usuario,
            'ip' => $ip,
            'minutos' => $minutos
        ]);

        $totalTentativas = (int) $stmt->fetchColumn();

        return $totalTentativas;
    }

    /**
     * Registra uma tentativa de login (com sucesso ou não).
     */
    public function registrar(string $usuario, string $ip, bool $sucesso, ?string $userAgent): void
    {
        $userAgent = $userAgent !== null ? mb_substr($userAgent, 0, 255) : null;
        $stmt = $this->pdo->prepare(
            'INSERT INTO login_tentativas (
            usuario_informado, ip, sucesso, user_agent) VALUES (:usuario, :ip, :sucesso, :user_agent)'
        );

        $stmt->execute([
            'usuario' => $usuario,
            'ip' => $ip,
            'sucesso' => (int) $sucesso,
            'user_agent' => $userAgent
        ]);
    }
}
