<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;
use ErrorException;
use Throwable;

final class ErrorHandler
{
    public static function register(): void
    {
        ini_set('display_errors', '0');

        // Converte warnings e notices do PHP em exceções
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler([self::class, 'handle']);
    }

    public static function handle(Throwable $e): void
    {
        if ($e instanceof HttpException) {
            Response::error($e->getErrorCode(), $e->getMessage(), $e->getStatusCode(), $e->getFields());
        }

        // Erro inesperado: registra o detalhe no log e devolve mensagem genérica
        error_log((string) $e);

        $isDev = ($_ENV['APP_ENV'] ?? 'prod') === 'dev';
        $message = $isDev ? $e->getMessage() : 'Erro interno do servidor.';

        Response::error('INTERNAL_ERROR', $message, 500);
    }
}
