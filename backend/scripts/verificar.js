
const campos = document.querySelectorAll("#campo-verificacao input");
const botao = document.querySelector("#verificar");

campos.forEach((campo, indice) => {
    campo.addEventListener("input", () => {
        campo.value = campo.value.replace(/\D/g, "").slice(-1);

        if (campo.value && indice < campos.length - 1) {
            campos[indice + 1].focus();
        }
    });

    campo.addEventListener("keydown", (evento) => {
        if (evento.key === "Backspace" && !campo.value && indice > 0) {
            campos[indice - 1].focus();
        }
    });
});

botao.addEventListener("click", () => {
    const codigo = Array.from(campos)
        .map(campo => campo.value)
        .join("");

    if (codigo.length !== 6) {
        alert("Preencha os 6 dígitos!");
        return;
    }

    console.log("Código digitado:", codigo);
    alert("Código preenchido: " + codigo);
});
