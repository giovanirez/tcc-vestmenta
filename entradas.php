<?php include 'includes/header.php'; ?>

<?php
// Dados Mockados - Histórico de Entradas
$entradas = [
    ['id' => 5001, 'data' => '2026-03-16', 'nf' => '001.234.567', 'fornecedor' => 'Têxtil Sul S.A.', 'qtd_total' => 150, 'valor_total' => 3500.00, 'status' => 'Concluída'],
    ['id' => 5002, 'data' => '2026-03-14', 'nf' => 'Sem NF (Manual)', 'fornecedor' => 'Jeans & Cia Distribuidora', 'qtd_total' => 30, 'valor_total' => 1200.00, 'status' => 'Concluída'],
    ['id' => 5003, 'data' => '2026-03-10', 'nf' => '009.876.543', 'fornecedor' => 'Couro Fino Importações', 'qtd_total' => 15, 'valor_total' => 2800.00, 'status' => 'Pendente']
];
?>

<div class="card">
    <h2><i class="fa-solid fa-box-open"></i> Nova Entrada de Produtos</h2>
    <p style="color: var(--text-muted); margin-top: 5px;">Registre a entrada via Nota Fiscal ou acerto manual de estoque.</p>
    
    <form action="#" method="POST" style="margin-top: 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color); margin-bottom: 15px;">
            <div class="form-group">
                <label>Número da NF (Opcional)</label>
                <input type="text" class="form-control" placeholder="Deixe em branco se manual">
            </div>
            <div class="form-group">
                <label>Fornecedor</label>
                <select class="form-control" required>
                    <option value="">Selecione o Fornecedor...</option>
                    <option>Têxtil Sul S.A.</option>
                    <option>Jeans & Cia Distribuidora</option>
                </select>
            </div>
            <div class="form-group">
                <label>Data da Entrada</label>
                <input type="date" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <h4><i class="fa-solid fa-tags"></i> Itens da Entrada</h4>
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; align-items: end; margin-top: 10px;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Produto (Código Interno ou Nome)</label>
                <select class="form-control">
                    <option>PROD-001 - Camiseta Básica Branca</option>
                    <option>PROD-002 - Calça Jeans Skinny</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Quantidade</label>
                <input type="number" class="form-control" value="1" min="1">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Custo Unit. (R$)</label>
                <input type="number" step="0.01" class="form-control" placeholder="0,00">
            </div>
            <button type="button" class="btn" style="background-color: var(--primary-color); height: 41px;"><i class="fa-solid fa-plus"></i></button>
        </div>

        <div style="margin-top: 20px; text-align: right;">
            <h3 style="margin-bottom: 15px;">Total Previsto: <span style="color: #27ae60;">R$ 0,00</span></h3>
            <button type="submit" class="btn"><i class="fa-solid fa-check"></i> Processar Entrada</button>
        </div>
    </form>
</div>

<div class="card">
    <h2><i class="fa-solid fa-clock-rotate-left"></i> Histórico de Entradas</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Lote ID</th>
                    <th>Data</th>
                    <th>Nota Fiscal</th>
                    <th>Fornecedor</th>
                    <th>Qtd Peças</th>
                    <th>Valor Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($entradas as $ent): ?>
                <tr>
                    <td>#<?= $ent['id'] ?></td>
                    <td><?= date('d/m/Y', strtotime($ent['data'])) ?></td>
                    <td><?= $ent['nf'] ?></td>
                    <td><strong><?= $ent['fornecedor'] ?></strong></td>
                    <td><?= $ent['qtd_total'] ?></td>
                    <td>R$ <?= number_format($ent['valor_total'], 2, ',', '.') ?></td>
                    <td>
                        <span class="badge <?= $ent['status'] == 'Concluída' ? 'badge-success' : 'badge-warning' ?>">
                            <?= $ent['status'] ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>