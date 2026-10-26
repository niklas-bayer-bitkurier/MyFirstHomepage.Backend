<?php
require_once __DIR__.'/../helpers/array_helper.php';

$session = [];

function create_token($user_id, $pdo) {
  $token = bin2hex(random_bytes(32));
  $token_hash = hash('sha256', $token);
  $created_at = time();
  $expires_at = $created_at + 3600;
  $stmt = $pdo->prepare("
    INSERT INTO auth_tokens (user_id, token_hash, expires_at, created_at)
    VALUES (:user_id, :token_hash, :expires_at, :created_at)
  ");
  $stmt->execute(['user_id' => $user_id, 'token_hash' => $token_hash, 'expires_at' => $expires_at, 'created_at' => $created_at]);

  setcookie('__Host-session', $token, [
    'expires' => $expires_at,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
  ]);

  $session[$user_id] = $token_hash;
  
  return json_encode([
    'success' => true
  ]);
}

function find_session($token_hash, $pdo) {
  $stmt = $pdo->prepare('
    SELECT id, expires_at, user_id FROM auth_tokens
    WHERE token_hash = :token_hash
  ');
  $stmt->execute(['token_hash' => $token_hash]);
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$result) {
    return null;
  }
  $expires_at = $result['expires_at'];
  $session_user_id = $result['user_id'];
  $is_valid = time() <= $expires_at;
  if (!$is_valid) {
    $stmt = $pdo->prepare('
      DELETE FROM auth_tokens
      WHERE id = :id
    ');
    $stmt->execute(['id' => $result['id']]);
  }
  return ['is_valid' => $is_valid, 'user_id' => $session_user_id];
}
