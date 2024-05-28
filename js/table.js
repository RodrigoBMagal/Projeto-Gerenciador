$(document).ready(function () {
    // Function to extract data from the table and send to server
    $('#saveTable').click(function () {
        let tableData = [];
        $('#dataTable tbody tr').each(function () {
            let row = [];
            $(this).find('td').each(function () {
                row.push($(this).text());
            });
            tableData.push(row);
        });

        console.log('Table Data:', tableData); // Log para depuração

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

    // Load table data on page load
    loadTableData();
    loadStaffWithTasks();

    // Function to load table data
    function loadTableData() {
        $.ajax({
            url: 'http://localhost/Projeto-Gerenciador/php/load_table_data.php',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                console.log('Response from server:', response); // Log the server response

                let tableBody = $('#dataTable tbody');
                tableBody.empty();

                response.forEach(function (row) {
                    let tableRow = '<tr>';
                    row.forEach(function (cell, index) {
                        if (index == row.length - 1) {
                            tableRow += '<td>' + generateTarefaDropdown(row[0], cell) + '</td>';
                        } else {
                            tableRow += '<td>' + cell + '</td>';
                        }
                    });
                    tableRow += '</tr>';
                    tableBody.append(tableRow);
                });

                // Fetch tarefas and populate dropdowns
                loadTarefas();
            },
            error: function (xhr, status, error) {
                console.error('Error in request:', error);
                console.log('Response text:', xhr.responseText); // Log the response text for more details
            }
        });
    }

    // Function to generate tarefa dropdown
    function generateTarefaDropdown(staffNome, currentTarefa) {
        return `
            <select class="tarefa-dropdown" data-staff-nome="${staffNome}">
                <option value="">Selecione uma tarefa</option>
            </select>
            <button class="save-tarefa" data-staff-nome="${staffNome}">Salvar</button>
            <span class="current-tarefa">${currentTarefa}</span>
        `;
    }

    // Function to load tarefas and populate dropdowns
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

                // Attach event listener to save buttons
                $('.save-tarefa').click(function () {
                    let staffNome = $(this).data('staff-nome');
                    let tarefaNome = $(this).siblings('.tarefa-dropdown').val();

                    if (tarefaNome) {
                        updateTarefa(staffNome, tarefaNome);
                    } else {
                        alert('Selecione uma tarefa antes de salvar.');
                    }
                });
            },
            error: function (xhr, status, error) {
                console.error('Error in request:', error);
            }
        });
    }

    // Function to update tarefa
    function updateTarefa(staffNome, tarefaNome) {
        $.ajax({
            url: 'http://localhost/Projeto-Gerenciador/php/update_tarefa.php',
            type: 'POST',
            data: {
                staff_nome: staffNome,
                tarefa_nome: tarefaNome
            },
            success: function (response) {
                alert('Tarefa atualizada com sucesso!');
                loadTableData(); // Refresh the table
            },
            error: function (xhr, status, error) {
                console.error('Error in request:', error);
            }
        });
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
                        let staffDiv = `
                            <div class="staff-member">
                                <img class="rounded-circle" src="https://png.pngtree.com/png-vector/20191009/ourmid/pngtree-user-icon-png-image_1796659.jpg" alt="${staff.nome}" class="staff-photo">
                                <div class="staff-info">
                                    <strong>${staff.nome}</strong>
                                    <p>${staff.tarefa_nome}</p>
                                </div>
                            </div>
                        `;
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

    $(document).ready(function () {
        loadStaffWithTasks();
    });

});
