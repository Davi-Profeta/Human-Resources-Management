const table = document.querySelector("#tabela");


/*==================== NOVA EMPRESA ===============================*/
const dialogEnterprice = document.querySelector(".newEnterprice");
const btnEnterprice = document.querySelector("#novo");
const cancel = document.querySelector("#cancel");

btnEnterprice.addEventListener("click", e=> {

    dialogEnterprice.showModal();

});

cancel.addEventListener("click", e=> {

    dialogEnterprice.close();

});