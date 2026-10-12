const campos = Array.from(document.querySelectorAll("#campo-verificacao input"));
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

  campo.addEventListener("paste", (evento) => {
    const texto = evento.clipboardData?.getData("text")?.replace(/\D/g, "").slice(0, 6);
    if (!texto) return;
    evento.preventDefault();
    texto.split("").forEach((digito, i) => {
      if (campos[i]) campos[i].value = digito;
    });
    campos[Math.min(texto.length, campos.length - 1)]?.focus();
  });
});

botao.addEventListener("click", async () => {
  const codigo = campos.map((campo) => campo.value).join("");
  const email = sessionStorage.getItem("epa2026_email_verificacao");

  if (!email) {
    alert("Não encontrei o e-mail do cadastro. Volte à tela de cadastro e tente novamente.");
    window.location.href = "./cadastrar.html";
    return;
  }

  if (codigo.length !== 6) {
    alert("Preencha os seis dígitos do código.");
    return;
  }

  botao.disabled = true;

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

    sessionStorage.removeItem("epa2026_email_verificacao");
    alert("E-mail confirmado. Agora você já pode entrar.");
    window.location.href = "./login.html";
  } catch (erro) {
    alert(erro.message || "Erro ao verificar o código.");
  } finally {
    botao.disabled = false;
  }
});
