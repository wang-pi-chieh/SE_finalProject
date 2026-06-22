<?php

function auth_password_ensure_column(mysqli $conn): void
{
    $stmt = $conn->prepare("
        SELECT CHARACTER_MAXIMUM_LENGTH
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND COLUMN_NAME = 'password'
        LIMIT 1
    ");

    if (!$stmt) {
        throw new RuntimeException('密碼欄位檢查初始化失敗');
    }

    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || (int) $row['CHARACTER_MAXIMUM_LENGTH'] < 255) {
        if (!$conn->query("ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL COMMENT '密碼雜湊或舊明文密碼'")) {
            throw new RuntimeException('密碼欄位升級失敗');
        }
    }
}

function auth_password_hash(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function auth_password_is_hash(string $storedPassword): bool
{
    $info = password_get_info($storedPassword);
    return ($info['algo'] ?? 0) !== 0;
}

function auth_password_verify(string $password, string $storedPassword): bool
{
    if (auth_password_is_hash($storedPassword)) {
        return password_verify($password, $storedPassword);
    }

    return hash_equals($storedPassword, $password);
}

function auth_password_needs_upgrade(string $storedPassword): bool
{
    if (!auth_password_is_hash($storedPassword)) {
        return true;
    }

    return password_needs_rehash($storedPassword, PASSWORD_DEFAULT);
}

function auth_password_upgrade_if_needed(mysqli $conn, string $username, string $password, string $storedPassword): void
{
    if (!auth_password_needs_upgrade($storedPassword)) {
        return;
    }

    try {
        auth_password_ensure_column($conn);
        $newHash = auth_password_hash($password);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        if (!$stmt) {
            return;
        }

        $stmt->bind_param("ss", $newHash, $username);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        return;
    }
}
