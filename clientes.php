<?php $page_js = 'clientes.js'; include 'includes/header.php'; ?>

<?php
// Dados Mockados - Clientes
$clientes = [
    ['id' => 101, 'nome' => 'Mariana Oliveira', 'cpf' => '111.222.333-44', 'telefone' => '(11) 98888-7777', 'email' => 'mariana.oliveira@email.com', 'logradouro' => 'Av. Central', 'numero' => '45', 'complemento' => 'Apto 302', 'bairro' => 'Centro', 'cidade' => 'São Paulo', 'uf' => 'SP'],
    ['id' => 102, 'nome' => 'Carlos Mendes', 'cpf' => '222.333.444-55', 'telefone' => '(11) 97777-6666', 'email' => 'carlos.mendes@email.com', 'logradouro' => 'Rua das Flores', 'numero' => '128', 'complemento' => '', 'bairro' => 'Bairro Alto', 'cidade' => 'São Paulo', 'uf' => 'SP'],
    ['id' => 103, 'nome' => 'Ana Paula Silva', 'cpf' => '333.444.555-66', 'telefone' => '(11) 96666-5555', 'email' => 'anapaula.silva@email.com', 'logradouro' => 'Praça da Liberdade', 'numero' => '10', 'complemento' => '', 'bairro' => 'Bela Vista', 'cidade' => 'São Paulo', 'uf' => 'SP']
];
?>

<div class="card">
    <h2><i class="fa-solid fa-user-plus"></i> Novo Cliente</h2>
    <form action="#" method="POST" style="margin-top: 15px;">
        <div class="form-grid">
            <div class="form-group">
                <label>Nome Completo</label>
                <input type="text" class="form-control" required placeholder="Ex: Maria João">
            </div>
            <div class="form-group">
                <label>Telefone</label>
                <input type="text" class="form-control" required placeholder="(00) 90000-0000">
            </div>
            <div class="form-group">
                <label>CPF</label>
                <input type="text" class="form-control mono-value" placeholder="Opcional">
            </div>
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" class="form-control" placeholder="Opcional">
            </div>
            <div class="form-group">
                <label>Limite de Crédito p/ Fiado (R$)</label>
                <input type="number" step="0.01" class="form-control" placeholder="Deixe em branco se não vende fiado">
            </div>
        </div>

        <h4 class="form-section-title"><i class="fa-solid fa-map-location-dot"></i> Endereço (opcional)</h4>
        <div class="form-grid">
            <div class="form-group" style="max-width: 160px;">
                <label>CEP</label>
                <input type="text" class="form-control mono-value" id="cli-cep" placeholder="00000-000" maxlength="9">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Rua / Logradouro</label>
                <input type="text" class="form-control" id="cli-logradouro" placeholder="Preenchido automaticamente pelo CEP">
            </div>
            <div class="form-group" style="max-width: 130px;">
                <label>Número</label>
                <input type="text" class="form-control" id="cli-numero" placeholder="Ex: 45">
            </div>
            <div class="form-group">
                <label>Complemento</label>
                <input type="text" class="form-control" id="cli-complemento" placeholder="Apto, bloco... (opcional)">
            </div>
            <div class="form-group">
                <label>Bairro</label>
                <input type="text" class="form-control" id="cli-bairro" placeholder="Preenchido automaticamente pelo CEP">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Cidade</label>
                <input type="text" class="form-control" id="cli-cidade" placeholder="Preenchido automaticamente pelo CEP">
            </div>
            <div class="form-group" style="max-width: 90px;">
                <label>UF</label>
                <input type="text" class="form-control mono-value" id="cli-uf" maxlength="2" placeholder="SP">
            </div>
        </div>

        <button type="submit" class="btn"><i class="fa-solid fa-save"></i> Guardar Cliente</button>
    </form>
</div>

<div class="card">
    <h2><i class="fa-solid fa-users"></i> Carteira de Clientes</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Contactos</th>
                    <th>Endereço</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($clientes as $cli): ?>
                <tr>
                    <td class="mono">#<?= $cli['id'] ?></td>
                    <td><strong><?= $cli['nome'] ?></strong></td>
                    <td class="mono"><?= $cli['cpf'] ?: '—' ?></td>
                    <td>
                        <div><i class="fa-solid fa-mobile-screen text-muted" style="font-size: 0.8rem;"></i> <?= $cli['telefone'] ?></div>
                        <?php if(!empty($cli['email'])): ?>
                            <div><i class="fa-solid fa-envelope text-muted" style="font-size: 0.8rem;"></i> <?= $cli['email'] ?></div>
                        <?php endif; ?>
                    </td>
                    <td><small><?= $cli['logradouro'] ? "{$cli['logradouro']}, {$cli['numero']} - {$cli['bairro']}, {$cli['cidade']}/{$cli['uf']}" : '—' ?></small></td>
                    <td>
                        <button class="btn-icon btn-icon--edit" title="Editar"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="btn-icon btn-icon--view" title="Ver Histórico"><i class="fa-solid fa-clock-rotate-left"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
