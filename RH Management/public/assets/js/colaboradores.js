/* =================== CREATE DIALOG =================== */
const dialogCreate = document.querySelector(".create");
const btnCreate = document.querySelector("#novo");
const btnCancel = document.getElementById("cancelColaborador");

btnCreate.addEventListener("click", e=> {

    dialogCreate.showModal();

});

btnCancel.addEventListener("click", e=> {

    dialogCreate.close();

});



/* =========================== PESQUISAR =========================== */
const search = document.getElementById("ipt");
const filtroStatus = document.getElementById("filtroStatus");
const person = document.querySelectorAll(".person");
const vazio = document.querySelector(".vazio");

// tira acentos e maiúsculas: "José" casa com "jose"
const normalizar = txt => txt.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();

function filtrar() {

    const digitado = normalizar(search.value);
    const status = filtroStatus.value;
    let achou = 0;

    person.forEach(card => {

        const bateNome = normalizar(card.dataset.nome).includes(digitado);
        const bateStatus = status === "" || card.dataset.status === status;

        card.hidden = !(bateNome && bateStatus);
        if (!card.hidden) achou++;

    });

    vazio.hidden = achou > 0;

}

search.addEventListener("input", _.throttle(filtrar, 150));
filtroStatus.addEventListener("change", filtrar);


/* ========================== EDITAR =============================== */
const edits = document.querySelectorAll(".person");
const cancelEdit = document.getElementById("cancelEdit");
const modalEditar = document.querySelector(".edit");

edits.forEach(element => {

    element.addEventListener("click", e=> {

        values(element);
        modalEditar.showModal();

    });

    element.addEventListener("keydown", e=> {

        if (e.key === "Enter") {
            values(element);
            modalEditar.showModal();
        }

    });
    
});

cancelEdit.addEventListener("click", e=> {

    modalEditar.close();

});

function values(card){

    const campo = nome => modalEditar.querySelector(`[name="${nome}"]`);

    campo("id").value    = card.dataset.id;
    campo("name").value  = card.dataset.nome;
    campo("cpf").value   = card.dataset.cpf;
    campo("email").value = card.dataset.email;
    campo("local").value = card.dataset.local;
    campo("tel").value   = card.dataset.tel;
    campo("photo").value = ""; // vazio = mantém a foto atual

};
