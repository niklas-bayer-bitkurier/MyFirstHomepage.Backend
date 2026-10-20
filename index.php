<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

require_once __DIR__.'/config/database.php';

$stmt = $pdo->query("SELECT id, name, email FROM users");

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
