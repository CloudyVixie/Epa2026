fetch("backend/api/personagens.php")
  .then((resposta) => resposta.json())
  .then((personagens) => {
    const select = document.getElementById("personagens");
    personagens.forEach((personagem) => {
      const option = document.createElement("option");
      option.value = personagem.id;
      option.textContent = personagem.nome_personagem;
      select.appendChild(option);
    });
  });

document.getElementById("cadastrar").onclick = function cadastrar() {
  let nome = document.getElementById("nome").value.trim();
  let email = document.getElementById("email").value.trim();
  let senha = document.getElementById("senha").value;
  let confirmarsenha = document.getElementById("confirmar_senha").value;

  let validarEmail = /^[^@\s]+@[^@\s]+\.com$/;

  if (nome === "" || email === "" || senha === "" || confirmarsenha === "") {
    alert("Preencha todos os campos!");
    return;
  }

  if (!validarEmail.test(email)) {
    alert("Digite um e-mail válido terminado em .com!");
    return;
  }

  if (senha.length < 6) {
    alert("A senha precisa ter pelo menos 6 caracteres!");
    return;
  }

  if (senha !== confirmarsenha) {
    alert("As senhas são diferentes!");
    return;
  }

  fetch("./api/cadastrar.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      nome: nome,
      email: email,
      senha: senha,
    }),
  })
    .then((resposta) => {
      if (!resposta.ok) {
        throw new Error("Erro ao cadastrar usuário!");
      }
      return resposta.json();
    })
    .then((dados) => {
      alert(dados.mensagem || "Cadastro realizado!");
    })
    .catch((erro) => {
      console.error(erro);
      alert("Erro ao realizar cadastro!");
    });
};
