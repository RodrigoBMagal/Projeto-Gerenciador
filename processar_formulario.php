<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

try {
    $conn = new PDO("mysql:host=localhost;dbname=bancoprojetoweb", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["nome"], $_POST["data"], $_POST["descricao"])) {
        $nome = $_POST["nome"];
        $data = $_POST["data"];
        $descricao = $_POST["descricao"];

        // Usando prepared statement para inserção segura de dados
        $sql = "INSERT INTO tarefa (nome, data, descricao) VALUES (:nome, :data, :descricao)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->execute();

        $response = ["success" => true, "message" => "Dados recebidos com sucesso!"];
    } else {
        // Se os dados não foram recebidos corretamente, retorna um erro em JSON
        http_response_code(400); // Bad Request
        $response = ["success" => false, "message" => "Dados incompletos ou inválidos."];
    }
} catch (PDOException $e) {
    // Se houver um erro na conexão com o banco de dados, retorna um erro em JSON
    http_response_code(500); // Internal Server Error
    $response = ["success" => false, "message" => "Erro no servidor: " . $e->getMessage()];
}

// Retorna a resposta em JSON
echo json_encode($response);
