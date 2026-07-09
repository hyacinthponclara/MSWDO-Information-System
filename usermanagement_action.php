<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';

$action = $_POST['action'] ?? '';

function backTo(string $msg, string $type = 'success'): void
{
    header('Location: usermanagement.php?msg=' . urlencode($msg) . '&type=' . urlencode($type));
    exit;
}

// Only Admin/Staff are assignable from this form for now. 'Social Worker' stays
// out of the picker per capstone scope, but existing DB rows with that value
// (e.g. legacy accounts) are left untouched unless someone edits them here.
$validRoles = ['Admin', 'Staff'];

if ($action === 'add' || $action === 'edit') {

    $id         = (int) ($_POST['id'] ?? 0);
    $firstName  = trim($_POST['firstName'] ?? '');
    $lastName   = trim($_POST['lastName'] ?? '');
    $middleName = trim($_POST['middleName'] ?? '') ?: null;
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $role       = trim($_POST['role'] ?? '');
    $email      = trim($_POST['email'] ?? '') ?: null;
    $contact    = trim($_POST['contact'] ?? '') ?: null;
    $isActive   = isset($_POST['isActive']) ? 1 : 0;

    // ── Validation ──
    if ($firstName === '' || $lastName === '' || $username === '' || $role === '') {
        backTo('First name, last name, username, and role are required.', 'error');
    }
    if (!in_array($role, $validRoles, true)) {
        backTo('Invalid role selected.', 'error');
    }
    if ($action === 'add' && strlen($password) < 8) {
        backTo('Password must be at least 8 characters.', 'error');
    }
    if ($action === 'edit' && $password !== '' && strlen($password) < 8) {
        backTo('Password must be at least 8 characters.', 'error');
    }
    if ($action === 'edit' && $id <= 0) {
        backTo('Invalid user.', 'error');
    }

    // ── Username uniqueness (excluding self on edit) ──
    if ($action === 'add') {
        $check = $pdo->prepare("SELECT COUNT(*) FROM MSWDO_USER WHERE username = ?");
        $check->execute([$username]);
    } else {
        $check = $pdo->prepare("SELECT COUNT(*) FROM MSWDO_USER WHERE username = ? AND user_id != ?");
        $check->execute([$username, $id]);
    }
    if ((int) $check->fetchColumn() > 0) {
        backTo('Username already exists. Please choose another.', 'error');
    }

    if ($action === 'add') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO MSWDO_USER
                (username, user_password, user_firstname, user_middlename, user_lastname,
                 user_role, user_contactnum, user_email, user_isactive)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $username, $hash, $firstName, $middleName, $lastName,
            $role, $contact, $email, $isActive,
        ]);
        backTo("User $firstName $lastName added successfully!");
    }

    // edit
    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            UPDATE MSWDO_USER SET
                username = ?, user_password = ?, user_firstname = ?, user_middlename = ?,
                user_lastname = ?, user_role = ?, user_contactnum = ?, user_email = ?, user_isactive = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $username, $hash, $firstName, $middleName, $lastName,
            $role, $contact, $email, $isActive, $id,
        ]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE MSWDO_USER SET
                username = ?, user_firstname = ?, user_middlename = ?,
                user_lastname = ?, user_role = ?, user_contactnum = ?, user_email = ?, user_isactive = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $username, $firstName, $middleName, $lastName,
            $role, $contact, $email, $isActive, $id,
        ]);
    }
    backTo("User $firstName $lastName updated successfully!");

} elseif ($action === 'toggle_status') {

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        backTo('Invalid user.', 'error');
    }

    // Prevent an admin from locking themselves out
    if ($id === (int) $_SESSION['user_id']) {
        backTo('You cannot disable your own account.', 'error');
    }

    $stmt = $pdo->prepare("SELECT user_isactive, user_firstname, user_lastname FROM MSWDO_USER WHERE user_id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        backTo('User not found.', 'error');
    }

    $newStatus = $row['user_isactive'] ? 0 : 1;
    $upd = $pdo->prepare("UPDATE MSWDO_USER SET user_isactive = ? WHERE user_id = ?");
    $upd->execute([$newStatus, $id]);

    $name = $row['user_firstname'] . ' ' . $row['user_lastname'];
    backTo("$name " . ($newStatus ? 'enabled' : 'disabled') . ' successfully!');

} else {
    backTo('Unknown action.', 'error');
}