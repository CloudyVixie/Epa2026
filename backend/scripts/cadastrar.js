const selectPersonagens = document.getElementById("personagens");
const botaoCadastrar = document.getElementById("cadastrar");

async function carregarPersonagens() {
  try {
    const resposta = await fetch("./backend/api/personagens.php");
    const dados = await resposta.json();

    if (!resposta.ok || !Array.isArray(dados)) {
      throw new Error(dados.erro || "Não foi possível carregar os personagens.");
    }

    selectPersonagens.replaceChildren(new Option("Selecione um personagem", ""));
    dados.forEach((personagem) => {
      selectPersonagens.add(new Option(personagem.nome_personagem, personagem.id));
    });
  } catch (erro) {
    console.error(erro);
    selectPersonagens.replaceChildren(new Option("Erro ao carregar personagens", ""));
    alert("Não foi possível carregar os personagens. Confira o PHP e o PostgreSQL.");
  }
}

botaoCadastrar.addEventListener("click", async (evento) => {
  evento.preventDefault();

  const nome = document.getElementById("nome").value.trim();
  const email = document.getElementById("email").value.trim();
  const senha = document.getElementById("senha").value;
  const confirmarSenha = document.getElementById("confirmar_senha").value;
  const personagemId = selectPersonagens.value;

  if (!nome || !email || !senha || !confirmarSenha || !personagemId) {
    alert("Preencha todos os campos e selecione um personagem.");
    return;
  }

  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
    alert("Digite um endereço de e-mail válido.");
    return;
  }

  if (senha.length < 9 || senha.length > 72) {
    alert("A senha precisa ter entre 9 e 72 caracteres.");
    return;
  }

  if (senha !== confirmarSenha) {
    alert("As senhas são diferentes.");
    return;
  }

  botaoCadastrar.disabled = true;
  const textoOriginal = botaoCadastrar.textContent;
  botaoCadastrar.textContent = "Enviando...";

  try {
    const resposta = await fetch("./backend/api/verificacao.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        acao: "solicitar",
        nome,
        email,
        senha,
        personagem_id: Number(personagemId),
      }),
    });

    const dados = await resposta.json();
    if (!resposta.ok || !dados.sucesso) {
      throw new Error(dados.erro || "Não foi possível iniciar o cadastro.");
    }

    window.location.href = "./telaverificacao.html?email=" + encodeURIComponent(email);
  } catch (erro) {
    alert(erro.message || "Erro ao realizar cadastro.");
  } finally {
    botaoCadastrar.disabled = false;
    botaoCadastrar.textContent = textoOriginal;
  }
});

carregarPersonagens();
