<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class ProdutoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexao();
    }

    // Traz nome da categoria/fornecedor já resolvido — evita a tela
    // ter que cruzar três listas na mão pra montar a tabela.
    public function listarTodos(): array
    {
        return $this->pdo->query(
            'SELECT p.*, c.nome AS categoria_nome, f.razao_social AS fornecedor_nome
             FROM produtos p
             LEFT JOIN categorias c ON c.id = p.categoria_id
             LEFT JOIN fornecedores f ON f.id = p.fornecedor_id
             ORDER BY p.nome'
        )->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->pdo->prepare('SELECT * FROM produtos WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $produto = $consulta->fetch();
        return $produto ?: null;
    }

    // Usado hoje só pelo backend (a tela de produtos não cria — o
    // cadastro nasce em entradas.php quando isso for conectado).
    public function criar(array $dados): array
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO produtos (codigo_interno, nome, descricao, categoria_id, fornecedor_id, preco_custo, preco_venda, estoque_atual)
             VALUES (:codigo_interno, :nome, :descricao, :categoria_id, :fornecedor_id, :preco_custo, :preco_venda, 0)
             RETURNING *'
        );
        $consulta->execute([
            'codigo_interno' => $dados['codigo_interno'],
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?: null,
            'categoria_id' => $dados['categoria_id'] ?: null,
            'fornecedor_id' => $dados['fornecedor_id'] ?: null,
            'preco_custo' => $dados['preco_custo'] ?? 0,
            'preco_venda' => $dados['preco_venda'] ?? 0,
        ]);
        return $consulta->fetch();
    }

    // Só o que faz sentido editar na tela de produtos — preco_custo e
    // estoque_atual vêm de entradas/documentos, não de edição manual.
    public function atualizar(int $id, array $dados): array
    {
        $consulta = $this->pdo->prepare(
            'UPDATE produtos SET
                nome = :nome, descricao = :descricao, categoria_id = :categoria_id,
                fornecedor_id = :fornecedor_id, preco_venda = :preco_venda
             WHERE id = :id
             RETURNING *'
        );
        $consulta->execute([
            'id' => $id,
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?: null,
            'categoria_id' => $dados['categoria_id'] ?: null,
            'fornecedor_id' => $dados['fornecedor_id'] ?: null,
            'preco_venda' => $dados['preco_venda'],
        ]);
        return $consulta->fetch();
    }

    public function alternarAtivo(int $id, bool $ativo): array
    {
        $consulta = $this->pdo->prepare('UPDATE produtos SET ativo = :ativo WHERE id = :id RETURNING *');
        // PDO_PGSQL exige o tipo explícito pra bool — via execute()
        // simples, "false" vira string vazia e o Postgres rejeita.
        $consulta->bindValue('id', $id, PDO::PARAM_INT);
        $consulta->bindValue('ativo', $ativo, PDO::PARAM_BOOL);
        $consulta->execute();
        return $consulta->fetch();
    }

    public function codigoJaExiste(string $codigo, ?int $ignorarId = null): bool
    {
        if ($ignorarId !== null) {
            $consulta = $this->pdo->prepare('SELECT 1 FROM produtos WHERE codigo_interno = :codigo AND id != :id');
            $consulta->execute(['codigo' => $codigo, 'id' => $ignorarId]);
        } else {
            $consulta = $this->pdo->prepare('SELECT 1 FROM produtos WHERE codigo_interno = :codigo');
            $consulta->execute(['codigo' => $codigo]);
        }
        return (bool) $consulta->fetchColumn();
    }
}
