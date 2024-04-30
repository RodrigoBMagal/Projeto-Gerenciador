<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

try {
    $conn = new PDO("mysql:host=localhost;dbname=bancoprojetoweb", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtém o nome enviado via POST
    $nome = $_POST['nome'];

    // Consulta SQL para excluir o item com o mesmo nome do banco de dados
    $query = "DELETE FROM tarefa WHERE nome = :nome";
    $stmt = $conn->prepare($query);
    $stmt->execute(array(':nome' => $nome));

    echo json_encode(array('success' => true));
} catch (PDOException $e) {
    echo json_encode(array('error' => 'Erro no servidor: ' . $e->getMessage()));
}
