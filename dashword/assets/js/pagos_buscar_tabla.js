document.addEventListener("DOMContentLoaded", () => {

    const input = document.getElementById("buscadorTabla");
    const filas = document.querySelectorAll("#tablaPagos tbody tr");

    input.addEventListener("input", function () {

        const texto = this.value.toLowerCase().trim();

        filas.forEach(fila => {
            fila.style.display = fila.innerText.toLowerCase().includes(texto)
                ? ""
                : "none";
        });

    });

});