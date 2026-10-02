<?php

declare(strict_types=1);

namespace App\Exceptions;

final class TooManyRequestsException extends HttpException
{
    public function __construct(
        string $message = 'Muitas tentativas. Aguarde alguns minutos e tente novamente.'
    ) {
        parent::__construct(429, 'TOO_MANY_REQUESTS', $message);
    }
}
