<?php

declare(strict_types=1);

namespace DDG\Enums;

use DDG\Exceptions\ValidationException;

enum ClassStatus: string
{
    case Disponivel = 'disponivel';
    case Encerrado = 'encerrado';

    public static function fromInput(mixed $value): self
    {
        if (!is_string($value)) {
            throw new ValidationException('Campo "status" deve ser uma string.');
        }
        $normalized = self::normalize($value);
        $status = self::tryFrom($normalized);
        if ($status === null) {
            throw new ValidationException(sprintf(
                'Status inválido: "%s". Valores aceitos: disponivel, encerrado.',
                $value
            ));
        }
        return $status;
    }

    private static function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        return match ($value) {
            'disponível', 'disponivel' => 'disponivel',
            'encerrado' => 'encerrado',
            default => $value,
        };
    }
}
