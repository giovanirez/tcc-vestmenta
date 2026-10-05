document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("cf-form");
    if (!form) return;


    const idInput = document.getElementById("cf-id");
    const nome = document.getElementById("cf-nome");
    const categoria = document.getElementById("cf-categoria");
    const valor = document.getElementById("cf-valor");
    const dia = document.getElementById("cf-dia");
    const observacao = document.getElementById("cf-observacao");
    const titulo = document.getElementById("cf-form-titulo");
    const submitBtn = document.getElementById("cf-submit-btn");
    const cancelarBtn = document.getElementById("cf-cancelar-btn");
    const mensagemEl = document.getElementById("cf-mensagem");
    const listaEl = document.getElementById("cf-lista");

    let custosCache = [];

    function paraReais(v) {
        return "R$ " + Number(v).toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapar(texto) {
        const div = document.createElement("div");
        div.textContent = texto ?? "";
        return div.innerHTML;
    }

    function atualizarResumo() {
        const ativos = custosCache.filter(c => c.ativo);
        document.getElementById("cf-total-mensal").textContent = paraReais(ativos.reduce((soma, c) => soma + Number(c.valor), 0));
        document.getElementById("cf-qtd-ativos").textContent = ativos.length;
    }

    function linhaCusto(c) {
        return `
            <tr data-id="${c.id}">
                <td><strong>${escapar(c.nome)}</strong>${c.observacao ? `<br><small class="text-muted">${escapar(c.observacao)}</small>` : ""}</td>
                <td>${escapar(c.categoria || "—")}</td>
                <td class="mono">${paraReais(c.valor)}</td>
                <td class="mono">dia ${c.dia_vencimento}</td>
                <td><span class="badge ${c.ativo ? "badge-success" : "badge-neutral"}">${c.ativo ? "Ativo" : "Inativo"}</span></td>
                <td>
                    <button type="button" class="btn-icon btn-icon--edit" data-acao="editar" title="Editar"><i class="fa-solid fa-pen-to-square"></i></button>
                    <button type="button" class="btn-icon btn-icon--delete" data-acao="alternar" title="${c.ativo ? "Desativar" : "Reativar"}"><i class="fa-solid ${c.ativo ? "fa-ban" : "fa-rotate-left"}"></i></button>
                </td>
            </tr>`;
    }

    async function carregarLista() {
        try {
            const resposta = await ModaSysAuth.requisitar(`/custos-fixos`);
            const dados = await resposta.json();
            if (!resposta.ok) throw new Error(dados.erro || "Falha ao carregar.");

            custosCache = dados;
            listaEl.innerHTML = custosCache.length
                ? custosCache.map(linhaCusto).join("")
                : '<tr><td colspan="6" class="text-muted">Nenhum custo fixo cadastrado ainda.</td></tr>';
            atualizarResumo();
        } catch (erro) {
            listaEl.innerHTML = `<tr><td colspan="6" class="text-rust">Falha ao carregar: ${escapar(erro.message)}</td></tr>`;
        }
    }

    function abrirEdicao(custo) {
        idInput.value = custo.id;
        nome.value = custo.nome;
        categoria.value = custo.categoria || "";
        valor.value = custo.valor;
        dia.value = custo.dia_vencimento;
        observacao.value = custo.observacao || "";

        titulo.textContent = `Editando: ${custo.nome}`;
        cancelarBtn.style.display = "inline-block";
        form.scrollIntoView({ behavior: "smooth" });
    }

    function limparFormulario() {
        form.reset();
        idInput.value = "";
        titulo.textContent = "Novo Custo Fixo";
        cancelarBtn.style.display = "none";
        mensagemEl.style.display = "none";
    }

    form.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        if (!form.checkValidity()) return;
        mensagemEl.style.display = "none";

        const editando = Boolean(idInput.value);
        submitBtn.disabled = true;
        try {
            const resposta = await ModaSysAuth.requisitar(`/custos-fixos${editando ? `/${idInput.value}` : ""}`, {
                method: editando ? "PUT" : "POST",
                body: JSON.stringify({
                    nome: nome.value,
                    categoria: categoria.value,
                    valor: valor.value,
                    dia_vencimento: dia.value,
                    observacao: observacao.value,
                }),
            });
            const dados = await resposta.json();
            if (!resposta.ok) throw new Error(dados.erro || "Não foi possível salvar.");

            limparFormulario();
            await carregarLista();
        } catch (erro) {
            mensagemEl.textContent = erro.message;
            mensagemEl.style.display = "block";
        } finally {
            submitBtn.disabled = false;
        }
    });

    cancelarBtn.addEventListener("click", limparFormulario);

    listaEl.addEventListener("click", async (evento) => {
        const botao = evento.target.closest("button[data-acao]");
        if (!botao) return;

        const id = botao.closest("tr").dataset.id;
        const custo = custosCache.find(c => String(c.id) === id);

        if (botao.dataset.acao === "editar") {
            abrirEdicao(custo);
            return;
        }

        const acaoTexto = custo.ativo ? "desativar" : "reativar";
        const confirmado = await ModaSysModal.confirmar(
            `Tem certeza que quer ${acaoTexto} "${custo.nome}"?`,
            { textoConfirmar: custo.ativo ? "Desativar" : "Reativar" }
        );
        if (!confirmado) return;

        try {
            const resposta = await ModaSysAuth.requisitar(`/custos-fixos/${id}/ativo`, { method: "PATCH" });
            if (!resposta.ok) throw new Error((await resposta.json()).erro || "Não foi possível alterar.");
            await carregarLista();
        } catch (erro) {
            alert(erro.message);
        }
    });

    carregarLista();
});
