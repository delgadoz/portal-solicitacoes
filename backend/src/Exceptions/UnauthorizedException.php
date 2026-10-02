<?php

declare(strict_types=1);

namespace App\Exceptions;

final class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Faça login para continuar.')
    {
        parent::__construct(401, 'UNAUTHORIZED', $message);
    }
}
