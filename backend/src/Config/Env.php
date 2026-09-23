<?php

namespace App\Config;

// Carrega o .env da raiz do projeto pra dentro de getenv()/$_ENV, só
// se essas variáveis ainda não existirem no ambiente real. No Cloud
// Run, as variáveis já vêm prontas do próprio serviço — esse loader
// não encontra nada pra fazer lá e não atrapalha.
class Env
{
    private static bool $carregado = false;

    public static function carregar(): void
    {
        if (self::$carregado) {
            return;
        }
        self::$carregado = true;

        $caminho = __DIR__ . '/../../../.env';
        if (!file_exists($caminho)) {
            return;
        }

        foreach (file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
            $linha = trim($linha);
            if ($linha === '' || str_starts_with($linha, '#')) {
                continue;
            }

            [$chave, $valor] = array_pad(explode('=', $linha, 2), 2, '');
            $chave = trim($chave);
            $valor = trim($valor);

            if ($chave !== '' && getenv($chave) === false) {
                putenv("{$chave}={$valor}");
                $_ENV[$chave] = $valor;
            }
        }
    }

    public static function obter(string $chave, ?string $padrao = null): ?string
    {
        self::carregar();
        $valor = getenv($chave);
        return $valor === false ? $padrao : $valor;
    }
}
