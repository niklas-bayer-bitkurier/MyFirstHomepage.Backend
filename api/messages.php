<?php

require_once __DIR__.'/../config/database.php';

if (!isset($session) || $session === null) {
  http_response_code(401);
  echo json_encode(['status' => "No access to endpoint $path"]);
  exit;
}

$user = $session['user'];

switch ($method) {
  case 'GET':
    $stmt = $pdo->prepare('
      SELECT messages.id, sender_id, sender.name AS "sender_name", recipient_id, recipient.name AS "recipient_name", subject, content, status, reference_type, reference_id
      FROM messages
      JOIN users AS sender ON sender_id = sender.id
      JOIN users AS recipient ON recipient_id = recipient.id
      WHERE recipient_id = :user_id
    ');
    $stmt->execute(['user_id' => $user['id']]);
    $result['messages'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result['status'] = 'Notes fetched';
    echo json_encode($result);
    break;
  default:
    http_response_code(405);
    echo json_encode(['status' => "Unexpected method $method on endpoint $path"]);
    break;
}
