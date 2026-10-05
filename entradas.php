<?php $page_js = 'entradas.js'; include 'includes/header.php'; ?>

<div class="card">
    <h2><i class="fa-solid fa-box-open"></i> Nova Entrada de Produtos</h2>
    <p style="color: var(--ink-muted); margin-top: 5px;">Registre a entrada via Nota Fiscal ou acerto manual de estoque.</p>

    <div class="tab-switch" role="tablist" style="margin-top: 18px;">
        <button type="button" class="tab-switch-btn active" data-tab="nf">Via Nota Fiscal</button>
        <button type="button" class="tab-switch-btn" data-tab="manual">Manual</button>
    </div>

    <form id="ent-form" novalidate>

        <div id="tab-conteudo-nf">
            <h4 class="form-section-title"><i class="fa-solid fa-file-invoice"></i> Identificação da Nota Fiscal</h4>
            <div class="form-grid">
                <div class="form-group">
                    <label>Número da NF</label>
                    <input type="text" class="form-control" id="ent-numero-nf" placeholder="Deixe em branco se manual">
                </div>
                <div class="form-group">
                    <label>Série</label>
                    <input type="text" class="form-control" id="ent-serie" placeholder="Ex: 1">
                </div>
                <div class="form-group">
                    <label>Natureza da Operação</label>
                    <input type="text" class="form-control" id="ent-natureza" placeholder="Ex: Compra para revenda">
                </div>
                <div class="form-group">
                    <label>Data de Emissão</label>
                    <input type="date" class="form-control" id="ent-data-emissao">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Chave de Acesso</label>
                    <input type="text" class="form-control mono-value" id="ent-chave" maxlength="44" inputmode="numeric" placeholder="Preenchida automaticamente ao importar o XML">
                </div>
            </div>
        </div>

        <h4 class="form-section-title"><i class="fa-solid fa-truck"></i> Fornecedor</h4>
        <div class="form-grid">
            <div class="form-group">
                <label>Fornecedor</label>
                <select class="form-control" id="ent-fornecedor" required>
                    <option value="">Selecione o Fornecedor...</option>
                </select>
            </div>
            <div class="form-group">
                <label>CNPJ</label>
                <input type="text" class="form-control mono-value" id="ent-fornecedor-cnpj" readonly placeholder="Preenchido ao escolher o fornecedor">
            </div>
            <div class="form-group">
                <label>Inscrição Estadual</label>
                <input type="text" class="form-control mono-value" id="ent-fornecedor-ie" readonly placeholder="—">
            </div>
            <div class="form-group">
                <label>Data de Entrada</label>
                <input type="date" class="form-control" id="ent-data-entrada" required value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <h4 class="form-section-title"><i class="fa-solid fa-tags"></i> Itens da Entrada</h4>
        <p class="text-muted" style="font-size: 0.85rem; margin-top: -8px; margin-bottom: 14px;">Se o código não existir no catálogo, o produto é cadastrado automaticamente com estes dados.</p>
        <div class="item-add-grid" style="display: grid; grid-template-columns: 1fr 1.6fr 1fr 0.6fr 1fr 0.8fr 1fr auto; gap: 10px; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Código</label>
                <input type="text" class="form-control mono-value" id="item-codigo" list="produtos-existentes" placeholder="PROD-001 ou novo">
                <datalist id="produtos-existentes"></datalist>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Descrição</label>
                <input type="text" class="form-control" id="item-descricao" placeholder="Nome do produto">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Categoria</label>
                <select class="form-control" id="item-categoria">
                    <option value="">Selecione...</option>
                </select>
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
            <button type="button" class="btn" id="item-adicionar-btn" title="Adicionar item" style="background-color: var(--ink); height: 41px; margin-top: 0;"><i class="fa-solid fa-plus"></i></button>
        </div>

        <div class="table-responsive" style="margin-top: 15px;">
            <table class="table-stack-mobile" style="margin-top: 0;">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Qtd.</th>
                        <th>Custo Unit.</th>
                        <th>% Lucro</th>
                        <th>Vlr. de Venda</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="ent-itens">
                    <tr><td colspan="8" class="text-muted">Nenhum item adicionado.</td></tr>
                </tbody>
            </table>
        </div>

        <h4 class="form-section-title"><i class="fa-solid fa-truck-ramp-box"></i> Transporte</h4>
        <div class="form-grid">
            <div class="form-group">
                <label>Modalidade do Frete</label>
                <select class="form-control" id="ent-modalidade-frete">
                    <option value="9">Sem Frete</option>
                    <option value="0">Por conta do Emitente</option>
                    <option value="1">Por conta do Destinatário</option>
                    <option value="2">Por conta de Terceiros</option>
                </select>
            </div>
            <div class="form-group">
                <label>Transportadora</label>
                <input type="text" class="form-control" id="ent-transportadora" placeholder="Opcional">
            </div>
            <div class="form-group">
                <label>Placa do Veículo</label>
                <input type="text" class="form-control mono-value" id="ent-placa" maxlength="10" placeholder="Opcional">
            </div>
        </div>

        <h4 class="form-section-title"><i class="fa-solid fa-calculator"></i> Totais da Nota</h4>
        <div class="form-grid">
            <div class="form-group">
                <label>Valor dos Produtos (R$)</label>
                <input type="text" class="form-control mono-value" id="ent-valor-produtos" readonly value="0,00">
            </div>
            <div class="form-group">
                <label>Frete (R$)</label>
                <input type="number" step="0.01" min="0" class="form-control" data-total="valor_frete" placeholder="0,00">
            </div>
            <div class="form-group">
                <label>Seguro (R$)</label>
                <input type="number" step="0.01" min="0" class="form-control" data-total="valor_seguro" placeholder="0,00">
            </div>
            <div class="form-group">
                <label>Outras Despesas (R$)</label>
                <input type="number" step="0.01" min="0" class="form-control" data-total="valor_outras_despesas" placeholder="0,00">
            </div>
            <div class="form-group">
                <label>Desconto (R$)</label>
                <input type="number" step="0.01" min="0" class="form-control" data-total="valor_desconto" placeholder="0,00">
            </div>
            <div class="form-group">
                <label>Valor do ICMS (R$)</label>
                <input type="number" step="0.01" min="0" class="form-control" data-total="valor_icms" placeholder="0,00">
            </div>
            <div class="form-group">
                <label>Valor do IPI (R$)</label>
                <input type="number" step="0.01" min="0" class="form-control" data-total="valor_ipi" placeholder="0,00">
            </div>
        </div>

        <h4 class="form-section-title"><i class="fa-solid fa-note-sticky"></i> Informações Complementares</h4>
        <div class="form-group">
            <textarea class="form-control" id="ent-info-complementares" rows="2" placeholder="Observações da nota, se houver"></textarea>
        </div>

        <div style="margin-top: 10px; text-align: right;">
            <h3 style="margin-bottom: 15px;">Valor Total da Nota: <span class="text-moss mono-value" id="ent-valor-total">R$ 0,00</span></h3>
            <p id="ent-mensagem" class="text-rust" style="display: none; margin-bottom: 10px; font-size: 0.85rem;"></p>
            <button type="submit" class="btn" id="ent-submit-btn"><i class="fa-solid fa-check"></i> Processar Entrada</button>
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
            <tbody id="ent-historico">
                <tr><td colspan="7" class="text-muted">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>