document.addEventListener("DOMContentLoaded", () => {
    const formaPagamento = document.getElementById("pdv-forma-pagamento");
    const cliente = document.getElementById("pdv-cliente");
    const clienteAviso = document.getElementById("pdv-cliente-aviso");
    const fiadoDetalhes = document.getElementById("pdv-fiado-detalhes");
    const parcelasSelect = document.getElementById("pdv-parcelas");
    const parcelasPreview = document.getElementById("pdv-parcelas-preview");
    const cartaoDetalhes = document.getElementById("pdv-cartao-detalhes");
    const carrinho = document.getElementById("pdv-carrinho");
    const descontoGeral = document.getElementById("pdv-desconto-geral");
    const subtotalEl = document.getElementById("pdv-subtotal");
    const valorDescontoEl = document.getElementById("pdv-valor-desconto");
    const totalEl = document.getElementById("pdv-total-valor");

    if (!formaPagamento) return;

    function paraReais(valor) {
        return "R$ " + valor.toFixed(2).replace(".", ",");
    }

    // ---- Carrinho: recalcula subtotal por item, subtotal geral,
    // desconto geral e total sempre que algo muda ----
    function recalcularCarrinho() {
        if (!carrinho) return;
        let subtotalGeral = 0;

        carrinho.querySelectorAll("tr").forEach(linha => {
            const qtd = parseFloat(linha.dataset.qtd);
            const preco = parseFloat(linha.dataset.preco);
            const descontoInput = linha.querySelector(".item-desconto");
            const desconto = descontoInput ? (parseFloat(descontoInput.value) || 0) : 0;

            const subtotalItem = qtd * preco * (1 - desconto / 100);
            const celulaSubtotal = linha.querySelector(".item-subtotal");
            if (celulaSubtotal) celulaSubtotal.textContent = paraReais(subtotalItem);

            subtotalGeral += subtotalItem;
        });

        const percentualGeral = descontoGeral ? (parseFloat(descontoGeral.value) || 0) : 0;
        const valorDesconto = subtotalGeral * (percentualGeral / 100);
        const total = subtotalGeral - valorDesconto;

        if (subtotalEl) subtotalEl.textContent = paraReais(subtotalGeral);
        if (valorDescontoEl) valorDescontoEl.textContent = paraReais(valorDesconto);
        if (totalEl) totalEl.textContent = paraReais(total);

        if (ehFiado()) atualizarPreviewParcelas();
    }

    function totalDaVenda() {
        if (!totalEl) return 0;
        const numero = totalEl.textContent.replace(/[^\d,]/g, "").replace(",", ".");
        return parseFloat(numero) || 0;
    }

    // ---- Fiado: parcelamento próprio da loja ----
    function atualizarPreviewParcelas() {
        if (!parcelasSelect || !parcelasPreview) return;
        const total = totalDaVenda();
        const qtd = parseInt(parcelasSelect.value, 10);
        const valorParcela = total / qtd;

        const hoje = new Date();
        let linhas = "";
        for (let i = 1; i <= qtd; i++) {
            const vencimento = new Date(hoje);
            vencimento.setMonth(vencimento.getMonth() + i);
            const dataFormatada = vencimento.toLocaleDateString("pt-BR");
            linhas += `<div style="display:flex; justify-content:space-between; ${i > 1 ? 'margin-top:6px;' : ''}">
                <span>${i}ª parcela — venc. ${dataFormatada}</span>
                <span class="mono-value">${paraReais(valorParcela)}</span>
            </div>`;
        }
        parcelasPreview.innerHTML = linhas;
    }

    function ehFiado() {
        return formaPagamento.value === "Fiado";
    }

    function ehCartaoCredito() {
        return formaPagamento.value === "Cartão de Crédito";
    }

    function atualizarEstadoFormaPagamento() {
        const fiado = ehFiado();
        if (fiadoDetalhes) fiadoDetalhes.style.display = fiado ? "block" : "none";
        if (cartaoDetalhes) cartaoDetalhes.style.display = ehCartaoCredito() ? "block" : "none";

        const clienteBalcao = cliente ? cliente.querySelector('option[value="balcao"]') : null;
        if (fiado) {
            if (clienteBalcao) clienteBalcao.disabled = true;
            if (cliente && cliente.value === "balcao") {
                cliente.value = "";
                if (clienteAviso) clienteAviso.style.display = "block";
            }
            atualizarPreviewParcelas();
        } else {
            if (clienteBalcao) clienteBalcao.disabled = false;
            if (clienteAviso) clienteAviso.style.display = "none";
        }
    }

    formaPagamento.addEventListener("change", atualizarEstadoFormaPagamento);
    if (parcelasSelect) parcelasSelect.addEventListener("change", atualizarPreviewParcelas);
    if (cliente) {
        cliente.addEventListener("change", () => {
            if (ehFiado() && cliente.value && cliente.value !== "balcao" && clienteAviso) {
                clienteAviso.style.display = "none";
            }
        });
    }

    if (descontoGeral) descontoGeral.addEventListener("input", recalcularCarrinho);

    if (carrinho) {
        carrinho.querySelectorAll(".item-desconto").forEach(input => {
            input.addEventListener("input", recalcularCarrinho);
        });
        carrinho.querySelectorAll(".btn-icon--delete").forEach(botao => {
            botao.addEventListener("click", () => {
                botao.closest("tr").remove();
                recalcularCarrinho();
            });
        });
    }

    recalcularCarrinho();
});
