<?php
require_once __DIR__ . '/env.php';

// Conexão única com o Supabase (Postgres), reaproveitada por qualquer
// página/serviço que precisar dela. Lê tudo de variável de ambiente
// (via .env local ou variáveis reais do Cloud Run) — nenhuma senha
// fica escrita no código.
function conectar_banco(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host = getenv('DB_HOST');
    $porta = getenv('DB_PORT') ?: '5432';
    $banco = getenv('DB_NAME') ?: 'postgres';
    $usuario = getenv('DB_USER');
    $senha = getenv('DB_PASSWORD');

    if (!$host || !$usuario) {
        throw new RuntimeException(
            'Faltam variáveis de conexão com o banco. Copie .env.example para .env e preencha (local), ' .
            'ou configure as variáveis de ambiente no serviço (Cloud Run).'
        );
    }

    $dsn = "pgsql:host={$host};port={$porta};dbname={$banco};sslmode=require";

    $pdo = new PDO($dsn, $usuario, $senha, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}
