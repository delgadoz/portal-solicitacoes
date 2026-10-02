<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ValidationException extends HttpException
{
    public function __construct(array $fields, string $message = 'Dados inválidos.')
    {
        parent::__construct(422, 'VALIDATION_ERROR', $message, $fields);
    }
}
