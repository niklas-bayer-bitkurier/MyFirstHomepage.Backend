<?php

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../auth/user_auth.php';
require_once __DIR__.'/../auth/token.php';

$retval = [];

switch ($method) {
  case 'POST':
    $data = json_decode(file_get_contents("php://input"), true);
    $name = $data['name'];
    $password = $data['password'];

    if (!(isset($name) && isset($password))) {
      http_response_code(400);
      $retval['status'] = 'Error: Username and password must be specified';
      echo json_encode($retval);
      exit;
    }

    if (!is_name_valid($name)) {
      http_response_code(400);
      $retval['status'] = 'Error: Invalid username';
      echo json_encode($retval);
      exit;
    }

    if (strlen($password) < 8) {
      http_response_code(400);
      $retval['status'] = 'Password too short';
      echo json_encode($retval);
      exit;
    }

    $salt = create_salt();
    $password = compute_hash($password, $salt);
    $user_id = register_user(['name' => $name, 'password' => $password, 'salt' => $salt], $pdo);

    if ($user_id === -1) {
      http_response_code(400);
      $retval['status'] = 'Error: User already exists';
      echo json_encode($retval);
      exit;
    }

    create_token($user_id, $pdo);
    $retval['status'] = 'Success';
    echo json_encode($retval);
    break;
  default:
    http_response_code(405);
    $retval['status'] = "Error: Unhandled method $method on endpoint $path";
    echo json_encode($retval);
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
