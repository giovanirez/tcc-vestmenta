document.addEventListener("DOMContentLoaded", () => {
    const formCard = document.getElementById("prod-form-card");
    const form = document.getElementById("prod-form");
    const idInput = document.getElementById("prod-id");
    const nome = document.getElementById("prod-nome");
    const categoria = document.getElementById("prod-categoria");
    const fornecedor = document.getElementById("prod-fornecedor");
    const precoVenda = document.getElementById("prod-preco-venda");
    const descricao = document.getElementById("prod-descricao");
    const cancelarBtn = document.getElementById("prod-cancelar-btn");
    const mensagemEl = document.getElementById("prod-mensagem");
    const listaEl = document.getElementById("prod-lista");

    if (!listaEl) return;

    let produtosCache = [];

    function formatarPreco(valor) {
        return "R$ " + Number(valor).toFixed(2).replace(".", ",");
    }

    function linhaProduto(p) {
        return `
            <tr data-id="${p.id}">
                <td class="mono">#${p.id}</td>
                <td>${p.nome}</td>
                <td>${p.categoria_nome || "—"}</td>
                <td>${p.fornecedor_nome || "—"}</td>
                <td class="mono">${p.estoque_atual} un.</td>
                <td class="mono">${formatarPreco(p.preco_venda)}</td>
                <td><span class="badge ${p.ativo ? "badge-success" : "badge-neutral"}">${p.ativo ? "Ativo" : "Inativo"}</span></td>
                <td>
                    <button type="button" class="btn-icon btn-icon--edit" data-acao="editar" title="Editar"><i class="fa-solid fa-pen-to-square"></i></button>
                    <button type="button" class="btn-icon btn-icon--delete" data-acao="alternar" title="${p.ativo ? "Desativar" : "Reativar"}"><i class="fa-solid ${p.ativo ? "fa-ban" : "fa-rotate-left"}"></i></button>
                </td>
            </tr>`;
    }

    async function carregarLista() {
        try {
            const resposta = await ModaSysAuth.requisitar("backend/public/index.php/produtos");
            produtosCache = await resposta.json();

            listaEl.innerHTML = produtosCache.length
                ? produtosCache.map(linhaProduto).join("")
                : '<tr><td colspan="8" class="text-muted">Nenhum produto cadastrado ainda.</td></tr>';
        } catch (erro) {
            listaEl.innerHTML = `<tr><td colspan="8" class="text-rust">Falha ao carregar: ${erro.message}</td></tr>`;
        }
    }

    async function carregarOpcoes(select, caminho, rotuloCampo) {
        try {
            const resposta = await ModaSysAuth.requisitar(`backend/public/index.php/${caminho}`);
            const itens = await resposta.json();
            itens.forEach(item => {
                const opcao = document.createElement("option");
                opcao.value = item.id;
                opcao.textContent = item[rotuloCampo];
                select.appendChild(opcao);
            });
        } catch (erro) {
            console.error(`Falha ao carregar ${caminho}:`, erro);
        }
    }

    function abrirEdicao(produto) {
        idInput.value = produto.id;
        nome.value = produto.nome;
        descricao.value = produto.descricao || "";
        precoVenda.value = produto.preco_venda;
        categoria.value = produto.categoria_id || "";
        fornecedor.value = produto.fornecedor_id || "";

        document.getElementById("prod-form-titulo").textContent = `Editando: ${produto.nome}`;
        formCard.style.display = "block";
        formCard.scrollIntoView({ behavior: "smooth" });
    }

    function fecharEdicao() {
        formCard.style.display = "none";
        form.reset();
        mensagemEl.style.display = "none";
    }

    form.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        if (!form.checkValidity()) return;

        mensagemEl.style.display = "none";

        try {
            const resposta = await ModaSysAuth.requisitar(`backend/public/index.php/produtos/${idInput.value}`, {
                method: "PUT",
                body: JSON.stringify({
                    nome: nome.value,
                    descricao: descricao.value,
                    categoria_id: categoria.value || null,
                    fornecedor_id: fornecedor.value || null,
                    preco_venda: precoVenda.value,
                }),
            });

            const dados = await resposta.json();
            if (!resposta.ok) throw new Error(dados.erro || "Não foi possível salvar.");

            fecharEdicao();
            await carregarLista();
        } catch (erro) {
            mensagemEl.textContent = erro.message;
            mensagemEl.style.display = "block";
        }
    });

    cancelarBtn.addEventListener("click", fecharEdicao);

    listaEl.addEventListener("click", async (evento) => {
        const botao = evento.target.closest("button[data-acao]");
        if (!botao) return;

        const id = botao.closest("tr").dataset.id;
        const produto = produtosCache.find(p => String(p.id) === id);

        if (botao.dataset.acao === "editar") {
            abrirEdicao(produto);
            return;
        }

        if (botao.dataset.acao === "alternar") {
            const acaoTexto = produto.ativo ? "desativar" : "reativar";
            const confirmado = await ModaSysModal.confirmar(
                `Tem certeza que quer ${acaoTexto} "${produto.nome}"?`,
                { textoConfirmar: produto.ativo ? "Desativar" : "Reativar" }
            );
            if (!confirmado) return;

            try {
                await ModaSysAuth.requisitar(`backend/public/index.php/produtos/${id}/ativo`, { method: "PATCH" });
                await carregarLista();
            } catch (erro) {
                alert(erro.message);
            }
        }
    });

    carregarOpcoes(categoria, "categorias", "nome");
    carregarOpcoes(fornecedor, "fornecedores", "razao_social");
    carregarLista();
});
