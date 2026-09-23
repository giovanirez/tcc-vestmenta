<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class ClienteRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexao();
    }

    public function listarTodos(): array
    {
        return $this->pdo->query('SELECT * FROM clientes ORDER BY nome')->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->pdo->prepare('SELECT * FROM clientes WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $cliente = $consulta->fetch();
        return $cliente ?: null;
    }

    public function criar(array $dados): array
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO clientes
                (nome, telefone, cpf, email, cep, logradouro, numero, complemento, bairro, cidade, uf, limite_credito)
             VALUES
                (:nome, :telefone, :cpf, :email, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :uf, :limite_credito)
             RETURNING *'
        );
        $consulta->execute($this->parametros($dados));
        return $consulta->fetch();
    }

    public function atualizar(int $id, array $dados): array
    {
        $consulta = $this->pdo->prepare(
            'UPDATE clientes SET
                nome = :nome, telefone = :telefone, cpf = :cpf, email = :email, cep = :cep,
                logradouro = :logradouro, numero = :numero, complemento = :complemento,
                bairro = :bairro, cidade = :cidade, uf = :uf, limite_credito = :limite_credito
             WHERE id = :id
             RETURNING *'
        );
        $consulta->execute($this->parametros($dados) + ['id' => $id]);
        return $consulta->fetch();
    }

    public function cpfJaExiste(string $cpf, ?int $ignorarId = null): bool
    {
        if ($ignorarId !== null) {
            $consulta = $this->pdo->prepare('SELECT 1 FROM clientes WHERE cpf = :cpf AND id != :id');
            $consulta->execute(['cpf' => $cpf, 'id' => $ignorarId]);
        } else {
            $consulta = $this->pdo->prepare('SELECT 1 FROM clientes WHERE cpf = :cpf');
            $consulta->execute(['cpf' => $cpf]);
        }
        return (bool) $consulta->fetchColumn();
    }

    private function parametros(array $dados): array
    {
        return [
            'nome' => $dados['nome'],
            'telefone' => $dados['telefone'],
            'cpf' => $dados['cpf'] ?: null,
            'email' => $dados['email'] ?: null,
            'cep' => $dados['cep'] ?: null,
            'logradouro' => $dados['logradouro'] ?: null,
            'numero' => $dados['numero'] ?: null,
            'complemento' => $dados['complemento'] ?: null,
            'bairro' => $dados['bairro'] ?: null,
            'cidade' => $dados['cidade'] ?: null,
            'uf' => $dados['uf'] ?: null,
            'limite_credito' => $dados['limite_credito'] ?: null,
        ];
    }
}
