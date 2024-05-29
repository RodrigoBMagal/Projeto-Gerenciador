<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

try {
    $conn = new PDO("mysql:host=localhost;dbname=bancoprojetoweb", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $data = json_decode($_POST['data'], true);

    $stmt = $conn->prepare("INSERT INTO staff (nome, cargo, local, idade, contrato, salario) VALUES (:nome, :cargo, :local, :idade, :contrato, :salario)");

    foreach ($data as $row) {
        $stmt->execute([
            ':nome' => $row[0],
            ':cargo' => $row[1],
            ':local' => $row[2],
            ':idade' => $row[3],
            ':contrato' => $row[4],
            ':salario' => $row[5]
        ]);
    }

    echo json_encode(["message" => "Data saved successfully"]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
