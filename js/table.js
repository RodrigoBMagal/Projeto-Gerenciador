$(document).ready(function () {
    $('#saveTable').click(function () {
        let tableData = [];
        $('#dataTable tbody tr').each(function () {
            let row = [];
            $(this).find('td').each(function () {
                row.push($(this).text());
            });
            tableData.push(row);
        });

        console.log('Table Data:', tableData);

        $.ajax({
            url: 'http://localhost/Projeto-Gerenciador/php/save_table_data.php',
            type: 'POST',
            data: { data: JSON.stringify(tableData) },
            success: function (response) {
                alert('Table data saved successfully!');
            },
            error: function (xhr, status, error) {
                console.error('Error in request:', error);
                alert('Error in request: ' + error);
            }
        });
    });

    function loadTableData(searchTerm = '') {
        $.ajax({
            url: 'http://localhost/Projeto-Gerenciador/php/load_table_data.php',
            type: 'GET',
            dataType: 'json',
            data: { search: searchTerm },
            success: function (response) {
                let tableBody = $('#dataTable tbody');
                tableBody.empty();

                response.forEach(function (row) {
                    let tableRow = '<tr>';
                    row.forEach(function (cell) {
                        tableRow += '<td>' + cell + '</td>';
                    });
                    var cell;
                    tableRow += '<td>' + generateTarefaDropdown(row[0]) + '</td>';
                    tableRow += '</tr>';
                    tableBody.append(tableRow);
                });

                loadTarefas();
            },
            error: function (xhr, status, error) {
                console.error('Error in request:', error);
                console.log('Response text:', xhr.responseText);
            }
        });
    }

    loadTableData();

    $('#searchInput').on('keyup', function () {
        let value = $(this).val().toLowerCase();
        loadTableData(value);
    });

    function generateTarefaDropdown(staffNome) {
        return `
            <select class="border rounded tarefa-dropdown w-75" data-staff-nome="${staffNome}">
                <option value="">Selecione uma tarefa</option>
            </select>
            <button class="border rounded save-tarefa" data-staff-nome="${staffNome}">Salvar</button>
        `;
    }

    function loadTarefas() {
        $.ajax({
            url: 'http://localhost/Projeto-Gerenciador/php/get_tarefa.php',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                $('.tarefa-dropdown').each(function () {
                    let dropdown = $(this);
                    let currentTarefa = dropdown.siblings('.current-tarefa').text();

                    response.forEach(function (tarefa) {
                        let selected = tarefa.nome === currentTarefa ? 'selected' : '';
                        dropdown.append('<option value="' + tarefa.nome + '" ' + selected + '>' + tarefa.nome + '</option>');
                    });
                });

                $('.save-tarefa').click(function () {
                    let staffNome = $(this).data('staff-nome');
                    let tarefaNome = $(this).siblings('.tarefa-dropdown').val();

                    if (tarefaNome) {
                        console.log('Updating task for:', staffNome, 'with task:', tarefaNome);
                        updateTarefa(staffNome, tarefaNome);
                    } else {
                        alert('Selecione uma tarefa antes de salvar.');
                    }
                });
            },
            error: function (error) {
                console.error('Error in request:', error);
            }
        });
    }

    function updateTarefa(staffNome, tarefaNome) {
        $.ajax({
            url: 'http://localhost/Projeto-Gerenciador/php/update_tarefa.php',
            type: 'POST',
            data: {
                staff_nome: staffNome,
                tarefa_nome: tarefaNome
            },
            success: function (response) {
                console.log('Update response:', response);
                alert('Tarefa atualizada com sucesso!');
                loadTableData("");  // Chame loadTableData para atualizar a tabela após a atualização da tarefa
            },
            error: function (xhr, status, error) {
                console.error('Error in request:', error);
                console.log('Response text:', xhr.responseText);
            }
        });
    }
});
