<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

try {
    $conn = new PDO("mysql:host=localhost;dbname=bancoprojetoweb", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $search = isset($_GET['search']) ? $_GET['search'] : '';

    $stmt = $conn->prepare("SELECT nome, cargo, local, idade, contrato, salario FROM staff WHERE nome LIKE :search OR cargo LIKE :search OR local LIKE :search OR idade LIKE :search OR contrato LIKE :search OR salario LIKE :search");
    $stmt->execute(['search' => '%' . $search . '%']);

    $data = $stmt->fetchAll(PDO::FETCH_NUM);

    echo json_encode($data);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
