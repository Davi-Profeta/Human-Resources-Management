/*==================== BUSCA + FILTRO DE STATUS ===============================*/
const input = document.querySelector("#ipt");
const select = document.querySelector("#filtroStatus");
const cartoes = document.querySelectorAll(".card-empresa");
const vazio = document.querySelector("#vazio");

// mostra só os cartões que combinam com o texto digitado E com o status escolhido
function aplicarFiltros() {

    const texto = input.value.trim().toLowerCase();
    const status = select.value;
    let visiveis = 0;

    cartoes.forEach(cartao => {

        const bateTexto = cartao.dataset.busca.includes(texto);
        const bateStatus = status === "" || cartao.dataset.statusNome === status;
        const mostrar = bateTexto && bateStatus;

        cartao.hidden = !mostrar;
        if (mostrar) visiveis++;

    });

    // só mostra "nenhuma encontrada" quando existem empresas mas o filtro escondeu todas
    if (cartoes.length > 0) {
        vazio.hidden = visiveis > 0;
        vazio.textContent = "Nenhuma empresa encontrada para esse filtro.";
    }

}

input.addEventListener("input", aplicarFiltros);
select.addEventListener("change", aplicarFiltros);


/*==================== NOVA EMPRESA ===============================*/
const dialogEnterprice = document.querySelector(".newEnterprice");
const btnEnterprice = document.querySelector("#novo");
const cancel = document.querySelector("#cancel");

btnEnterprice.addEventListener("click", () => {

    dialogEnterprice.showModal();

});

cancel.addEventListener("click", () => {

    dialogEnterprice.close();

});


/*==================== DETALHES DA EMPRESA ===============================*/
const modalDetalhes = document.querySelector(".detalhes");
const btnDetFechar = document.querySelector("#detFechar");
const btnDetApagar = document.querySelector("#detApagar");

let idDetalhe = null; // empresa que está aberta no modal de detalhes

document.querySelectorAll(".btn-detalhes").forEach(btn => {

    btn.addEventListener("click", () => {

        const c = btn.closest(".card-empresa").dataset;

        idDetalhe = c.id;

        document.querySelector("#detNome").textContent = c.nome;
        document.querySelector("#detCnpj").textContent = c.cnpjFmt;
        document.querySelector("#detStatus").textContent = c.statusNome;
        document.querySelector("#detEmail").textContent = c.email;
        document.querySelector("#detTel").textContent = c.tel;
        document.querySelector("#detLocal").textContent = c.local;
        document.querySelector("#detRS").textContent = c.rs || "—";
        document.querySelector("#detQtd").textContent = c.qtd;

        modalDetalhes.showModal();

    });

});

btnDetFechar.addEventListener("click", () => modalDetalhes.close());

// clicar fora da caixa (no fundo escuro) também fecha
modalDetalhes.addEventListener("click", e => {
    if (e.target === modalDetalhes) modalDetalhes.close();
});


/* ============================ APAGAR EMPRESA =========================*/
// o botão "Apagar empresa" fica dentro dos detalhes e abre a confirmação
const modalDelete = document.querySelector(".delete");
const iptDelete = document.querySelector("#idApagar");
const closeModal = document.querySelector("#cancelarExcluir");

btnDetApagar.addEventListener("click", () => {

    iptDelete.value = idDetalhe;
    modalDetalhes.close();
    modalDelete.showModal();

});

closeModal.addEventListener("click", () => {

    modalDelete.close();

});

/* ========================== EDITAR DADOS ===========================*/
const edit = document.querySelector(".edit");
const formEdit = edit.querySelector("form");
const cancelEdit = document.querySelector("#cancelEdit");
const idEditar = document.querySelector("#idEditar");

document.querySelectorAll(".btn-editar").forEach(btn => {

    btn.addEventListener("click", () => {

        const c = btn.closest(".card-empresa").dataset;

        idEditar.value = c.id; // id vai para o input hidden

        formEdit.elements["name"].value = c.nome;
        formEdit.elements["cnpj"].value = c.cnpj;
        formEdit.elements["email"].value = c.email;
        formEdit.elements["tel"].value = c.tel;
        formEdit.elements["local"].value = c.local;
        formEdit.elements["RS"].value = c.rs;
        formEdit.elements["status"].value = c.status; // agora o status atual já vem marcado

        edit.showModal();

    });

});

cancelEdit.addEventListener("click", () => {

    edit.close();

});

/* ============================ USER LOGADO ============================ */
const profile = document.querySelector(".profile");
const dialogUser = document.querySelector("dialog.user");

profile.addEventListener("click", async () => {

    try {

        const resposta = await fetch("../../src/APIs/loginUser.php");

        if (!resposta.ok) {
            throw new Error("Erro ao carregar perfil");
        }

        dialogUser.innerHTML = await resposta.text();
        dialogUser.showModal();

    } catch (erro) {
        console.log("Erro ao buscar login");
    };

});

dialogUser.addEventListener("click", e => {

    if (e.target === dialogUser || e.target.closest("#closeUser")) {
        dialogUser.close();
    }

    if (e.target.closest("#logout")) {
        window.top.location.href = new URL("logout.php", window.top.location.href).href;
    }

});
