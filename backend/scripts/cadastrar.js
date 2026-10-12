const formulario = {
  nome: document.getElementById("nome"),
  email: document.getElementById("email"),
  senha: document.getElementById("senha"),
  confirmarSenha: document.getElementById("confirmar_senha"),
  personagens: document.getElementById("personagens"),
  botao: document.getElementById("cadastrar"),
};

async function carregarPersonagens() {
  try {
    const resposta = await fetch("./backend/api/personagens.php");
    const personagens = await resposta.json();

    if (!resposta.ok || !Array.isArray(personagens)) {
      throw new Error("Resposta inválida ao carregar personagens.");
    }

    formulario.personagens.replaceChildren(
      new Option("Selecione um personagem", ""),
      ...personagens.map((personagem) =>
        new Option(personagem.nome_personagem, personagem.id),
      ),
    );
  } catch (erro) {
    console.error("Erro ao carregar personagens:", erro);
    formulario.personagens.replaceChildren(
      new Option("Não foi possível carregar os personagens", ""),
    );
  }
}

carregarPersonagens();

formulario.botao.addEventListener("click", async () => {
  const nome = formulario.nome.value.trim();
  const email = formulario.email.value.trim();
  const senha = formulario.senha.value;
  const confirmarSenha = formulario.confirmarSenha.value;

  if (!nome || !email || !senha || !confirmarSenha) {
    alert("Preencha todos os campos.");
    return;
  }

  if (!formulario.email.validity.valid) {
    alert("Digite um endereço de e-mail válido.");
    formulario.email.focus();
    return;
  }

  if (senha.length < 9 || senha.length > 72) {
    alert("A senha deve ter entre 9 e 72 caracteres.");
    formulario.senha.focus();
    return;
  }

  if (senha !== confirmarSenha) {
    alert("As senhas não coincidem.");
    formulario.confirmarSenha.focus();
    return;
  }

  formulario.botao.disabled = true;

  try {
    const resposta = await fetch("./backend/api/verificacao.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ acao: "solicitar", nome, email, senha }),
    });

    const dados = await resposta.json();

    if (!resposta.ok || !dados.sucesso) {
      throw new Error(dados.erro || "Não foi possível iniciar o cadastro.");
    }

    sessionStorage.setItem("epa2026_email_verificacao", email);
    window.location.href = "./telaverificacao.html";
  } catch (erro) {
    alert(erro.message || "Erro ao realizar cadastro.");
  } finally {
    formulario.botao.disabled = false;
  }
});
