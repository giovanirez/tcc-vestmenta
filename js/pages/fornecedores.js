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
});
