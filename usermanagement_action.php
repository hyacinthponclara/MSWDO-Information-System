<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';

$action = $_POST['action'] ?? '';

function backTo(string $msg, string $type = 'success'): void
{
    header(
        'Location: usermanagement.php?msg=' .
        urlencode($msg) .
        '&type=' .
        urlencode($type)
    );
    exit;
}

$validRoles = ['Admin', 'Staff'];
$validEmploymentStatuses = [
    'Permanent',
    'Casual',
    'Contractual',
    'Job Order',
    'Temporary',
    'Probationary'
];

if ($action === 'add' || $action === 'edit') {

    $id                   = (int) ($_POST['id'] ?? 0);
    $firstName            = trim($_POST['firstName'] ?? '');
    $lastName             = trim($_POST['lastName'] ?? '');
    $middleName           = trim($_POST['middleName'] ?? '') ?: null;
    $username             = trim($_POST['username'] ?? '');
    $password             = $_POST['password'] ?? '';
    $role                 = trim($_POST['role'] ?? '');
    $email                = trim($_POST['email'] ?? '') ?: null;
    $contact              = trim($_POST['contact'] ?? '') ?: null;
    $position             = trim($_POST['position'] ?? '');
    $employmentStatus     = trim($_POST['employmentStatus'] ?? '');
    $office               = trim($_POST['office'] ?? '') ?: null;
    $municipality         = trim($_POST['municipality'] ?? '') ?: null;
    $province             = trim($_POST['province'] ?? '') ?: null;
    $isActive             = isset($_POST['isActive']) ? 1 : 0;

    if (
        $firstName === '' ||
        $lastName === '' ||
        $username === '' ||
        $role === '' ||
        $position === '' ||
        $employmentStatus === ''
    ) {
        backTo(
            'First name, last name, username, role, position, and employment status are required.',
            'error'
        );
    }

    if (!preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ\\s.'-]+$/u", $firstName)) {
        backTo('Invalid first name.', 'error');
    }

    if (!preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ\\s.'-]+$/u", $lastName)) {
        backTo('Invalid last name.', 'error');
    }

    if (!preg_match('/^[A-Za-z0-9._-]{4,30}$/', $username)) {
        backTo('Username must be 4–30 characters and may contain letters, numbers, dot, underscore, or hyphen.', 'error');
    }

    if (!in_array($role, $validRoles, true)) {
        backTo('Invalid role selected.', 'error');
    }

    if (!in_array($employmentStatus, $validEmploymentStatuses, true)) {
        backTo('Invalid employment status selected.', 'error');
    }

    if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        backTo('Please enter a valid email address.', 'error');
    }

    if ($contact !== null) {
        $normalizedContact = preg_replace('/[\s-]/', '', $contact);

        if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $normalizedContact)) {
            backTo('Please enter a valid Philippine mobile number.', 'error');
        }
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

    /*
     * ROLE PROMOTION RULE
     *
     * An existing Staff account cannot be changed to Admin.
     * A separate Admin account must be created instead.
     */
    if ($action === 'edit') {

        $existingStmt = $pdo->prepare(
            "SELECT user_role FROM mswdo_user WHERE user_id = ?"
        );
        $existingStmt->execute([$id]);

        $existingUser = $existingStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingUser) {
            backTo('User not found.', 'error');
        }

        if (
            $existingUser['user_role'] === 'Staff' &&
            $role === 'Admin'
        ) {
            backTo(
                'A Staff account cannot be changed to Admin. Create a new Admin account for this employee instead.',
                'error'
            );
        }
    }

    if ($action === 'add') {
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM mswdo_user WHERE username = ?"
        );
        $check->execute([$username]);
    } else {
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM mswdo_user
             WHERE username = ? AND user_id != ?"
        );
        $check->execute([$username, $id]);
    }

    if ((int) $check->fetchColumn() > 0) {
        backTo(
            'Username already exists. Please choose another.',
            'error'
        );
    }

    if ($action === 'add') {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO mswdo_user
            (
                username,
                user_password,
                user_firstname,
                user_middlename,
                user_lastname,
                user_role,
                user_position,
                user_employment_status,
                user_office,
                user_municipality,
                user_province,
                user_contactnum,
                user_email,
                user_isactive
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $username,
            $hash,
            $firstName,
            $middleName,
            $lastName,
            $role,
            $position,
            $employmentStatus,
            $office,
            $municipality,
            $province,
            $contact,
            $email,
            $isActive
        ]);

        backTo(
            "User $firstName $lastName added successfully!"
        );
    }

    if ($password !== '') {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE mswdo_user SET
                username = ?,
                user_password = ?,
                user_firstname = ?,
                user_middlename = ?,
                user_lastname = ?,
                user_role = ?,
                user_position = ?,
                user_employment_status = ?,
                user_office = ?,
                user_municipality = ?,
                user_province = ?,
                user_contactnum = ?,
                user_email = ?,
                user_isactive = ?
            WHERE user_id = ?
        ");

        $stmt->execute([
            $username,
            $hash,
            $firstName,
            $middleName,
            $lastName,
            $role,
            $position,
            $employmentStatus,
            $office,
            $municipality,
            $province,
            $contact,
            $email,
            $isActive,
            $id
        ]);

    } else {

        $stmt = $pdo->prepare("
            UPDATE mswdo_user SET
                username = ?,
                user_firstname = ?,
                user_middlename = ?,
                user_lastname = ?,
                user_role = ?,
                user_position = ?,
                user_employment_status = ?,
                user_office = ?,
                user_municipality = ?,
                user_province = ?,
                user_contactnum = ?,
                user_email = ?,
                user_isactive = ?
            WHERE user_id = ?
        ");

        $stmt->execute([
            $username,
            $firstName,
            $middleName,
            $lastName,
            $role,
            $position,
            $employmentStatus,
            $office,
            $municipality,
            $province,
            $contact,
            $email,
            $isActive,
            $id
        ]);
    }

    backTo(
        "User $firstName $lastName updated successfully!"
    );

} elseif ($action === 'toggle_status') {

    $id = (int) ($_POST['id'] ?? 0);

    if ($id <= 0) {
        backTo('Invalid user.', 'error');
    }

    if ($id === (int) $_SESSION['user_id']) {
        backTo(
            'You cannot disable your own account.',
            'error'
        );
    }

    $stmt = $pdo->prepare("
        SELECT user_isactive, user_firstname, user_lastname
        FROM mswdo_user
        WHERE user_id = ?
    ");

    $stmt->execute([$id]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        backTo('User not found.', 'error');
    }

    $newStatus = $row['user_isactive'] ? 0 : 1;

    $upd = $pdo->prepare("
        UPDATE mswdo_user
        SET user_isactive = ?
        WHERE user_id = ?
    ");

    $upd->execute([
        $newStatus,
        $id
    ]);

    $name =
        $row['user_firstname'] .
        ' ' .
        $row['user_lastname'];

    backTo(
        "$name " .
        ($newStatus ? 'enabled' : 'disabled') .
        ' successfully!'
    );

} else {

    backTo(
        'Unknown action.',
        'error'
    );
}