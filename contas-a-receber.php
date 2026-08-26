<?php include 'includes/header.php'; ?>

<?php
// Dados Mockados - Parcelas de vendas "Fiado"
// Status não é um campo gravado: é calculado aqui do mesmo jeito que
// seria calculado numa consulta real (ver database/README.md).
$parcelas_brutas = [
    ['cliente' => 'Carlos Mendes',    'venda_id' => 1039, 'numero' => 1, 'valor' => 99.80,  'vencimento' => '2026-04-16', 'pago_em' => '2026-04-15'],
    ['cliente' => 'Carlos Mendes',    'venda_id' => 1039, 'numero' => 2, 'valor' => 99.80,  'vencimento' => '2026-05-16', 'pago_em' => null],
    ['cliente' => 'Carlos Mendes',    'venda_id' => 1039, 'numero' => 3, 'valor' => 99.80,  'vencimento' => '2026-06-16', 'pago_em' => null],
    ['cliente' => 'Mariana Oliveira', 'venda_id' => 1043, 'numero' => 1, 'valor' => 150.00, 'vencimento' => date('Y-m-d', strtotime('+15 days')), 'pago_em' => null],
];

$hoje = date('Y-m-d');
$parcelas = array_map(function ($p) use ($hoje) {
    if ($p['pago_em']) {
        $p['status'] = 'Paga';
    } elseif ($p['vencimento'] < $hoje) {
        $p['status'] = 'Atrasada';
    } else {
        $p['status'] = 'Pendente';
    }
    return $p;
}, $parcelas_brutas);

$total_aberto = array_sum(array_column(array_filter($parcelas, fn($p) => $p['status'] !== 'Paga'), 'valor'));
$total_atrasado = array_sum(array_column(array_filter($parcelas, fn($p) => $p['status'] === 'Atrasada'), 'valor'));
$recebido = array_sum(array_column(array_filter($parcelas, fn($p) => $p['status'] === 'Paga'), 'valor'));
$clientes_com_fiado = count(array_unique(array_column(array_filter($parcelas, fn($p) => $p['status'] !== 'Paga'), 'cliente')));

$badge_por_status = ['Pendente' => 'badge-warning', 'Atrasada' => 'badge-danger', 'Paga' => 'badge-success'];
?>

<div class="dashboard-cards">
    <div class="tag-card tag-card--denim">
        <div class="kpi-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <div class="kpi-info">
            <h3>R$ <?= number_format($total_aberto, 2, ',', '.') ?></h3>
            <p>Total em Aberto</p>
        </div>
    </div>

    <div class="tag-card tag-card--rust">
        <div class="kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="kpi-info">
            <h3>R$ <?= number_format($total_atrasado, 2, ',', '.') ?></h3>
            <p>Total Atrasado</p>
        </div>
    </div>

    <div class="tag-card tag-card--moss">
        <div class="kpi-icon"><i class="fa-solid fa-sack-dollar"></i></div>
        <div class="kpi-info">
            <h3>R$ <?= number_format($recebido, 2, ',', '.') ?></h3>
            <p>Recebido</p>
        </div>
    </div>

    <div class="tag-card tag-card--brass">
        <div class="kpi-icon"><i class="fa-solid fa-users"></i></div>
        <div class="kpi-info">
            <h3><?= $clientes_com_fiado ?></h3>
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
            <tbody>
                <?php foreach ($parcelas as $p): ?>
                <tr>
                    <td><strong><?= $p['cliente'] ?></strong></td>
                    <td class="mono">#<?= $p['venda_id'] ?></td>
                    <td class="mono"><?= $p['numero'] ?>ª</td>
                    <td class="mono">R$ <?= number_format($p['valor'], 2, ',', '.') ?></td>
                    <td><?= date('d/m/Y', strtotime($p['vencimento'])) ?></td>
                    <td><span class="badge <?= $badge_por_status[$p['status']] ?>"><?= $p['status'] ?></span></td>
                    <td>
                        <?php if ($p['status'] !== 'Paga'): ?>
                        <button class="btn-icon btn-icon--view" title="Marcar como Paga"><i class="fa-solid fa-check"></i></button>
                        <?php else: ?>
                        <span class="text-muted mono" style="font-size: 0.8rem;">pago em <?= date('d/m/Y', strtotime($p['pago_em'])) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
