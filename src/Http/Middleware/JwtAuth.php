<?php
declare(strict_types=1);

namespace Nemr\Apex\Http\Middleware;

/**
 * Lightweight Zero-Dependency HMAC-SHA256 JWT Authenticator
 */
class JwtAuth
{
    public static function generate(array $payload, string $secret, int $expirySeconds = 86400): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload['iat'] = time();
        $payload['exp'] = time() + $expirySeconds;

        $base64Header = self::base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $base64Payload = self::base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));

        $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $secret, true);
        $base64Signature = self::base64UrlEncode($signature);

        return "{$base64Header}.{$base64Payload}.{$base64Signature}";
    }

    public static function verify(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$base64Header, $base64Payload, $base64Signature] = $parts;

        $expectedSig = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $secret, true);
        if (!hash_equals(self::base64UrlEncode($expectedSig), $base64Signature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    public static function middleware(string $secret): callable
    {
        return function ($request, $next) use ($secret) {
            $authHeader = $request['headers']['Authorization'] ?? $request['headers']['authorization'] ?? '';

            if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                http_response_code(401);
                return [
                    'error'   => 'Unauthorized',
                    'message' => 'Missing or invalid Authorization Bearer header'
                ];
            }

            $payload = self::verify($matches[1], $secret);
            if (!$payload) {
                http_response_code(401);
                return [
                    'error'   => 'Unauthorized',
                    'message' => 'Invalid or expired JWT token'
                ];
            }

            $request['user'] = $payload;
            return $next($request);
        };
    }

    protected static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }
}
