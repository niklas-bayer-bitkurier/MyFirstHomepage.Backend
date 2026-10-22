<?php
require_once __DIR__.'/../config/database.php';

switch ($method) {
  case 'POST':
    $data = json_decode(file_get_contents("php://input"), true);
    $username = $data['name'];
    $password = $data['password'];

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

    $stmt = $pdo->prepare("
      SELECT COUNT(id) FROM users
      WHERE name = :name AND password = :password
    ");
    $stmt->execute(['name' => $username, 'password' => $password]);
    $user_count_json = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_count = json_decode($user_count_json['COUNT(id)']);

    if ($user_count != 1) {
      http_response_code(401);
      echo json_encode(['error' => "Please check your username and password"]);
      exit;
    }

    $password = null;
    $stmt = $pdo->prepare("
      SELECT id FROM users
      WHERE name = :name
    ");
    $stmt->execute(['name' => $username]);
    $user_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    require_once __DIR__.'/../auth/token.php';
    echo create_token($user_id, $pdo);
    break;
  default:
    http_response_code(405);
    echo json_encode(['error' => "Unhadled method $method on endpoint $path"]);
    break;
}
