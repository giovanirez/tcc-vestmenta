<?php $page_js = 'entradas.js'; include 'includes/header.php'; ?>

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
    <p style="color: var(--ink-muted); margin-top: 5px;">Registre a entrada via Nota Fiscal ou acerto manual de estoque.</p>
    
    <form action="#" method="POST" style="margin-top: 10px;">

        <h4 class="form-section-title"><i class="fa-solid fa-file-invoice"></i> Identificação da Nota Fiscal</h4>
        <div class="form-grid">
            <div class="form-group">
                <label>Número da NF (Opcional)</label>
                <input type="text" class="form-control" placeholder="Deixe em branco se manual">
            </div>
            <div class="form-group">
                <label>Série</label>
                <input type="text" class="form-control" placeholder="Ex: 1">
            </div>
            <div class="form-group">
                <label>Natureza da Operação</label>
                <input type="text" class="form-control" placeholder="Ex: Compra para revenda">
            </div>
            <div class="form-group">
                <label>Data de Emissão</label>
                <input type="date" class="form-control">
            </div>
            <div class="form-group">
                <label>Data de Entrada</label>
                <input type="date" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Chave de Acesso</label>
                <input type="text" class="form-control mono-value" maxlength="44" placeholder="Preenchida automaticamente ao importar o XML">
            </div>
        </div>

        <h4 class="form-section-title"><i class="fa-solid fa-truck"></i> Fornecedor (Emitente)</h4>
        <div class="form-grid">
            <div class="form-group">
                <label>Fornecedor</label>
                <select class="form-control" required>
                    <option value="">Selecione o Fornecedor...</option>
                    <option>Têxtil Sul S.A.</option>
                    <option>Jeans & Cia Distribuidora</option>
                </select>
            </div>
            <div class="form-group">
                <label>CNPJ</label>
                <input type="text" class="form-control mono-value" placeholder="00.000.000/0001-00">
            </div>
            <div class="form-group">
                <label>Inscrição Estadual</label>
                <input type="text" class="form-control mono-value" placeholder="Opcional">
            </div>
        </div>

        <h4 class="form-section-title"><i class="fa-solid fa-tags"></i> Itens da Entrada</h4>
        <div style="display: grid; grid-template-columns: 0.8fr 1.6fr 0.6fr 1fr 0.8fr 1fr auto; gap: 10px; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Código</label>
                <select class="form-control" id="item-codigo">
                    <option value="PROD-001">PROD-001</option>
                    <option value="PROD-002">PROD-002</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Descrição</label>
                <input type="text" class="form-control" id="item-descricao" placeholder="Nome do produto">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Qtd.</label>
                <input type="number" class="form-control" id="item-qtd" value="1" min="1">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Custo Unit. (R$)</label>
                <input type="number" step="0.01" class="form-control" id="item-custo" placeholder="0,00">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>% Lucro</label>
                <input type="number" step="0.01" class="form-control" id="item-lucro" placeholder="Ex: 100">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Vlr. de Venda (R$)</label>
                <input type="number" step="0.01" class="form-control" id="item-venda" placeholder="0,00">
            </div>
            <button type="button" class="btn" style="background-color: var(--ink); height: 41px; margin-top: 0;"><i class="fa-solid fa-plus"></i></button>
        </div>

        <div class="table-responsive" style="margin-top: 15px;">
            <table style="margin-top: 0;">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descrição</th>
                        <th>Qtd.</th>
                        <th>Custo Unit.</th>
                        <th>% Lucro</th>
                        <th>Vlr. de Venda</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="mono">PROD-001</td>
                        <td>Camiseta Básica de Algodão</td>
                        <td class="mono">50</td>
                        <td class="mono">R$ 24,90</td>
                        <td class="mono">100%</td>
                        <td class="mono">R$ 49,80</td>
                        <td style="text-align: right;"><button type="button" class="btn-icon btn-icon--delete"><i class="fa-solid fa-xmark"></i></button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h4 class="form-section-title"><i class="fa-solid fa-truck-ramp-box"></i> Transporte</h4>
        <div class="form-grid">
            <div class="form-group">
                <label>Modalidade do Frete</label>
                <select class="form-control">
                    <option value="9">Sem Frete</option>
                    <option value="0">Por conta do Emitente</option>
                    <option value="1">Por conta do Destinatário</option>
                    <option value="2">Por conta de Terceiros</option>
                </select>
            </div>
            <div class="form-group">
                <label>Transportadora</label>
                <input type="text" class="form-control" placeholder="Opcional">
            </div>
            <div class="form-group">
                <label>Placa do Veículo</label>
                <input type="text" class="form-control mono-value" placeholder="Opcional">
            </div>
        </div>

        <h4 class="form-section-title"><i class="fa-solid fa-calculator"></i> Totais da Nota</h4>
        <div class="form-grid">
            <div class="form-group">
                <label>Valor dos Produtos (R$)</label>
                <input type="text" class="form-control mono-value" readonly value="0,00">
            </div>
            <div class="form-group">
                <label>Frete (R$)</label>
                <input type="number" step="0.01" class="form-control" placeholder="0,00">
            </div>
            <div class="form-group">
                <label>Seguro (R$)</label>
                <input type="number" step="0.01" class="form-control" placeholder="0,00">
            </div>
            <div class="form-group">
                <label>Outras Despesas (R$)</label>
                <input type="number" step="0.01" class="form-control" placeholder="0,00">
            </div>
            <div class="form-group">
                <label>Desconto (R$)</label>
                <input type="number" step="0.01" class="form-control" placeholder="0,00">
            </div>
            <div class="form-group">
                <label>Valor do ICMS (R$)</label>
                <input type="number" step="0.01" class="form-control" placeholder="0,00">
            </div>
            <div class="form-group">
                <label>Valor do IPI (R$)</label>
                <input type="number" step="0.01" class="form-control" placeholder="0,00">
            </div>
        </div>

        <h4 class="form-section-title"><i class="fa-solid fa-note-sticky"></i> Informações Complementares</h4>
        <div class="form-group">
            <textarea class="form-control" rows="2" placeholder="Observações da nota, se houver"></textarea>
        </div>

        <div style="margin-top: 10px; text-align: right;">
            <h3 style="margin-bottom: 15px;">Valor Total da Nota: <span class="text-moss mono-value">R$ 0,00</span></h3>
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
                    <td class="mono">#<?= $ent['id'] ?></td>
                    <td><?= date('d/m/Y', strtotime($ent['data'])) ?></td>
                    <td class="mono"><?= $ent['nf'] ?></td>
                    <td><strong><?= $ent['fornecedor'] ?></strong></td>
                    <td class="mono"><?= $ent['qtd_total'] ?></td>
                    <td class="mono">R$ <?= number_format($ent['valor_total'], 2, ',', '.') ?></td>
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