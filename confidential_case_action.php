<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: confidential.php');
    exit;
}

$user_id = $_SESSION['user_id'];

function redirectBack(string $msg, string $type = 'success'): void {
    header('Location: confidential.php?msg=' . urlencode($msg) . '&type=' . $type);
    exit;
}

// ── Collect + validate the required fields ──────────────────────────────
$clientId   = (int)($_POST['client_id'] ?? 0);
$caseType   = trim($_POST['wc_case_type'] ?? '');
$incidentDate  = trim($_POST['wc_incident_date'] ?? '');
$incidentPlace = trim($_POST['wc_incident_place'] ?? '');
$narrative     = trim($_POST['wc_narrative'] ?? '');
$offenderInfo  = trim($_POST['wc_offender_info'] ?? '');
$witnessInfo   = trim($_POST['wc_witness_info'] ?? '');
$assignedWorker = trim($_POST['wc_assigned_worker'] ?? '');
$status         = trim($_POST['wc_status'] ?? 'Active');
$actions        = $_POST['actions'] ?? [];
$otherActions   = trim($_POST['other_actions'] ?? '');

$validTypes   = ['VAWC', 'CICL', 'CAR', 'Child Abuse'];
$validStatus  = ['Active', 'Monitoring', 'Resolved', 'Closed', 'Referred'];

if ($clientId <= 0) {
    redirectBack('Please select a client.', 'error');
}
if (!in_array($caseType, $validTypes, true)) {
    redirectBack('Please select a valid case category and type.', 'error');
}
if ($incidentDate === '' || $incidentPlace === '' || $narrative === '' || $assignedWorker === '') {
    redirectBack('Please fill in all required fields.', 'error');
}
if (!in_array($status, $validStatus, true)) {
    $status = 'Active';
}

// Merge the checked action checkboxes with the free-text "Other" box into
// one readable block, since wc_actions_taken is a single TEXT column.
$actionsTaken = '';
if (!empty($actions)) {
    $actionsTaken .= "- " . implode("\n- ", array_map('trim', $actions));
}
if ($otherActions !== '') {
    $actionsTaken .= ($actionsTaken !== '' ? "\n\n" : '') . "Other: " . $otherActions;
}

// ── Confirm the client actually exists ──────────────────────────────────
$stmt = $pdo->prepare("SELECT client_id FROM CLIENT WHERE client_id = ?");
$stmt->execute([$clientId]);
if (!$stmt->fetch()) {
    redirectBack('Selected client was not found.', 'error');
}

// ── Handle file uploads ─────────────────────────────────────────────────
// Everything lands in its own restricted-access folder, kept separate from
// the general AICS/document uploads since these are legally protected records.
$uploadDir = __DIR__ . '/Uploads/confidential/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function saveUpload(array $file): ?string {
    global $uploadDir;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file['name']));
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $safeName)) {
        return null;
    }
    return $safeName;
}

$attachments = [];

foreach (['conf_blotter' => 'blotter', 'conf_medical' => 'medical', 'conf_court' => 'court'] as $field => $key) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
        $saved = saveUpload($_FILES[$field]);
        if ($saved) $attachments[$key] = $saved;
    }
}

foreach (['conf_photos' => 'photos', 'conf_other' => 'other'] as $field => $key) {
    if (isset($_FILES[$field]) && is_array($_FILES[$field]['name'])) {
        $saved = [];
        foreach ($_FILES[$field]['name'] as $i => $name) {
            if ($_FILES[$field]['error'][$i] === UPLOAD_ERR_OK) {
                $single = [
                    'name'     => $name,
                    'type'     => $_FILES[$field]['type'][$i],
                    'tmp_name' => $_FILES[$field]['tmp_name'][$i],
                    'error'    => $_FILES[$field]['error'][$i],
                    'size'     => $_FILES[$field]['size'][$i],
                ];
                $result = saveUpload($single);
                if ($result) $saved[] = $result;
            }
        }
        if ($saved) $attachments[$key] = $saved;
    }
}

$attachmentsJson = !empty($attachments) ? json_encode($attachments) : null;

// ── Generate a human-readable case number (e.g. CV-2026-018) ───────────
$prefixMap = ['VAWC' => 'CV', 'CICL' => 'CC', 'CAR' => 'CR', 'Child Abuse' => 'CA'];
$prefix = $prefixMap[$caseType];
$year   = date('Y');

try {
    $pdo->beginTransaction();

    // Lock on the prefix+year so two simultaneous saves of the same case
    // type in the same year can't be handed the same sequence number.
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM woman_and_children
        WHERE wc_case_number LIKE ?
        FOR UPDATE
    ");
    $countStmt->execute([$prefix . '-' . $year . '-%']);
    $seq = (int)$countStmt->fetchColumn() + 1;
    $caseNumber = sprintf('%s-%s-%03d', $prefix, $year, $seq);

    $insert = $pdo->prepare("
        INSERT INTO woman_and_children
            (client_id, wc_case_number, user_id, wc_case_type, wc_incident_date,
             wc_incident_place, wc_narrative, wc_offender_info, wc_witness_info,
             wc_actions_taken, wc_assigned_worker, wc_status, wc_attachments)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->execute([
        $clientId, $caseNumber, $user_id, $caseType, $incidentDate,
        $incidentPlace, $narrative, $offenderInfo ?: null, $witnessInfo ?: null,
        $actionsTaken ?: null, $assignedWorker, $status, $attachmentsJson,
    ]);

    $pdo->commit();

    header('Location: confidential_case_view.php?id=' . urlencode($caseNumber) . '&msg=' . urlencode('Case saved securely.'));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    redirectBack('Something went wrong while saving the case. Please try again.', 'error');
}