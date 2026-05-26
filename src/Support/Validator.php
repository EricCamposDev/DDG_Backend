<?php

declare(strict_types=1);

namespace DDG\Support;

use DDG\Exceptions\ValidationException;

final class Validator
{
    /**
     * @param array<string, mixed> $data
     */
    public static function requireString(array $data, string $field, int $maxLength = 255): string
    {
        if (!array_key_exists($field, $data)) {
            throw new ValidationException(sprintf('Campo "%s" é obrigatório.', $field));
        }
        $value = $data[$field];
        if (!is_string($value) || trim($value) === '') {
            throw new ValidationException(sprintf('Campo "%s" deve ser uma string não vazia.', $field));
        }
        $value = trim($value);
        if (mb_strlen($value) > $maxLength) {
            throw new ValidationException(sprintf('Campo "%s" não pode exceder %d caracteres.', $field, $maxLength));
        }
        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function requireText(array $data, string $field, int $maxLength = 5000): string
    {
        if (!array_key_exists($field, $data)) {
            throw new ValidationException(sprintf('Campo "%s" é obrigatório.', $field));
        }
        $value = $data[$field];
        if (!is_string($value) || trim($value) === '') {
            throw new ValidationException(sprintf('Campo "%s" deve ser uma string não vazia.', $field));
        }
        $value = trim($value);
        if (mb_strlen($value) > $maxLength) {
            throw new ValidationException(sprintf('Campo "%s" não pode exceder %d caracteres.', $field, $maxLength));
        }
        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function requireInt(array $data, string $field, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int
    {
        if (!array_key_exists($field, $data)) {
            throw new ValidationException(sprintf('Campo "%s" é obrigatório.', $field));
        }
        $value = $data[$field];
        if (is_string($value) && ctype_digit(ltrim($value, '-'))) {
            $value = (int) $value;
        }
        if (!is_int($value)) {
            throw new ValidationException(sprintf('Campo "%s" deve ser um inteiro.', $field));
        }
        if ($value < $min || $value > $max) {
            throw new ValidationException(sprintf('Campo "%s" deve estar entre %d e %d.', $field, $min, $max));
        }
        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function requireEmail(array $data, string $field): string
    {
        $value = self::requireString($data, $field, 320);
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new ValidationException(sprintf('Campo "%s" deve ser um e-mail válido.', $field));
        }
        return strtolower($value);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function requireUrl(array $data, string $field): string
    {
        $value = self::requireString($data, $field, 2048);
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new ValidationException(sprintf('Campo "%s" deve ser uma URL válida.', $field));
        }
        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function requireDate(array $data, string $field): string
    {
        $value = self::requireString($data, $field, 10);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new ValidationException(sprintf('Campo "%s" deve estar no formato YYYY-MM-DD.', $field));
        }
        return $value;
    }

    public static function requireIdParam(array $params, string $name = 'id'): int
    {
        if (!isset($params[$name]) || !ctype_digit((string) $params[$name])) {
            throw new ValidationException(sprintf('Parâmetro "%s" inválido.', $name));
        }
        return (int) $params[$name];
    }
}
