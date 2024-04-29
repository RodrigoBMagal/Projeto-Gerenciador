<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

try {
    $conn = new PDO("mysql:host=localhost;dbname=bancoprojetoweb", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage();
    exit;
}

// Obtém o nome enviado via POST
$nome = $_POST['nome'];

// Consulta SQL para verificar se o nome já existe no banco de dados
$query = "SELECT COUNT(*) AS total FROM tarefa WHERE nome = :nome";
$stmt = $conn->prepare($query);
$stmt->execute(array(':nome' => $nome));
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

// Se o total for maior que 0, o nome já existe
if ($resultado['total'] > 0) {
    echo json_encode(array('existe' => true));
} else {
    echo json_encode(array('existe' => false));
}
