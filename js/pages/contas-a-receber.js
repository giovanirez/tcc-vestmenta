document.addEventListener("DOMContentLoaded", () => {
    const listaEl = document.getElementById("cr-lista");
    if (!listaEl) return;

    const BADGE_POR_STATUS = { Pendente: "badge-warning", Atrasada: "badge-danger", Paga: "badge-success" };

    let parcelasCache = [];

    function paraReais(valor) {
        return "R$ " + Number(valor).toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function dataBr(data) {
        return new Date(data + "T00:00:00").toLocaleDateString("pt-BR");
    }

    function escapar(texto) {
        const div = document.createElement("div");
        div.textContent = texto ?? "";
        return div.innerHTML;
    }

    // "Atrasada" já chega calculada pelo backend (vencimento < hoje),
    // aqui só soma os cartões do topo.
    function atualizarResumo() {
        const soma = (lista) => lista.reduce((total, p) => total + Number(p.valor), 0);
        const abertas = parcelasCache.filter(p => p.status !== "Paga");

        document.getElementById("cr-total-aberto").textContent = paraReais(soma(abertas));
        document.getElementById("cr-total-atrasado").textContent = paraReais(soma(abertas.filter(p => p.status === "Atrasada")));
        document.getElementById("cr-recebido").textContent = paraReais(soma(parcelasCache.filter(p => p.status === "Paga")));
        document.getElementById("cr-clientes").textContent = new Set(abertas.map(p => p.cliente_id)).size;
    }

    function linhaParcela(p) {
        const acao = p.status === "Paga"
            ? `<span class="text-muted mono" style="font-size: 0.8rem;">pago em ${dataBr(p.data_pagamento)}</span>`
            : '<button type="button" class="btn-icon btn-icon--view" data-acao="pagar" title="Marcar como Paga"><i class="fa-solid fa-check"></i></button>';

        return `
            <tr data-id="${p.id}">
                <td><strong>${escapar(p.cliente_nome || "—")}</strong></td>
                <td class="mono"><a href="recibo.php?venda=${p.venda_id}" target="_blank">#${p.venda_id}</a></td>
                <td class="mono">${p.numero_parcela}ª</td>
                <td class="mono">${paraReais(p.valor)}</td>
                <td>${dataBr(p.data_vencimento)}</td>
                <td><span class="badge ${BADGE_POR_STATUS[p.status]}">${p.status}</span></td>
                <td>${acao}</td>
            </tr>`;
    }

    async function carregarLista() {
        try {
            const resposta = await ModaSysAuth.requisitar(`/contas-a-receber`);
            const dados = await resposta.json();
            if (!resposta.ok) throw new Error(dados.erro || "Falha ao carregar.");

            parcelasCache = dados;
            listaEl.innerHTML = parcelasCache.length
                ? parcelasCache.map(linhaParcela).join("")
                : '<tr><td colspan="7" class="text-muted">Nenhuma venda fiado registrada.</td></tr>';
            atualizarResumo();
        } catch (erro) {
            listaEl.innerHTML = `<tr><td colspan="7" class="text-rust">Falha ao carregar: ${escapar(erro.message)}</td></tr>`;
        }
    }

    listaEl.addEventListener("click", async (evento) => {
        const botao = evento.target.closest('button[data-acao="pagar"]');
        if (!botao) return;

        const id = botao.closest("tr").dataset.id;
        const parcela = parcelasCache.find(p => String(p.id) === id);

        const confirmado = await ModaSysModal.confirmar(
            `Confirmar recebimento da ${parcela.numero_parcela}ª parcela de ${parcela.cliente_nome} (${paraReais(parcela.valor)})?`,
            { textoConfirmar: "Marcar como paga" }
        );
        if (!confirmado) return;

        try {
            const resposta = await ModaSysAuth.requisitar(`/contas-a-receber/${id}/pagar`, { method: "PATCH" });
            const dados = await resposta.json();
            if (!resposta.ok) throw new Error(dados.erro || "Não foi possível registrar o pagamento.");
            await carregarLista();
        } catch (erro) {
            alert(erro.message);
        }
    });

    carregarLista();
});
