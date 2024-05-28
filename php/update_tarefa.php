<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

try {
    $conn = new PDO("mysql:host=localhost;dbname=bancoprojetoweb", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $staffNome = $_POST['staff_nome'];
    $tarefaNome = $_POST['tarefa_nome'];

    $stmt = $conn->prepare("UPDATE staff SET tarefa_id = (SELECT nome FROM tarefa WHERE nome = :tarefa_nome) WHERE nome = :staff_nome");
    $stmt->execute([':tarefa_nome' => $tarefaNome, ':staff_nome' => $staffNome]);

    echo json_encode(["message" => "Tarefa atualizada com sucesso"]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
