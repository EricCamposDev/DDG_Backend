<?php

declare(strict_types=1);

namespace DDG\Exceptions;

use RuntimeException;

abstract class AppException extends RuntimeException
{
    abstract public function httpStatus(): int;

    abstract public function errorCode(): string;
}
