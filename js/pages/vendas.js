document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("pdv-form");
    if (!form) return;


    const formaPagamento = document.getElementById("pdv-forma-pagamento");
    const cliente = document.getElementById("pdv-cliente");
    const clienteAviso = document.getElementById("pdv-cliente-aviso");
    const fiadoDetalhes = document.getElementById("pdv-fiado-detalhes");
    const parcelasSelect = document.getElementById("pdv-parcelas");
    const parcelasPreview = document.getElementById("pdv-parcelas-preview");
    const cartaoDetalhes = document.getElementById("pdv-cartao-detalhes");
    const parcelasCartao = document.getElementById("pdv-parcelas-cartao");
    const carrinhoEl = document.getElementById("pdv-carrinho");
    const descontoGeral = document.getElementById("pdv-desconto-geral");
    const subtotalEl = document.getElementById("pdv-subtotal");
    const valorDescontoEl = document.getElementById("pdv-valor-desconto");
    const totalEl = document.getElementById("pdv-total-valor");
    const busca = document.getElementById("pdv-produto-busca");
    const buscaQtd = document.getElementById("pdv-produto-qtd");
    const adicionarBtn = document.getElementById("pdv-adicionar-btn");
    const datalist = document.getElementById("pdv-produtos");
    const mensagemEl = document.getElementById("pdv-mensagem");
    const finalizarBtn = document.getElementById("pdv-finalizar-btn");
    const historicoEl = document.getElementById("pdv-historico");

    const usuario = ModaSysAuth.obterUsuario();
    const ehAdmin = usuario && usuario.papel === "admin";

    let produtosCache = [];
    let clientesCache = [];
    // Cada linha: { produto, quantidade, desconto_percentual }
    let carrinho = [];

    // Arredonda pra centavos do mesmo jeito que o backend (round do PHP)
    // — senão a pré-visualização podia diferir 1 centavo do valor salvo.
    function centavos(valor) {
        return Math.round((valor + Number.EPSILON) * 100) / 100;
    }

    function paraReais(valor) {
        return "R$ " + Number(valor).toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapar(texto) {
        const div = document.createElement("div");
        div.textContent = texto ?? "";
        return div.innerHTML;
    }

    function mostrarMensagem(texto) {
        mensagemEl.className = "text-rust";
        mensagemEl.textContent = texto;
        mensagemEl.style.display = texto ? "block" : "none";
    }

    // ---- Busca de produto: código exato, nome exato, ou um único
    // produto cujo nome contém o texto digitado ----
    function encontrarProduto(texto) {
        const alvo = texto.trim().toLowerCase();
        if (!alvo) return null;

        const exato = produtosCache.find(p =>
            p.codigo_interno.toLowerCase() === alvo || p.nome.toLowerCase() === alvo);
        if (exato) return exato;

        const parciais = produtosCache.filter(p => p.nome.toLowerCase().includes(alvo));
        return parciais.length === 1 ? parciais[0] : null;
    }

    function quantidadeNoCarrinho(produtoId) {
        return carrinho.filter(l => l.produto.id === produtoId).reduce((soma, l) => soma + l.quantidade, 0);
    }

    function adicionarProduto() {
        const produto = encontrarProduto(busca.value);
        const quantidade = parseInt(buscaQtd.value, 10);

        if (!produto) return mostrarMensagem("Produto não encontrado — confira o código ou escolha da lista.");
        if (!(quantidade >= 1)) return mostrarMensagem("Quantidade precisa ser pelo menos 1.");
        if (quantidadeNoCarrinho(produto.id) + quantidade > produto.estoque_atual) {
            return mostrarMensagem(`Estoque insuficiente de "${produto.nome}": há ${produto.estoque_atual} un.`);
        }
        mostrarMensagem("");

        const existente = carrinho.find(l => l.produto.id === produto.id);
        if (existente) existente.quantidade += quantidade;
        else carrinho.push({ produto, quantidade, desconto_percentual: 0 });

        busca.value = "";
        buscaQtd.value = 1;
        busca.focus();
        renderizarCarrinho();
    }

    adicionarBtn.addEventListener("click", adicionarProduto);
    busca.addEventListener("keydown", (evento) => {
        if (evento.key === "Enter") {
            evento.preventDefault();
            adicionarProduto();
        }
    });
    buscaQtd.addEventListener("keydown", (evento) => {
        if (evento.key === "Enter") {
            evento.preventDefault();
            adicionarProduto();
        }
    });

    // ---- Carrinho ----
    function renderizarCarrinho() {
        if (!carrinho.length) {
            carrinhoEl.innerHTML = '<tr class="pdv-vazio"><td colspan="6" class="text-muted">Nenhum produto lançado.</td></tr>';
        } else {
            carrinhoEl.innerHTML = carrinho.map((linha, indice) => `
                <tr data-indice="${indice}">
                    <td data-label="Produto">${escapar(linha.produto.nome)} <small class="text-muted mono">${escapar(linha.produto.codigo_interno)}</small></td>
                    <td class="mono" data-label="Qtd">${linha.quantidade}</td>
                    <td class="mono" data-label="Vlr. Unit.">${paraReais(linha.produto.preco_venda)}</td>
                    <td data-label="% Desc."><input type="number" class="form-control item-desconto" value="${linha.desconto_percentual}" min="0" max="100" step="0.01" style="width: 75px; padding: 6px 8px;"></td>
                    <td class="mono item-subtotal" data-label="Subtotal"></td>
                    <td style="text-align: right;"><button type="button" class="btn-icon btn-icon--delete" title="Remover"><i class="fa-solid fa-xmark"></i></button></td>
                </tr>`).join("");
        }
        recalcular();
    }

    carrinhoEl.addEventListener("input", (evento) => {
        if (!evento.target.classList.contains("item-desconto")) return;
        const linha = carrinho[Number(evento.target.closest("tr").dataset.indice)];
        linha.desconto_percentual = Math.min(100, Math.max(0, parseFloat(evento.target.value) || 0));
        recalcular();
    });

    carrinhoEl.addEventListener("click", (evento) => {
        const botao = evento.target.closest(".btn-icon--delete");
        if (!botao) return;
        carrinho.splice(Number(botao.closest("tr").dataset.indice), 1);
        renderizarCarrinho();
    });

    // Mesma conta do VendaService: desconto de cada item em reais,
    // depois desconto geral sobre o subtotal. O backend recalcula tudo
    // com o preço do cadastro — isso aqui é só pré-visualização.
    function calcularTotais() {
        let subtotal = 0;
        const subtotaisItens = carrinho.map(linha => {
            const bruto = centavos(linha.quantidade * Number(linha.produto.preco_venda));
            const desconto = centavos(bruto * linha.desconto_percentual / 100);
            subtotal += bruto - desconto;
            return bruto - desconto;
        });
        subtotal = centavos(subtotal);

        const percentualGeral = Math.min(100, Math.max(0, parseFloat(descontoGeral.value) || 0));
        const valorDesconto = centavos(subtotal * percentualGeral / 100);
        return { subtotaisItens, subtotal, valorDesconto, total: centavos(subtotal - valorDesconto) };
    }

    function recalcular() {
        const { subtotaisItens, subtotal, valorDesconto, total } = calcularTotais();

        carrinhoEl.querySelectorAll("tr[data-indice]").forEach(tr => {
            tr.querySelector(".item-subtotal").textContent = paraReais(subtotaisItens[Number(tr.dataset.indice)]);
        });
        subtotalEl.textContent = paraReais(subtotal);
        valorDescontoEl.textContent = paraReais(valorDesconto);
        totalEl.textContent = paraReais(total);

        if (ehFiado()) atualizarPreviewParcelas();
    }

    descontoGeral.addEventListener("input", recalcular);

    // ---- Fiado: parcelamento próprio da loja. Mesma divisão do
    // backend: centavos que sobram vão pra última parcela, e o
    // vencimento não "pula" mês (31/01 + 1 mês = 28/02). ----
    function somarMeses(data, meses) {
        const alvo = new Date(data.getFullYear(), data.getMonth() + meses, 1);
        const ultimoDia = new Date(alvo.getFullYear(), alvo.getMonth() + 1, 0).getDate();
        alvo.setDate(Math.min(data.getDate(), ultimoDia));
        return alvo;
    }

    function atualizarPreviewParcelas() {
        const { total } = calcularTotais();
        const qtd = parseInt(parcelasSelect.value, 10);
        const valorBase = Math.floor(total * 100 / qtd) / 100;
        const hoje = new Date();

        let linhas = "";
        for (let i = 1; i <= qtd; i++) {
            const valor = i === qtd ? centavos(total - valorBase * (qtd - 1)) : valorBase;
            linhas += `<div style="display:flex; justify-content:space-between; ${i > 1 ? "margin-top:6px;" : ""}">
                <span>${i}ª parcela — venc. ${somarMeses(hoje, i).toLocaleDateString("pt-BR")}</span>
                <span class="mono-value">${paraReais(valor)}</span>
            </div>`;
        }

        const clienteSelecionado = clientesCache.find(c => String(c.id) === cliente.value);
        if (clienteSelecionado) {
            linhas += clienteSelecionado.limite_credito === null
                ? '<div class="text-rust" style="margin-top:8px;">Este cliente não tem fiado liberado (sem limite de crédito).</div>'
                : `<div class="text-muted" style="margin-top:8px;">Limite de crédito do cliente: ${paraReais(clienteSelecionado.limite_credito)}</div>`;
        }
        parcelasPreview.innerHTML = linhas;
    }

    function ehFiado() {
        return formaPagamento.value === "Fiado";
    }

    function atualizarEstadoFormaPagamento() {
        const fiado = ehFiado();
        fiadoDetalhes.style.display = fiado ? "block" : "none";
        cartaoDetalhes.style.display = formaPagamento.value === "Cartão de Crédito" ? "block" : "none";

        const clienteBalcao = cliente.querySelector('option[value="balcao"]');
        if (fiado) {
            clienteBalcao.disabled = true;
            if (cliente.value === "balcao") {
                cliente.value = "";
                clienteAviso.style.display = "block";
            }
            atualizarPreviewParcelas();
        } else {
            clienteBalcao.disabled = false;
            clienteAviso.style.display = "none";
            if (!cliente.value) cliente.value = "balcao";
        }
    }

    formaPagamento.addEventListener("change", atualizarEstadoFormaPagamento);
    parcelasSelect.addEventListener("change", atualizarPreviewParcelas);
    cliente.addEventListener("change", () => {
        if (ehFiado()) {
            if (cliente.value && cliente.value !== "balcao") clienteAviso.style.display = "none";
            atualizarPreviewParcelas();
        }
    });

    // ---- Finalizar venda ----
    form.addEventListener("submit", async (evento) => {
        evento.preventDefault();

        if (!carrinho.length) return mostrarMensagem("Adicione pelo menos um produto à venda.");
        if (ehFiado() && (!cliente.value || cliente.value === "balcao")) {
            clienteAviso.style.display = "block";
            return mostrarMensagem("Venda fiado exige um cliente identificado.");
        }
        mostrarMensagem("");

        const { total } = calcularTotais();
        const confirmado = await ModaSysModal.confirmar(
            `Finalizar venda de ${paraReais(total)} no ${formaPagamento.value}?`,
            { textoConfirmar: "Finalizar" }
        );
        if (!confirmado) return;

        finalizarBtn.disabled = true;
        try {
            const resposta = await ModaSysAuth.requisitar(`/vendas`, {
                method: "POST",
                body: JSON.stringify({
                    cliente_id: cliente.value === "balcao" ? null : cliente.value,
                    forma_pagamento: formaPagamento.value,
                    parcelas_cartao: parcelasCartao.value,
                    parcelas: parcelasSelect.value,
                    desconto_percentual: parseFloat(descontoGeral.value) || 0,
                    itens: carrinho.map(l => ({
                        produto_id: l.produto.id,
                        quantidade: l.quantidade,
                        desconto_percentual: l.desconto_percentual,
                    })),
                }),
            });
            const venda = await resposta.json();
            if (!resposta.ok) throw new Error(venda.erro || "Não foi possível finalizar a venda.");

            carrinho = [];
            descontoGeral.value = 0;
            cliente.value = "balcao";
            formaPagamento.value = "PIX";
            atualizarEstadoFormaPagamento();
            renderizarCarrinho();

            // Não abre o recibo sozinho: window.open depois de um await
            // não conta mais como clique do usuário e o navegador bloqueia
            // como pop-up. Mostra o link pra quem quiser imprimir.
            mensagemEl.className = "text-moss";
            mensagemEl.innerHTML = `Venda #${venda.id} finalizada. <a href="recibo.php?venda=${venda.id}" target="_blank">Imprimir recibo</a>`;
            mensagemEl.style.display = "block";
            // Estoque mudou — recarrega pra próxima venda já ver o saldo novo.
            await Promise.all([carregarProdutos(), carregarHistorico()]);
        } catch (erro) {
            mostrarMensagem(erro.message);
        } finally {
            finalizarBtn.disabled = false;
        }
    });

    // ---- Histórico ----
    function linhaHistorico(v) {
        const cancelada = v.status === "Cancelada";
        const pagamento = v.forma_pagamento + (v.parcelas_cartao ? ` (${v.parcelas_cartao}x)` : "");
        return `
            <tr data-id="${v.id}" ${cancelada ? 'style="opacity: 0.6;"' : ""}>
                <td class="mono">#${v.id}</td>
                <td>${new Date(v.data_venda).toLocaleString("pt-BR", { dateStyle: "short", timeStyle: "short" })}</td>
                <td><strong>${escapar(v.cliente_nome || "Cliente Balcão")}</strong></td>
                <td><small>${escapar(v.itens_resumo || "")}</small></td>
                <td><span class="badge ${v.forma_pagamento === "Fiado" ? "badge-warning" : "badge-info"}">${escapar(pagamento)}</span></td>
                <td class="mono"><strong>${paraReais(v.valor_total)}</strong></td>
                <td><span class="badge ${cancelada ? "badge-neutral" : "badge-success"}">${v.status}</span></td>
                <td>
                    <a class="btn-icon btn-icon--view" title="Imprimir Recibo" href="recibo.php?venda=${v.id}" target="_blank" style="text-decoration: none;"><i class="fa-solid fa-file-invoice"></i></a>
                    ${ehAdmin && !cancelada ? '<button type="button" class="btn-icon btn-icon--delete" data-acao="cancelar" title="Cancelar venda"><i class="fa-solid fa-ban"></i></button>' : ""}
                </td>
            </tr>`;
    }

    historicoEl.addEventListener("click", async (evento) => {
        const botao = evento.target.closest('button[data-acao="cancelar"]');
        if (!botao) return;

        const id = botao.closest("tr").dataset.id;
        const confirmado = await ModaSysModal.confirmar(
            `Cancelar a venda #${id}? As peças voltam pro estoque e as parcelas de fiado (se houver) são apagadas.`,
            { textoConfirmar: "Cancelar venda", textoCancelar: "Voltar" }
        );
        if (!confirmado) return;

        try {
            const resposta = await ModaSysAuth.requisitar(`/vendas/${id}/cancelar`, { method: "PATCH" });
            const dados = await resposta.json();
            if (!resposta.ok) throw new Error(dados.erro || "Não foi possível cancelar.");
            await Promise.all([carregarProdutos(), carregarHistorico()]);
        } catch (erro) {
            alert(erro.message);
        }
    });

    // ---- Carregamento de dados ----
    async function buscar(caminho) {
        const resposta = await ModaSysAuth.requisitar(`/${caminho}`);
        const dados = await resposta.json();
        if (!resposta.ok) throw new Error(dados.erro || `Falha ao carregar ${caminho}.`);
        return dados;
    }

    async function carregarProdutos() {
        produtosCache = (await buscar("produtos")).filter(p => p.ativo);
        datalist.innerHTML = produtosCache
            .map(p => `<option value="${escapar(p.codigo_interno)}">${escapar(p.nome)} — ${paraReais(p.preco_venda)} (${p.estoque_atual} un.)</option>`)
            .join("");

        // Atualiza o saldo dos produtos que já estão no carrinho.
        carrinho.forEach(linha => {
            const atualizado = produtosCache.find(p => p.id === linha.produto.id);
            if (atualizado) linha.produto = atualizado;
        });
    }

    async function carregarClientes() {
        clientesCache = await buscar("clientes");
        clientesCache.forEach(c => {
            const opcao = document.createElement("option");
            opcao.value = c.id;
            opcao.textContent = c.nome;
            cliente.appendChild(opcao);
        });
    }

    async function carregarHistorico() {
        try {
            const vendas = await buscar("vendas");
            historicoEl.innerHTML = vendas.length
                ? vendas.map(linhaHistorico).join("")
                : '<tr><td colspan="8" class="text-muted">Nenhuma venda registrada ainda.</td></tr>';
        } catch (erro) {
            historicoEl.innerHTML = `<tr><td colspan="8" class="text-rust">Falha ao carregar: ${escapar(erro.message)}</td></tr>`;
        }
    }

    Promise.all([carregarProdutos(), carregarClientes()]).catch(erro => mostrarMensagem(erro.message));
    carregarHistorico();
    renderizarCarrinho();
});
