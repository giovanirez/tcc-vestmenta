<?php $page_js = 'fornecedores.js'; include 'includes/header.php'; ?>

<?php
// Dados Mockados - Fornecedores
$fornecedores = [
    ['id' => 1, 'nome' => 'Têxtil Sul S.A.', 'cnpj' => '12.345.678/0001-90', 'telefone' => '(11) 3333-4444', 'email' => 'contato@textilsul.com.br', 'logradouro' => 'Rua das Malhas', 'numero' => '123', 'bairro' => 'Centro', 'cidade' => 'São Paulo', 'uf' => 'SP'],
    ['id' => 2, 'nome' => 'Jeans & Cia Distribuidora', 'cnpj' => '98.765.432/0001-10', 'telefone' => '(47) 3222-1111', 'email' => 'vendas@jeanscia.com.br', 'logradouro' => 'Av. Industrial', 'numero' => '450', 'bairro' => 'Velha', 'cidade' => 'Blumenau', 'uf' => 'SC'],
    ['id' => 3, 'nome' => 'Couro Fino Importações', 'cnpj' => '45.678.901/0001-55', 'telefone' => '(51) 3444-5555', 'email' => 'import@courofino.com', 'logradouro' => 'Rodovia BR-116', 'numero' => 'Km 12', 'bairro' => 'Distrito Industrial', 'cidade' => 'Novo Hamburgo', 'uf' => 'RS']
];
?>

<div class="card">
    <h2><i class="fa-solid fa-truck-medical"></i> Novo Fornecedor</h2>
    <form action="#" method="POST" style="margin-top: 15px;">
        <div class="form-grid">
            <div class="form-group">
                <label>Nome da Empresa (Razão Social)</label>
                <input type="text" class="form-control" id="forn-razao" required placeholder="Ex: Têxtil Sul S.A.">
            </div>
            <div class="form-group">
                <label>CNPJ</label>
                <input type="text" class="form-control mono-value" id="forn-cnpj" required placeholder="00.000.000/0001-00" maxlength="18">
            </div>
            <div class="form-group">
                <label>Telefone</label>
                <input type="text" class="form-control" id="forn-telefone" required placeholder="(00) 0000-0000">
            </div>
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" class="form-control" id="forn-email" required placeholder="email@empresa.com">
            </div>
        </div>

        <h4 class="form-section-title"><i class="fa-solid fa-map-location-dot"></i> Endereço (opcional)</h4>
        <div class="form-grid">
            <div class="form-group" style="max-width: 160px;">
                <label>CEP</label>
                <input type="text" class="form-control mono-value" id="forn-cep" placeholder="00000-000" maxlength="9">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Rua / Logradouro</label>
                <input type="text" class="form-control" id="forn-logradouro" placeholder="Preenchido automaticamente pelo CEP">
            </div>
            <div class="form-group" style="max-width: 130px;">
                <label>Número</label>
                <input type="text" class="form-control" id="forn-numero" placeholder="Ex: 123">
            </div>
            <div class="form-group">
                <label>Complemento</label>
                <input type="text" class="form-control" id="forn-complemento" placeholder="Sala, galpão... (opcional)">
            </div>
            <div class="form-group">
                <label>Bairro</label>
                <input type="text" class="form-control" id="forn-bairro" placeholder="Preenchido automaticamente pelo CEP">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Cidade</label>
                <input type="text" class="form-control" id="forn-cidade" placeholder="Preenchido automaticamente pelo CEP">
            </div>
            <div class="form-group" style="max-width: 90px;">
                <label>UF</label>
                <input type="text" class="form-control mono-value" id="forn-uf" maxlength="2" placeholder="SP">
            </div>
        </div>

        <button type="submit" class="btn"><i class="fa-solid fa-save"></i> Guardar Fornecedor</button>
    </form>
</div>

<div class="card">
    <h2><i class="fa-solid fa-list"></i> Lista de Fornecedores</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Empresa</th>
                    <th>CNPJ</th>
                    <th>Contactos</th>
                    <th>Endereço</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($fornecedores as $forn): ?>
                <tr>
                    <td class="mono">#<?= $forn['id'] ?></td>
                    <td><strong><?= $forn['nome'] ?></strong></td>
                    <td class="mono"><?= $forn['cnpj'] ?></td>
                    <td>
                        <div><i class="fa-solid fa-phone text-muted" style="font-size: 0.8rem;"></i> <?= $forn['telefone'] ?></div>
                        <div><i class="fa-solid fa-envelope text-muted" style="font-size: 0.8rem;"></i> <?= $forn['email'] ?></div>
                    </td>
                    <td><small><?= $forn['logradouro'] ?>, <?= $forn['numero'] ?> - <?= $forn['bairro'] ?>, <?= $forn['cidade'] ?>/<?= $forn['uf'] ?></small></td>
                    <td>
                        <button class="btn-icon btn-icon--edit"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="btn-icon btn-icon--delete"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>