<?php

declare(strict_types=1);

namespace DDG\Exceptions;

final class ConflictException extends AppException
{
    public function httpStatus(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'conflict';
    }
}
