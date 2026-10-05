document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("cond-form");
    if (!form) return;

    const BADGE_POR_STATUS = { Pendente: "badge-warning", Aprovado: "badge-success", Devolvido: "badge-neutral" };

    const cliente = document.getElementById("cond-cliente");
    const busca = document.getElementById("cond-produto-busca");
    const buscaQtd = document.getElementById("cond-produto-qtd");
    const adicionarBtn = document.getElementById("cond-adicionar-btn");
    const datalist = document.getElementById("cond-produtos");
    const itensEl = document.getElementById("cond-itens");
    const mensagemEl = document.getElementById("cond-mensagem");
    const submitBtn = document.getElementById("cond-submit-btn");
    const listaEl = document.getElementById("cond-lista");
    const listaMensagemEl = document.getElementById("cond-lista-mensagem");

    const finCard = document.getElementById("fin-card");
    const finTitulo = document.getElementById("fin-titulo");
    const finItensEl = document.getElementById("fin-itens");
    const finDescontoGeral = document.getElementById("fin-desconto-geral");
    const finForma = document.getElementById("fin-forma-pagamento");
    const finCartao = document.getElementById("fin-cartao-detalhes");
    const finParcelasCartao = document.getElementById("fin-parcelas-cartao");
    const finFiado = document.getElementById("fin-fiado-detalhes");
    const finParcelas = document.getElementById("fin-parcelas");
    const finTotal = document.getElementById("fin-total");
    const finMensagem = document.getElementById("fin-mensagem");
    const finConfirmarBtn = document.getElementById("fin-confirmar-btn");

    let produtosCache = [];
    let condicionaisCache = [];
    let pecas = [];          // nova condicional: [{ produto, quantidade }]
    let finalizando = null;  // condicional aberta no card de finalizar
    let finLinhas = [];      // [{ produto_id, nome, levou, comprou, preco, desconto_percentual }]

    function centavos(valor) {
        return Math.round((valor + Number.EPSILON) * 100) / 100;
    }

    function paraReais(valor) {
        return "R$ " + Number(valor).toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function dataBr(data) {
        return data ? new Date(data + "T00:00:00").toLocaleDateString("pt-BR") : "-";
    }

    function escapar(texto) {
        const div = document.createElement("div");
        div.textContent = texto ?? "";
        return div.innerHTML;
    }

    function mostrar(el, texto) {
        el.textContent = texto;
        el.style.display = texto ? "block" : "none";
    }

    async function requisitarJson(caminho, opcoes) {
        const resposta = await ModaSysAuth.requisitar(`/${caminho}`, opcoes);
        const dados = await resposta.json();
        if (!resposta.ok) throw new Error(dados.erro || "Falha na operação.");
        return dados;
    }

    // ---- Nova condicional ----
    function encontrarProduto(texto) {
        const alvo = texto.trim().toLowerCase();
        if (!alvo) return null;
        const exato = produtosCache.find(p => p.codigo_interno.toLowerCase() === alvo || p.nome.toLowerCase() === alvo);
        if (exato) return exato;
        const parciais = produtosCache.filter(p => p.nome.toLowerCase().includes(alvo));
        return parciais.length === 1 ? parciais[0] : null;
    }

    function adicionarPeca() {
        const produto = encontrarProduto(busca.value);
        const quantidade = parseInt(buscaQtd.value, 10);

        if (!produto) return mostrar(mensagemEl, "Produto não encontrado — confira o código ou escolha da lista.");
        if (!(quantidade >= 1)) return mostrar(mensagemEl, "Quantidade precisa ser pelo menos 1.");

        const existente = pecas.find(p => p.produto.id === produto.id);
        const total = (existente ? existente.quantidade : 0) + quantidade;
        if (total > produto.estoque_atual) {
            return mostrar(mensagemEl, `Estoque insuficiente de "${produto.nome}": há ${produto.estoque_atual} un.`);
        }
        mostrar(mensagemEl, "");

        if (existente) existente.quantidade = total;
        else pecas.push({ produto, quantidade });

        busca.value = "";
        buscaQtd.value = 1;
        busca.focus();
        renderizarPecas();
    }

    function renderizarPecas() {
        itensEl.innerHTML = pecas.length
            ? pecas.map((p, indice) => `
                <tr>
                    <td data-label="Produto">${escapar(p.produto.nome)} <small class="text-muted mono">${escapar(p.produto.codigo_interno)}</small></td>
                    <td class="mono" data-label="Qtd">${p.quantidade}</td>
                    <td class="mono" data-label="Vlr. Unit.">${paraReais(p.produto.preco_venda)}</td>
                    <td style="text-align: right;"><button type="button" class="btn-icon btn-icon--delete" data-indice="${indice}" title="Remover"><i class="fa-solid fa-xmark"></i></button></td>
                </tr>`).join("")
            : '<tr><td colspan="4" class="text-muted">Nenhuma peça adicionada.</td></tr>';
    }

    adicionarBtn.addEventListener("click", adicionarPeca);
    [busca, buscaQtd].forEach(campo => campo.addEventListener("keydown", (evento) => {
        if (evento.key === "Enter") {
            evento.preventDefault();
            adicionarPeca();
        }
    }));

    itensEl.addEventListener("click", (evento) => {
        const botao = evento.target.closest("button[data-indice]");
        if (!botao) return;
        pecas.splice(Number(botao.dataset.indice), 1);
        renderizarPecas();
    });

    form.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        if (!cliente.value) return mostrar(mensagemEl, "Selecione o cliente.");
        if (!pecas.length) return mostrar(mensagemEl, "Adicione pelo menos uma peça.");
        mostrar(mensagemEl, "");

        submitBtn.disabled = true;
        try {
            const nova = await requisitarJson("condicionais", {
                method: "POST",
                body: JSON.stringify({
                    cliente_id: cliente.value,
                    itens: pecas.map(p => ({ produto_id: p.produto.id, quantidade: p.quantidade })),
                }),
            });
            pecas = [];
            cliente.value = "";
            renderizarPecas();
            mostrar(listaMensagemEl, `Condicional #${nova.id} registrada para ${nova.cliente_nome}.`);
            await Promise.all([carregarProdutos(), carregarLista()]);
        } catch (erro) {
            mostrar(mensagemEl, erro.message);
        } finally {
            submitBtn.disabled = false;
        }
    });

    // ---- Lista ----
    function linhaCondicional(c) {
        let acoes = "";
        if (c.status === "Pendente") {
            acoes = `
                <button type="button" class="btn-icon btn-icon--view" data-acao="finalizar" title="Finalizar Venda"><i class="fa-solid fa-check"></i></button>
                <button type="button" class="btn-icon btn-icon--delete" data-acao="devolver" title="Registrar Devolução"><i class="fa-solid fa-rotate-left"></i></button>`;
        } else if (c.venda_id) {
            acoes = `<a class="btn-icon btn-icon--view" title="Recibo da venda #${c.venda_id}" href="recibo.php?venda=${c.venda_id}" target="_blank" style="text-decoration: none;"><i class="fa-solid fa-file-invoice"></i></a>`;
        }

        return `
            <tr data-id="${c.id}">
                <td class="mono">#${c.id}</td>
                <td>${dataBr(c.data_saida)}</td>
                <td><strong>${escapar(c.cliente_nome)}</strong></td>
                <td><small>${escapar(c.produtos_resumo || "")}</small></td>
                <td><span class="badge ${BADGE_POR_STATUS[c.status]}">${c.status}</span></td>
                <td>${dataBr(c.data_conclusao)}</td>
                <td>${acoes}</td>
            </tr>`;
    }

    async function carregarLista() {
        try {
            condicionaisCache = await requisitarJson("condicionais");
            listaEl.innerHTML = condicionaisCache.length
                ? condicionaisCache.map(linhaCondicional).join("")
                : '<tr><td colspan="7" class="text-muted">Nenhuma condicional registrada ainda.</td></tr>';
        } catch (erro) {
            listaEl.innerHTML = `<tr><td colspan="7" class="text-rust">Falha ao carregar: ${escapar(erro.message)}</td></tr>`;
        }
    }

    listaEl.addEventListener("click", async (evento) => {
        const botao = evento.target.closest("button[data-acao]");
        if (!botao) return;
        const id = botao.closest("tr").dataset.id;
        const condicional = condicionaisCache.find(c => String(c.id) === id);

        if (botao.dataset.acao === "devolver") {
            const confirmado = await ModaSysModal.confirmar(
                `Registrar a devolução de todas as peças da condicional #${id} (${condicional.cliente_nome})? Elas voltam pro estoque.`,
                { textoConfirmar: "Registrar devolução" }
            );
            if (!confirmado) return;

            try {
                await requisitarJson(`condicionais/${id}/devolver`, { method: "PATCH" });
                mostrar(listaMensagemEl, `Condicional #${id} devolvida.`);
                if (finalizando && String(finalizando.id) === id) fecharFinalizacao();
                await Promise.all([carregarProdutos(), carregarLista()]);
            } catch (erro) {
                alert(erro.message);
            }
            return;
        }

        if (botao.dataset.acao === "finalizar") {
            try {
                abrirFinalizacao(await requisitarJson(`condicionais/${id}`));
            } catch (erro) {
                alert(erro.message);
            }
        }
    });

    // ---- Finalizar venda ----
    function abrirFinalizacao(condicional) {
        finalizando = condicional;
        finLinhas = condicional.itens.map(item => ({
            produto_id: item.produto_id,
            nome: item.produto_nome,
            levou: Number(item.quantidade),
            comprou: Number(item.quantidade),
            preco: Number(item.preco_venda),
            desconto_percentual: 0,
        }));

        finTitulo.textContent = `Finalizar Venda — Condicional #${condicional.id} (${condicional.cliente_nome})`;
        finDescontoGeral.value = 0;
        finForma.value = "PIX";
        atualizarForma();
        mostrar(finMensagem, "");

        finItensEl.innerHTML = finLinhas.map((linha, indice) => `
            <tr data-indice="${indice}">
                <td data-label="Produto">${escapar(linha.nome)}</td>
                <td class="mono" data-label="Levou">${linha.levou}</td>
                <td data-label="Comprou"><input type="number" class="form-control fin-comprou" value="${linha.comprou}" min="0" max="${linha.levou}" style="width: 75px; padding: 6px 8px;"></td>
                <td class="mono" data-label="Vlr. Unit.">${paraReais(linha.preco)}</td>
                <td data-label="% Desc."><input type="number" class="form-control fin-desconto" value="0" min="0" max="100" step="0.01" style="width: 75px; padding: 6px 8px;"></td>
                <td class="mono fin-subtotal" data-label="Subtotal"></td>
            </tr>`).join("");

        recalcularFinalizacao();
        finCard.style.display = "block";
        finCard.scrollIntoView({ behavior: "smooth" });
    }

    function fecharFinalizacao() {
        finalizando = null;
        finLinhas = [];
        finCard.style.display = "none";
    }

    // Mesma conta do VendaService (só pré-visualização — o backend
    // recalcula com o preço do cadastro).
    function recalcularFinalizacao() {
        let subtotal = 0;
        finItensEl.querySelectorAll("tr[data-indice]").forEach(tr => {
            const linha = finLinhas[Number(tr.dataset.indice)];
            const bruto = centavos(linha.comprou * linha.preco);
            const valor = bruto - centavos(bruto * linha.desconto_percentual / 100);
            tr.querySelector(".fin-subtotal").textContent = paraReais(valor);
            subtotal += valor;
        });
        subtotal = centavos(subtotal);
        const geral = Math.min(100, Math.max(0, parseFloat(finDescontoGeral.value) || 0));
        finTotal.textContent = paraReais(centavos(subtotal - centavos(subtotal * geral / 100)));
    }

    finItensEl.addEventListener("input", (evento) => {
        const tr = evento.target.closest("tr[data-indice]");
        if (!tr) return;
        const linha = finLinhas[Number(tr.dataset.indice)];

        if (evento.target.classList.contains("fin-comprou")) {
            linha.comprou = Math.min(linha.levou, Math.max(0, parseInt(evento.target.value, 10) || 0));
        }
        if (evento.target.classList.contains("fin-desconto")) {
            linha.desconto_percentual = Math.min(100, Math.max(0, parseFloat(evento.target.value) || 0));
        }
        recalcularFinalizacao();
    });

    function atualizarForma() {
        finCartao.style.display = finForma.value === "Cartão de Crédito" ? "block" : "none";
        finFiado.style.display = finForma.value === "Fiado" ? "block" : "none";
    }

    finForma.addEventListener("change", atualizarForma);
    finDescontoGeral.addEventListener("input", recalcularFinalizacao);
    document.getElementById("fin-cancelar-btn").addEventListener("click", fecharFinalizacao);

    finConfirmarBtn.addEventListener("click", async () => {
        const comprados = finLinhas.filter(l => l.comprou > 0);
        if (!comprados.length) {
            return mostrar(finMensagem, 'Nenhuma peça comprada — para devolver tudo, use "Registrar Devolução" na lista.');
        }

        const devolvidas = finLinhas.reduce((soma, l) => soma + (l.levou - l.comprou), 0);
        const confirmado = await ModaSysModal.confirmar(
            `Confirmar venda de ${finTotal.textContent} no ${finForma.value}?` +
            (devolvidas ? ` ${devolvidas} peça(s) não comprada(s) voltam pro estoque.` : ""),
            { textoConfirmar: "Confirmar venda" }
        );
        if (!confirmado) return;

        finConfirmarBtn.disabled = true;
        try {
            const id = finalizando.id;
            const venda = await requisitarJson(`condicionais/${id}/finalizar`, {
                method: "POST",
                body: JSON.stringify({
                    forma_pagamento: finForma.value,
                    parcelas_cartao: finParcelasCartao.value,
                    parcelas: finParcelas.value,
                    desconto_percentual: parseFloat(finDescontoGeral.value) || 0,
                    itens: comprados.map(l => ({
                        produto_id: l.produto_id,
                        quantidade: l.comprou,
                        desconto_percentual: l.desconto_percentual,
                    })),
                }),
            });

            fecharFinalizacao();
            listaMensagemEl.innerHTML = `Condicional #${id} finalizada — venda #${venda.id}. <a href="recibo.php?venda=${venda.id}" target="_blank">Imprimir recibo</a>`;
            listaMensagemEl.style.display = "block";
            await Promise.all([carregarProdutos(), carregarLista()]);
        } catch (erro) {
            mostrar(finMensagem, erro.message);
        } finally {
            finConfirmarBtn.disabled = false;
        }
    });

    // ---- Carregamento ----
    async function carregarProdutos() {
        produtosCache = (await requisitarJson("produtos")).filter(p => p.ativo);
        datalist.innerHTML = produtosCache
            .map(p => `<option value="${escapar(p.codigo_interno)}">${escapar(p.nome)} (${p.estoque_atual} un.)</option>`)
            .join("");
    }

    async function carregarClientes() {
        (await requisitarJson("clientes")).forEach(c => {
            const opcao = document.createElement("option");
            opcao.value = c.id;
            opcao.textContent = c.nome;
            cliente.appendChild(opcao);
        });
    }

    Promise.all([carregarProdutos(), carregarClientes()]).catch(erro => mostrar(mensagemEl, erro.message));
    carregarLista();
});
