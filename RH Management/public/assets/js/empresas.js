/*==================== BUSCA + FILTRO DE STATUS ===============================*/
const table = document.querySelector("#tabela");
const input = document.querySelector("#ipt");
const select = document.querySelector("#filtroStatus");
const linhas = document.querySelectorAll(".tbody tr");

let nomesEncontrados = null; 

const normalizar = texto => texto.trim().toLowerCase();

// Decide quais linhas aparecem, considerando busca E status
function aplicarFiltros() {

    const statusSelecionado = select.value;

    linhas.forEach(linha => {

        const nome = normalizar(linha.cells[0].textContent);
        const statusLinha = linha.querySelector(".statsNow")?.textContent.trim();

        const passaBusca = nomesEncontrados === null || nomesEncontrados.has(nome);
        const passaStatus = statusSelecionado === "" || statusLinha === statusSelecionado;

        linha.style.display = (passaBusca && passaStatus) ? "" : "none";

    });

}

input.addEventListener("input", _.throttle(async e => {

    const digitado = e.target.value.trim();

    // campo vazio: remove a busca e mostra tudo (respeitando o status)
    if (digitado === "") {
        nomesEncontrados = null;
        aplicarFiltros();
        return;
    }

    try {

        const { data } = await axios.get("../../src/APIs/buscarEmpresas.php", {
            params: { empresa: digitado }
        });

        // guarda os nomes que a API devolveu
        nomesEncontrados = new Set(data.map(emp => normalizar(emp.nome_empresas)));
        aplicarFiltros();

    } catch (error) {
        console.log(error);
    }

}, 300));

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


/* ============================ APAGAR EMPRESA =========================*/
const modalDelete = document.querySelector(".delete");
const iptDelete = document.querySelector("#idApagar");
const closeModal = document.querySelector("#cancelarExcluir");

table.addEventListener("click", e => {

    const btn = e.target.closest(".btn-excluir");

    if (btn) {

        modalDelete.showModal();
        iptDelete.value = btn.dataset.id;

    };

});

closeModal.addEventListener("click", () => {

    modalDelete.close();

});

/* ========================== EDITAR DADOS ===========================*/
const edit = document.querySelector(".edit");
const btnEditar = document.querySelectorAll(".btn-editar");
const cancelEdit = document.querySelector("#cancelEdit");
const idEditar = document.querySelector("#idEditar");

btnEditar.forEach(btn => {

    btn.addEventListener("click", e => {

        const btnClicado = e.currentTarget;
        const cima = btnClicado.closest("tr");
        const inpt = edit.getElementsByTagName("input");

        idEditar.value = btnClicado.dataset.id; // id vai para o input hidden

        values(cima, inpt);
        edit.showModal();

    });

});

function values(tr, inpt) {

    inpt.name.value = tr.children[0].textContent;
    inpt.cnpj.value = tr.children[1].textContent;
    inpt.email.value = tr.children[2].textContent;
    inpt.tel.value = tr.children[3].textContent;
    inpt.local.value = tr.children[4].textContent;
    inpt.RS.value = tr.children[5].textContent;

};

cancelEdit.addEventListener("click", () => {

    edit.close();

});

/* ============================ USER LOGADO ============================ */
const profile = document.querySelector(".profile");
const dialogUser = document.querySelector("dialog.user");

profile.addEventListener("click", async e => {

    try {
        const res = await axios.get("../../src/APIs/loginUser.php");
        dialogUser.innerHTML = res.data;
        dialogUser.showModal();

    } catch (error) {
        console.log("Erro ao buscar login");
    };

});

dialogUser.addEventListener("click", e => {
    if (e.target === dialogUser || e.target.id === "closeUser") {
        dialogUser.close();
    }
    if (e.target.id === "logout") {
        window.location.href = ""; // seu logout
    }
});