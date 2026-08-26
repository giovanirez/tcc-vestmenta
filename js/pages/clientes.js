document.addEventListener("DOMContentLoaded", () => {
    const cep = document.getElementById("cli-cep");
    const logradouro = document.getElementById("cli-logradouro");
    const complemento = document.getElementById("cli-complemento");
    const bairro = document.getElementById("cli-bairro");
    const cidade = document.getElementById("cli-cidade");
    const uf = document.getElementById("cli-uf");

    if (!cep) return;

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
});
