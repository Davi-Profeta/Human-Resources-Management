// "DADOS" é criado pelo beneficios.php (benefícios, vínculos e colaboradores)

/* ============================ ELEMENTOS ============================== */
const modalForm = document.getElementById("modalForm");
const modalApagar = document.getElementById("modalApagar");
const modalVinc = document.getElementById("modalVinculos");

const formBen = document.getElementById("formBeneficio");
const tituloForm = document.getElementById("tituloForm");
const acaoForm = document.getElementById("acaoForm");
const idForm = document.getElementById("idForm");
const erroForm = document.getElementById("erroForm");

const idExcluir = document.getElementById("idExcluir");
const nomeExcluir = document.getElementById("nomeExcluir");

const tituloVinc = document.getElementById("tituloVinc");
const listaVinc = document.getElementById("listaVinc");
const selectColab = document.getElementById("vColab");
const dataVinc = document.getElementById("vData");
const idVincBen = document.getElementById("vIdBeneficio");
const btnVincular = document.getElementById("btnVincular");

/* ====================== FECHAR MODAIS (geral) ======================== */
// botões "Cancelar" / "Fechar"
document.querySelectorAll("[data-fechar]").forEach(botao => {
    botao.addEventListener("click", () => botao.closest("dialog").close());
});

// clicar fora da caixa (no fundo escuro) também fecha
document.querySelectorAll("dialog.modal").forEach(modal => {
    modal.addEventListener("click", e => {
        if (e.target === modal) modal.close();
    });
});

/* ====================== NOVO BENEFÍCIO =============================== */
document.getElementById("novo").addEventListener("click", () => {

    formBen.reset();
    tituloForm.textContent = "Cadastrar benefício";
    acaoForm.value = "cadastrar";
    idForm.value = "";
    erroForm.textContent = "";
    modalForm.showModal();

});

/* ====================== EDITAR BENEFÍCIO ============================= */
document.querySelectorAll(".btn-editar").forEach(botao => {

    botao.addEventListener("click", () => {

        const b = DADOS.beneficios[botao.dataset.id];
        if (!b) return;

        formBen.reset();
        tituloForm.textContent = "Editar benefício";
        acaoForm.value = "editar";
        idForm.value = botao.dataset.id;
        erroForm.textContent = "";

        formBen.elements["nome"].value = b.nome;
        formBen.elements["descricao"].value = b.descricao;
        formBen.elements["tipo"].value = b.tipo;
        formBen.elements["valor"].value = b.valor;
        formBen.elements["desconto"].value = b.desconto;
        formBen.elements["empresa"].value = b.empresa === null ? "" : b.empresa;
        formBen.elements["status"].value = b.status === null ? "" : b.status;

        modalForm.showModal();

    });

});

/* ========= validação simples antes de enviar (desconto <= valor) ====== */
formBen.addEventListener("submit", e => {

    const valor = parseFloat(formBen.elements["valor"].value) || 0;
    const desconto = parseFloat(formBen.elements["desconto"].value) || 0;

    if (desconto > valor) {
        e.preventDefault();
        erroForm.textContent = "O desconto do colaborador não pode ser maior que o valor do benefício.";
        formBen.elements["desconto"].focus();
    }

});

formBen.elements["desconto"].addEventListener("input", () => erroForm.textContent = "");
formBen.elements["valor"].addEventListener("input", () => erroForm.textContent = "");

/* ====================== APAGAR BENEFÍCIO ============================= */
document.querySelectorAll(".btn-excluir").forEach(botao => {

    botao.addEventListener("click", () => {

        const b = DADOS.beneficios[botao.dataset.id];
        if (!b) return;

        idExcluir.value = botao.dataset.id;
        nomeExcluir.textContent = b.nome;
        modalApagar.showModal();

    });

});

/* =================== COLABORADORES DO BENEFÍCIO ====================== */
document.querySelectorAll(".btn-vincular").forEach(botao => {

    botao.addEventListener("click", () => abrirVinculos(botao.dataset.id));

});

function abrirVinculos(id) {

    const b = DADOS.beneficios[id];
    if (!b) return;

    const ligados = (DADOS.vinculos && DADOS.vinculos[id]) || [];

    tituloVinc.textContent = "Colaboradores · " + b.nome;
    idVincBen.value = id;
    dataVinc.value = DADOS.hoje;

    /* ---- lista de quem já recebe ---- */
    listaVinc.replaceChildren();

    if (ligados.length === 0) {

        const vazio = document.createElement("li");
        vazio.className = "vazio-lista";
        vazio.textContent = "Nenhum colaborador vinculado ainda.";
        listaVinc.appendChild(vazio);

    }

    ligados.forEach(v => {

        const li = document.createElement("li");

        const info = document.createElement("div");
        const nome = document.createElement("span");
        const desde = document.createElement("small");
        nome.textContent = v.nome;
        desde.textContent = "Desde " + v.inicio;
        info.append(nome, desde);

        // formulário de remoção (um por colaborador)
        const form = document.createElement("form");
        form.method = "post";
        form.action = "beneficios.php";

        const inAcao = document.createElement("input");
        inAcao.type = "hidden";
        inAcao.name = "acao";
        inAcao.value = "desvincular";

        const inId = document.createElement("input");
        inId.type = "hidden";
        inId.name = "id_vinculo";
        inId.value = v.id_vinculo;

        const remover = document.createElement("button");
        remover.type = "submit";
        remover.className = "btn-remover";
        remover.textContent = "Remover";

        form.addEventListener("submit", e => {
            if (!confirm("Remover " + v.nome + " deste benefício?")) e.preventDefault();
        });

        form.append(inAcao, inId, remover);
        li.append(info, form);
        listaVinc.appendChild(li);

    });

    /* ---- opções do select: quem ainda não tem e é da empresa certa ---- */
    const jaTem = new Set(ligados.map(v => String(v.id_colaboradores)));

    const disponiveis = DADOS.colaboradores.filter(c =>
        !jaTem.has(String(c.id)) &&
        (b.empresa === null || String(c.empresa) === String(b.empresa))
    );

    selectColab.replaceChildren();

    if (disponiveis.length === 0) {

        const op = document.createElement("option");
        op.value = "";
        op.textContent = "Nenhum colaborador disponível";
        selectColab.appendChild(op);

    } else {

        disponiveis.forEach(c => {
            const op = document.createElement("option");
            op.value = c.id;
            op.textContent = c.nome;
            selectColab.appendChild(op);
        });

    }

    selectColab.disabled = disponiveis.length === 0;
    btnVincular.disabled = disponiveis.length === 0;

    modalVinc.showModal();

}

/* ====================== PESQUISA E FILTRO ============================ */
const campoBusca = document.getElementById("ipt");
const filtroStatus = document.getElementById("filtroStatus");
const linhas = document.querySelectorAll("tbody tr");
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

    // só mostra "nenhum encontrado" quando existem benefícios mas o filtro escondeu todos
    if (linhas.length > 0) {
        vazio.hidden = visiveis > 0;
        vazio.textContent = "Nenhum benefício encontrado para esse filtro.";
    }

}

campoBusca.addEventListener("input", filtrar);
filtroStatus.addEventListener("change", filtrar);

/* ======= limpa o ?msg=... da URL para o aviso não voltar ao atualizar ==== */
if (location.search.includes("msg=")) {
    history.replaceState(null, "", location.pathname);
}
