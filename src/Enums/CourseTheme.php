<?php

declare(strict_types=1);

namespace DDG\Enums;

use DDG\Exceptions\ValidationException;

enum CourseTheme: string
{
    case Inovacao = 'inovacao';
    case Tecnologia = 'tecnologia';
    case Marketing = 'marketing';
    case Empreendedorismo = 'empreendedorismo';
    case Agro = 'agro';

    public static function fromInput(mixed $value): self
    {
        if (!is_string($value)) {
            throw new ValidationException('Campo "theme" deve ser uma string.');
        }
        $normalized = self::normalize($value);
        $theme = self::tryFrom($normalized);
        if ($theme === null) {
            throw new ValidationException(sprintf(
                'Tema inválido: "%s". Valores aceitos: %s.',
                $value,
                implode(', ', array_map(fn (self $c) => $c->value, self::cases()))
            ));
        }
        return $theme;
    }

    private static function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $map = [
            'inovação' => 'inovacao',
            'inovacao' => 'inovacao',
        ];
        return $map[$value] ?? $value;
    }
}
