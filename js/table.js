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
});


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
                row.forEach(function (cell) {
                    tableRow += '<td>' + cell + '</td>';
                });
                tableRow += '</tr>';
                tableBody.append(tableRow);
            });
        },
        error: function (xhr, status, error) {
            console.error('Error in request:', error);
            console.log('Response text:', xhr.responseText); // Log the response text for more details
        }
    });
}

loadTableData();
