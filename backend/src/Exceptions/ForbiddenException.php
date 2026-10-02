<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'Você não tem permissão para realizar esta ação.')
    {
        parent::__construct(403, 'FORBIDDEN', $message);
    }
}
