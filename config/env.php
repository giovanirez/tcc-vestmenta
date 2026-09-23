<?php
// Carrega o .env pra dentro de getenv()/$_ENV, só se essas variáveis
// ainda não existirem no ambiente real. No Cloud Run, as variáveis já
// vêm prontas (definidas no deploy) — esse arquivo simplesmente não
// encontra nada pra fazer lá e não atrapalha. No XAMPP local, é ele
// quem preenche tudo a partir do arquivo .env na raiz do projeto.

function carregar_env(string $caminho): void
{
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

carregar_env(__DIR__ . '/../.env');
