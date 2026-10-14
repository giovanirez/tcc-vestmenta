<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

// Categorias não têm tela de cadastro própria: alimentam o <select> de
// categoria do produto (produtos.php e entradas.php), que tem um botão
// "+" pra criar uma nova na hora.
class CategoriaRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexao();
    }

    // Só as categorias que algum produto realmente usa — uma categoria
    // criada e nunca usada (ou cujos produtos mudaram de categoria) não
    // fica poluindo o select.
    public function listarEmUso(): array
    {
        return $this->pdo->query(
            'SELECT DISTINCT c.id, c.nome
             FROM categorias c
             JOIN produtos p ON p.categoria_id = c.id
             ORDER BY c.nome'
        )->fetchAll();
    }

    // Comparação sem diferenciar maiúscula/minúscula: "vestidos" e
    // "Vestidos" são a mesma categoria.
    public function buscarPorNome(string $nome): ?array
    {
        $consulta = $this->pdo->prepare('SELECT id, nome FROM categorias WHERE lower(nome) = lower(:nome)');
        $consulta->execute(['nome' => $nome]);
        $categoria = $consulta->fetch();
        return $categoria ?: null;
    }

    public function criar(string $nome): array
    {
        $consulta = $this->pdo->prepare('INSERT INTO categorias (nome) VALUES (:nome) RETURNING id, nome');
        $consulta->execute(['nome' => $nome]);
        return $consulta->fetch();
    }
}
