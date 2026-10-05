document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("rel-form");
    if (!form) return;

    const inicio = document.getElementById("rel-inicio");
    const fim = document.getElementById("rel-fim");
    const tipo = document.getElementById("rel-tipo");
    const gerarBtn = document.getElementById("rel-gerar-btn");
    const mensagemEl = document.getElementById("rel-mensagem");

    function paraReais(valor) {
        return "R$ " + Number(valor).toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapar(texto) {
        const div = document.createElement("div");
        div.textContent = texto ?? "";
        return div.innerHTML;
    }

    // "sv-SE" formata como AAAA-MM-DD no fuso do navegador — toISOString()
    // usaria UTC e, à noite, já mostraria o dia seguinte.
    function dataIso(data) {
        return data.toLocaleDateString("sv-SE");
    }

    const hoje = new Date();
    inicio.value = dataIso(new Date(hoje.getFullYear(), hoje.getMonth(), 1));
    fim.value = dataIso(hoje);

    function mostrarSecoes() {
        document.querySelectorAll("[data-secao]").forEach(el => {
            el.style.display = el.dataset.secao.split(" ").includes(tipo.value) ? "" : "none";
        });
    }

    function barras(linhas, rotulo, extra = () => "") {
        if (!linhas.length) return '<p class="text-muted">Nenhuma venda no período.</p>';
        return linhas.map(l => `
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem; gap: 10px;">
                    <span><strong>${escapar(l[rotulo])}</strong>${extra(l)}</span>
                    <span class="mono-value">${paraReais(l.valor)} (${String(l.percentual).replace(".", ",")}%)</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: ${l.percentual}%;"></div>
                </div>
            </div>`).join("");
    }

    function renderizar(dados) {
        const r = dados.resumo;
        document.getElementById("rel-faturamento").textContent = paraReais(r.faturamento);
        document.getElementById("rel-qtd-vendas").textContent = r.qtd_vendas;
        document.getElementById("rel-pecas").textContent = `(${r.pecas_vendidas} peças)`;
        document.getElementById("rel-ticket").textContent = paraReais(r.ticket_medio);
        document.getElementById("rel-descontos").textContent = paraReais(r.descontos);

        const observacoes = [`Custos fixos mensais ativos: ${paraReais(dados.custos_fixos_mensais)}.`];
        if (r.qtd_canceladas) observacoes.push(`${r.qtd_canceladas} venda(s) cancelada(s) no período (fora dos totais).`);
        document.getElementById("rel-observacoes").textContent = observacoes.join(" ");

        document.getElementById("rel-categorias").innerHTML = barras(dados.por_categoria, "categoria");
        document.getElementById("rel-pagamentos").innerHTML = barras(
            dados.por_pagamento, "forma_pagamento",
            l => ` <small class="text-muted">(${l.qtd_vendas})</small>`
        );

        document.getElementById("rel-top-produtos").innerHTML = dados.top_produtos.length
            ? dados.top_produtos.map((p, i) => `
                <tr>
                    <td class="mono"><strong>${i + 1}º</strong></td>
                    <td>${escapar(p.nome)} <small class="text-muted mono">${escapar(p.codigo_interno)}</small></td>
                    <td class="mono">${p.qtd} un.</td>
                    <td class="mono text-moss"><strong>${paraReais(p.receita)}</strong></td>
                </tr>`).join("")
            : '<tr><td colspan="4" class="text-muted">Nenhuma venda no período.</td></tr>';

        document.getElementById("rel-top-clientes").innerHTML = dados.top_clientes.length
            ? dados.top_clientes.map((c, i) => `
                <tr>
                    <td class="mono"><strong>${i + 1}º</strong></td>
                    <td><strong>${escapar(c.nome)}</strong><br><small class="text-muted">${escapar(c.telefone)}</small></td>
                    <td class="mono">${c.qtd_compras}</td>
                    <td class="mono text-moss"><strong>${paraReais(c.total)}</strong></td>
                    <td>${new Date(c.ultima_compra).toLocaleDateString("pt-BR")}</td>
                    <td class="mono ${Number(c.fiado_em_aberto) > 0 ? "text-rust" : ""}">${paraReais(c.fiado_em_aberto)}</td>
                </tr>`).join("")
            : '<tr><td colspan="6" class="text-muted">Nenhuma venda para cliente identificado no período.</td></tr>';
    }

    async function gerar() {
        mensagemEl.style.display = "none";
        if (!inicio.value || !fim.value) {
            mensagemEl.textContent = "Informe as duas datas.";
            mensagemEl.style.display = "block";
            return;
        }

        gerarBtn.disabled = true;
        try {
            const parametros = new URLSearchParams({ inicio: inicio.value, fim: fim.value });
            const resposta = await ModaSysAuth.requisitar(`/relatorios?${parametros}`);
            const dados = await resposta.json();
            if (!resposta.ok) throw new Error(dados.erro || "Falha ao gerar o relatório.");

            renderizar(dados);
            mostrarSecoes();
        } catch (erro) {
            mensagemEl.textContent = erro.message;
            mensagemEl.style.display = "block";
        } finally {
            gerarBtn.disabled = false;
        }
    }

    form.addEventListener("submit", (evento) => {
        evento.preventDefault();
        gerar();
    });
    // Trocar o tipo só muda o que aparece — os dados já vieram todos.
    tipo.addEventListener("change", mostrarSecoes);

    mostrarSecoes();
    gerar();
});
