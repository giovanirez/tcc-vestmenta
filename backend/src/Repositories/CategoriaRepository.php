<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

// Só leitura — categorias não têm tela de cadastro própria, servem
// pra alimentar o <select> de categoria no formulário de produto.
class CategoriaRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexao();
    }

    public function listarTodas(): array
    {
        return $this->pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();
    }
}
