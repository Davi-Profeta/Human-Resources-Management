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

