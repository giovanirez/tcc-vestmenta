<?php
// A folha impressa é sempre A4 normal — a maioria das impressoras e
// diálogos de impressão ignora tamanho de papel customizado e cai pra
// A4 de qualquer jeito. O que muda de tamanho é o BLOCO do recibo
// dentro da folha: 105mm de largura fixos, com a altura crescendo
// conforme a quantidade de itens (calculada no JS, depois que a venda
// chega da API). Ele fica ancorado no topo da página — é isso que
// permite, no futuro, encaixar um segundo recibo do lado ou embaixo,
// na mesma folha A4.
//
// Os dados vêm da API pelo navegador (o token de login só existe lá),
// por isso esta página não consulta nada no PHP.
$LARGURA_MM = 105;
$ALTURA_BASE_MM = 148.5;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo · ModaSys</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/base.css">
    <style>
        @page { size: A4 portrait; margin: 8mm; }

        body { background: #d8d5cc; padding: 24px 0; display: flex; flex-direction: column; align-items: center; }

        .recibo {
            width: <?= $LARGURA_MM ?>mm;
            min-height: <?= $ALTURA_BASE_MM ?>mm;
            background: var(--surface);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 6mm;
            font-size: 0.78rem;
        }

        .recibo-cabecalho { text-align: center; border-bottom: 1.5px solid var(--ink); padding-bottom: 8px; margin-bottom: 10px; }
        .recibo-marca { font-family: var(--font-display); font-size: 1.15rem; font-weight: 600; }
        .recibo-marca::before { content: ""; display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--thread); margin-right: 6px; vertical-align: middle; }
        .recibo-rotulo { font-family: var(--font-mono); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink-muted); margin-top: 4px; }
        .recibo-rotulo strong { color: var(--ink); }

        .recibo-meta { font-size: 0.75rem; margin-bottom: 10px; }
        .recibo-meta div { display: flex; justify-content: space-between; gap: 8px; padding: 1px 0; }
        .recibo-meta span:first-child { color: var(--ink-muted); }

        .recibo-linha-pontilhada { border-top: 1px dashed var(--border-color); margin: 8px 0; }

        .recibo-item { margin-bottom: 7px; }
        .recibo-item .nome { font-size: 0.78rem; }
        .recibo-item .calculo { display: flex; justify-content: space-between; font-family: var(--font-mono); font-size: 0.72rem; color: var(--ink-muted); }
        .recibo-item .calculo strong { color: var(--ink); font-weight: 600; }
        .recibo-item .desconto-item { font-size: 0.68rem; color: var(--rust); }

        .recibo-totais div { display: flex; justify-content: space-between; padding: 2px 0; font-size: 0.78rem; }
        .recibo-totais .total-final { border-top: 1.5px solid var(--ink); margin-top: 6px; padding-top: 6px; font-family: var(--font-display); font-size: 1rem; }

        .recibo-rodape { margin-top: 14px; text-align: center; color: var(--ink-muted); font-size: 0.62rem; line-height: 1.4; }

        .acoes-tela { margin: 0 auto 16px; display: flex; gap: 10px; justify-content: center; }

        @media print {
            html, body { display: block; background: none; padding: 0; margin: 0; }
            .recibo { box-shadow: none; margin: 0; padding: 5mm; }
            .no-print { display: none !important; }
        }
    </style>
    <?php include 'includes/config-api.php'; ?>
</head>
<body>

    <div class="no-print acoes-tela" id="recibo-acoes" style="display: none;">
        <button class="btn" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir</button>
        <button class="btn btn-outline" onclick="window.close()">Fechar</button>
    </div>

    <div class="recibo" id="recibo">
        <p class="text-muted" style="text-align: center;">Carregando recibo...</p>
    </div>

    <script src="js/main.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", async () => {
        const ITENS_QUE_CABEM_NA_BASE = 6;
        const ALTURA_POR_ITEM_EXTRA_MM = 9;
        const ALTURA_BASE_MM = <?= $ALTURA_BASE_MM ?>;

        const reciboEl = document.getElementById("recibo");
        const id = parseInt(new URLSearchParams(window.location.search).get("venda"), 10) || 0;

        const reais = (valor) => "R$ " + Number(valor).toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const escapar = (texto) => { const d = document.createElement("div"); d.textContent = texto ?? ""; return d.innerHTML; };
        const dataHora = (data) => new Date(data).toLocaleString("pt-BR", { dateStyle: "short", timeStyle: "short" });

        function erro(mensagem) {
            reciboEl.style.textAlign = "center";
            reciboEl.innerHTML = `<p><i class="fa-solid fa-circle-exclamation text-rust"></i> ${escapar(mensagem)}</p>`;
        }

        if (!ModaSysAuth.obterToken()) return;

        let venda;
        try {
            const resposta = await ModaSysAuth.requisitar(`/vendas/${id}`);
            venda = await resposta.json();
            if (!resposta.ok) return erro(resposta.status === 404 ? `Recibo não encontrado para o pedido #${id}.` : (venda.erro || "Falha ao carregar o recibo."));
        } catch (e) {
            return erro(e.message);
        }

        document.title = `Recibo #${venda.id} · ModaSys`;
        const extras = Math.max(0, venda.itens.length - ITENS_QUE_CABEM_NA_BASE);
        reciboEl.style.minHeight = (ALTURA_BASE_MM + extras * ALTURA_POR_ITEM_EXTRA_MM) + "mm";

        const itens = venda.itens.map(item => `
            <div class="recibo-item">
                <div class="nome">${escapar(item.produto_nome)}</div>
                <div class="calculo">
                    <span>${item.quantidade} x ${reais(item.valor_unitario)}</span>
                    <strong>${reais(item.valor_total)}</strong>
                </div>
                ${Number(item.valor_desconto) > 0 ? `<div class="desconto-item">desconto: -${reais(item.valor_desconto)}</div>` : ""}
            </div>`).join("");

        const parcelas = venda.parcelas.length ? `
            <div class="recibo-linha-pontilhada"></div>
            <div class="recibo-meta">
                ${venda.parcelas.map(p => `<div><span>${p.numero_parcela}ª parcela — venc. ${new Date(p.data_vencimento + "T00:00:00").toLocaleDateString("pt-BR")}</span><span class="mono-value">${reais(p.valor)}</span></div>`).join("")}
            </div>` : "";

        reciboEl.innerHTML = `
            <div class="recibo-cabecalho">
                <div class="recibo-marca">ModaSys</div>
                <div class="recibo-rotulo">Comprovante de Venda <strong>#${venda.id}</strong></div>
                ${venda.status === "Cancelada" ? '<div class="recibo-rotulo text-rust"><strong>VENDA CANCELADA</strong></div>' : ""}
            </div>

            <div class="recibo-meta">
                <div><span>Data</span><span>${dataHora(venda.data_venda)}</span></div>
                <div><span>Cliente</span><span>${escapar(venda.cliente_nome || "Cliente Balcão")}</span></div>
                <div><span>Pagamento</span><span>${escapar(venda.forma_pagamento)}${venda.parcelas_cartao ? ` (${venda.parcelas_cartao}x)` : ""}</span></div>
                ${venda.usuario_nome ? `<div><span>Atendido por</span><span>${escapar(venda.usuario_nome)}</span></div>` : ""}
            </div>

            <div class="recibo-linha-pontilhada"></div>
            ${itens}
            <div class="recibo-linha-pontilhada"></div>

            <div class="recibo-totais">
                <div><span>Subtotal</span><span class="mono-value">${reais(venda.valor_subtotal)}</span></div>
                ${Number(venda.valor_desconto) > 0 ? `<div><span>Desconto</span><span class="mono-value text-rust">- ${reais(venda.valor_desconto)}</span></div>` : ""}
                <div class="total-final"><span>Total</span><span class="mono-value"><strong>${reais(venda.valor_total)}</strong></span></div>
            </div>
            ${parcelas}

            <div class="recibo-rodape">
                Comprovante gerado pelo ModaSys em ${dataHora(new Date())}<br>
                não possui valor fiscal.
            </div>`;

        document.getElementById("recibo-acoes").style.display = "flex";
    });
    </script>

</body>
</html>
