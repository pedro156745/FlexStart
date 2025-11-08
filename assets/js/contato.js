document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("formContato");
  const msgBox = document.getElementById("msgRetorno");
  const btn = form.querySelector('button[type="submit"]');
  const siteKey = "6LfNdgUsAAAAAMOAV9XCg4qfnhf3Mb-_XCau54w3"; // reCAPTCHA site key da Anpha Web

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    // Reseta mensagens anteriores
    msgBox.innerHTML = "";
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Enviando...`;

    // Executa o reCAPTCHA
    grecaptcha.ready(function () {
      grecaptcha.execute(siteKey, { action: "contato" }).then(function (token) {
        // Adiciona o token como campo oculto
        let input = form.querySelector('input[name="recaptcha_token"]');
        if (!input) {
          input = document.createElement("input");
          input.type = "hidden";
          input.name = "recaptcha_token";
          form.appendChild(input);
        }
        input.value = token;

        // Envia via fetch
        fetch("enviar_contato.php", {
          method: "POST",
          body: new FormData(form),
        })
          .then((res) => res.json())
          .then((data) => {
            btn.disabled = false;
            btn.innerHTML = "Enviar mensagem";

            // Animação e feedback
            if (data.status === "sucesso") {
              msgBox.innerHTML = `
                <div class="alert alert-success shadow-sm p-4 rounded-3" data-aos="fade-up">
                  <h5 class="mb-1"><i class="bi bi-check-circle-fill me-2"></i>Mensagem enviada!</h5>
                  <p class="mb-0">${data.msg}</p>
                  <small class="text-muted d-block mt-2">Entraremos em contato em breve. 💬</small>
                </div>
              `;
              form.reset();
            } else {
              msgBox.innerHTML = `
                <div class="alert alert-danger shadow-sm p-4 rounded-3" data-aos="fade-up">
                  <h5 class="mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Erro</h5>
                  <p class="mb-0">${data.msg}</p>
                </div>
              `;
            }

            if (typeof AOS !== "undefined") AOS.refresh(); // atualiza animações
          })
          .catch((err) => {
            console.error(err);
            msgBox.innerHTML = `
              <div class="alert alert-danger shadow-sm p-4 rounded-3" data-aos="fade-up">
                <h5><i class="bi bi-wifi-off me-2"></i>Erro de rede</h5>
                <p>Tente novamente em alguns instantes.</p>
              </div>
            `;
            btn.disabled = false;
            btn.innerHTML = "Enviar mensagem";
          });
      });
    });
  });
});
