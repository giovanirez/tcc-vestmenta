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

<style>
    /* Estilos específicos para os cards do Dashboard */
    .dashboard-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    .kpi-card {
        background: var(--card-bg);
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        display: flex;
        align-items: center;
        border-left: 4px solid var(--accent-color);
    }
    .kpi-icon {
        font-size: 2rem;
        color: var(--text-muted);
        margin-right: 15px;
        opacity: 0.7;
    }
    .kpi-info h3 { font-size: 1.5rem; color: var(--text-main); margin-bottom: 5px; }
    .kpi-info p { font-size: 0.9rem; color: var(--text-muted); margin: 0; }
</style>

<div class="dashboard-cards">
    <div class="kpi-card" style="border-left-color: #2ecc71;">
        <div class="kpi-icon"><i class="fa-solid fa-dollar-sign"></i></div>
        <div class="kpi-info">
            <h3>R$ <?= number_format($kpis['vendas_mes'], 2, ',', '.') ?></h3>
            <p>Vendas no Mês</p>
        </div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #f39c12;">
        <div class="kpi-icon"><i class="fa-solid fa-person-booth"></i></div>
        <div class="kpi-info">
            <h3><?= $kpis['condicionais_abertas'] ?></h3>
            <p>Condicionais Abertas</p>
        </div>
    </div>
    
    <div class="kpi-card" style="border-left-color: #e74c3c;">
        <div class="kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="kpi-info">
            <h3><?= $kpis['produtos_baixa'] ?></h3>
            <p>Produtos em Baixa</p>
        </div>
    </div>

    <div class="kpi-card" style="border-left-color: #3498db;">
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
                    <td>#<?= $venda['id'] ?></td>
                    <td><?= date('d/m/Y', strtotime($venda['data'])) ?></td>
                    <td><?= $venda['cliente'] ?></td>
                    <td><strong>R$ <?= number_format($venda['valor'], 2, ',', '.') ?></strong></td>
                    <td><span class="badge badge-success"><?= $venda['status'] ?></span></td>
                    <td>
                        <button class="btn-icon" title="Ver Recibo"><i class="fa-solid fa-file-invoice"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>