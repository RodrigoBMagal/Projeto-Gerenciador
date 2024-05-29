<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

try {
    $conn = new PDO("mysql:host=localhost;dbname=bancoprojetoweb", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Use $conn instead of $pdo
    $stmt = $conn->query("SELECT nome, cargo, local, idade, contrato, salario FROM staff");
    $data = $stmt->fetchAll(PDO::FETCH_NUM);

    foreach ($data as &$row) {
        $row['contrato'] = DateTime::createFromFormat('Y-m-d', $row['contrato'])->format('d/m/Y');
    }

    echo json_encode($data);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
