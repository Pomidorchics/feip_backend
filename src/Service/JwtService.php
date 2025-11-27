<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;

class JwtService
{
    private string $secretKey;

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function generateToken(User $user): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT'
        ]));

        $payload = $this->base64UrlEncode(json_encode([
            'user_id' => $user->getId(),
            'phone' => $user->getPhone(),
            'roles' => $user->getRoles(),
            'exp' => time() + 3600 // 1 hour
        ]));

        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $this->secretKey, true)
        );

        return "$header.$payload.$signature";
    }

    public function validateToken(string $token): bool
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        [$header, $payload, $signature] = $parts;

        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $this->secretKey, true)
        );

        return hash_equals($signature, $expectedSignature);
    }

    public function getPayload(string $token): ?array
    {
        if (!$this->validateToken($token)) {
            return null;
        }

        $parts = explode('.', $token);
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
