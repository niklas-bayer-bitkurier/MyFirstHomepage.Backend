<?php

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../auth/user_auth.php';
require_once __DIR__.'/../auth/token.php';

$retval = [];

switch ($method) {
  case 'GET':
    $is_logged_in = isset($session) && $session !== null;
    $retval['logged_in'] = $is_logged_in;
    $retval['user'] = $session['user'] ?? null;
    echo json_encode($retval);
    break;
  case 'POST':
    $data = json_decode(file_get_contents("php://input"), true);
    $username = $data['name'];
    $password = $data['password'];

    if (!(isset($username) && isset($password))) {
      http_response_code(400);
      $retval['status'] = 'Error: Username and password must be specfied';
      echo json_encode($retval);
      exit;
    }

    $user_id = get_id_if_matches($username, $password, $pdo);

    if ($user_id === -1) {
      http_response_code(401);
      $retval['status'] = "Error: Password mismatch";
      echo json_encode($retval);
      exit;
    }

    if (isset($session) && $session !== null) {
      $user = $session['user']; 
    
      if ($user['name'] === $username) {
        $retval['status'] = 'Success: Using existing session';
        echo json_encode($retval);
        exit;
      }
    }

    create_token($user_id, $pdo);
    $retval['status'] = 'Success: Token created';
    echo json_encode($retval);
    break;
  default:
    http_response_code(405);
    $retval['status'] = "Error: Unhandled method $method on endpoint $path";
    echo json_encode($retval);
    break;
}
