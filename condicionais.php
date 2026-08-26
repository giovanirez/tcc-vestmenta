<?php include 'includes/header.php'; ?>

<?php
// Dados Mockados - Condicionais
$condicionais = [
    ['id' => 101, 'data' => '2026-03-15', 'cliente' => 'Mariana Oliveira', 'produtos' => 'Vestido Floral, Cinto Fino', 'status' => 'Pendente', 'data_conclusao' => '-'],
    ['id' => 102, 'data' => '2026-03-12', 'cliente' => 'Carlos Mendes', 'produtos' => 'Camisa Social Azul', 'status' => 'Aprovado', 'data_conclusao' => '2026-03-14'],
    ['id' => 103, 'data' => '2026-03-10', 'cliente' => 'Ana Paula', 'produtos' => 'Saia Plissada', 'status' => 'Devolvido', 'data_conclusao' => '2026-03-11'],
];
?>

<div class="card">
    <h2><i class="fa-solid fa-person-booth"></i> Gestão de Condicionais (Malotes)</h2>
    <p style="color: var(--ink-muted); margin-top: 5px;">Controle de peças enviadas para experimentação dos clientes.</p>
    
    <div class="table-responsive" style="margin-top: 20px;">
        <table>
            <thead>
                <tr>
                    <th>Cód</th>
                    <th>Data Saída</th>
                    <th>Cliente</th>
                    <th>Peças</th>
                    <th>Status</th>
                    <th>Conclusão</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($condicionais as $cond):
                    $badgeClass = 'badge-warning';
                    if($cond['status'] == 'Aprovado') $badgeClass = 'badge-success';
                    if($cond['status'] == 'Devolvido') $badgeClass = 'badge-neutral';
                ?>
                <tr>
                    <td class="mono">#<?= $cond['id'] ?></td>
                    <td><?= date('d/m/Y', strtotime($cond['data'])) ?></td>
                    <td><strong><?= $cond['cliente'] ?></strong></td>
                    <td><?= $cond['produtos'] ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= $cond['status'] ?></span></td>
                    <td><?= $cond['data_conclusao'] !== '-' ? date('d/m/Y', strtotime($cond['data_conclusao'])) : '-' ?></td>
                    <td>
                        <button class="btn-icon btn-icon--view" title="Finalizar Venda"><i class="fa-solid fa-check"></i></button>
                        <button class="btn-icon btn-icon--delete" title="Registrar Devolução"><i class="fa-solid fa-rotate-left"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>