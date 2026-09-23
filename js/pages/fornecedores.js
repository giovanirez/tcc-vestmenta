document.addEventListener("DOMContentLoaded", () => {
    const razao = document.getElementById("forn-razao");
    const cnpj = document.getElementById("forn-cnpj");
    const telefone = document.getElementById("forn-telefone");
    const email = document.getElementById("forn-email");
    const cep = document.getElementById("forn-cep");
    const logradouro = document.getElementById("forn-logradouro");
    const numero = document.getElementById("forn-numero");
    const complemento = document.getElementById("forn-complemento");
    const bairro = document.getElementById("forn-bairro");
    const cidade = document.getElementById("forn-cidade");
    const uf = document.getElementById("forn-uf");

    if (!cnpj || !cep) return;

    ModaSysMascaras.aplicar(cnpj, "cnpj");
    ModaSysMascaras.aplicar(telefone, "telefone");
    ModaSysMascaras.aplicar(cep, "cep");
    ModaSysMascaras.aplicar(uf, "uf");

    // Evita buscar o mesmo CEP de novo — importante porque a busca de
    // CNPJ também preenche o CEP, e não queremos disparar uma segunda
    // consulta desnecessária (ou sobrescrever os dados que já vieram do CNPJ).
    let ultimoCepConsultado = null;

    function preencherEndereco(dados) {
        logradouro.value = dados.logradouro || logradouro.value;
        bairro.value = dados.bairro || bairro.value;
        cidade.value = dados.cidade || cidade.value;
        uf.value = dados.uf || uf.value;
        if (dados.complemento) complemento.value = dados.complemento;
    }

    async function buscarCep(valor) {
        const cepLimpo = valor.replace(/\D/g, "");
        if (cepLimpo.length !== 8 || cepLimpo === ultimoCepConsultado) return;

        try {
            const resposta = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
            const dados = await resposta.json();
            if (dados.erro) return;

            preencherEndereco({
                logradouro: dados.logradouro,
                bairro: dados.bairro,
                cidade: dados.localidade,
                uf: dados.uf,
                complemento: dados.complemento
            });
            ultimoCepConsultado = cepLimpo;
        } catch (erro) {
            console.error("Falha ao consultar o CEP:", erro);
        }
    }

    async function buscarCnpj(valor) {
        const cnpjLimpo = valor.replace(/\D/g, "");
        if (cnpjLimpo.length !== 14) return;

        try {
            const resposta = await fetch(`https://brasilapi.com.br/api/cnpj/v1/${cnpjLimpo}`);
            if (!resposta.ok) return;
            const dados = await resposta.json();

            if (dados.razao_social && !razao.value) razao.value = dados.razao_social;
            if (dados.email && !email.value) email.value = dados.email;
            if (dados.ddd_telefone_1 && !telefone.value) telefone.value = dados.ddd_telefone_1;
            if (dados.numero) numero.value = dados.numero;

            preencherEndereco({
                logradouro: dados.logradouro,
                bairro: dados.bairro,
                cidade: dados.municipio,
                uf: dados.uf,
                complemento: dados.complemento
            });

            if (dados.cep) {
                const cepLimpo = String(dados.cep).replace(/\D/g, "");
                cep.value = cepLimpo.replace(/(\d{5})(\d{3})/, "$1-$2");
                // já resolvido pelo CNPJ: marca como consultado para o
                // blur do campo CEP não repetir a mesma busca.
                ultimoCepConsultado = cepLimpo;
            }
        } catch (erro) {
            console.error("Falha ao consultar o CNPJ:", erro);
        }
    }

    cep.addEventListener("blur", () => buscarCep(cep.value));
    cnpj.addEventListener("blur", () => buscarCnpj(cnpj.value));

    // ---- Lista + criar/editar/ativar via API ----
    const form = document.getElementById("forn-form");
    const idInput = document.getElementById("forn-id");
    const tituloForm = document.getElementById("forn-form-titulo");
    const submitBtn = document.getElementById("forn-submit-btn");
    const cancelarBtn = document.getElementById("forn-cancelar-btn");
    const mensagemEl = document.getElementById("forn-mensagem");
    const listaEl = document.getElementById("forn-lista");

    if (!form || !listaEl) return;

    function mostrarErro(texto) {
        mensagemEl.textContent = texto;
        mensagemEl.style.display = "block";
    }

    function limparErro() {
        mensagemEl.style.display = "none";
    }

    function entrarModoEdicao(fornecedor) {
        idInput.value = fornecedor.id;
        razao.value = fornecedor.razao_social;
        cnpj.value = fornecedor.cnpj;
        telefone.value = fornecedor.telefone;
        email.value = fornecedor.email;
        cep.value = fornecedor.cep || "";
        logradouro.value = fornecedor.logradouro || "";
        numero.value = fornecedor.numero || "";
        complemento.value = fornecedor.complemento || "";
        bairro.value = fornecedor.bairro || "";
        cidade.value = fornecedor.cidade || "";
        uf.value = fornecedor.uf || "";

        tituloForm.textContent = `Editando: ${fornecedor.razao_social}`;
        submitBtn.innerHTML = '<i class="fa-solid fa-save"></i> Atualizar Fornecedor';
        cancelarBtn.style.display = "inline-block";
        form.scrollIntoView({ behavior: "smooth" });
    }

    function sairModoEdicao() {
        idInput.value = "";
        form.reset();
        tituloForm.textContent = "Novo Fornecedor";
        submitBtn.innerHTML = '<i class="fa-solid fa-save"></i> Guardar Fornecedor';
        cancelarBtn.style.display = "none";
    }

    function linhaFornecedor(f) {
        const endereco = f.logradouro
            ? `${f.logradouro}, ${f.numero || "s/n"} - ${f.bairro || ""}, ${f.cidade || ""}/${f.uf || ""}`
            : "—";

        return `
            <tr data-id="${f.id}">
                <td class="mono">#${f.id}</td>
                <td><strong>${f.razao_social}</strong></td>
                <td class="mono">${f.cnpj}</td>
                <td>
                    <div><i class="fa-solid fa-phone text-muted" style="font-size:0.8rem;"></i> ${f.telefone}</div>
                    <div><i class="fa-solid fa-envelope text-muted" style="font-size:0.8rem;"></i> ${f.email}</div>
                </td>
                <td><small>${endereco}</small></td>
                <td><span class="badge ${f.ativo ? "badge-success" : "badge-neutral"}">${f.ativo ? "Ativo" : "Inativo"}</span></td>
                <td>
                    <button type="button" class="btn-icon btn-icon--edit" data-acao="editar" title="Editar"><i class="fa-solid fa-pen-to-square"></i></button>
                    <button type="button" class="btn-icon btn-icon--delete" data-acao="alternar" title="${f.ativo ? "Desativar" : "Reativar"}"><i class="fa-solid ${f.ativo ? "fa-ban" : "fa-rotate-left"}"></i></button>
                </td>
            </tr>`;
    }

    let fornecedoresCache = [];

    async function carregarLista() {
        try {
            const resposta = await ModaSysAuth.requisitar("backend/public/index.php/fornecedores");
            fornecedoresCache = await resposta.json();

            listaEl.innerHTML = fornecedoresCache.length
                ? fornecedoresCache.map(linhaFornecedor).join("")
                : '<tr><td colspan="7" class="text-muted">Nenhum fornecedor cadastrado ainda.</td></tr>';
        } catch (erro) {
            listaEl.innerHTML = `<tr><td colspan="7" class="text-rust">Falha ao carregar: ${erro.message}</td></tr>`;
        }
    }

    form.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        if (!form.checkValidity()) return;

        limparErro();
        const emEdicao = idInput.value !== "";
        const corpo = {
            razao_social: razao.value,
            cnpj: cnpj.value,
            telefone: telefone.value,
            email: email.value,
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
                    ? `backend/public/index.php/fornecedores/${idInput.value}`
                    : "backend/public/index.php/fornecedores",
                { method: emEdicao ? "PUT" : "POST", body: JSON.stringify(corpo) }
            );

            const dados = await resposta.json();
            if (!resposta.ok) throw new Error(dados.erro || "Não foi possível salvar.");

            sairModoEdicao();
            await carregarLista();
        } catch (erro) {
            mostrarErro(erro.message);
        }
    });

    cancelarBtn.addEventListener("click", sairModoEdicao);

    listaEl.addEventListener("click", async (evento) => {
        const botao = evento.target.closest("button[data-acao]");
        if (!botao) return;

        const linha = botao.closest("tr");
        const id = linha.dataset.id;
        const fornecedor = fornecedoresCache.find(f => String(f.id) === id);

        if (botao.dataset.acao === "editar") {
            entrarModoEdicao(fornecedor);
            return;
        }

        if (botao.dataset.acao === "alternar") {
            const acaoTexto = fornecedor.ativo ? "desativar" : "reativar";
            const confirmado = await ModaSysModal.confirmar(
                `Tem certeza que quer ${acaoTexto} "${fornecedor.razao_social}"?`,
                { textoConfirmar: fornecedor.ativo ? "Desativar" : "Reativar" }
            );
            if (!confirmado) return;

            try {
                await ModaSysAuth.requisitar(`backend/public/index.php/fornecedores/${id}/ativo`, { method: "PATCH" });
                await carregarLista();
            } catch (erro) {
                alert(erro.message);
            }
        }
    });

    carregarLista();
});
