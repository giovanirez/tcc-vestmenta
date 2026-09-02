<?php include 'includes/header.php'; ?>

<?php
// Dados Mockados - KPIs do Dashboard
$kpis = [
    'vendas_mes' => 15420.50,
    'condicionais_abertas' => 8,
    'produtos_baixa' => 5,
    'novos_clientes' => 12
];

require 'includes/mock-vendas.php';
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
                <?php foreach($vendas as $id => $venda): ?>
                <tr>
                    <td class="mono">#<?= $id ?></td>
                    <td><?= date('d/m/Y', strtotime($venda['data'])) ?></td>
                    <td><?= $venda['cliente'] ?></td>
                    <td><strong class="price">R$ <?= number_format($venda['total'], 2, ',', '.') ?></strong></td>
                    <td><span class="badge badge-success"><?= $venda['status'] ?></span></td>
                    <td>
                        <a class="btn-icon btn-icon--view" title="Ver Recibo" href="recibo.php?venda=<?= $id ?>" target="_blank" style="text-decoration: none;"><i class="fa-solid fa-file-invoice"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>