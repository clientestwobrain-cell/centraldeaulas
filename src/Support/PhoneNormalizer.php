<?php

declare(strict_types=1);

namespace CentralDeAulas\Support;

use InvalidArgumentException;

final class PhoneNormalizer
{
    public function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) < 10 || strlen($digits) > 15) {
            throw new InvalidArgumentException('Telefone invalido.');
        }

        return $digits;
    }
}
