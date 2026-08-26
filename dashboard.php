<?php include 'includes/header.php'; ?>

<?php
// Dados Mockados - KPIs do Dashboard
$kpis = [
    'vendas_mes' => 15420.50,
    'condicionais_abertas' => 8,
    'produtos_baixa' => 5,
    'novos_clientes' => 12
];

// Dados Mockados - Últimas Vendas
$ultimas_vendas = [
    ['id' => 1042, 'cliente' => 'Mariana Oliveira', 'valor' => 159.90, 'data' => '2026-03-17', 'status' => 'Concluída'],
    ['id' => 1041, 'cliente' => 'Carlos Mendes', 'valor' => 349.50, 'data' => '2026-03-16', 'status' => 'Concluída'],
    ['id' => 1040, 'cliente' => 'Cliente Balcão', 'valor' => 89.90, 'data' => '2026-03-16', 'status' => 'Concluída'],
    ['id' => 1039, 'cliente' => 'Ana Paula', 'valor' => 210.00, 'data' => '2026-03-15', 'status' => 'Concluída']
];
?>

<div class="dashboard-cards">
    <div class="tag-card tag-card--moss">
        <div class="kpi-icon"><i class="fa-solid fa-sack-dollar"></i></div>
        <div class="kpi-info">
            <h3>R$ <?= number_format($kpis['vendas_mes'], 2, ',', '.') ?></h3>
            <p>Vendas no Mês</p>
        </div>
    </div>

    <div class="tag-card tag-card--brass">
        <div class="kpi-icon"><i class="fa-solid fa-person-booth"></i></div>
        <div class="kpi-info">
            <h3><?= $kpis['condicionais_abertas'] ?></h3>
            <p>Condicionais Abertas</p>
        </div>
    </div>

    <div class="tag-card tag-card--rust">
        <div class="kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="kpi-info">
            <h3><?= $kpis['produtos_baixa'] ?></h3>
            <p>Produtos em Baixa</p>
        </div>
    </div>

    <div class="tag-card tag-card--denim">
        <div class="kpi-icon"><i class="fa-solid fa-user-plus"></i></div>
        <div class="kpi-info">
            <h3><?= $kpis['novos_clientes'] ?></h3>
            <p>Novos Clientes</p>
        </div>
    </div>
</div>

<div class="card">
    <h2><i class="fa-solid fa-clock-rotate-left"></i> Últimas Vendas</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Valor Total</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($ultimas_vendas as $venda): ?>
                <tr>
                    <td class="mono">#<?= $venda['id'] ?></td>
                    <td><?= date('d/m/Y', strtotime($venda['data'])) ?></td>
                    <td><?= $venda['cliente'] ?></td>
                    <td><strong class="price">R$ <?= number_format($venda['valor'], 2, ',', '.') ?></strong></td>
                    <td><span class="badge badge-success"><?= $venda['status'] ?></span></td>
                    <td>
                        <button class="btn-icon btn-icon--view" title="Ver Recibo"><i class="fa-solid fa-file-invoice"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>