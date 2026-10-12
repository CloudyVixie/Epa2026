document.getElementById("login").addEventListener("click", async (evento) => {
  evento.preventDefault();

  const email = document.getElementById("email").value.trim();
  const senha = document.getElementById("senha").value;
  const botao = document.getElementById("login");

  if (!email || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email) || !senha) {
    alert("Informe um e-mail válido e a senha.");
    return;
  }

  botao.disabled = true;
  const textoOriginal = botao.textContent;
  botao.textContent = "Entrando...";

  try {
    const resposta = await fetch("./backend/api/login.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify({ email, senha }),
    });
    const dados = await resposta.json();

    if (!resposta.ok || !dados.sucesso) {
      throw new Error(dados.erro || "Não foi possível entrar.");
    }

    alert("Login realizado com sucesso. Olá, " + dados.usuario.nome + "!");
  } catch (erro) {
    alert(erro.message || "Erro ao fazer login.");
  } finally {
    botao.disabled = false;
    botao.textContent = textoOriginal;
  }
});

const parametros = new URLSearchParams(window.location.search);
if (parametros.get("verificado") === "1") {
  alert("E-mail confirmado! Agora você pode entrar.");
}
