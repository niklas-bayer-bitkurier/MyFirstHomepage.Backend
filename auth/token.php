<?php

function create_token($user_id, $pdo) {
  $token = bin2hex(random_bytes(32));
  $tokenHash = hash('sha256', $token);
  $expiresAt = date(
    'Y-m-d H:i:s',
    time() + 3600
  );
  $stmt = $pdo->prepare("
    INSERT INTO auth_tokens (user_id, token_hash, expires_at)
    VALUES (:user_id, :token_hash, :expires_at)
  ");
  $stmt->execute(['user_id' => $user_id, 'token_hash' => $tokenHash, 'expires_at' => $expiresAt]);
  
  return json_encode([
    'success' => true,
    'token' => $token,
    'expires_in' => 3600
  ]);
}
