<?php

require_once __DIR__.'/../config/database.php';

switch ($method) {
  case 'POST':
    $data = json_decode(file_get_contents("php://input"), true);
    $name = $data['name'];
    $password = $data['password'];

    if (!(isset($name) && isset($password))) {
      http_response_code(400);
      echo json_encode(['error' => "Username and password must be specified"]);
      exit;
    }

    if (!is_name_valid($name)) {
      http_response_code(400);
      echo json_encode(['error' => "Username not valid"]);
      exit;
    }

    if (strlen($password) != 64) {
      http_response_code(400);
      echo json_encode(['error' => "Password has was odd"]);
      exit;
    }

    $stmt = $pdo->prepare("
      SELECT COUNT(id) FROM users
      WHERE name = :name
    ");
    $stmt->execute(['name' => $name]);
    $user_exists = $stmt->fetch(PDO::FETCH_ASSOC)['COUNT(id)'];

    if ($user_exists != 0) {
      http_response_code(400);
      echo json_encode(['error' => "User already exists"]);
      exit;
    }

    $stmt = $pdo->prepare("
      INSERT INTO users (name, password)
      VALUES (:name, :password)
    ");
    $stmt->execute(['name' => $name, 'password' => $password]);
    $stmt->fetch(PDO::FETCH_ASSOC);
    
    break;
  default:
    http_response_code(405);
    echo json_encode(['error' => "Unhandled method $method on endpoint $path"]);
    break;
}

function is_name_valid($name) {
  if (strlen($name) > 100)
    return false;

  for ($i = 0; $i < strlen($name); $i++) {
    $c = $name[$i];
    if (!(($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z'))) {
      return ($c >= '0' && $c <= '9');
    }
  }

  return true;
}
