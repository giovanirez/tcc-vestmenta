<?php $page_js = 'dashboard.js'; include 'includes/header.php'; ?>

<div class="dashboard-cards">
    <div class="tag-card tag-card--moss">
        <div class="kpi-icon"><i class="fa-solid fa-sack-dollar"></i></div>
        <div class="kpi-info">
            <h3 id="kpi-vendas-mes">R$ 0,00</h3>
            <p>Vendas no Mês <small class="text-muted" id="kpi-qtd-vendas"></small></p>
        </div>
    </div>

    <div class="tag-card tag-card--brass">
        <div class="kpi-icon"><i class="fa-solid fa-person-booth"></i></div>
        <div class="kpi-info">
            <h3 id="kpi-condicionais">0</h3>
            <p>Condicionais Abertas</p>
        </div>
    </div>

    <div class="tag-card tag-card--rust">
        <div class="kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="kpi-info">
            <h3 id="kpi-produtos-baixa">0</h3>
            <p>Produtos em Baixa</p>
        </div>
    </div>

    <div class="tag-card tag-card--denim">
        <div class="kpi-icon"><i class="fa-solid fa-user-plus"></i></div>
        <div class="kpi-info">
            <h3 id="kpi-novos-clientes">0</h3>
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
            <tbody id="dash-ultimas-vendas">
                <tr><td colspan="6" class="text-muted">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card" id="dash-baixa-card" style="display: none;">
    <h2><i class="fa-solid fa-boxes-stacked"></i> Produtos em Baixa <small class="text-muted" id="dash-baixa-limite"></small></h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Produto</th>
                    <th>Estoque</th>
                </tr>
            </thead>
            <tbody id="dash-baixa-lista"></tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
