<?php

namespace App\Repositories;

use App\Config\Database;
use App\Support\Lote;
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
            'SELECT p.*, c.nome AS categoria_nome, f.razao_social AS fornecedor_nome,
                    -- Custo da compra mais recente (sugestão na próxima entrada);
                    -- sem nenhuma entrada ainda, cai no custo do cadastro.
                    COALESCE(
                        (SELECT ei.custo_unitario FROM entrada_itens ei
                         WHERE ei.produto_id = p.id ORDER BY ei.id DESC LIMIT 1),
                        p.preco_custo
                    ) AS ultimo_custo
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

    // Busca vários produtos travando as linhas (FOR UPDATE) até o fim
    // da transação: assim duas vendas simultâneas da mesma peça não
    // conseguem as duas "ver" o mesmo saldo e vender além do estoque.
    public function buscarParaMovimentar(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $consulta = $this->pdo->prepare("SELECT * FROM produtos WHERE id IN ({$marcadores}) FOR UPDATE");
        $consulta->execute(array_values($ids));

        $porId = [];
        foreach ($consulta->fetchAll() as $produto) {
            $porId[(int) $produto['id']] = $produto;
        }
        return $porId;
    }

    // Vários produtos por código numa ida só. Devolve [codigo => produto].
    // $bloquear trava as linhas até o fim da transação (FOR UPDATE).
    public function buscarPorCodigos(array $codigos, bool $bloquear = false): array
    {
        $codigos = array_values(array_unique($codigos));
        if (!$codigos) {
            return [];
        }
        $marcadores = implode(',', array_fill(0, count($codigos), '?'));
        $consulta = $this->pdo->prepare(
            "SELECT * FROM produtos WHERE codigo_interno IN ({$marcadores})" . ($bloquear ? ' FOR UPDATE' : '')
        );
        $consulta->execute($codigos);

        $porCodigo = [];
        foreach ($consulta->fetchAll() as $produto) {
            $porCodigo[$produto['codigo_interno']] = $produto;
        }
        return $porCodigo;
    }

    // Cadastra vários produtos novos numa ida só (usado pela entrada).
    // Devolve [codigo => produto criado].
    public function criarVarios(array $produtos): array
    {
        if (!$produtos) {
            return [];
        }
        $colunas = ['codigo_interno', 'nome', 'categoria_id', 'fornecedor_id', 'preco_custo', 'preco_venda'];
        [$valores, $parametros] = Lote::valores($produtos, $colunas);

        $consulta = $this->pdo->prepare(
            "INSERT INTO produtos (codigo_interno, nome, categoria_id, fornecedor_id, preco_custo, preco_venda)
             VALUES {$valores}
             RETURNING *"
        );
        $consulta->execute($parametros);

        $porCodigo = [];
        foreach ($consulta->fetchAll() as $produto) {
            $porCodigo[$produto['codigo_interno']] = $produto;
        }
        return $porCodigo;
    }

    public function buscarPorCodigo(string $codigo): ?array
    {
        $consulta = $this->pdo->prepare('SELECT * FROM produtos WHERE codigo_interno = :codigo');
        $consulta->execute(['codigo' => $codigo]);
        $produto = $consulta->fetch();
        return $produto ?: null;
    }

    // Entrada de um produto que já existe: descrição, categoria e preço
    // de venda editados na tela de entradas valem pro produto inteiro
    // (inclusive as peças que já estavam no estoque — a tela avisa antes).
    // O custo NÃO muda aqui: ele é de cada compra e fica em entrada_itens.
    //
    // Campo null = manter o atual. Categoria é tratada à parte porque
    // "sem categoria" (null) também é uma escolha válida.
    public function atualizarCadastroPelaEntrada(int $id, ?string $nome, bool $mudarCategoria, ?int $categoriaId, ?float $precoVenda): void
    {
        $consulta = $this->pdo->prepare(
            'UPDATE produtos SET
                nome = COALESCE(:nome, nome),
                categoria_id = CASE WHEN :mudar_categoria THEN CAST(:categoria_id AS BIGINT) ELSE categoria_id END,
                preco_venda = COALESCE(:preco_venda, preco_venda)
             WHERE id = :id'
        );
        $consulta->bindValue('id', $id, PDO::PARAM_INT);
        $consulta->bindValue('nome', $nome);
        $consulta->bindValue('mudar_categoria', $mudarCategoria, PDO::PARAM_BOOL);
        $consulta->bindValue('categoria_id', $categoriaId, $categoriaId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $consulta->bindValue('preco_venda', $precoVenda);
        $consulta->execute();
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
