<?php include 'includes/header.php'; ?>

<?php
// Dados Mockados - Histórico de Vendas
$vendas = [
    ['id' => 1042, 'data' => '2026-03-17 14:30', 'cliente' => 'Mariana Oliveira', 'itens' => '1x Vestido Floral, 2x Cinto Fino', 'pagamento' => 'Cartão de Crédito', 'total' => 259.80],
    ['id' => 1041, 'data' => '2026-03-17 10:15', 'cliente' => 'Cliente Balcão', 'itens' => '1x Camiseta Básica', 'pagamento' => 'PIX', 'total' => 49.90],
    ['id' => 1040, 'data' => '2026-03-16 16:45', 'cliente' => 'Carlos Mendes', 'itens' => '1x Calça Jeans Skinny, 1x Jaqueta PU', 'pagamento' => 'Cartão de Débito', 'total' => 379.80]
];
?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    
    <div class="card">
        <h2><i class="fa-solid fa-cart-arrow-down"></i> Frente de Caixa (PDV)</h2>
        
        <form action="#" method="POST" style="margin-top: 15px;">
            <div class="form-group">
                <label>Cliente</label>
                <div style="display: flex; gap: 10px;">
                    <select class="form-control" style="flex: 1;">
                        <option>Cliente Balcão (Não Identificado)</option>
                        <option>Mariana Oliveira</option>
                        <option>Carlos Mendes</option>
                    </select>
                    <button type="button" class="btn-icon" title="Novo Cliente" style="background: var(--bg-color); padding: 0 15px; border-radius: 4px; border: 1px solid var(--border-color);"><i class="fa-solid fa-user-plus"></i></button>
                </div>
            </div>

            <div style="background: var(--bg-color); padding: 15px; border-radius: 4px; margin-bottom: 15px; border: 1px dashed var(--border-color);">
                <label style="display: block; margin-bottom: 10px; font-weight: 500;"><i class="fa-solid fa-barcode"></i> Lançar Produto</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" class="form-control" placeholder="Código de barras ou nome do produto..." style="flex: 1;">
                    <input type="number" class="form-control" value="1" min="1" style="width: 80px;" title="Quantidade">
                    <button type="button" class="btn" style="background-color: var(--primary-color);">Adicionar</button>
                </div>
            </div>

            <div class="table-responsive" style="margin-bottom: 20px;">
                <table style="margin-top: 0;">
                    <thead style="background: #eee;">
                        <tr>
                            <th>Produto</th>
                            <th>Qtd</th>
                            <th>Vlr. Unit.</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Vestido Floral Verão</td>
                            <td>1</td>
                            <td>R$ 159,90</td>
                            <td>R$ 159,90</td>
                            <td style="text-align: right;"><button type="button" class="btn-icon" style="color: #e74c3c;"><i class="fa-solid fa-xmark"></i></button></td>
                        </tr>
                        <tr>
                            <td>Cinto Fino Couro</td>
                            <td>2</td>
                            <td>R$ 49,95</td>
                            <td>R$ 99,90</td>
                            <td style="text-align: right;"><button type="button" class="btn-icon" style="color: #e74c3c;"><i class="fa-solid fa-xmark"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <div class="card" style="display: flex; flex-direction: column;">
        <h2><i class="fa-solid fa-money-bill-wave"></i> Resumo</h2>
        
        <div style="flex: 1; margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: var(--text-muted);">
                <span>Subtotal:</span>
                <span>R$ 259,80</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; color: var(--text-muted);">
                <span>Desconto:</span>
                <span>R$ 0,00</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding-top: 15px; border-top: