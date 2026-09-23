<?php

namespace App\Support;

use App\Config\Env;

// Implementação mínima de JWT (HS256) — sem depender de biblioteca
// externa. Um JWT é só três pedaços em Base64URL separados por ".":
// cabeçalho.payload.assinatura. A assinatura garante que ninguém
// alterou o payload sem saber o segredo (JWT_SECRET no .env).
class Jwt
{
    private static function segredo(): string
    {
        $segredo = Env::obter('JWT_SECRET');
        if (!$segredo) {
            throw new \RuntimeException('JWT_SECRET não configurado no .env.');
        }
        return $segredo;
    }

    private static function base64UrlEncode(string $dado): string
    {
        return rtrim(strtr(base64_encode($dado), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $dado): string
    {
        $preenchido = str_pad($dado, strlen($dado) % 4 === 0 ? strlen($dado) : strlen($dado) + (4 - strlen($dado) % 4), '=');
        return base64_decode(strtr($preenchido, '-_', '+/'));
    }

    // $payload deve incluir 'exp' (timestamp unix de expiração) —
    // quem chama decide por quanto tempo o token vale.
    public static function gerar(array $payload): string
    {
        $cabecalho = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $corpo = self::base64UrlEncode(json_encode($payload));
        $assinatura = self::base64UrlEncode(hash_hmac('sha256', "{$cabecalho}.{$corpo}", self::segredo(), true));

        return "{$cabecalho}.{$corpo}.{$assinatura}";
    }

    // Retorna o payload decodificado se o token for válido e não
    // tiver expirado, ou null caso contrário. Nunca lança exceção —
    // quem chama trata "null" como "não autenticado".
    public static function validar(string $token): ?array
    {
        $partes = explode('.', $token);
        if (count($partes) !== 3) {
            return null;
        }
        [$cabecalho, $corpo, $assinaturaRecebida] = $partes;

        $assinaturaEsperada = self::base64UrlEncode(hash_hmac('sha256', "{$cabecalho}.{$corpo}", self::segredo(), true));
        if (!hash_equals($assinaturaEsperada, $assinaturaRecebida)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($corpo), true);
        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && time() >= (int) $payload['exp']) {
            return null;
        }

        return $payload;
    }
}
