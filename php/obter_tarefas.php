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

    // Formata a data para o formato brasileiro
    foreach ($resultados as &$row) {
        if (isset($row['data'])) { 
            $date = DateTime::createFromFormat('Y-m-d', $row['data']);
            if ($date) {
                $row['data'] = $date->format('d/m/Y');
            }
        }
    }

    // Retorna os resultados em JSON
    echo json_encode($resultados);
} catch (PDOException $e) {
    // Se houver um erro na conexão com o banco de dados, retorna um erro em JSON
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Erro no servidor: " . $e->getMessage()]);
}
