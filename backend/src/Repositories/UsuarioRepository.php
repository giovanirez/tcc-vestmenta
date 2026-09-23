<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class UsuarioRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexao();
    }

    public function buscarPorEmail(string $email): ?array
    {
        $consulta = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = :email');
        $consulta->execute(['email' => $email]);
        $usuario = $consulta->fetch();
        return $usuario ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = :id');
        $consulta->execute(['id' => $id]);
        $usuario = $consulta->fetch();
        return $usuario ?: null;
    }

    public function listarTodos(): array
    {
        return $this->pdo
            ->query('SELECT id, nome, email, papel, ativo, criado_em FROM usuarios ORDER BY nome')
            ->fetchAll();
    }

    public function criar(string $nome, string $email, string $senhaHash, string $papel): array
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO usuarios (nome, email, senha_hash, papel, ativo)
             VALUES (:nome, :email, :senha_hash, :papel, true)
             RETURNING id, nome, email, papel, ativo, criado_em'
        );
        $consulta->execute([
            'nome' => $nome,
            'email' => $email,
            'senha_hash' => $senhaHash,
            'papel' => $papel,
        ]);
        return $consulta->fetch();
    }

    public function atualizar(int $id, string $nome, string $email, string $papel): array
    {
        $consulta = $this->pdo->prepare(
            'UPDATE usuarios SET nome = :nome, email = :email, papel = :papel
             WHERE id = :id
             RETURNING id, nome, email, papel, ativo, criado_em'
        );
        $consulta->execute([
            'id' => $id,
            'nome' => $nome,
            'email' => $email,
            'papel' => $papel,
        ]);
        return $consulta->fetch();
    }

    public function redefinirSenha(int $id, string $senhaHash): void
    {
        $consulta = $this->pdo->prepare('UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :id');
        $consulta->execute(['id' => $id, 'senha_hash' => $senhaHash]);
    }

    // Nunca apaga de verdade — só ativa/desativa. Um usuário que já
    // registrou vendas não pode sumir do histórico (FK em vendas.usuario_id).
    public function alternarAtivo(int $id, bool $ativo): array
    {
        $consulta = $this->pdo->prepare(
            'UPDATE usuarios SET ativo = :ativo WHERE id = :id
             RETURNING id, nome, email, papel, ativo, criado_em'
        );
        // PDO_PGSQL não converte bool do PHP pro boolean do Postgres
        // sozinho via execute() — precisa dizer o tipo explicitamente,
        // senão "false" vira string vazia e o Postgres rejeita.
        $consulta->bindValue('id', $id, PDO::PARAM_INT);
        $consulta->bindValue('ativo', $ativo, PDO::PARAM_BOOL);
        $consulta->execute();
        return $consulta->fetch();
    }

    public function emailJaExiste(string $email, ?int $ignorarId = null): bool
    {
        if ($ignorarId !== null) {
            $consulta = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE email = :email AND id != :id');
            $consulta->execute(['email' => $email, 'id' => $ignorarId]);
        } else {
            $consulta = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE email = :email');
            $consulta->execute(['email' => $email]);
        }
        return (bool) $consulta->fetchColumn();
    }
}
