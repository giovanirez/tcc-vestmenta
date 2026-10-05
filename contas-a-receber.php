<?php $page_js = 'contas-a-receber.js'; include 'includes/header.php'; ?>


<div class="dashboard-cards">
    <div class="tag-card tag-card--denim">
        <div class="kpi-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <div class="kpi-info">
            <h3 id="cr-total-aberto">R$ 0,00</h3>
            <p>Total em Aberto</p>
        </div>
    </div>

    <div class="tag-card tag-card--rust">
        <div class="kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="kpi-info">
            <h3 id="cr-total-atrasado">R$ 0,00</h3>
            <p>Total Atrasado</p>
        </div>
    </div>
 
    <div class="tag-card tag-card--moss">
        <div class="kpi-icon"><i class="fa-solid fa-sack-dollar"></i></div>
        <div class="kpi-info">
            <h3 id="cr-recebido">R$ 0,00</h3>
            <p>Recebido</p>
        </div>
    </div>

    <div class="tag-card tag-card--brass">
        <div class="kpi-icon"><i class="fa-solid fa-users"></i></div>
        <div class="kpi-info">
            <h3 id="cr-clientes">0</h3>
            <p>Clientes com Fiado Ativo</p>
        </div>
    </div>
</div>

<div class="card">
    <h2><i class="fa-solid fa-list"></i> Parcelas</h2>
    <p class="text-muted" style="font-size: 0.85rem; margin-top: 4px;">"Atrasada" não é um status gravado — é calculado comparando o vencimento com a data de hoje.</p>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Venda</th>
                    <th>Parcela</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="cr-lista">
                <tr><td colspan="7" class="text-muted">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
