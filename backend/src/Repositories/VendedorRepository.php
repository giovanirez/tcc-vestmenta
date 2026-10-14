<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

// Vendedores não têm tela de cadastro própria: alimentam o select
// "Vendedor" do PDV, que tem um botão "+" pra adicionar na hora.
class VendedorRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexao();
    }

    // Só quem já aparece em alguma venda. Um vendedor criado pelo "+"
    // passa a aparecer pra todo mundo a partir da primeira venda dele.
    public function listarComVendas(): array
    {
        return $this->pdo->query(
            'SELECT DISTINCT ve.id, ve.nome
             FROM vendedores ve
             JOIN vendas v ON v.vendedor_id = ve.id
             ORDER BY ve.nome'
        )->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->pdo->prepare('SELECT id, nome FROM vendedores WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $vendedor = $consulta->fetch();
        return $vendedor ?: null;
    }

    // Mesmo critério do índice único ux_vendedores_nome (lower(nome)).
    public function buscarPorNome(string $nome): ?array
    {
        $consulta = $this->pdo->prepare('SELECT id, nome FROM vendedores WHERE lower(nome) = lower(:nome)');
        $consulta->execute(['nome' => $nome]);
        $vendedor = $consulta->fetch();
        return $vendedor ?: null;
    }

    public function criar(string $nome): array
    {
        $consulta = $this->pdo->prepare('INSERT INTO vendedores (nome) VALUES (:nome) RETURNING id, nome');
        $consulta->execute(['nome' => $nome]);
        return $consulta->fetch();
    }
}
