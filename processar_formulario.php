<?php

$conn = new PDO("mysql:host=localhost;dbname=bancoprojetoweb", "root", "");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$nome = $_POST["nome"];
$data = $_POST["data"];
$descricao = $_POST["descricao"];

$sql = "INSERT INTO tarefa ('nome', 'data' , 'descricao') VALUES (\"$nome\", \"$data\", \"$descricao\")";

$exec = $$conn->query($sql);
