$(document).ready(function () {
    $("#meuFormulario").submit(function (e) {
        e.preventDefault();

        var txt_nome = $('#nome').val();
        var txt_data = $('#data').val();
        var txt_descricao = $('#descricao').val();

        console.log(txt_nome, txt_data, txt_descricao);

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
