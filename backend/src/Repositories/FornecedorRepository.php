<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class FornecedorRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexao();
    }

    public function listarTodos(): array
    {
        return $this->pdo->query('SELECT * FROM fornecedores ORDER BY razao_social')->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->pdo->prepare('SELECT * FROM fornecedores WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $fornecedor = $consulta->fetch();
        return $fornecedor ?: null;
    }

    public function criar(array $dados): array
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO fornecedores
                (razao_social, cnpj, inscricao_estadual, telefone, email, cep, logradouro, numero, complemento, bairro, cidade, uf)
             VALUES
                (:razao_social, :cnpj, :inscricao_estadual, :telefone, :email, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :uf)
             RETURNING *'
        );
        $consulta->execute($this->parametros($dados));
        return $consulta->fetch();
    }

    public function atualizar(int $id, array $dados): array
    {
        $consulta = $this->pdo->prepare(
            'UPDATE fornecedores SET
                razao_social = :razao_social, cnpj = :cnpj, inscricao_estadual = :inscricao_estadual,
                telefone = :telefone, email = :email, cep = :cep, logradouro = :logradouro,
                numero = :numero, complemento = :complemento, bairro = :bairro, cidade = :cidade, uf = :uf
             WHERE id = :id
             RETURNING *'
        );
        $consulta->execute($this->parametros($dados) + ['id' => $id]);
        return $consulta->fetch();
    }

    public function alternarAtivo(int $id, bool $ativo): array
    {
        $consulta = $this->pdo->prepare('UPDATE fornecedores SET ativo = :ativo WHERE id = :id RETURNING *');
        // PDO_PGSQL exige o tipo explícito pra bool — via execute()
        // simples, "false" vira string vazia e o Postgres rejeita.
        $consulta->bindValue('id', $id, PDO::PARAM_INT);
        $consulta->bindValue('ativo', $ativo, PDO::PARAM_BOOL);
        $consulta->execute();
        return $consulta->fetch();
    }

    public function cnpjJaExiste(string $cnpj, ?int $ignorarId = null): bool
    {
        if ($ignorarId !== null) {
            $consulta = $this->pdo->prepare('SELECT 1 FROM fornecedores WHERE cnpj = :cnpj AND id != :id');
            $consulta->execute(['cnpj' => $cnpj, 'id' => $ignorarId]);
        } else {
            $consulta = $this->pdo->prepare('SELECT 1 FROM fornecedores WHERE cnpj = :cnpj');
            $consulta->execute(['cnpj' => $cnpj]);
        }
        return (bool) $consulta->fetchColumn();
    }

    private function parametros(array $dados): array
    {
        return [
            'razao_social' => $dados['razao_social'],
            'cnpj' => $dados['cnpj'],
            'inscricao_estadual' => $dados['inscricao_estadual'] ?: null,
            'telefone' => $dados['telefone'],
            'email' => $dados['email'],
            'cep' => $dados['cep'] ?: null,
            'logradouro' => $dados['logradouro'] ?: null,
            'numero' => $dados['numero'] ?: null,
            'complemento' => $dados['complemento'] ?: null,
            'bairro' => $dados['bairro'] ?: null,
            'cidade' => $dados['cidade'] ?: null,
            'uf' => $dados['uf'] ?: null,
        ];
    }
}
