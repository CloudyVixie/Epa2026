const campos = [...document.querySelectorAll("#campo-verificacao input")];
const botao = document.querySelector("#verificar");
const email = new URLSearchParams(window.location.search).get("email");
const mensagem = document.getElementById("mensagem-verificacao");

function mostrarMensagem(texto, erro = false) {
  if (mensagem) {
    mensagem.textContent = texto;
    mensagem.dataset.tipo = erro ? "erro" : "sucesso";
  } else {
    alert(texto);
  }
}

if (!email) {
  mostrarMensagem("O e-mail não foi informado. Volte ao cadastro e tente novamente.", true);
  botao.disabled = true;
}

campos.forEach((campo, indice) => {
  campo.autocomplete = indice === 0 ? "one-time-code" : "off";
  campo.addEventListener("input", () => {
    campo.value = campo.value.replace(/\D/g, "").slice(-1);
    if (campo.value && indice < campos.length - 1) campos[indice + 1].focus();
  });

  campo.addEventListener("keydown", (evento) => {
    if (evento.key === "Backspace" && !campo.value && indice > 0) campos[indice - 1].focus();
    if (evento.key === "Enter") botao.click();
  });

  campo.addEventListener("paste", (evento) => {
    const texto = evento.clipboardData.getData("text").replace(/\D/g, "").slice(0, 6);
    if (!texto) return;
    evento.preventDefault();
    texto.split("").forEach((digito, i) => {
      if (campos[i]) campos[i].value = digito;
    });
    campos[Math.min(texto.length, campos.length - 1)].focus();
  });
});

botao.addEventListener("click", async () => {
  const codigo = campos.map((campo) => campo.value).join("");
  if (codigo.length !== 6) {
    mostrarMensagem("Preencha os 6 dígitos do código.", true);
    return;
  }

  botao.disabled = true;
  const textoOriginal = botao.textContent;
  botao.textContent = "Verificando...";

  try {
    const resposta = await fetch("./backend/api/verificacao.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ acao: "confirmar", email, codigo }),
    });
    const dados = await resposta.json();

    if (!resposta.ok || !dados.valido) {
      throw new Error(dados.erro || "Não foi possível confirmar o código.");
    }

    mostrarMensagem("E-mail confirmado! Redirecionando para o login...");
    window.location.href = "./login.html?verificado=1";
  } catch (erro) {
    mostrarMensagem(erro.message || "Erro ao verificar o código.", true);
  } finally {
    botao.disabled = false;
    botao.textContent = textoOriginal;
  }
});
