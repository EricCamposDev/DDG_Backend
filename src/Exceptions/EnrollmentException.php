<?php

declare(strict_types=1);

namespace DDG\Exceptions;

final class EnrollmentException extends AppException
{
    public function httpStatus(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'enrollment_error';
    }
}
