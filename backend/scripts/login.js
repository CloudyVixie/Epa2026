document.getElementById("login").onclick = function logar(){
    let email = document.getElementById("email").value;
    let senha = document.getElementById("senha").value;

    let validarEmail = /^[^@\s]+@[^@\s]+\.com$/;

    if (!validarEmail.test(email)){
        alert("Email inválido! precisa ser email@email.com!")
        return;
    }

    console.log(email, senha);
}

