<?php
require_once __DIR__.'/../config/database.php';

switch ($method) {
  case 'POST':
    $username = $_POST['name'];
    $password = $_POST['password'];
    if (!(isset($username) && isset($password))) {
      http_response_code(400);
      echo json_encode(['error' => "Username and password must be specified."]);
      exit;
    }
    if (strlen($password) != 64) {
      http_response_code(400);
      echo json_encode(['error' => "Password hash was odd"]);
      exit;
    }
    $stmt = pdo->prepare("
      SELECT COUNT(id) FROM users
      WHERE name = :name AND password = :password;
    ");
    $stmt->execute(['name' => $name, 'password' => $password]);
    $user_count = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user_count != 1) {
      http_response_code(401);
      echo json_encode(['error' => "Unauthorized: login failed"]);
      exit;
    }
    $password = null;
    echo json_encode(['Success' => 'Logged in']);
    break;
  default:
    http_response_code(405);
    echo json_encode(['error' => "Unhadled method $method on endpoint $path"]);
    break;
}
