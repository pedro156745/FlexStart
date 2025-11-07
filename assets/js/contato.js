// assets/js/contato.js
document.addEventListener("DOMContentLoaded", function () {
  const siteKey = '6LfNdgUsAAAAAMOAV9XCg4qfnhf3Mb-_XCau54w3';
  const form = document.getElementById('formContato');
  const msgBox = document.getElementById('msgRetorno');
  const btn = form.querySelector('button[type="submit"]');

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    btn.disabled = true;
    msgBox.innerHTML = '<div class="alert alert-info">Enviando mensagem...</div>';

    grecaptcha.ready(() => {
      grecaptcha.execute(siteKey, { action: 'contato' })
        .then(token => {
          let input = form.querySelector('input[name="recaptcha_token"]');
          if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'recaptcha_token';
            form.appendChild(input);
          }
          input.value = token;

          fetch('enviar_contato.php', { method: 'POST', body: new FormData(form) })
            .then(res => res.json())
            .then(data => {
              msgBox.innerHTML = `<div class="alert alert-${data.status === 'sucesso' ? 'success' : 'danger'}">${data.msg}</div>`;
              if (data.status === 'sucesso') form.reset();
              btn.disabled = false;
            })
            .catch(() => {
              msgBox.innerHTML = '<div class="alert alert-danger">Erro ao enviar. Tente novamente.</div>';
              btn.disabled = false;
            });
        });
    });
  });
});
