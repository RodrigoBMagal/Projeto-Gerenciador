<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

try {
    // Conexão com o banco de dados
    $conn = new PDO("mysql:host=localhost;dbname=bancoprojetoweb", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query para buscar funcionários com tarefas não nulas
    $stmt = $conn->query("SELECT staff.nome, tarefa.nome AS tarefa_nome FROM staff JOIN tarefa ON staff.tarefa_id = tarefa.nome WHERE staff.tarefa_id IS NOT NULL");
    $staffWithTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificar saída no log do servidor
    error_log(json_encode($staffWithTasks));

    // Retornar os dados em formato JSON
    echo json_encode($staffWithTasks);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
