<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$preparedByName = 'Unknown User';
$preparedByPosition = '';

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId > 0 && isset($pdo)) {

    $stmt = $pdo->prepare("
        SELECT
            user_firstname,
            user_middlename,
            user_lastname,
            user_position
        FROM MSWDO_USER
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$userId]);

    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($currentUser) {

        $nameParts = array_filter([
            trim($currentUser['user_firstname'] ?? ''),
            trim($currentUser['user_middlename'] ?? ''),
            trim($currentUser['user_lastname'] ?? '')
        ]);

        $preparedByName = trim(implode(' ', $nameParts));

        $preparedByPosition = trim(
            $currentUser['user_position'] ?? ''
        );

        if ($preparedByName === '') {
            $preparedByName = 'Unknown User';
        }
    }
}
?>