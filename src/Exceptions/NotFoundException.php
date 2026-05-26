<?php

declare(strict_types=1);

namespace DDG\Exceptions;

final class NotFoundException extends AppException
{
    public function httpStatus(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'not_found';
    }
}
