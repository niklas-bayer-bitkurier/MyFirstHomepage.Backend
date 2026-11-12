<?php

require_once __DIR__.'/../config/database.php';

switch ($method) {
  case 'GET':
    $is_logged_in = isset($session) && $session !== null;
    $retval['logged_out'] = !$is_logged_in;
    echo json_encode($retval);
    break;
  case 'POST':
    if (!isset($session) || $session === null) {
      echo json_encode(['status' => 'Not logged in']);
      exit;
    }
    $user = $session['user'];
    $success = try_delete_token($session['token'], $pdo);
    $message = '';
    if ($success) {
      $status_message = 'Successfully logged off';
    } else {
      http_response_code(400);
      $status_message = 'Error: Could not log off';
    }
    echo json_encode(['status' => $status_message]);
    break;
  default:
    http_response_code(405);
    echo json_encode(['status' => "Error: Unhandled method $method on endpoint $path"]);
    break;
}
