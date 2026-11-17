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
  case 'PATCH':
    $current_time = time();
    $data = json_decode(file_get_contents("php://input"), true);
    $messages = $data['messages'];

    if (!isset($messages)) {
      http_response_code(400);
      echo json_encode(['status' => 'Messages must be specified']);
      exit;
    }

    if (!is_array($messages) || empty($messages)) {
      http_response_code(400);
      echo json_encode(['status' => 'Messages object must be an array and must not be empty']);
      exit;
    }

    $message_ids = [];
    for ($i = 0; $i < count($messages); $i++) {
      $message = $messages[$i];
      if (!isset($message['id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'Each message must contain its id']);
        exit;
      }
      $id = $message['id'];
      if (filter_var($id, FILTER_VALIDATE_INT) !== true && $id < 1) {
        http_response_code(400);
        echo json_encode(['status' => "A message contained an id in an invalid format: $id"]);
        exit;
      }
      if (in_array($id, $message_ids)) {
        http_response_code(400);
        echo json_encode(['status' => 'A message may only be specified once']);
        exit;
      }
      
      $message_ids[$i] = $id;
    }

    $placeholder = implode(',', array_fill(0, count($message_ids), '?'));
    $stmt = $pdo->prepare("
      UPDATE messages
      SET status = 'r', read_at = ?
      WHERE recipient_id = ? AND status = 's' AND id IN ($placeholder)
    ");
    $stmt->execute([$current_time, $user['id'], ...$message_ids]);
    $count = $stmt->rowCount();

    if ($count !== count($messages)) {
      $retval['warning'] = 'Not all messages where updated. Messages must be unread and directed to you. Transferred messages: '.count($messages);
    }

    $retval['status'] = "Updated $count messages as read";
    echo json_encode($retval);
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
