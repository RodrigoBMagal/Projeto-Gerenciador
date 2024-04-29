const localStorageKey = "ListaTarefas";
$(document).ready(function () {
    $("#meuFormulario").submit(function (e) {
        

        var txt_nome = $('#nome').val();
        var txt_data = $('#data').val();
        var txt_descricao = $('#descricao').val();

        let valores = JSON.parse(localStorage.getItem(localStorageKey) || "[]")
        valores.push({
            nome: txt_nome,
            data: txt_data,
            descricao: txt_descricao,
        })
        localStorage.setItem(localStorageKey, JSON.stringify(valores))
        
        mostrarTarefas();
        e.preventDefault();

        

        $.ajax({
            url: 'http://localhost/ProjetoWeb/processar_formulario.php',
            type: 'POST',
            data: {
                nome: txt_nome, data: txt_data, descricao: txt_descricao,
            },
            dataType: 'json',
            success: function (response) {
                console.log("Requisição bem-sucedida:", response);
            },
            error: function (xhr, status, error) {
                console.error("Erro na requisição:", error);
                alert("Erro na requisição: " + error);
            }
        });
    });
});

function mostrarTarefas(){
    let valores = JSON.parse(localStorage.getItem(localStorageKey) || "[]")
    let lista = document.getElementById("listaTarefa")
    
    for (let i = 0; i < valores.length; i++){
        lista.innerHTML += `<li class="d-flex flex-column mw-100 mh-100 mb-5 h-25">
        <div class="p-2">${valores[i]['nome']}</div>
        <div class="p-2">${valores[i]['descricao']}</div>
        <div class="p-2">${valores[i]['data']}</div>
        <div class="p-2"><button id="concluida" onclick="apagaritem()"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16">
         <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
       </svg>
         </button></div></li>
         `
    }
    //console.log(lista.innerHTML)
}
function apagaritem(data){
    let valores = JSON.parse(localStorage.getItem(localStorageKey) || "[]")
    let index = valores.findIndex(x=>x.name == data)
    valores.splice(index, 1)
    localStorage.setItem(localStorageKey, JSON.stringify(valores))
    window.location.reload()
    mostrarTarefas();
}
mostrarTarefas();