<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Security\SecretEncryptorInterface;
use InvalidArgumentException;
use RuntimeException;

final readonly class SodiumSecretEncryptor implements SecretEncryptorInterface
{
    private string $key;

    public function __construct(string $appSecret)
    {
        $this->key = self::normalizeKey($appSecret);
    }

    public function encrypt(string $plaintext): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $this->key);

        return base64_encode($nonce . $ciphertext);
    }

    public function decrypt(string $ciphertext): string
    {
        $decoded = base64_decode($ciphertext, true);

        if ($decoded === false) {
            throw new InvalidArgumentException('Некорректный формат зашифрованных данных.');
        }

        $nonceLength = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;

        if (strlen($decoded) < $nonceLength) {
            throw new InvalidArgumentException('Некорректный формат зашифрованных данных.');
        }

        $nonce = substr($decoded, 0, $nonceLength);
        $encrypted = substr($decoded, $nonceLength);
        $plaintext = sodium_crypto_secretbox_open($encrypted, $nonce, $this->key);

        if ($plaintext === false) {
            throw new RuntimeException('Не удалось расшифровать данные.');
        }

        return $plaintext;
    }

    private static function normalizeKey(string $appSecret): string
    {
        if ($appSecret === '') {
            throw new InvalidArgumentException('APP_SECRET не задан.');
        }

        $decoded = base64_decode($appSecret, true);

        if ($decoded !== false && strlen($decoded) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return $decoded;
        }

        if (strlen($appSecret) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return $appSecret;
        }

        throw new InvalidArgumentException(
            'APP_SECRET должен быть строкой из 32 байт или base64 от 32 байт.',
        );
    }
}
