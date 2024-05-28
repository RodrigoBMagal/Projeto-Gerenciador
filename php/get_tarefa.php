<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

try {
    $conn = new PDO("mysql:host=localhost;dbname=bancoprojetoweb", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->query("SELECT nome FROM tarefa");
    $tarefas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($tarefas);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
