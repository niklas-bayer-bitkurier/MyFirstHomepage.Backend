<?php
require_once __DIR__.'/../helpers/array_helper.php';

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
    'samesite' => 'None'
  ]);
}

function find_session($token, $pdo) {
  $token_hash = hash('sha256', $token);
  $stmt = $pdo->prepare('
    SELECT auth_tokens.id AS "id", expires_at, user_id, users.name AS "user_name", users.email AS "user_email", users.role AS "user_role"
    FROM auth_tokens
    JOIN users ON auth_tokens.user_id = users.id
    WHERE token_hash = :token_hash
  ');
  $stmt->execute(['token_hash' => $token_hash]);
  $result = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$result) {
    return null;
  }
  
  $expires_at = $result['expires_at'];
  $is_valid = time() <= $expires_at;
  
  if (!$is_valid) {
    $stmt = $pdo->prepare('
      DELETE FROM auth_tokens
      WHERE id = :id
    ');
    $stmt->execute(['id' => $result['id']]);
  }

  $user['id'] = $result['user_id'];
  $user['name'] = $result['user_name'];
  $user['email'] = $result['user_email'];
  $user['role'] = $result['user_role'];

  return ['is_valid' => $is_valid, 'user' => $user];
}
