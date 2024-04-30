<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

try {
    // Conecta ao banco de dados
    $conn = new PDO("mysql:host=localhost;dbname=bancoprojetoweb", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta os dados na tabela 'tarefa'
    $sql = "SELECT * FROM tarefa";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    // Obtém os resultados da consulta
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retorna os resultados em JSON
    echo json_encode($resultados);
} catch (PDOException $e) {
    // Se houver um erro na conexão com o banco de dados, retorna um erro em JSON
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Erro no servidor: " . $e->getMessage()]);
}
