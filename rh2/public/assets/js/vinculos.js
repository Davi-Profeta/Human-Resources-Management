// "DADOS" é criado pelo vinculos.php (vínculos e colaboradores)

/* ============================ ELEMENTOS ============================== */
const modalForm = document.getElementById("modalForm");
const modalEncerrar = document.getElementById("modalEncerrar");
const modalApagar = document.getElementById("modalApagar");

const formVinc = document.getElementById("formVinculo");
const tituloForm = document.getElementById("tituloForm");
const acaoForm = document.getElementById("acaoForm");
const idForm = document.getElementById("idForm");
const erroForm = document.getElementById("erroForm");
const btnSalvar = document.getElementById("btnSalvar");
const selectColab = document.getElementById("fColab");
const selectEmpresa = document.getElementById("fEmpresa");

const formEncerrar = document.getElementById("formEncerrar");
const idEncerrar = document.getElementById("idEncerrar");
const nomeEncerrar = document.getElementById("nomeEncerrar");
const dataEncerrar = document.getElementById("eData");
const erroEncerrar = document.getElementById("erroEncerrar");

const idExcluir = document.getElementById("idExcluir");
const nomeExcluir = document.getElementById("nomeExcluir");

/* ====================== FECHAR MODAIS (geral) ======================== */
// botões "Cancelar"
document.querySelectorAll("[data-fechar]").forEach(botao => {
    botao.addEventListener("click", () => botao.closest("dialog").close());
});

// clicar fora da caixa (no fundo escuro) também fecha
document.querySelectorAll("dialog.modal").forEach(modal => {
    modal.addEventListener("click", e => {
        if (e.target === modal) modal.close();
    });
});

/* ====================== COLABORADOR DO FORMULÁRIO ==================== */
// monta o select com os colaboradores que ainda não têm vínculo ativo
function listarColaboradoresLivres() {

    selectColab.replaceChildren();

    const livres = DADOS.colaboradores.filter(c => !c.ocupado);

    if (livres.length === 0) {

        const op = document.createElement("option");
        op.value = "";
        op.textContent = "Nenhum colaborador disponível";
        selectColab.appendChild(op);

    } else {

        livres.forEach(c => {
            const op = document.createElement("option");
            op.value = c.id;
            op.textContent = c.nome;
            selectColab.appendChild(op);
        });

    }

    selectColab.disabled = livres.length === 0;
    btnSalvar.disabled = livres.length === 0;

    return livres;
}

// ao escolher o colaborador, já sugere a empresa dele
selectColab.addEventListener("change", () => {

    const c = DADOS.colaboradores.find(x => String(x.id) === selectColab.value);

    if (c && c.empresa !== null) selectEmpresa.value = c.empresa;

});

/* ====================== NOVO VÍNCULO ================================= */
document.getElementById("novo").addEventListener("click", () => {

    formVinc.reset();
    tituloForm.textContent = "Cadastrar vínculo";
    acaoForm.value = "cadastrar";
    idForm.value = "";
    erroForm.textContent = "";

    const livres = listarColaboradoresLivres();

    if (livres.length > 0 && livres[0].empresa !== null) selectEmpresa.value = livres[0].empresa;

    formVinc.elements["data_inicio"].value = DADOS.hoje;
    modalForm.showModal();

});

/* ====================== EDITAR VÍNCULO =============================== */
document.querySelectorAll(".btn-editar").forEach(botao => {

    botao.addEventListener("click", () => {

        const v = DADOS.vinculos[botao.dataset.id];
        if (!v) return;

        formVinc.reset();
        tituloForm.textContent = "Editar vínculo";
        acaoForm.value = "editar";
        idForm.value = botao.dataset.id;
        erroForm.textContent = "";

        // na edição o colaborador não muda: só mostra o nome dele
        selectColab.replaceChildren();
        const op = document.createElement("option");
        op.value = v.colaborador ?? "";
        op.textContent = v.nomeColab;
        selectColab.appendChild(op);
        selectColab.disabled = true;
        selectColab.required = false;
        btnSalvar.disabled = false;

        formVinc.elements["empresa"].value = v.empresa === null ? "" : v.empresa;
        if (formVinc.elements["tipo"]) formVinc.elements["tipo"].value = v.tipo === null ? "" : v.tipo;
        formVinc.elements["cargo"].value = v.cargo;
        formVinc.elements["salario"].value = v.salario;
        formVinc.elements["data_inicio"].value = v.inicio;
        formVinc.elements["data_termino"].value = v.termino;

        modalForm.showModal();

    });

});

// volta a exigir o colaborador no cadastro
modalForm.addEventListener("close", () => selectColab.required = true);

/* ========= validação simples antes de enviar (término >= início) ====== */
formVinc.addEventListener("submit", e => {

    const inicio = formVinc.elements["data_inicio"].value;
    const termino = formVinc.elements["data_termino"].value;

    if (termino && termino < inicio) {
        e.preventDefault();
        erroForm.textContent = "A data de término não pode ser antes da data de início.";
        formVinc.elements["data_termino"].focus();
    }

});

formVinc.elements["data_inicio"].addEventListener("input", () => erroForm.textContent = "");
formVinc.elements["data_termino"].addEventListener("input", () => erroForm.textContent = "");

/* ====================== ENCERRAR VÍNCULO ============================= */
document.querySelectorAll(".btn-encerrar").forEach(botao => {

    botao.addEventListener("click", () => {

        const v = DADOS.vinculos[botao.dataset.id];
        if (!v) return;

        idEncerrar.value = botao.dataset.id;
        nomeEncerrar.textContent = v.nomeColab;
        dataEncerrar.min = v.inicio;
        dataEncerrar.value = DADOS.hoje < v.inicio ? v.inicio : DADOS.hoje;
        erroEncerrar.textContent = "";
        modalEncerrar.showModal();

    });

});

formEncerrar.addEventListener("submit", e => {

    const v = DADOS.vinculos[idEncerrar.value];

    if (v && dataEncerrar.value < v.inicio) {
        e.preventDefault();
        erroEncerrar.textContent = "A data de término não pode ser antes do início do vínculo.";
    }

});

/* ====================== APAGAR VÍNCULO =============================== */
document.querySelectorAll(".btn-excluir").forEach(botao => {

    botao.addEventListener("click", () => {

        const v = DADOS.vinculos[botao.dataset.id];
        if (!v) return;

        idExcluir.value = botao.dataset.id;
        nomeExcluir.textContent = v.nomeColab;
        modalApagar.showModal();

    });

});

/* ====================== PESQUISA E FILTRO ============================ */
const campoBusca = document.getElementById("ipt");
const filtroStatus = document.getElementById("filtroStatus");
const linhas = document.querySelectorAll(".vinculo");
const vazio = document.getElementById("vazio");

function filtrar() {

    const texto = campoBusca.value.trim().toLowerCase();
    const status = filtroStatus.value;
    let visiveis = 0;

    linhas.forEach(tr => {

        const bateTexto = tr.dataset.busca.includes(texto);
        const bateStatus = status === "" || tr.dataset.status === status;
        const mostrar = bateTexto && bateStatus;

        tr.hidden = !mostrar;
        if (mostrar) visiveis++;

    });

    // só mostra "nenhum encontrado" quando existem vínculos mas o filtro escondeu todos
    if (linhas.length > 0) {
        vazio.hidden = visiveis > 0;
        vazio.textContent = "Nenhum vínculo encontrado para esse filtro.";
    }

}

campoBusca.addEventListener("input", filtrar);
filtroStatus.addEventListener("change", filtrar);

/* ======= limpa o ?msg=... da URL para o aviso não voltar ao atualizar ==== */
if (location.search.includes("msg=")) {
    history.replaceState(null, "", location.pathname);
}

/* ================= PERFIL DO USUÁRIO (menu com "Sair") ================= */
const perfil = document.querySelector(".profile");
const dialogPerfil = document.getElementById("dialogPerfil");

perfil.addEventListener("click", async () => {

    try {
        const resposta = await fetch("../../src/APIs/loginUser.php");

        if (!resposta.ok) {
            throw new Error("Erro ao carregar perfil");
        }

        dialogPerfil.innerHTML = await resposta.text();
        dialogPerfil.showModal();

    } catch (erro) {
        console.error("Erro ao buscar usuário:", erro);
    }

});

dialogPerfil.addEventListener("click", e => {

    if (e.target === dialogPerfil || e.target.closest("#closeUser")) {
        dialogPerfil.close();
    }

    if (e.target.closest("#logout")) {
        window.top.location.href = new URL("logout.php", window.top.location.href).href;
    }

});