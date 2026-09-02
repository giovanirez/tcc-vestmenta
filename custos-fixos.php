<?php include 'includes/header.php'; ?>

<?php
// Dados Mockados - Custos Fixos
$custos_fixos = [
    ['id' => 1, 'nome' => 'Aluguel da Loja',     'categoria' => 'Ocupação',  'valor' => 2800.00, 'vencimento' => 5,  'ativo' => true],
    ['id' => 2, 'nome' => 'Energia Elétrica',    'categoria' => 'Utilidades', 'valor' => 420.00, 'vencimento' => 10, 'ativo' => true],
    ['id' => 3, 'nome' => 'Internet e Telefone', 'categoria' => 'Utilidades', 'valor' => 180.00, 'vencimento' => 10, 'ativo' => true],
    ['id' => 4, 'nome' => 'Salário - Vendedora', 'categoria' => 'Pessoal',   'valor' => 1800.00, 'vencimento' => 5,  'ativo' => true],
    ['id' => 5, 'nome' => 'Contador',            'categoria' => 'Serviços', 'valor' => 350.00,  'vencimento' => 15, 'ativo' => true],
];

$total_mensal = array_sum(array_column(array_filter($custos_fixos, fn($c) => $c['ativo']), 'valor'));
?>

<div class="dashboard-cards">
    <div class="tag-card tag-card--rust">
        <div class="kpi-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div class="kpi-info">
            <h3>R$ <?= number_format($total_mensal, 2, ',', '.') ?></h3>
            <p>Total Fixo Mensal</p>
        </div>
    </div>
    <div class="tag-card tag-card--denim">
        <div class="kpi-icon"><i class="fa-solid fa-list-check"></i></div>
        <div class="kpi-info">
            <h3><?= count(array_filter($custos_fixos, fn($c) => $c['ativo'])) ?></h3>
            <p>Custos Ativos</p>
        </div>
    </div>
</div>

<div class="card">
    <h2><i class="fa-solid fa-money-bill-transfer"></i> Novo Custo Fixo</h2>
    <form action="#" method="POST" style="margin-top: 15px;">
        <div class="form-grid">
            <div class="form-group">
                <label>Nome do Custo</label>
                <input type="text" class="form-control" required placeholder="Ex: Aluguel da Loja">
            </div>
            <div class="form-group">
                <label>Categoria</label>
                <select class="form-control">
                    <option value="">Selecione...</option>
                    <option>Ocupação</option>
                    <option>Utilidades</option>
                    <option>Pessoal</option>
                    <option>Serviços</option>
                    <option>Outros</option>
                </select>
            </div>
            <div class="form-group">
                <label>Valor (R$)</label>
                <input type="number" step="0.01" class="form-control" required placeholder="0,00">
            </div>
            <div class="form-group">
                <label>Dia de Vencimento</label>
                <input type="number" class="form-control" min="1" max="31" required placeholder="Ex: 10">
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Observação</label>
                <input type="text" class="form-control" placeholder="Opcional">
            </div>
        </div>
        <button type="submit" class="btn"><i class="fa-solid fa-save"></i> Salvar Custo Fixo</button>
    </form>
</div>

<div class="card">
    <h2><i class="fa-solid fa-list"></i> Custos Fixos Cadastrados</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($custos_fixos as $c): ?>
                <tr>
                    <td><strong><?= $c['nome'] ?></strong></td>
                    <td><?= $c['categoria'] ?></td>
                    <td class="mono">R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                    <td class="mono">dia <?= $c['vencimento'] ?></td>
                    <td><span class="badge <?= $c['ativo'] ? 'badge-success' : 'badge-neutral' ?>"><?= $c['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
                    <td>
                        <button class="btn-icon btn-icon--edit" title="Editar"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="btn-icon btn-icon--delete" title="Desativar"><i class="fa-solid fa-ban"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
