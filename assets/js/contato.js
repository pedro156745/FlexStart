document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("formContato");
  const msgBox = document.getElementById("msgRetorno");
  const btn = form.querySelector('button[type="submit"]');
  const siteKey = "6LfNdgUsAAAAAMOAV9XCg4qfnhf3Mb-_XCau54w3";

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    msgBox.innerHTML = "";
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Enviando...`;

    grecaptcha.ready(function () {
      grecaptcha.execute(siteKey, { action: "contato" }).then(function (token) {
        let input = form.querySelector('input[name="recaptcha_token"]');
        if (!input) {
          input = document.createElement("input");
          input.type = "hidden";
          input.name = "recaptcha_token";
          form.appendChild(input);
        }
        input.value = token;

        fetch("enviar_contato.php", {
          method: "POST",
          body: new FormData(form),
        })
          .then((res) => res.json())
          .then((data) => {
            btn.disabled = false;
            btn.innerHTML = "Enviar mensagem";

            if (data.status === "sucesso") {
              // ✅ anima o botão e mostra sucesso
              btn.innerHTML = `<i class="bi bi-check-circle-fill"></i> Enviado!`;
              btn.classList.add("btn-success");
              setTimeout(() => {
                btn.innerHTML = "Enviar mensagem";
                btn.classList.remove("btn-success");
              }, 3000);

              msgBox.innerHTML = `
                <div class="alert alert-success shadow-sm p-4 rounded-3" data-aos="fade-up">
                  <h5 class="mb-1"><i class="bi bi-envelope-check-fill me-2"></i>Mensagem enviada!</h5>
                  <p>${data.msg}</p>
                </div>
              `;
              form.reset();
            } else {
              msgBox.innerHTML = `
                <div class="alert alert-danger shadow-sm p-4 rounded-3" data-aos="fade-up">
                  <h5><i class="bi bi-x-circle-fill me-2"></i>Erro</h5>
                  <p>${data.msg}</p>
                </div>
              `;
            }

            if (typeof AOS !== "undefined") AOS.refresh();
          })
          .catch((err) => {
            console.error(err);
            msgBox.innerHTML = `
              <div class="alert alert-danger shadow-sm p-4 rounded-3" data-aos="fade-up">
                <h5><i class="bi bi-wifi-off me-2"></i>Erro de conexão</h5>
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
