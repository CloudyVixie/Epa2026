const botaoLogin = document.getElementById("login");
const campoEmail = document.getElementById("email");
const campoSenha = document.getElementById("senha");

botaoLogin.addEventListener("click", async () => {
  const email = campoEmail.value.trim();
  const senha = campoSenha.value;

  if (!campoEmail.validity.valid || !email || !senha) {
    alert("Informe um e-mail válido e a senha.");
    return;
  }

  botaoLogin.disabled = true;
  try {
    const resposta = await fetch("./backend/api/login.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, senha }),
    });
    const dados = await resposta.json();
    if (!resposta.ok || !dados.sucesso) {
      throw new Error(dados.erro || "Não foi possível entrar.");
    }
    alert(dados.mensagem);
  } catch (erro) {
    alert(erro.message || "Erro ao realizar login.");
  } finally {
    botaoLogin.disabled = false;
  }
});
