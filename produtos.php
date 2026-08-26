<?php include 'includes/header.php'; ?>

<?php
// Dados Mockados - Produtos
$produtos = [
    ['id' => 1, 'nome' => 'Camiseta Básica de Algodão', 'descricao' => '100% algodão, gola careca', 'preco' => 49.90, 'fornecedor' => 'Têxtil Sul', 'estoque' => 50, 'categoria' => 'Camisetas'],
    ['id' => 2, 'nome' => 'Calça Jeans Skinny', 'descricao' => 'Jeans com elastano', 'preco' => 129.90, 'fornecedor' => 'Jeans & Cia', 'estoque' => 15, 'categoria' => 'Calças'],
    ['id' => 3, 'nome' => 'Jaqueta de Couro PU', 'descricao' => 'Jaqueta preta com zíper', 'preco' => 249.90, 'fornecedor' => 'Couro Fino', 'estoque' => 5, 'categoria' => 'Casacos']
];
?>

<div class="card">
    <h2><i class="fa-solid fa-plus-circle"></i> Novo Produto</h2>
    <form action="#" method="POST" style="margin-top: 15px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label>Nome do Produto</label>
                <input type="text" class="form-control" required placeholder="Ex: Vestido Floral">
            </div>
            <div class="form-group">
                <label>Categoria</label>
                <input type="text" class="form-control" required placeholder="Ex: Vestidos">
            </div>
            <div class="form-group">
                <label>Preço (R$)</label>
                <input type="number" step="0.01" class="form-control" required>
            </div>
        </div>
        <button type="submit" class="btn"><i class="fa-solid fa-save"></i> Salvar Produto</button>
    </form>
</div>

<div class="card">
    <h2><i class="fa-solid fa-list"></i> Lista de Produtos</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Fornecedor</th>
                    <th>Estoque</th>
                    <th>Preço</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($produtos as $prod): ?>
                <tr>
                    <td class="mono">#<?= $prod['id'] ?></td>
                    <td><?= $prod['nome'] ?></td>
                    <td><?= $prod['categoria'] ?></td>
                    <td><?= $prod['fornecedor'] ?></td>
                    <td class="mono"><?= $prod['estoque'] ?> un.</td>
                    <td class="mono">R$ <?= number_format($prod['preco'], 2, ',', '.') ?></td>
                    <td>
                        <button class="btn-icon btn-icon--edit"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="btn-icon btn-icon--delete"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>