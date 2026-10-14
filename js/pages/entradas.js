document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("ent-form");
    if (!form) return;


    const blocoNf = document.getElementById("tab-conteudo-nf");
    const abas = document.querySelectorAll(".tab-switch-btn");
    const fornecedor = document.getElementById("ent-fornecedor");
    const fornecedorCnpj = document.getElementById("ent-fornecedor-cnpj");
    const fornecedorIe = document.getElementById("ent-fornecedor-ie");
    const dataEntrada = document.getElementById("ent-data-entrada");

    const itemCodigo = document.getElementById("item-codigo");
    const itemDescricao = document.getElementById("item-descricao");
    const itemCategoria = document.getElementById("item-categoria");
    const itemQtd = document.getElementById("item-qtd");
    const custo = document.getElementById("item-custo");
    const lucro = document.getElementById("item-lucro");
    const venda = document.getElementById("item-venda");
    const adicionarBtn = document.getElementById("item-adicionar-btn");
    const datalist = document.getElementById("produtos-existentes");
    const itensEl = document.getElementById("ent-itens");

    const valorProdutosEl = document.getElementById("ent-valor-produtos");
    const valorTotalEl = document.getElementById("ent-valor-total");
    const camposTotais = document.querySelectorAll("[data-total]");
    const mensagemEl = document.getElementById("ent-mensagem");
    const submitBtn = document.getElementById("ent-submit-btn");
    const historicoEl = document.getElementById("ent-historico");

    let modoManual = false;
    let fornecedoresCache = [];
    let produtosCache = [];
    let categoriasCache = [];
    let itens = [];

    function formatarPreco(valor) {
        return "R$ " + Number(valor).toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapar(texto) {
        const div = document.createElement("div");
        div.textContent = texto ?? "";
        return div.innerHTML;
    }

    function mostrarMensagem(texto) {
        mensagemEl.textContent = texto;
        mensagemEl.style.display = texto ? "block" : "none";
    }

    // ---- Alternância entre "Via Nota Fiscal" e "Manual" ----
    abas.forEach(botao => {
        botao.addEventListener("click", () => {
            abas.forEach(b => b.classList.remove("active"));
            botao.classList.add("active");
            modoManual = botao.dataset.tab === "manual";
            blocoNf.style.display = modoManual ? "none" : "block";
        });
    });

    // ---- Cálculo de preço de venda a partir do custo + % de lucro (e vice-versa) ----
    function vendaPeloLucro() {
        const custoVal = parseFloat(custo.value);
        const lucroVal = parseFloat(lucro.value);
        if (!isNaN(custoVal) && !isNaN(lucroVal)) {
            venda.value = (custoVal * (1 + lucroVal / 100)).toFixed(2);
        }
    }

    function lucroPelaVenda() {
        const custoVal = parseFloat(custo.value);
        const vendaVal = parseFloat(venda.value);
        if (!isNaN(custoVal) && custoVal > 0 && !isNaN(vendaVal)) {
            lucro.value = (((vendaVal - custoVal) / custoVal) * 100).toFixed(2);
        }
    }

    lucro.addEventListener("input", vendaPeloLucro);
    venda.addEventListener("input", lucroPelaVenda);
    custo.addEventListener("input", () => {
        if (!isNaN(parseFloat(lucro.value))) vendaPeloLucro();
        else lucroPelaVenda();
    });

    // ---- Fornecedor: mostra CNPJ/IE do cadastro ao escolher ----
    fornecedor.addEventListener("change", () => {
        const f = fornecedoresCache.find(item => String(item.id) === fornecedor.value);
        fornecedorCnpj.value = f ? f.cnpj : "";
        fornecedorIe.value = f ? (f.inscricao_estadual || "") : "";
    });

    // ---- Código do item: se já existe no catálogo, puxa os dados dele
    // pra edição. Descrição, categoria e preço de venda editados aqui
    // atualizam o CADASTRO do produto (valem pras peças que já estavam
    // no estoque também — por isso o aviso). O custo é diferente: vale só
    // pras peças desta entrada e fica registrado nela. ----
    function produtoPorCodigo(codigo) {
        const alvo = codigo.trim().toLowerCase();
        return produtosCache.find(p => p.codigo_interno.toLowerCase() === alvo);
    }

    itemCodigo.addEventListener("change", () => {
        const produto = produtoPorCodigo(itemCodigo.value);
        if (!produto) return;

        itemCodigo.value = produto.codigo_interno;
        itemDescricao.value = produto.nome;
        itemCategoria.value = produto.categoria_id || "";
        // Sugere o custo da última compra; quem digita confirma ou troca.
        custo.value = Number(produto.ultimo_custo) > 0 ? produto.ultimo_custo : "";
        venda.value = Number(produto.preco_venda) > 0 ? produto.preco_venda : "";
        lucro.value = "";
        lucroPelaVenda();
    });

    function limparCamposItem() {
        [itemCodigo, itemDescricao, custo, lucro, venda].forEach(campo => { campo.value = ""; });
        itemCategoria.value = "";
        itemQtd.value = 1;
        itemCodigo.focus();
    }

    function nomeCategoria(id) {
        const categoria = categoriasCache.find(c => String(c.id) === String(id));
        return categoria ? categoria.nome : null;
    }

    // O que muda no cadastro do produto existente, em texto pro aviso.
    function mudancasNoCadastro(existente, nome, categoriaId, precoVenda) {
        const mudancas = [];
        if (nome !== existente.nome) {
            mudancas.push(`• Descrição: "${existente.nome}" → "${nome}"`);
        }
        if (String(categoriaId || "") !== String(existente.categoria_id || "")) {
            mudancas.push(`• Categoria: ${existente.categoria_nome || "sem categoria"} → ${nomeCategoria(categoriaId) || "sem categoria"}`);
        }
        if (precoVenda !== null && Math.round(precoVenda * 100) !== Math.round(Number(existente.preco_venda) * 100)) {
            mudancas.push(`• Preço de venda: ${formatarPreco(existente.preco_venda)} → ${formatarPreco(precoVenda)}`);
        }
        return mudancas;
    }

    adicionarBtn.addEventListener("click", async () => {
        const codigo = itemCodigo.value.trim();
        const nome = itemDescricao.value.trim();
        const quantidade = parseInt(itemQtd.value, 10);
        const custoVal = parseFloat(custo.value);
        const vendaVal = parseFloat(venda.value);
        const precoVenda = isNaN(vendaVal) ? null : vendaVal;
        const categoriaId = itemCategoria.value || null;
        const existente = produtoPorCodigo(codigo);

        if (!codigo) return mostrarMensagem("Informe o código do item.");
        if (!nome) return mostrarMensagem("Informe a descrição do produto.");
        if (!(quantidade >= 1)) return mostrarMensagem("Quantidade precisa ser pelo menos 1.");
        if (isNaN(custoVal) || custoVal < 0) return mostrarMensagem("Informe o custo unitário.");
        mostrarMensagem("");

        let atualizaCadastro = false;
        if (existente) {
            const mudancas = mudancasNoCadastro(existente, nome, categoriaId, precoVenda);
            atualizaCadastro = mudancas.length > 0;

            // Só pergunta se há peças antigas que vão mudar junto.
            if (atualizaCadastro && existente.estoque_atual > 0) {
                const confirmado = await ModaSysModal.confirmar(
                    `O código ${existente.codigo_interno} já tem ${existente.estoque_atual} un. em estoque. ` +
                    "Ao processar esta entrada, o cadastro muda para TODAS as peças, inclusive as que já estavam:\n\n" +
                    mudancas.join("\n") +
                    `\n\nO custo informado (${formatarPreco(custoVal)}) vale só para as ${quantidade} un. desta entrada.`,
                    { textoConfirmar: "Atualizar e adicionar", textoCancelar: "Voltar e revisar" }
                );
                if (!confirmado) return;
            }
        }

        itens.push({
            codigo: existente ? existente.codigo_interno : codigo,
            nome,
            categoria_id: categoriaId,
            categoria_nome: nomeCategoria(categoriaId),
            quantidade,
            custo_unitario: custoVal,
            preco_venda: precoVenda,
            novo: !existente,
            atualiza_cadastro: atualizaCadastro,
        });

        renderizarItens();
        limparCamposItem();
    });

    // Enter no meio da linha de item adiciona o item, em vez de
    // tentar enviar o formulário inteiro.
    document.querySelector(".item-add-grid").addEventListener("keydown", (evento) => {
        if (evento.key === "Enter") {
            evento.preventDefault();
            adicionarBtn.click();
        }
    });

    function renderizarItens() {
        if (!itens.length) {
            itensEl.innerHTML = '<tr><td colspan="8" class="text-muted">Nenhum item adicionado.</td></tr>';
        } else {
            itensEl.innerHTML = itens.map((item, indice) => {
                const lucroItem = item.preco_venda !== null && item.custo_unitario > 0
                    ? ((item.preco_venda - item.custo_unitario) / item.custo_unitario * 100).toFixed(0) + "%"
                    : "—";
                return `
                <tr>
                    <td class="mono" data-label="Código">${escapar(item.codigo)}${item.novo ? ' <span class="badge badge-warning">Novo</span>' : ""}${item.atualiza_cadastro ? ' <span class="badge badge-info" title="Descrição, categoria ou preço de venda vão mudar no cadastro">Atualiza cadastro</span>' : ""}</td>
                    <td data-label="Descrição">${escapar(item.nome)}</td>
                    <td data-label="Categoria">${escapar(item.categoria_nome || "—")}</td>
                    <td class="mono" data-label="Qtd.">${item.quantidade}</td>
                    <td class="mono" data-label="Custo Unit.">${formatarPreco(item.custo_unitario)}</td>
                    <td class="mono" data-label="% Lucro">${lucroItem}</td>
                    <td class="mono" data-label="Vlr. de Venda">${item.preco_venda !== null ? formatarPreco(item.preco_venda) : "—"}</td>
                    <td style="text-align: right;"><button type="button" class="btn-icon btn-icon--delete" data-indice="${indice}" title="Remover"><i class="fa-solid fa-xmark"></i></button></td>
                </tr>`;
            }).join("");
        }
        atualizarTotais();
    }

    itensEl.addEventListener("click", (evento) => {
        const botao = evento.target.closest("button[data-indice]");
        if (!botao) return;
        itens.splice(Number(botao.dataset.indice), 1);
        renderizarItens();
    });

    // ---- Totais: mesma fórmula do backend (vNF da NF-e — ICMS já
    // está embutido no preço, então não soma; IPI soma). O backend
    // recalcula de qualquer jeito, isso aqui é só pré-visualização. ----
    function valorCampo(nome) {
        const campo = document.querySelector(`[data-total="${nome}"]`);
        const valor = parseFloat(campo.value);
        return isNaN(valor) ? 0 : valor;
    }

    function atualizarTotais() {
        const produtos = itens.reduce((soma, item) => soma + item.quantidade * item.custo_unitario, 0);
        const total = produtos + valorCampo("valor_frete") + valorCampo("valor_seguro")
            + valorCampo("valor_outras_despesas") + valorCampo("valor_ipi") - valorCampo("valor_desconto");

        valorProdutosEl.value = produtos.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        valorTotalEl.textContent = formatarPreco(total);
    }

    camposTotais.forEach(campo => campo.addEventListener("input", atualizarTotais));

    // ---- Envio ----
    function valorDe(id) {
        return document.getElementById(id).value.trim();
    }

    form.addEventListener("submit", async (evento) => {
        evento.preventDefault();

        if (!fornecedor.value) return mostrarMensagem("Selecione o fornecedor.");
        if (!dataEntrada.value) return mostrarMensagem("Informe a data de entrada.");
        if (!itens.length) return mostrarMensagem("Adicione pelo menos um item à entrada.");
        mostrarMensagem("");

        const corpo = {
            fornecedor_id: fornecedor.value,
            data_entrada: dataEntrada.value,
            numero_nf: modoManual ? "" : valorDe("ent-numero-nf"),
            serie: modoManual ? "" : valorDe("ent-serie"),
            natureza_operacao: modoManual ? "" : valorDe("ent-natureza"),
            data_emissao: modoManual ? "" : valorDe("ent-data-emissao"),
            chave_acesso: modoManual ? "" : valorDe("ent-chave"),
            modalidade_frete: valorDe("ent-modalidade-frete"),
            transportadora: valorDe("ent-transportadora"),
            placa_veiculo: valorDe("ent-placa"),
            informacoes_complementares: valorDe("ent-info-complementares"),
            itens: itens.map(({ codigo, nome, categoria_id, quantidade, custo_unitario, preco_venda }) =>
                ({ codigo, nome, categoria_id, quantidade, custo_unitario, preco_venda })),
        };
        camposTotais.forEach(campo => { corpo[campo.dataset.total] = valorCampo(campo.dataset.total); });

        const pararCarregando = ModaSysCarregando.botao(submitBtn);
        try {
            const resposta = await ModaSysAuth.requisitar(`/entradas`, {
                method: "POST",
                body: JSON.stringify(corpo),
            });
            const dados = await resposta.json();
            if (!resposta.ok) throw new Error(dados.erro || "Não foi possível processar a entrada.");

            form.reset();
            dataEntrada.value = new Date().toLocaleDateString("sv-SE");
            fornecedorCnpj.value = "";
            fornecedorIe.value = "";
            itens = [];
            renderizarItens();
            // Produtos novos agora existem no catálogo — recarrega pra
            // aparecerem no autocomplete da próxima entrada.
            await Promise.all([carregarProdutos(), carregarHistorico()]);
        } catch (erro) {
            mostrarMensagem(erro.message);
        } finally {
            pararCarregando();
        }
    });

    // ---- Carregamento de dados ----
    async function buscar(caminho) {
        const resposta = await ModaSysAuth.requisitar(`/${caminho}`);
        const dados = await resposta.json();
        if (!resposta.ok) throw new Error(dados.erro || `Falha ao carregar ${caminho}.`);
        return dados;
    }

    async function carregarFornecedores() {
        fornecedoresCache = (await buscar("fornecedores")).filter(f => f.ativo);
        fornecedoresCache.forEach(f => {
            const opcao = document.createElement("option");
            opcao.value = f.id;
            opcao.textContent = f.razao_social;
            fornecedor.appendChild(opcao);
        });
    }

    // Categoria criada pelo "+" entra na lista local, pra o nome dela
    // aparecer na tabela de itens.
    ModaSysSelectComNovo.ligar(itemCategoria, {
        rota: "/categorias",
        rotulo: "categoria",
        tamanhoMaximo: 60,
        aoCriar: (categoria) => categoriasCache.push(categoria),
    });

    async function carregarCategorias() {
        categoriasCache = await buscar("categorias");
        categoriasCache.forEach(c => {
            const opcao = document.createElement("option");
            opcao.value = c.id;
            opcao.textContent = c.nome;
            itemCategoria.appendChild(opcao);
        });
    }

    async function carregarProdutos() {
        produtosCache = await buscar("produtos");
        datalist.innerHTML = produtosCache
            .map(p => `<option value="${escapar(p.codigo_interno)}">${escapar(p.nome)}</option>`)
            .join("");
    }

    async function carregarHistorico() {
        try {
            const entradas = await buscar("entradas");
            historicoEl.innerHTML = entradas.length
                ? entradas.map(e => `
                    <tr>
                        <td class="mono">#${e.id}</td>
                        <td>${new Date(e.data_entrada + "T00:00:00").toLocaleDateString("pt-BR")}</td>
                        <td class="mono">${e.numero_nf ? escapar(e.numero_nf + (e.serie ? ` / ${e.serie}` : "")) : "Sem NF (Manual)"}</td>
                        <td><strong>${escapar(e.fornecedor_nome)}</strong></td>
                        <td class="mono">${e.qtd_total}</td>
                        <td class="mono">${formatarPreco(e.valor_total)}</td>
                        <td><span class="badge ${e.status === "Concluída" ? "badge-success" : "badge-warning"}">${e.status}</span></td>
                    </tr>`).join("")
                : '<tr><td colspan="7" class="text-muted">Nenhuma entrada registrada ainda.</td></tr>';
        } catch (erro) {
            historicoEl.innerHTML = `<tr><td colspan="7" class="text-rust">Falha ao carregar: ${escapar(erro.message)}</td></tr>`;
        }
    }

    Promise.all([carregarFornecedores(), carregarCategorias(), carregarProdutos()])
        .catch(erro => mostrarMensagem(erro.message));
    carregarHistorico();
});
