document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("#formContato");
    const msg = document.querySelector("#msgRetorno");
  
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      msg.innerHTML = '<div class="text-primary">Enviando...</div>';
  
      const dados = new FormData(form);
      const resp = await fetch("enviar_contato.php", {
        method: "POST",
        body: dados
      });
      const res = await resp.json();
  
      if (res.status === "sucesso") {
        msg.innerHTML = '<div class="alert alert-success">'+res.msg+'</div>';
        form.reset();
      } else {
        msg.innerHTML = '<div class="alert alert-danger">'+res.msg+'</div>';
      }
    });
  });
  