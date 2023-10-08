document.addEventListener("DOMContentLoaded", function() {
    let btns = document.querySelectorAll(".btn-gerar-artigo");
    btns.forEach(function(btn) {
        btn.addEventListener("click", function() {
            let titulo = this.getAttribute("data-titulo");
            location.href = location.href + "&gerar_artigo&titulo=" + encodeURIComponent(titulo);
        });
    });
});
