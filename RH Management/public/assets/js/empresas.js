const table = document.querySelector("#tabela");
const input = document.querySelector("#ipt");

input.addEventListener("input", e=> {

    console.log(e.target.value);

});


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

/* ============================ APAGAR EMPREAS =========================*/
const modalDelete = document.querySelector(".delete");
const iptDelete = document.querySelector("#idApagar");
const closeModal = document.querySelector("#cancelarExcluir");

table.addEventListener("click", e=> {

    if(e.target.closest(".btn-excluir")){

        modalDelete.showModal();
        iptDelete.value = e.target.dataset.id;

    };

});

closeModal.addEventListener("click", e=> {

    modalDelete.close();

});

/*============================= FILTRO ===========================*/
const select = document.querySelector("#filtroStatus");
const stats = Array.from(document.querySelectorAll(".statsNow"));

select.addEventListener("change", e=> {

    const valorSelecionado = e.target.value;

    stats.forEach(element => {

        const texto = element.textContent;
        const linha = element.closest("tr");

        if(valorSelecionado === "" || texto === valorSelecionado){
            linha.style.display = "";
        } else {
            linha.style.display = "none";
        };


    });

});
