<?php

namespace App\Config;

use PDO;
use RuntimeException;

class Database
{
    private static ?PDO $instancia = null;

    public static function conexao(): PDO
    {
        if (self::$instancia !== null) {
            return self::$instancia;
        }

        $host = Env::obter('DB_HOST');
        $porta = Env::obter('DB_PORT', '5432');
        $banco = Env::obter('DB_NAME', 'postgres');
        $usuario = Env::obter('DB_USER');
        $senha = Env::obter('DB_PASSWORD');

        if (!$host || !$usuario) {
            throw new RuntimeException('Faltam variáveis de conexão com o banco (DB_HOST/DB_USER). Confira o .env.');
        }

        $dsn = "pgsql:host={$host};port={$porta};dbname={$banco};sslmode=require";

        self::$instancia = new PDO($dsn, $usuario, $senha, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Por padrão o PDO faz DUAS idas ao banco por consulta (prepara
            // e depois executa). Com isto é uma só: SQL e parâmetros vão
            // juntos, mas ainda separados (os valores nunca são colados
            // no texto do SQL — continua protegido contra SQL injection).
            // Faz muita diferença porque a API (EUA) fica longe do banco
            // (São Paulo): cada ida custa ~100 ms.
            PDO::PGSQL_ATTR_DISABLE_PREPARES => true,
            // Reaproveita a conexão entre requisições: abrir uma nova (TLS
            // + autenticação até São Paulo) custa ~0,5 s a partir do Render.
            // Só liga onde o número de processos do Apache é limitado (o
            // Dockerfile da API limita a 6) — no XAMPP do Windows, com
            // 150 threads, poderia abrir conexões demais no Supabase. Se
            // uma requisição morrer no meio de uma transação, o PDO desfaz
            // a transação antes de devolver a conexão pro próximo uso.
            PDO::ATTR_PERSISTENT => Env::obter('DB_PERSISTENTE') === '1',
        ]);

        return self::$instancia;
    }

    // Roda $operacao dentro de uma transação: ou tudo grava, ou nada
    // grava. Usado em operações que mexem em várias tabelas de uma vez
    // (ex.: entrada + itens + produtos + documento de estoque) — se
    // qualquer passo falhar no meio, o banco não fica pela metade.
    //
    // Se já existe uma transação aberta (ex.: finalizar condicional chama
    // o VendaService, que também usa transacao()), só roda a operação
    // dentro dela — o PDO não suporta transação aninhada, e quem abriu a
    // de fora é quem decide o commit/rollback de tudo.
    public static function transacao(callable $operacao): mixed
    {
        $pdo = self::conexao();
        if ($pdo->inTransaction()) {
            return $operacao($pdo);
        }

        $pdo->beginTransaction();

        try {
            $resultado = $operacao($pdo);
            $pdo->commit();
            return $resultado;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
