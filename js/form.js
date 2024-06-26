const localStorageKey = "ListaTarefas";

$(document).ready(function () {

    if (!localStorage.getItem(localStorageKey)) {
        localStorage.setItem(localStorageKey, JSON.stringify([]));
    }

    $("#meuFormulario").submit(function (e) {
        e.preventDefault(); // Previne o comportamento padrão do formulário

        function formataData(data) {
            var partesData = data.split('-');
            return partesData[2] + '/' + partesData[1] + '/' + partesData[0];
        }

        var txt_nome = $('#nome').val();
        var txt_data = formataData($('#data').val());
        var txt_descricao = $('#descricao').val();

        // Verifica se o nome já existe no banco de dados
        $.ajax({
            url: 'http://localhost/Projeto-Gerenciador/php/verificar_nome.php',
            type: 'POST',
            data: { nome: txt_nome },
            dataType: 'json',
            success: function (response) {
                if (response.existe) {
                    alert("Este nome já existe, por favor, escolha outro.");
                } else {
                    adicionarTarefa(txt_nome, txt_data, txt_descricao);
                }
            },
            error: function (xhr, status, error) {
                console.error("Erro na requisição:", error);
                alert("Erro na requisição: " + error);
            }
        });
    });


    function adicionarTarefa(txt_nome, txt_data, txt_descricao) {
        let valores = JSON.parse(localStorage.getItem(localStorageKey)) || [];
        valores.push({
            nome: txt_nome,
            data: txt_data,
            descricao: txt_descricao,
        });
        localStorage.setItem(localStorageKey, JSON.stringify(valores));

        // Faz a requisição AJAX para adicionar no banco de dados
        $.ajax({
            url: 'http://localhost/Projeto-Gerenciador/php/processar_formulario.php',
            type: 'POST',
            data: {
                nome: txt_nome,
                data: txt_data,
                descricao: txt_descricao,
            },
            dataType: 'json',
            success: function (response) {
                console.log("Requisição bem-sucedida:", response);
                mostrarTarefas(); // Atualiza a lista após adicionar a tarefa
            },
            error: function (xhr, status, error) {
                console.error("Erro na requisição:", error);
                alert("Erro na requisição: " + error);
            }
        });
    }

    $("#pegar-tarefas").click(function (e) {
        e.preventDefault();
        $.ajax({
            url: 'http://localhost/Projeto-Gerenciador/php/obter_tarefas.php',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                console.log("Requisição bem-sucedida:", response);
                localStorage.setItem(localStorageKey, JSON.stringify(response));
                mostrarTarefas();
            },
            error: function (xhr, status, error) {
                console.error("Erro na requisição:", error);
            }
        });
    });

    function mostrarTarefas() {
    let valores = JSON.parse(localStorage.getItem(localStorageKey)) || [];
    let lista = $("#listaTarefa");
    lista.empty(); // Limpa a lista antes de mostrar as tarefas novamente

    valores.forEach((tarefa, index) => {
        // Substituir pontos finais por quebras de linha
        let descricaoFormatada = tarefa.descricao.split('.').join('.<br>');

        lista.append(
            `<div class="task-card d-flex flex-column align-items-center border rounded m-2 p-2 mh-fit w-49">
                <h4 class="w-100 text-center">${tarefa.nome}</h4>
                <div class="w-100 text-center">${descricaoFormatada}</div>
                <div class="w-100 text-center">${tarefa.data}</div>
                <button id="concluida" class="p-2" onclick="apagaritem('${tarefa.nome}')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16">
                        <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
                    </svg>
                </button>
            </div>`
        );
    });


        
        // Função para converter dd-mm-yyyy para yyyy-mm-dd
        function formatDate(dateStr) {
            let parts = dateStr.split('/');
            return `${parts[2]}-${parts[1]}-${parts[0]}`;
        }

        // Ordena os valores após converter as datas
        valores.sort((a, b) => new Date(formatDate(a.data)) - new Date(formatDate(b.data)));

        // Limpa os elementos de tarefas, descrições e datas
        for (let i = 1; i <= 4; i++) {
            $(`#tarefa${i}`).empty();
            $(`#descricao${i}`).empty();
            $(`#data${i}`).empty();
        }

        // Distribui as tarefas pelos layouts, limitando a 4 tarefas
        valores.forEach((tarefa, index) => {
            if (index < 4) { // Verifica se o índice é menor que 4
                let layoutIndex = (index % 4) + 1; // Distribui ciclicamente entre 1 e 4
                $(`#tarefa${layoutIndex}`).append(`<div>${tarefa.nome}</div>`);
                $(`#descricao${layoutIndex}`).append(`<div>${tarefa.descricao}</div>`);
                $(`#data${layoutIndex}`).append(`<div>${tarefa.data}</div>`);
            }
        });

    }


window.apagaritem = function(nome) {
    let valores = JSON.parse(localStorage.getItem(localStorageKey)) || [];
    let index = valores.findIndex(x => x.nome === nome);
    if (index > -1) {
        valores.splice(index, 1);
        localStorage.setItem(localStorageKey, JSON.stringify(valores));

        // Faz a requisição AJAX para excluir o item no banco de dados
        $.ajax({
            url: 'http://localhost/Projeto-Gerenciador/php/apagar_item.php',
            type: 'POST',
            data: { nome: nome },
            dataType: 'json',
            success: function (response) {
                console.log("Item excluído do banco de dados:", response);
                mostrarTarefas(); // Atualiza a lista após a exclusão
                loadStaffWithTasks();
            },
            error: function (xhr, status, error) {
                console.error("Erro na requisição:", error);
                alert("Erro na requisição: " + error);
            }
        });
    }
}


        function loadStaffWithTasks() {
        $.ajax({
            url: 'http://localhost/Projeto-Gerenciador/php/load_staff.php',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                console.log('Response from server:', response); 
                
                if (Array.isArray(response)) {
                    let staffContainer = $('#staffContainer');
                    staffContainer.empty();

                    response.forEach(function (staff) {
                        let staffDiv = 
                        `<div class="staff-member d-flex flex-column align-items-center border rounded p-1 m-1 mh-fit w-47">
                            <img class="w-100 text-center" src="https://png.pngtree.com/png-vector/20191009/ourmid/pngtree-user-icon-png-image_1796659.jpg" alt="${staff.nome}" class="staff-photo">
                            <div class="w-100 text-center">${staff.nome}</div>
                            <h5 class="w-100 text-center"><strong>${staff.tarefa_nome}</strong></h5>
                        </div>`;
                        staffContainer.append(staffDiv);
                    });
                } else {
                    console.error('Unexpected response format:', response);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error in request:', error);
                console.log('Response text:', xhr.responseText); 
            }
        });
    }

    
    loadStaffWithTasks();
    
    mostrarTarefas(); // Chama a função para mostrar tarefas ao carregar a página
});
