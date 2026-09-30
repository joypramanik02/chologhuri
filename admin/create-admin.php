<?php

require_once __DIR__ . '/../includes/db.php';

$username = 'admin';
$password = 'admin123';
$name = 'CholoGhuri Administrator';

$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$stmt = $pdo->prepare("
    SELECT id
    FROM admin_users
    WHERE username = ?
    LIMIT 1
");

$stmt->execute([$username]);

if ($stmt->fetch()) {

    $stmt = $pdo->prepare("
        UPDATE admin_users
        SET password = ?, name = ?
        WHERE username = ?
    ");

    $stmt->execute([
        $hashedPassword,
        $name,
        $username
    ]);

    echo "
        <h2>Admin password updated successfully.</h2>
        <p>Username: <strong>admin</strong></p>
        <p>Password: <strong>admin123</strong></p>
        <p>Please delete this file after setup.</p>
    ";

} else {

    $stmt = $pdo->prepare("
        INSERT INTO admin_users (
            username,
            password,
            name
        )
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $username,
        $hashedPassword,
        $name
    ]);

    echo "
        <h2>Admin account created successfully.</h2>
        <p>Username: <strong>admin</strong></p>
        <p>Password: <strong>admin123</strong></p>
        <p>Please delete this file after setup.</p>
    ";
}
