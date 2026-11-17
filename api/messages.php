<?php

require_once __DIR__.'/../config/database.php';

define('MESSAGES_QUERY', '
  SELECT messages.id, sender_id, sender.name AS "sender_name",
    recipient_id, recipient.name AS "recipient_name",
    subject, content, status, sent_at, reference_type, reference_id
  FROM messages
  JOIN users AS sender ON sender_id = sender.id
  JOIN users AS recipient ON recipient_id = recipient.id
  WHERE recipient_id = :user_id
');

if (!isset($session) || $session === null) {
  http_response_code(401);
  echo json_encode(['status' => "No access to endpoint $path"]);
  exit;
}

$user = $session['user'];

switch ($method) {
  case 'GET':
    if (!isset($_GET['status'])) {
      $result['messages'] = retrieve_messages($pdo, $user);
      $result['status'] = 'Notes fetched';
      echo json_encode($result);
      exit;
    }
  
    $status = filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW);

    switch ($status) {
      case 'unread':
        $stmt = $pdo->prepare(MESSAGES_QUERY.'
          AND status = "s"
        ');
        $result['messages'] = retrieve_messages($pdo, $user, $stmt);
        break;
      case 'read':
        $stmt = $pdo->prepare(MESSAGES_QUERY.'
          AND status = "r"
        ');
        $result['messages'] = retrieve_messages($pdo, $user, $stmt);
        break;
      case 'all':
        $result['messages'] = retrieve_messages($pdo, $user);
        break;
      default:
        http_response_code(400);
        $result['status'] = "Unhandled status query value $status in GET on $path";
        break;
    }
    $result['status'] ??= "Notes fetched for query status=$status";
    echo json_encode($result);
    break;
  default:
    http_response_code(405);
    echo json_encode(['status' => "Unexpected method $method on endpoint $path"]);
    break;
}

function retrieve_messages($pdo, $user, $stmt = null) {
  $stmt ??= $pdo->prepare(MESSAGES_QUERY);
  $stmt->execute(['user_id' => $user['id']]);
  $result = [];
  while (($fetch = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
    $entry = [
      'id' => $fetch['id'],
      'sender' => [ 'id' => $fetch['sender_id'], 'name' => $fetch['sender_name'] ],
      'recipient' => [ 'id' => $fetch['recipient_id'], 'name' => $fetch['recipient_name'] ],
      'subject' => $fetch['subject'],
      'content' => $fetch['content'],
      'status' => $fetch['status'],
      'sent_at' => $fetch['sent_at'],
      'reference' => [ 'id' => $fetch['reference_id'], 'type' => $fetch['reference_type'] ]
    ];
    array_push($result, $entry);
  }
  return $result;
}
