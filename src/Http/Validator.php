<?php

declare(strict_types=1);

namespace CentralDeAulas\Http;

use InvalidArgumentException;

final class Validator
{
    public function requireString(array $input, string $field, int $maxLength = 255): string
    {
        $value = trim((string) ($input[$field] ?? ''));

        if ($value === '') {
            throw new InvalidArgumentException("Campo obrigatorio: {$field}.");
        }

        if (strlen($value) > $maxLength) {
            throw new InvalidArgumentException("Campo muito longo: {$field}.");
        }

        return $value;
    }

    public function requireEmail(array $input, string $field): string
    {
        $email = $this->requireString($input, $field, 190);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail invalido.');
        }

        return $email;
    }
}
