<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}

// Search by first/last name or client_id. Wrapping with % on both sides
// so "santos" matches "Maria Santos" wherever it appears.
$like = '%' . $q . '%';

$stmt = $pdo->prepare("
    SELECT c.client_id, c.cl_firstname, c.cl_lastname, c.cl_age, c.cl_sex,
           b.barangay_name
    FROM client c
    LEFT JOIN barangay b ON b.barangay_id = c.brgy_id
    WHERE c.cl_firstname LIKE ?
       OR c.cl_lastname LIKE ?
       OR c.client_id = ?
    ORDER BY c.cl_lastname, c.cl_firstname
    LIMIT 8
");
// client_id = ? needs a numeric-safe value; if $q isn't numeric this just won't match anything, which is fine
$stmt->execute([$like, $like, is_numeric($q) ? (int)$q : 0]);

$results = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
    $results[] = [
        'client_id' => (int)$c['client_id'],
        'name'      => trim($c['cl_firstname'] . ' ' . $c['cl_lastname']),
        'barangay'  => $c['barangay_name'] ?? '—',
        'meta'      => ($c['cl_age'] ?? '?') . ' yrs, ' . $c['cl_sex'],
    ];
}

echo json_encode($results);