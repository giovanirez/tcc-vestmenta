document.addEventListener("DOMContentLoaded", () => {
    const cep = document.getElementById("cli-cep");
    const logradouro = document.getElementById("cli-logradouro");
    const complemento = document.getElementById("cli-complemento");
    const bairro = document.getElementById("cli-bairro");
    const cidade = document.getElementById("cli-cidade");
    const uf = document.getElementById("cli-uf");

    if (!cep) return;

    ModaSysMascaras.aplicar(cep, "cep");
    ModaSysMascaras.aplicar(uf, "uf");

    let ultimoCepConsultado = null;

    cep.addEventListener("blur", async () => {
        const cepLimpo = cep.value.replace(/\D/g, "");
        if (cepLimpo.length !== 8 || cepLimpo === ultimoCepConsultado) return;

        try {
            const resposta = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
            const dados = await resposta.json();
            if (dados.erro) return;

            logradouro.value = dados.logradouro || "";
            bairro.value = dados.bairro || "";
            cidade.value = dados.localidade || "";
            uf.value = dados.uf || "";
            if (dados.complemento) complemento.value = dados.complemento;

            ultimoCepConsultado = cepLimpo;
        } catch (erro) {
            console.error("Falha ao consultar o CEP:", erro);
        }
    });

    // ---- Lista + criar/editar via API ----
    const form = document.getElementById("cli-form");
    const idInput = document.getElementById("cli-id");
    const nome = document.getElementById("cli-nome");
    const telefone = document.getElementById("cli-telefone");
    const cpf = document.getElementById("cli-cpf");
    const email = document.getElementById("cli-email");
    const limiteCredito = document.getElementById("cli-limite-credito");
    const numero = document.getElementById("cli-numero");
    const tituloForm = document.getElementById("cli-form-titulo");
    const submitBtn = document.getElementById("cli-submit-btn");
    const cancelarBtn = document.getElementById("cli-cancelar-btn");
    const mensagemEl = document.getElementById("cli-mensagem");
    const listaEl = document.getElementById("cli-lista");

    if (!form || !listaEl) return;

    ModaSysMascaras.aplicar(telefone, "telefone");
    ModaSysMascaras.aplicar(cpf, "cpf");

    let clientesCache = [];

    function linhaCliente(c) {
        const endereco = c.logradouro
            ? `${c.logradouro}, ${c.numero || "s/n"} - ${c.bairro || ""}, ${c.cidade || ""}/${c.uf || ""}`
            : "—";
        const emailLinha = c.email
            ? `<div><i class="fa-solid fa-envelope text-muted" style="font-size:0.8rem;"></i> ${c.email}</div>`
            : "";

        return `
            <tr data-id="${c.id}">
                <td class="mono">#${c.id}</td>
                <td><strong>${c.nome}</strong></td>
                <td class="mono">${c.cpf || "—"}</td>
                <td>
                    <div><i class="fa-solid fa-mobile-screen text-muted" style="font-size:0.8rem;"></i> ${c.telefone}</div>
                    ${emailLinha}
                </td>
                <td><small>${endereco}</small></td>
                <td>
                    <button type="button" class="btn-icon btn-icon--edit" data-acao="editar" title="Editar"><i class="fa-solid fa-pen-to-square"></i></button>
                </td>
            </tr>`;
    }

    async function carregarLista() {
        try {
            const resposta = await ModaSysAuth.requisitar("backend/public/index.php/clientes");
            clientesCache = await resposta.json();

            listaEl.innerHTML = clientesCache.length
                ? clientesCache.map(linhaCliente).join("")
                : '<tr><td colspan="6" class="text-muted">Nenhum cliente cadastrado ainda.</td></tr>';
        } catch (erro) {
            listaEl.innerHTML = `<tr><td colspan="6" class="text-rust">Falha ao carregar: ${erro.message}</td></tr>`;
        }
    }

    function entrarModoEdicao(cliente) {
        idInput.value = cliente.id;
        nome.value = cliente.nome;
        telefone.value = cliente.telefone;
        cpf.value = cliente.cpf || "";
        email.value = cliente.email || "";
        limiteCredito.value = cliente.limite_credito || "";
        cep.value = cliente.cep || "";
        logradouro.value = cliente.logradouro || "";
        numero.value = cliente.numero || "";
        complemento.value = cliente.complemento || "";
        bairro.value = cliente.bairro || "";
        cidade.value = cliente.cidade || "";
        uf.value = cliente.uf || "";

        tituloForm.textContent = `Editando: ${cliente.nome}`;
        submitBtn.innerHTML = '<i class="fa-solid fa-save"></i> Atualizar Cliente';
        cancelarBtn.style.display = "inline-block";
        form.scrollIntoView({ behavior: "smooth" });
    }

    function sairModoEdicao() {
        idInput.value = "";
        form.reset();
        tituloForm.textContent = "Novo Cliente";
        submitBtn.innerHTML = '<i class="fa-solid fa-save"></i> Guardar Cliente';
        cancelarBtn.style.display = "none";
    }

    form.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        if (!form.checkValidity()) return;

        mensagemEl.style.display = "none";
        const emEdicao = idInput.value !== "";
        const corpo = {
            nome: nome.value,
            telefone: telefone.value,
            cpf: cpf.value,
            email: email.value,
            limite_credito: limiteCredito.value || null,
            cep: cep.value,
            logradouro: logradouro.value,
            numero: numero.value,
            complemento: complemento.value,
            bairro: bairro.value,
            cidade: cidade.value,
            uf: uf.value,
        };

        try {
            const resposta = await ModaSysAuth.requisitar(
                emEdicao
                    ? `backend/public/index.php/clientes/${idInput.value}`
                    : "backend/public/index.php/clientes",
                { method: emEdicao ? "PUT" : "POST", body: JSON.stringify(corpo) }
            );

            const dados = await resposta.json();
            if (!resposta.ok) throw new Error(dados.erro || "Não foi possível salvar.");

            sairModoEdicao();
            await carregarLista();
        } catch (erro) {
            mensagemEl.textContent = erro.message;
            mensagemEl.style.display = "block";
        }
    });

    cancelarBtn.addEventListener("click", sairModoEdicao);

    listaEl.addEventListener("click", (evento) => {
        const botao = evento.target.closest("button[data-acao='editar']");
        if (!botao) return;

        const id = botao.closest("tr").dataset.id;
        const cliente = clientesCache.find(c => String(c.id) === id);
        entrarModoEdicao(cliente);
    });

    carregarLista();
});
