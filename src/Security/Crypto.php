<?php

declare(strict_types=1);

namespace CentralDeAulas\Security;

use CentralDeAulas\Core\Env;
use RuntimeException;

final class Crypto
{
    private const CIPHER = 'aes-256-gcm';

    private string $key;

    public function __construct(?string $key = null)
    {
        $this->key = $key ?? Env::get('APP_KEY', '');

        if ($this->key === '') {
            throw new RuntimeException('APP_KEY nao configurada.');
        }
    }

    public function encrypt(string $plainText): string
    {
        $ivLength = openssl_cipher_iv_length(self::CIPHER);

        if ($ivLength === false) {
            throw new RuntimeException('Cifra indisponivel para criptografia.');
        }

        $iv = random_bytes($ivLength);
        $tag = '';
        $encrypted = openssl_encrypt($plainText, self::CIPHER, $this->normalizedKey(), OPENSSL_RAW_DATA, $iv, $tag);

        if ($encrypted === false) {
            throw new RuntimeException('Falha ao criptografar valor.');
        }

        return base64_encode(json_encode([
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'value' => base64_encode($encrypted),
        ], JSON_THROW_ON_ERROR));
    }

    public function decrypt(string $payload): string
    {
        $decodedPayload = base64_decode($payload, true);

        if ($decodedPayload === false) {
            throw new RuntimeException('Payload criptografado invalido.');
        }

        $data = json_decode($decodedPayload, true, 512, JSON_THROW_ON_ERROR);
        $iv = base64_decode($data['iv'] ?? '', true);
        $tag = base64_decode($data['tag'] ?? '', true);
        $encrypted = base64_decode($data['value'] ?? '', true);

        if ($iv === false || $tag === false || $encrypted === false) {
            throw new RuntimeException('Payload criptografado incompleto.');
        }

        $plainText = openssl_decrypt($encrypted, self::CIPHER, $this->normalizedKey(), OPENSSL_RAW_DATA, $iv, $tag);

        if ($plainText === false) {
            throw new RuntimeException('Falha ao descriptografar valor.');
        }

        return $plainText;
    }

    public function fingerprint(string $value): string
    {
        return hash_hmac('sha256', $value, $this->key);
    }

    private function normalizedKey(): string
    {
        return hash('sha256', $this->key, true);
    }
}
