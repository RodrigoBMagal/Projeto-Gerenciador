$(document.ready(function(){

    $('#botaoEnviar').click(async function(){
        dados = {
            nome: $('nome').val(),
            data: $('data').val(),
            descricao: $('descrricao').val(),
        }

        $$.ajax({
            url: 'http://localhost/projeto/index.php',
            method: 'GET',
            data: dados,
            dataType: 'json',
            success: function(response){

            },
            error: function(xhr, status, error){
                console.error('Erro na requisição, ', xhr, status, error)
            }
        })
    })
}))