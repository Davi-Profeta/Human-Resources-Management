const user = document.querySelector(".createUser");
const drop = document.querySelector(".delete");
const personDelete = document.querySelector("#idExcluir");
const btnDrop = document.querySelectorAll(".btn-excluir");
const btnUser = document.querySelector("#new");
const btnCancel = document.getElementById("cancel");

btnUser.addEventListener("click", e=> {

    user.showModal();
    user.style.display = "flex";

});

btnCancel.addEventListener("click", e=> {

    user.close();
    user.style.display = "none";

});

/* ========================= MESMA SENHA ================================ */
const form = document.querySelector(".createUser form");
const erro = document.getElementById("erro");

form.addEventListener("submit", (e) => {
    const senha1 = form.elements["password1"].value;
    const senha2 = form.elements["password2"].value;

    if (senha1 !== senha2) {
        e.preventDefault(); // não deixa fechar
        erro.textContent = "As senhas não coincidem.";
        form.elements["password2"].focus();
    }
});

form.elements["password2"].addEventListener("input", () => {
    erro.textContent = "";
});

/* ============================= EXCLUIR USER ======================== */
const btnSair = document.querySelector("#cancelarExcluir");

btnDrop.forEach(element => {

    element.addEventListener("click", e=> {

        drop.showModal();
        personDelete.value = element.dataset.id;
        console.log(personDelete.value);
        drop.style.display = "flex";

    });
    
});

btnSair.addEventListener("click", e=> {

    drop.close();
    drop.style.display = "none";

});

/*============================= EDITAR DADOS ==========================*/
const edit = document.querySelector(".edit");
const btnEdit = document.querySelectorAll(".btn-acao");
console.log(edit);
btnEdit.forEach(element => {

    element.addEventListener("click", e=> {

        console.log(edit);
        edit.showModal();
        edit.style.display = "flex";

    });
    
});