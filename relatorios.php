<?php $page_css = 'relatorios.css'; include 'includes/header.php'; ?>

<?php
// Dados Mockados - Relatórios
$vendas_por_categoria = [
    ['categoria' => 'Vestidos', 'valor' => 5200.00, 'percentual' => 45],
    ['categoria' => 'Calças', 'valor' => 3100.00, 'percentual' => 25],
    ['categoria' => 'Camisetas', 'valor' => 1800.00, 'percentual' => 15],
    ['categoria' => 'Acessórios', 'valor' => 1200.00, 'percentual' => 10],
    ['categoria' => 'Casacos', 'valor' => 600.00, 'percentual' => 5],
];

$top_produtos = [
    ['posicao' => 1, 'nome' => 'Vestido Floral Verão', 'qtd' => 42, 'receita' => 6715.80],
    ['posicao' => 2, 'nome' => 'Calça Jeans Skinny', 'qtd' => 38, 'receita' => 4936.20],
    ['posicao' => 3, 'nome' => 'Camiseta Básica Branca', 'qtd' => 85, 'receita' => 4241.50],
];
?>

<div class="card">
    <h2><i class="fa-solid fa-filter"></i> Filtros de Relatório</h2>
    <form action="#" method="GET" class="filter-bar" style="margin-top: 15px;">
        <div class="form-group" style="margin-bottom: 0;">
            <label>Data Inicial</label>
            <input type="date" class="form-control" value="2026-03-01">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Data Final</label>
            <input type="date" class="form-control" value="2026-03-17">
        </div>
        <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
            <label>Tipo de Relatório</label>
            <select class="form-control">
                <option>Visão Geral de Vendas</option>
                <option>Desempenho de Produtos</option>
                <option>Histórico de Clientes</option>
            </select>
        </div>
        <button type="submit" class="btn" style="height: 40px;"><i class="fa-solid fa-search"></i> Gerar</button>
    </form>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
    
    <div class="card">
        <h3><i class="fa-solid fa-chart-pie"></i> Vendas por Categoria</h3>
        <div style="margin-top: 20px;">
            <?php foreach($vendas_por_categoria as $cat): ?>
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <span><strong><?= $cat['categoria'] ?></strong></span>
                    <span class="mono-value">R$ <?= number_format($cat['valor'], 2, ',', '.') ?> (<?= $cat['percentual'] ?>%)</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?= $cat['percentual'] ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h3><i class="fa-solid fa-trophy text-brass"></i> Top 3 Produtos (Receita)</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produto</th>
                        <th>Qtd. Vendida</th>
                        <th>Receita Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($top_produtos as $prod): ?>
                    <tr>
                        <td class="mono"><strong><?= $prod['posicao'] ?>º</strong></td>
                        <td><?= $prod['nome'] ?></td>
                        <td class="mono"><?= $prod['qtd'] ?> un.</td>
                        <td class="mono text-moss"><strong>R$ <?= number_format($prod['receita'], 2, ',', '.') ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>