<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'auth.php';
requireRole(['Admin', 'Social Worker', 'Staff']);
require 'db_connect.php';

$client_id = filter_input(INPUT_GET, 'client_id', FILTER_VALIDATE_INT);
if (!$client_id || $client_id <= 0) {
    header('Location: clientslist.php');
    exit;
}

/* Fetch the registered client. */
$stmt = $pdo->prepare("
    SELECT c.*, b.barangay_name
    FROM CLIENT c
    LEFT JOIN BARANGAY b ON b.barangay_id = c.brgy_id
    WHERE c.client_id = :client_id
    LIMIT 1
");
$stmt->execute([':client_id' => $client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    header('Location: clientslist.php');
    exit;
}

/* Fetch the original family composition saved during client registration. */
$famStmt = $pdo->prepare("
    SELECT family_composition_json
    FROM CASE_STUDY
    WHERE client_id = :client_id
      AND problem_presented = 'Initial registration'
    ORDER BY created_at ASC
    LIMIT 1
");
$famStmt->execute([':client_id' => $client_id]);
$famRow = $famStmt->fetch(PDO::FETCH_ASSOC);
$registrationFamily = [];

if ($famRow && !empty($famRow['family_composition_json'])) {
    $decoded = json_decode($famRow['family_composition_json'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $registrationFamily = $decoded;
    }
}

/* Detect previous approved/released assistance. */
$prevStmt = $pdo->prepare("
    SELECT
        a.availment_id,
        a.av_amount,
        a.av_date_applied,
        a.av_status,
        p.program_name
    FROM AVAILMENT a
    LEFT JOIN PROGRAM p ON p.program_id = a.program_id
    WHERE a.client_id = :client_id
      AND a.av_status IN ('Approved', 'Released')
    ORDER BY a.av_date_applied DESC
");
$prevStmt->execute([':client_id' => $client_id]);
$prevAvailments = $prevStmt->fetchAll(PDO::FETCH_ASSOC);
$has_prev_dswd_assistance = !empty($prevAvailments);

$prev_assistance_details_auto = '';
$prev_assistance_date_auto = '';
if ($has_prev_dswd_assistance) {
    $parts = [];
    foreach ($prevAvailments as $av) {
        $program = $av['program_name'] ?? 'Assistance';
        $amount = number_format((float)($av['av_amount'] ?? 0), 2);
        $date = !empty($av['av_date_applied']) ? date('F j, Y', strtotime($av['av_date_applied'])) : '—';
        $parts[] = $program . ' — ₱' . $amount . ' — ' . $date;
    }
    $prev_assistance_details_auto = implode("\n", $parts);
    $prev_assistance_date_auto = $prevAvailments[0]['av_date_applied'] ?? '';
}

/* Build a normalized family snapshot for this case-study form. */
$clientFullName = trim(
    ($client['cl_firstname'] ?? '') . ' ' .
    ($client['cl_middlename'] ?? '') . ' ' .
    ($client['cl_lastname'] ?? '') . ' ' .
    ($client['cl_suffix'] ?? '')
);

$clientFamilyRow = [
    'name' => $clientFullName,
    'relationship' => 'Client (Self)',
    'age' => $client['cl_age'] ?? null,
    'sex' => $client['cl_sex'] ?? '',
    'civil_status' => $client['cl_civilstatus'] ?? '',
    'education' => $client['cl_educ_attain'] ?? '',
    'occupation' => $client['cl_occupation'] ?? '',
    'income' => (float)($client['cl_monthly_income'] ?? 0),
];

/* Avoid duplicating the client if the registration JSON already contains it. */
$familyRows = [$clientFamilyRow];
foreach ($registrationFamily as $member) {
    $memberName = trim((string)($member['name'] ?? ''));
    if ($memberName === '' || strcasecmp($memberName, $clientFullName) !== 0) {
        $familyRows[] = [
            'name' => $memberName,
            'relationship' => $member['relationship'] ?? $member['relation'] ?? '',
            'age' => $member['age'] ?? '',
            'sex' => $member['sex'] ?? '',
            'civil_status' => $member['civil_status'] ?? $member['civilStatus'] ?? '',
            'education' => $member['education'] ?? '',
            'occupation' => $member['occupation'] ?? '',
            'income' => (float)($member['income'] ?? 0),
        ];
    }
}

/* The form stores the household total as CASE_STUDY.combined_income. */
$combinedIncome = 0.0;
foreach ($familyRows as $member) {
    $combinedIncome += (float)($member['income'] ?? 0);
}

$full_name = htmlspecialchars($clientFullName, ENT_QUOTES, 'UTF-8');
$barangay = htmlspecialchars($client['barangay_name'] ?? 'Unknown Barangay', ENT_QUOTES, 'UTF-8');
$age = htmlspecialchars((string)($client['cl_age'] ?? '—'), ENT_QUOTES, 'UTF-8');
$sex = htmlspecialchars((string)($client['cl_sex'] ?? ''), ENT_QUOTES, 'UTF-8');
$civilStat = htmlspecialchars((string)($client['cl_civilstatus'] ?? ''), ENT_QUOTES, 'UTF-8');
$subtitle = "ID-{$client_id} · {$barangay} · {$age} yrs, {$sex} · {$civilStat}";

/* SAVE CASE STUDY */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    if ($user_id <= 0) {
        die('Unable to identify the logged-in user.');
    }

    $interview_date = trim($_POST['interview_date'] ?? '');
    $type_of_case_study = trim($_POST['type_of_case_study'] ?? '') ?: null;
    $patient_name = trim($_POST['patient_name'] ?? '') ?: null;
    $patient_relationship = trim($_POST['patient_relationship'] ?? '') ?: null;

    $combined_income = (float)($_POST['combined_income'] ?? $combinedIncome);
    $monthly_expenses = (float)($_POST['monthly_expenses'] ?? 0);
    $emergency_fund_available = ((int)($_POST['emergency_fund_available'] ?? 0) === 1) ? 1 : 0;

    $crisis_severity = trim($_POST['crisis_severity'] ?? '') ?: null;
    $crisis_experienced = trim($_POST['crisis_experienced'] ?? '') ?: null;

    $problem_presented = trim($_POST['problem_presented'] ?? '');
    $home_condition = trim($_POST['home_condition'] ?? '') ?: null;
    $indigency_assessment = trim($_POST['indigency_assessment'] ?? '') ?: null;
    $recommendation = trim($_POST['recommendation'] ?? '') ?: null;

    $previous_dswd_assistance = ((int)($_POST['previous_dswd_assistance'] ?? 0) === 1) ? 1 : 0;
    $previous_assistance_details = trim($_POST['previous_assistance_details'] ?? '') ?: null;
    $previous_assistance_date = trim($_POST['previous_assistance_date'] ?? '') ?: null;
    $insurance_coverage = trim($_POST['insurance_coverage'] ?? '') ?: null;
    $savings = (float)($_POST['savings'] ?? 0);

    if ($interview_date === '') {
        die('Interview date is required.');
    }
    if ($problem_presented === '') {
        die('Problem presented is required.');
    }

    $family_json = json_encode(
        $familyRows,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    if ($family_json === false) {
        die('Unable to encode family composition.');
    }

    try {
        $pdo->beginTransaction();

        $insert = $pdo->prepare("
            INSERT INTO CASE_STUDY (
                client_id,
                user_id,
                interview_date,
                type_of_case_study,
                patient_name,
                patient_relationship,
                family_composition_json,
                combined_income,
                monthly_expenses,
                emergency_fund_available,
                crisis_severity,
                crisis_experienced,
                problem_presented,
                home_condition,
                indigency_assessment,
                recommendation,
                previous_dswd_assistance,
                previous_assistance_details,
                previous_assistance_date,
                insurance_coverage,
                savings
            ) VALUES (
                :client_id,
                :user_id,
                :interview_date,
                :type_of_case_study,
                :patient_name,
                :patient_relationship,
                :family_composition_json,
                :combined_income,
                :monthly_expenses,
                :emergency_fund_available,
                :crisis_severity,
                :crisis_experienced,
                :problem_presented,
                :home_condition,
                :indigency_assessment,
                :recommendation,
                :previous_dswd_assistance,
                :previous_assistance_details,
                :previous_assistance_date,
                :insurance_coverage,
                :savings
            )
        ");

        $insert->execute([
            ':client_id' => $client_id,
            ':user_id' => $user_id,
            ':interview_date' => $interview_date,
            ':type_of_case_study' => $type_of_case_study,
            ':patient_name' => $patient_name,
            ':patient_relationship' => $patient_relationship,
            ':family_composition_json' => $family_json,
            ':combined_income' => $combined_income,
            ':monthly_expenses' => $monthly_expenses,
            ':emergency_fund_available' => $emergency_fund_available,
            ':crisis_severity' => $crisis_severity,
            ':crisis_experienced' => $crisis_experienced,
            ':problem_presented' => $problem_presented,
            ':home_condition' => $home_condition,
            ':indigency_assessment' => $indigency_assessment,
            ':recommendation' => $recommendation,
            ':previous_dswd_assistance' => $previous_dswd_assistance,
            ':previous_assistance_details' => $previous_assistance_details,
            ':previous_assistance_date' => $previous_assistance_date,
            ':insurance_coverage' => $insurance_coverage,
            ':savings' => $savings,
        ]);

        $case_study_id = (int)$pdo->lastInsertId();
        $pdo->commit();

        header('Location: casestudy_view.php?id=' . $case_study_id);
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('CASE_STUDY INSERT ERROR: ' . $e->getMessage());
        die('Unable to save the Case Study. ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
}

$familyJsonForJs = json_encode(
    $familyRows,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Case Study – MSWDO San Enrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['DM Sans', 'sans-serif'], serif: ['DM Serif Display', 'serif'] },
                    colors: {
                        navy: { DEFAULT: '#16a34a', 50: '#f0fdf4', 100: '#dcfce7', 400: '#4ade80', 500: '#22c55e', 600: '#16a34a', 700: '#15803d' },
                        gold: { DEFAULT: '#C49A2A', 400: '#C49A2A' },
                        slate2: '#F4F7FC',
                        mswdo: {
                            50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac',
                            400: '#4ade80', 500: '#22c55e', 600: '#16a34a', 700: '#15803d',
                            800: '#166534', 900: '#14532d'
                        },
                    },
                    keyframes: {
                        fadeUp: { '0%': { opacity: '0', transform: 'translateY(10px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.35s ease both',
                        'fade-up-1': 'fadeUp 0.35s 0.05s ease both',
                        'fade-up-2': 'fadeUp 0.35s 0.10s ease both',
                        'fade-up-3': 'fadeUp 0.35s 0.15s ease both',
                        'fade-up-4': 'fadeUp 0.35s 0.20s ease both',
                        'fade-up-5': 'fadeUp 0.35s 0.25s ease both',
                        'fade-up-6': 'fadeUp 0.35s 0.30s ease both',
                        'fade-up-7': 'fadeUp 0.35s 0.35s ease both',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'DM Sans', sans-serif;
        }

        .sidebar-item {
            transition: all .15s;
        }

        .sidebar-item:hover {
            background: rgba(255, 255, 255, .07);
            color: rgba(255, 255, 255, .95);
        }

        .sidebar-item.active {
            background: rgba(22, 163, 74, .20);
            border-left-color: #C49A2A;
            color: #fff;
        }

        .field {
            display: block;
            width: 100%;
            border-radius: 0.75rem;
            border: 1px solid #94a3b8;
            background: #F8FAFC;
            padding: 10px 14px;
            font-size: 13px;
            color: #1e293b;
            outline: none;
            transition: all .2s ease;
        }

        .field:focus {
            border: 1px solid black;
            background: white;
            box-shadow: none;
        }

        .field::placeholder {
            color: #94A3B8;
        }

        select.field {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236B7280' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
            appearance: none;
        }

        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748B;
            margin-bottom: 6px;
        }

        .req::after {
            content: '*';
            color: #EF4444;
            margin-left: 2px;
        }

        textarea.field {
            resize: vertical;
            min-height: 80px;
        }

        .section-card {
            background: #fff;
            border-radius: 1rem;
            border: 1px solid #E2E8F0;
            overflow: hidden;
            margin-bottom: 1.25rem;
        }

        .section-head {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .875rem 1.5rem;
            border-bottom: 1px solid #F1F5F9;
            background: #F8FAFC;
        }

        .section-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #15803d;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .section-body {
            padding: 1.5rem;
        }

        .indig-opt {
            transition: all .18s;
            cursor: pointer;
        }

        .indig-opt:hover {
            border-color: #94A3B8;
        }

        .indig-opt.sel-indigent {
            border-color: #C0392B;
            background: #FEF2F2;
        }

        .indig-opt.sel-indigent p {
            color: #991B1B;
        }

        .indig-opt.sel-nearpoor {
            border-color: #F59E0B;
            background: #FFFBEB;
        }

        .indig-opt.sel-nearpoor p {
            color: #92400E;
        }

        .indig-opt.sel-notindigent {
            border-color: #10B981;
            background: #ECFDF5;
        }

        .indig-opt.sel-notindigent p {
            color: #065F46;
        }

        .indig-opt.sel-notassessed {
            border-color: #94A3B8;
            background: #F8FAFC;
        }

        .indig-opt.sel-notassessed p {
            color: #475569;
        }

        .fam-input {
            width: 100%;
            border: none;
            background: transparent;
            font-size: 12px;
            font-family: 'DM Sans', sans-serif;
            color: #1e293b;
            outline: none;
            padding: 2px 4px;
        }

        .fam-input:focus {
            border-bottom: 1.5px solid #16A34A;
        }

        .fam-row:hover {
            background: #F8FAFC;
        }

        .fam-select {
            width: 100%;
            border: none;
            background: transparent;
            font-size: 12px;
            font-family: 'DM Sans', sans-serif;
            color: #1e293b;
            outline: none;
        }

        .upload-zone {
            border: 2px dashed #CBD5E1;
            border-radius: .875rem;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            background: #F8FAFC;
        }

        .upload-zone:hover {
            border-color: #16A34A;
            background: #ECFDF5;
        }

        .upload-zone.has-file {
            border-color: #10B981;
            background: #F0FDF4;
            border-style: solid;
        }

        .upload-zone input[type=file] {
            display: none;
        }

        .char-counter {
            font-size: 10px;
            color: #94A3B8;
            text-align: right;
            margin-top: 4px;
        }

        .char-counter.warn {
            color: #F59E0B;
        }

        .char-counter.limit {
            color: #EF4444;
        }

        .calc-row {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .5rem 0;
            border-bottom: 1px solid #F1F5F9;
        }

        .calc-row:last-child {
            border-bottom: none;
        }

        .calc-label {
            flex: 1;
            font-size: 12px;
            color: #475569;
        }

        .calc-input {
            width: 120px;
        }

        @media print {

            aside,
            header,
            #pdfToggleBtn,
            .no-print,
            footer {
                display: none !important;
            }

            .ml-64 {
                margin-left: 0 !important;
            }

            body {
                background: #fff;
            }
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, .15);
            border-radius: 2px;
        }

        #caseSummaryView { display: none; }
        #caseSummaryView .readonly-field {
            display: block;
            width: 100%;
            border-radius: .75rem;
            border: 1px solid #D7E4DA;
            background: #F8FCF9;
            padding: 10px 14px;
            font-size: 13px;
            color: #1f2937;
            min-height: 42px;
            line-height: 1.5;
        }
        #caseSummaryView .readonly-field.empty {
            color: #94a3b8;
            font-style: italic;
        }
        #caseSummaryView .readonly-textarea {
            min-height: 92px;
            white-space: pre-wrap;
        }
        #caseSummaryView .summary-section-card {
            background: #fff;
            border-radius: 1rem;
            border: 1px solid #E2E8F0;
            overflow: hidden;
            margin-bottom: 1.25rem;
            box-shadow: 0 2px 10px rgba(20, 83, 45, .035);
        }
        #caseSummaryView .summary-section-head {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .875rem 1.5rem;
            border-bottom: 1px solid #E5EFE7;
            background: linear-gradient(90deg, #F3FBF5, #FAFDFC);
        }
        #caseSummaryView .summary-section-num {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #15803d;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }
        #caseSummaryView .summary-section-body { padding: 1.5rem; }
        #caseSummaryView .summary-family-table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #E2E8F0;
            border-radius: .875rem;
        }
        #caseSummaryView .summary-family-table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
            font-size: 11px;
        }
        #caseSummaryView .summary-family-table th {
            text-align: left;
            padding: 10px 12px;
            background: #F3F8F4;
            color: #64748B;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 700;
            border-bottom: 1px solid #E2E8F0;
        }
        #caseSummaryView .summary-family-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: top;
        }
        #caseSummaryView .summary-family-table tbody tr:last-child td { border-bottom: none; }
        #caseSummaryView .summary-family-table tbody tr:hover { background: #FAFDFC; }
        #caseSummaryView .summary-family-table .client-row { background: #F0FDF4; }
        #caseSummaryView .summary-money-card {
            border-radius: .875rem;
            padding: 14px;
            text-align: center;
        }
        #caseSummaryView .summary-money-card.income { background: #F0FDF4; border: 1px solid #BBF7D0; }
        #caseSummaryView .summary-money-card.expense { background: #FFF7F7; border: 1px solid #FECACA; }
        #caseSummaryView .summary-money-card.net { background: #F4F7FC; border: 1px solid #DDE5E1; }
        #caseSummaryView .summary-assessment-card {
            border: 2px solid #BBF7D0;
            background: #F0FDF4;
            border-radius: 1rem;
            padding: 16px;
        }
        #caseSummaryView .summary-recommendation-box {
            border-left: 4px solid #16a34a;
            background: #F6FCF7;
            border-radius: 0 .75rem .75rem 0;
            padding: 16px 18px;
            white-space: pre-wrap;
            line-height: 1.7;
            font-size: 13px;
        }
        #caseSummaryView .summary-ledger-row {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .5rem 0;
            border-bottom: 1px solid #F1F5F9;
            font-size: 12px;
        }
        #caseSummaryView .summary-ledger-row:last-child { border-bottom: none; }
        #caseSummaryView .summary-ledger-label { flex: 1; color: #475569; }
        #caseSummaryView .summary-ledger-value { font-weight: 600; color: #166534; }
        #caseSummaryView .summary-expense-value { color: #dc2626; }
        #caseSummaryView .summary-action-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: .75rem;
            margin: 0 0 1rem;
        }
        #caseSummaryView .summary-action-bar button {
            border-radius: .75rem;
            padding: .625rem 1.25rem;
            font-size: 13px;
            font-weight: 600;
            transition: all .15s ease;
        }
        #caseSummaryView .summary-status {
            margin-right: auto;
            font-size: 11px;
            color: #64748B;
        }
        @media (max-width: 640px) {
            #caseSummaryView .summary-section-body { padding: 1rem; }
            #caseSummaryView .summary-section-head { padding: .8rem 1rem; }
            #caseSummaryView .summary-action-bar { justify-content: stretch; }
            #caseSummaryView .summary-action-bar button { width: 100%; }
            #caseSummaryView .summary-status { width: 100%; margin-right: 0; }
        }

        #caseSummaryView {
            max-width: 900px;
            margin: 0 auto;
        }
        #caseSummaryView .summary-document-header {
            background: #fff;
            border: 1px solid #d8e1da;
            border-radius: 10px;
            padding: 22px 28px 18px;
            margin-bottom: 18px;
            text-align: center;
        }
        #caseSummaryView .summary-document-header .office-name {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #14532d;
        }
        #caseSummaryView .summary-document-header .document-title {
            margin-top: 4px;
            font-family: 'DM Serif Display', serif;
            font-size: 22px;
            color: #166534;
            text-transform: uppercase;
        }
        #caseSummaryView .summary-document-header .document-subtitle {
            margin-top: 3px;
            font-size: 10px;
            color: #64748b;
        }
        #caseSummaryView .readonly-field {
            border: 0 !important;
            background: transparent !important;
            border-radius: 0 !important;
            padding: 2px 0 !important;
            min-height: 0 !important;
            font-size: 12px !important;
            line-height: 1.55 !important;
            color: #1f2937 !important;
            box-shadow: none !important;
        }
        #caseSummaryView .readonly-textarea {
            min-height: 0 !important;
            white-space: pre-wrap;
        }
        #caseSummaryView .summary-section-card {
            border-radius: 8px;
            border-color: #d8e1da;
            box-shadow: none;
            margin-bottom: 14px;
        }
        #caseSummaryView .summary-section-head {
            padding: 11px 16px;
            background: #f1f6f2;
        }
        #caseSummaryView .summary-section-body {
            padding: 16px 18px;
        }
        #caseSummaryView .field-label {
            font-size: 9px !important;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #64748b !important;
            margin-bottom: 2px;
        }
        #caseSummaryView .summary-signatories {
            background: #fff;
            border: 1px solid #d8e1da;
            border-radius: 8px;
            padding: 24px 22px 20px;
            margin-top: 14px;
            margin-bottom: 16px;
        }
        #caseSummaryView .summary-signatory-label {
            font-size: 10px;
            color: #475569;
            margin-bottom: 24px;
        }
        #caseSummaryView .summary-signatory-name {
            font-size: 12px;
            font-weight: 700;
            color: #111827;
            text-transform: uppercase;
        }
        #caseSummaryView .summary-signatory-role {
            font-size: 10px;
            color: #475569;
            margin-top: 2px;
        }
        #caseSummaryView .summary-signatory-line {
            width: 250px;
            border-top: 1px solid #64748b;
            margin-top: 22px;
            margin-bottom: 6px;
        }
        #caseSummaryView .summary-client-formal {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            align-items: center;
        }
        @media (max-width: 640px) {
            #caseSummaryView .summary-client-formal { grid-template-columns: 1fr; }
            #caseSummaryView .summary-signatory-line { width: 100%; max-width: 250px; }
        }

        @media print {
            #caseStudyFormView,
            #caseStudyForm,
            #caseSummaryActions,
            #caseSummaryBottomActions,
            aside,
            header,
            footer,
            #toast {
                display: none !important;
            }
            #caseSummaryView {
                display: block !important;
            }
            #caseSummaryView .summary-section-card {
                break-inside: avoid;
                box-shadow: none;
            }
            #caseSummaryView .summary-section-head {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body {
                background: #fff !important;
            }
            .ml-64 {
                margin-left: 0 !important;
            }
            main {
                padding: 0 !important;
            }
            #caseSummaryView .max-w-4xl {
                max-width: none !important;
            }
        }


        /* ============================================================
           FORMAL CASE SUMMARY PREVIEW
        ============================================================ */
        #caseSummaryView {
            display: none;
            width: 100%;
            background: #f4f7f5;
            min-height: calc(100vh - 56px);
        }
        #caseSummaryView .formal-summary-actions {
            max-width: 794px;
            padding-top: 4px;
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }
        #caseSummaryView .formal-summary-actions .summary-status {
            margin-right: auto;
            font-size: 11px;
            color: #64748b;
        }
        #caseSummaryView .formal-paper {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 24px;
            padding: 12mm 12mm 11mm;
            background: #fff;
            color: #111827;
            box-shadow: 0 4px 24px rgba(15, 23, 42, .10);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5px;
            line-height: 1.35;
        }
        #caseSummaryView .formal-letterhead {
            position: relative;
            min-height: 24mm;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            text-align: center;
        }
        #caseSummaryView .formal-logo {
            position: absolute;
            top: 1mm;
            width: 20mm;
            height: 20mm;
            object-fit: contain;
        }
        #caseSummaryView .formal-logo.left { left: 0; top:}
        #caseSummaryView .formal-logo.right { right: 0; }
        #caseSummaryView .formal-heading {
            padding-top: 0;
            font-weight: 700;
            color: #111827;
            line-height: 1.25;
        }
        #caseSummaryView .formal-heading .line {
            font-size: 10.5px;
        }
        #caseSummaryView .formal-heading .office {
            margin-top: 2px;
            font-size: 11.5px;
        }
        #caseSummaryView .formal-title {
            margin-top: 1mm;
            text-align: center;
            font-weight: 700;
            font-size: 11.5px;
            letter-spacing: .01em;
        }
        #caseSummaryView .formal-date {
            margin-top: 1mm;
            text-align: center;
            font-size: 9.5px;
        }
        #caseSummaryView .formal-section {
            margin-top: 5mm;
        }
        #caseSummaryView .formal-section-title {
            margin: 0 0 2.5mm;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
        }
        #caseSummaryView .formal-identifying {
            display: grid;
            grid-template-columns: 48mm 1fr;
            column-gap: 4mm;
            row-gap: 1.2mm;
        }
        #caseSummaryView .formal-identifying .label {
            font-weight: 700;
        }
        #caseSummaryView .formal-identifying .label::after {
            content: ':';
            margin-left: 2mm;
        }
        #caseSummaryView .formal-identifying .value {
            min-width: 0;
        }
        #caseSummaryView .formal-family-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8.1px;
        }
        #caseSummaryView .formal-family-table th,
        #caseSummaryView .formal-family-table td {
            border: .25px solid #8b9690;
            padding: 2.2px 3px;
            vertical-align: middle;
        }
        #caseSummaryView .formal-family-table th {
            background: #eef1ee;
            font-size: 7.3px;
            text-align: center;
            font-weight: 700;
        }
        #caseSummaryView .formal-family-table td:first-child {
            text-align: center;
        }
        #caseSummaryView .formal-family-table th:nth-child(1) { width: 5%; }
        #caseSummaryView .formal-family-table th:nth-child(2) { width: 28%; }
        #caseSummaryView .formal-family-table th:nth-child(3) { width: 10%; }
        #caseSummaryView .formal-family-table th:nth-child(4) { width: 23%; }
        #caseSummaryView .formal-family-table th:nth-child(5) { width: 22%; }
        #caseSummaryView .formal-family-table th:nth-child(6) { width: 12%; }
        #caseSummaryView .formal-paragraph {
            margin: 0;
            white-space: pre-wrap;
            line-height: 1.45;
        }
        #caseSummaryView .formal-signatory {
            margin-top: 8mm;
            page-break-inside: avoid;
        }
        #caseSummaryView .formal-signatory .prepared {
            margin-bottom: 14mm;
        }
        #caseSummaryView .formal-signatory .name {
            font-weight: 700;
            font-size: 10.5px;
        }
        #caseSummaryView .formal-signatory .role {
            font-size: 10px;
            margin-top: 1mm;
        }
        #caseSummaryView .formal-bottom-actions {
            max-width: 794px;
            margin: 0 auto 24px;
            display: flex;
            justify-content: flex-end;
        }
        #caseSummaryView .case-summary-generating {
            min-height: 260mm;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f7f5;
            border-radius: 12px;
        }
        #caseSummaryView .case-summary-generating-box {
            text-align: center;
            background: #fff;
            border: 1px solid #d7e4da;
            border-radius: 16px;
            padding: 32px 42px;
            box-shadow: 0 10px 30px rgba(20,83,45,.08);
            color: #14532d;
        }
        #caseSummaryView .case-summary-generating-box p {
            margin: 10px 0 0;
            font-size: 12px;
            color: #64748b;
        }
        #caseSummaryView .case-summary-spinner {
            width: 32px;
            height: 32px;
            border: 3px solid #dcfce7;
            border-top-color: #15803d;
            border-radius: 50%;
            margin: 0 auto 16px;
            animation: caseSummarySpin 1s linear infinite;
        }
        @keyframes caseSummarySpin {
            to { transform: rotate(360deg); }
        }
        #caseSummaryView .case-summary-pdf-host {
            display: none;
            width: min(216mm, calc(100vw - 32px));
            height: 356mm;
            max-height: calc(100vh - 140px);
            min-height: 520px;
            margin: 0 auto 24px;
            background: #fff;
            border: 1px solid #d7e4da;
            box-shadow: 0 10px 30px rgba(20,83,45,.08);
            overflow: hidden;
        }
        #caseSummaryView .case-summary-pdf-host iframe {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
        }
        @media (max-width: 900px) {
            #caseSummaryView .case-summary-pdf-host {
                width: 100%;
                height: 80vh;
                box-shadow: none;
            }
        }

        @media (max-width: 900px) {
            #caseSummaryView .formal-paper {
                width: 100%;
                min-height: auto;
                padding: 24px 20px;
                box-shadow: none;
            }
        }
        @media (max-width: 640px) {
            #caseSummaryView .formal-summary-actions {
                justify-content: stretch;
                padding: 0 12px;
            }
            #caseSummaryView .formal-summary-actions button {
                flex: 1 1 100%;
            }
            #caseSummaryView .formal-summary-actions .summary-status {
                flex: 1 1 100%;
                margin-right: 0;
            }
            #caseSummaryView .formal-identifying {
                grid-template-columns: 42mm 1fr;
            }
            #caseSummaryView .formal-paper {
                overflow-x: auto;
            }
        }
        @media print {
            #caseSummaryView .formal-summary-actions,
            #caseSummaryView .formal-bottom-actions,
            #caseSummaryView .no-print {
                display: none !important;
            }
            #caseSummaryView .formal-paper {
                width: 210mm;
                min-height: 297mm;
                margin: 0;
                padding: 12mm 12mm 11mm;
                box-shadow: none;
            }
            #caseSummaryView .formal-family-table,
            #caseSummaryView .formal-section,
            #caseSummaryView .formal-signatory {
                break-inside: avoid;
            }
            body {
                background: #fff !important;
            }
        }

    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
</head>

<body class="bg-slate2 min-h-screen flex">

    <?php require 'sidebar.php'; ?>

    <div class="ml-64 flex-1 flex flex-col min-h-screen">

            <div class="flex items-center gap-2 text-[13px]">
                <a href="clientslist.php" class="text-slate-400 hover:text-navy-600 transition-colors">Clients</a>
                <span class="text-slate-300">/</span>
                <a href="clientprofile.php?id=<?= $client_id ?>"
                    class="text-slate-400 hover:text-navy-600 transition-colors"><?= $full_name ?></a>
                <span class="text-slate-300">/</span>
                <span class="text-navy-600 font-semibold">Case Study</span>
            </div>

        <main class="flex-1 p-6">
            <div class="max-w-4xl mx-auto">

                <div id="caseStudyFormView">
                    <div class="animate-fade-up mb-6">
                        <h1 class="text-xl font-serif text-mswdo-800">Case Study / Social Case Summary</h1>
                    </div>

                    <form id="caseStudyForm" method="POST" action="casestudy.php?client_id=<?= (int)$client_id ?>">

                    <!-- Client banner -->
                    <div
                        class="animate-fade-up-1 bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-4 mb-5">
                        <div class="flex-1">
                            <p class="text-[14px] font-semibold text-mswdo-800"><?= $full_name ?></p>
                            <p class="text-[11px] text-slate-400"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>

                    <!--  Section 1: Interview Details  -->
                    <div class="section-card animate-fade-up-1">
                        <div class="section-head">
                            <div class="section-num">1</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-mswdo-600">Interview Details</h2>
                                <p class="text-[11px] text-slate-400">Basic information about this case study interview
                                </p>
                            </div>
                        </div>
                        <div class="section-body">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label req">Interview Date</label>
                                    <input type="date" class="field" name="interview_date" required>
                                </div>
                                <div>
                                    <label class="field-label req">Type of Case Study</label>
                                    <select class="field" name="type_of_case_study" required
                                        onchange="applyCaseStudyTemplate(this.value)">
                                        <option value="">Select type</option>
                                        <option value="Medical">Medical</option>
                                        <option value="Financial">Financial</option>
                                        <option value="Educational">Educational</option>
                                        <option value="Livelihood">Livelihood</option>
                                        <option value="Burial">Burial</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Patient toggle -->
                            <div
                                class="mt-4 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 flex items-center justify-between">
                                <div>
                                    <p class="text-[13px] font-semibold text-slate-700">Patient is different from client
                                        / claimant</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Enable if the person being assisted is
                                        not the registered client</p>
                                </div>
                                <label class="relative cursor-pointer flex items-center gap-3">
                                    <input type="checkbox" id="patientDiff" class="sr-only" onchange="togglePatient()">
                                    <div class="w-11 h-6 bg-slate-200 rounded-full relative transition-colors"
                                        id="ptTrack">
                                        <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                            id="ptThumb"></div>
                                    </div>
                                    <span id="ptLabel" class="text-[12px] font-medium text-slate-500">No</span>
                                </label>
                            </div>

                            <div id="patientInfoFields" class="hidden mt-4 grid grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Patient Name</label>
                                    <input type="text" class="field" name="patient_name">
                                </div>
                                <div>
                                    <label class="field-label">Relationship to Client</label>
                                    <input type="text" class="field" name="patient_relationship"
                                        placeholder="e.g. Child, Spouse, Parent">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--  Section 2: Family Composition  -->
                    <div class="section-card animate-fade-up-2">
                        <div class="section-head">
                            <div class="section-num">2</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-mswdo-600">Family Composition</h2>
                                <p class="text-[11px] text-slate-400">All household members — names, relationships,
                                    ages, education, occupation, and income</p>
                            </div>
                        </div>
                        <div class="section-body">
                            <div class="rounded-xl border border-slate-200 overflow-hidden mb-3">
                                <table class="w-full text-[11px]">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-200">
                                            <th
                                                class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-6">
                                                #</th>
                                            <th
                                                class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                                Name</th>
                                            <th
                                                class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-28">
                                                Relationship</th>
                                            <th
                                                class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-14">
                                                Age</th>
                                            <th
                                                class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-16">
                                                Sex</th>
                                            <th
                                                class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-24">
                                                Civil Status</th>
                                            <th
                                                class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-24">
                                                Education</th>
                                            <th
                                                class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                                Occupation</th>
                                            <th
                                                class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-24">
                                                Income/mo (₱)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="famBody">
                                        <!-- Family members are supplied by the existing case-study data source. -->
                                        <tr>
                                            <td colspan="9" class="px-3 py-4 text-center text-[11px] text-slate-400 italic">
                                                No family members have been entered.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Combined income display -->
                            <div class="flex items-center justify-end mt-3 gap-4 text-[12px]">
                                <span class="text-slate-400">Combined monthly income:</span>
                                <span id="totalIncome"
                                    class="font-bold text-mswdo-600 text-[14px]">₱0.00</span>
                            </div>

                            <!-- Hidden fields submitted to DB -->
                            <input type="hidden" name="combined_income" id="hiddenIncome" value="0">
                            <input type="hidden" name="monthly_expenses" id="hiddenExpenses" value="0">
                        </div>
                    </div>

                    <!--  Section 3: Income & Financial Resources  -->
                    <div class="section-card animate-fade-up-3">
                        <div class="section-head">
                            <div class="section-num">3</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-mswdo-600">Income & Financial Resources</h2>
                                <p class="text-[11px] text-slate-400">Monthly financial picture — used for indigency
                                    assessment</p>
                            </div>
                        </div>
                        <div class="section-body">
                            <div class="grid grid-cols-2 gap-6">

                                <!-- Income side -->
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 mb-3">
                                        Income Sources</p>
                                    <div class="ledger-group">
                                        <div class="calc-row">
                                            <span class="calc-label">Combined family income</span>
                                            <div class="relative calc-input">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[12px]">₱</span>
                                                <input type="number" id="sec3CombinedIncome"
                                                    class="field pl-6 text-[12px] py-2" placeholder="0" readonly
                                                    oninput="calcNet()">
                                            </div>
                                        </div>
                                        <div class="calc-row">
                                            <span class="calc-label">Remittance / OFW support</span>
                                            <div class="relative calc-input">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[12px]">₱</span>
                                                <input type="number" class="field pl-6 text-[12px] py-2" placeholder="0"
                                                    oninput="calcNet()">
                                            </div>
                                        </div>
                                        <div class="calc-row">
                                            <span class="calc-label">Pension / government benefit</span>
                                            <div class="relative calc-input">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[12px]">₱</span>
                                                <input type="number" class="field pl-6 text-[12px] py-2" placeholder="0"
                                                    oninput="calcNet()">
                                            </div>
                                        </div>
                                        <div class="calc-row">
                                            <span class="calc-label">Other income sources</span>
                                            <div class="relative calc-input">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[12px]">₱</span>
                                                <input type="number" class="field pl-6 text-[12px] py-2" placeholder="0"
                                                    oninput="calcNet()">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 space-y-3">
                                        <div>
                                            <label class="field-label">Insurance / PhilHealth / SSS / GSIS</label>
                                            <input type="text" class="field" name="insurance_coverage"
                                                placeholder="e.g. PhilHealth only, None">
                                        </div>
                                        <div>
                                            <label class="field-label">Savings</label>
                                            <div class="relative">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[12px]">₱</span>
                                                <input type="number" class="field pl-6" name="savings" placeholder="0">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="field-label">Emergency Fund Available</label>
                                            <div class="flex gap-2 mt-0.5">
                                                <label
                                                    class="flex-1 flex items-center justify-center gap-2 border-2 border-slate-200 rounded-xl py-2 cursor-pointer hover:border-navy-400 transition-all text-[12px] font-medium text-slate-600 has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50 has-[:checked]:text-navy-700">
                                                    <input type="radio" name="emergency_fund_available" value="1"
                                                        class="hidden"> Yes
                                                </label>
                                                <label
                                                    class="flex-1 flex items-center justify-center gap-2 border-2 border-red-100 rounded-xl py-2 cursor-pointer hover:border-red-300 transition-all text-[12px] font-medium text-slate-600 has-[:checked]:border-red-500 has-[:checked]:bg-red-50 has-[:checked]:text-red-700">
                                                    <input type="radio" name="emergency_fund_available" value="0"
                                                        class="hidden" checked> None
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Expenses side -->
                                <div class="expense-group">
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-red-500 mb-3">Monthly
                                        Expenses</p>
                                    <div>
                                        <div class="calc-row">
                                            <span class="calc-label">Utilities (electric, water)</span>
                                            <div class="relative calc-input">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[12px]">₱</span>
                                                <input type="number" class="field pl-6 text-[12px] py-2" placeholder="0"
                                                    oninput="calcNet()">
                                            </div>
                                        </div>
                                        <div class="calc-row">
                                            <span class="calc-label">Food & daily needs</span>
                                            <div class="relative calc-input">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[12px]">₱</span>
                                                <input type="number" class="field pl-6 text-[12px] py-2" placeholder="0"
                                                    oninput="calcNet()">
                                            </div>
                                        </div>
                                        <div class="calc-row">
                                            <span class="calc-label">Rent / housing</span>
                                            <div class="relative calc-input">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[12px]">₱</span>
                                                <input type="number" class="field pl-6 text-[12px] py-2" placeholder="0"
                                                    oninput="calcNet()">
                                            </div>
                                        </div>
                                        <div class="calc-row">
                                            <span class="calc-label">Medication / medical</span>
                                            <div class="relative calc-input">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[12px]">₱</span>
                                                <input type="number" class="field pl-6 text-[12px] py-2" placeholder="0"
                                                    oninput="calcNet()">
                                            </div>
                                        </div>
                                        <div class="calc-row">
                                            <span class="calc-label">Debt / loan payments</span>
                                            <div class="relative calc-input">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[12px]">₱</span>
                                                <input type="number" class="field pl-6 text-[12px] py-2" placeholder="0"
                                                    oninput="calcNet()">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Net summary -->
                            <div class="mt-5 grid grid-cols-3 gap-3">
                                <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-emerald-600 font-semibold mb-1">
                                        Total Income</p>
                                    <p id="totalIncomeSum" class="text-[18px] font-bold text-emerald-700">₱0</p>
                                </div>
                                <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-red-500 font-semibold mb-1">Total
                                        Expenses</p>
                                    <p id="totalExpenses" class="text-[18px] font-bold text-red-600">₱0</p>
                                </div>
                                <div class="bg-navy-50 border border-navy-100 rounded-xl px-4 py-3 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-navy-500 font-semibold mb-1">Net
                                        Monthly</p>
                                    <p id="netMonthly" class="text-[18px] font-bold text-navy-600">₱0</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--  Section 4: Problem Presented & Home Condition  -->
                    <div class="section-card animate-fade-up-4">
                        <div class="section-head">
                            <div class="section-num">4</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-mswdo-600">Problem Presented & Home Condition
                                </h2>
                                <p class="text-[11px] text-slate-400">Narrative details as stated by the client and
                                    observed by the social worker</p>
                            </div>
                        </div>
                        <div class="section-body space-y-4">
                            <div>
                                <label class="field-label req">Problem Presented</label>
                                <textarea class="field" rows="4" name="problem_presented" id="problemText"
                                    maxlength="1000" oninput="countChars('problemText','problemCount',1000)"
                                    required></textarea>
                                <div class="char-counter" id="problemCount">0 / 1000 characters</div>
                            </div>
                            <div>
                                <label class="field-label">Home & Economic Condition</label>
                                <textarea class="field" rows="3" name="home_condition" id="homeText" maxlength="800"
                                    oninput="countChars('homeText','homeCount',800)"></textarea>
                                <div class="char-counter" id="homeCount">0 / 800 characters</div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Crisis Severity</label>
                                    <select class="field" name="crisis_severity">
                                        <option value="">Select severity</option>
                                        <option value="Recently diagnosed (≤3 months)">Recently diagnosed (≤3 months)</option>
                                        <option value="3 months to 1 year">3 months to 1 year</option>
                                        <option value="Chronic/lifelong">Chronic/lifelong</option>
                                        <option value="Not applicable">Not applicable</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="field-label">Crisis Experienced</label>
                                    <textarea class="field" rows="3" name="crisis_experienced" maxlength="1000" placeholder="Describe the crisis experienced, if applicable."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--  Section 5: Indigency Assessment  -->
                    <div class="section-card animate-fade-up-5">
                        <div class="section-head">
                            <div class="section-num">5</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-mswdo-600">Indigency Assessment</h2>
                                <p class="text-[11px] text-slate-400">Based on DOH / DSWD Assessment Tool</p>
                            </div>
                        </div>
                        <div class="section-body">
                            <input type="hidden" name="indigency_assessment" id="indigValue" value="">
                            <div class="grid grid-cols-4 gap-3" id="indigSelector">
                                <div onclick="setIndig(this,'Indigent','sel-indigent')"
                                    class="indig-opt border-2 border-slate-200 rounded-2xl p-4 text-center">
                                    <p class="text-[13px] font-semibold text-slate-600">Indigent</p>
                                    <p class="text-[10px] text-slate-500 mt-1">Below poverty threshold</p>
                                </div>
                                <div onclick="setIndig(this,'Near Poor','sel-nearpoor')"
                                    class="indig-opt border-2 border-slate-200 rounded-2xl p-4 text-center">
                                    <p class="text-[13px] font-semibold text-slate-600">Near Poor</p>
                                    <p class="text-[10px] text-slate-500 mt-1">Slightly above threshold</p>
                                </div>
                                <div onclick="setIndig(this,'Not Indigent','sel-notindigent')"
                                    class="indig-opt border-2 border-slate-200 rounded-2xl p-4 text-center">
                                    <p class="text-[13px] font-semibold text-slate-600">Not Indigent</p>
                                    <p class="text-[10px] text-slate-500 mt-1">Above threshold</p>
                                </div>
                                <div onclick="setIndig(this,'Not Assessed','sel-notassessed')"
                                    class="indig-opt border-2 border-slate-200 rounded-2xl p-4 text-center">
                                    <p class="text-[13px] font-semibold text-slate-600">Not Assessed</p>
                                    <p class="text-[10px] text-slate-500 mt-1">Unable to assess</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--  Section 6: Previous DSWD Assistance  -->
                    <div class="section-card animate-fade-up-6">
                        <div class="section-head">
                            <div class="section-num">6</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-navy-600">Previous DSWD Assistance</h2>
                                <p class="text-[11px] text-slate-400">Auto-detected from this client's availment
                                    records</p>
                            </div>
                        </div>
                        <div class="section-body space-y-4">
                            <input type="hidden" name="previous_dswd_assistance"
                                value="<?= $has_prev_dswd_assistance ? 1 : 0 ?>">
                            <?php if ($has_prev_dswd_assistance): ?>
                                <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl p-3">
                                    <i class="fas fa-circle-info text-amber-500 mt-0.5"></i>
                                    <p class="text-[12px] text-amber-800">
                                        <span class="font-semibold">Auto-detected:</span> this client has
                                        <?= count($prevAvailments) ?> prior approved/released availment(s) on record.
                                        Details are pre-filled below — review and adjust before saving.
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="flex items-start gap-3 bg-slate-50 border border-slate-200 rounded-xl p-3">
                                    <i class="fas fa-circle-info text-slate-400 mt-0.5"></i>
                                    <p class="text-[12px] text-slate-500">
                                        <span class="font-semibold">Auto-detected:</span> no prior approved/released
                                        availments found for this client.
                                    </p>
                                </div>
                            <?php endif; ?>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label class="field-label">Details of Previous Assistance</label>
                                    <textarea class="field" rows="2" name="previous_assistance_details"
                                        placeholder="Type of assistance, amount, program..."><?= htmlspecialchars($prev_assistance_details_auto) ?></textarea>
                                </div>
                                <div>
                                    <label class="field-label">Date of Previous Assistance</label>
                                    <input type="date" class="field" name="previous_assistance_date"
                                        value="<?= htmlspecialchars($prev_assistance_date_auto) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--  Section 7: Evaluation & Recommendation  -->
                    <div class="section-card animate-fade-up-7">
                        <div class="section-head">
                            <div class="section-num">7</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-navy-600">Evaluation & Recommendation</h2>
                                <p class="text-[11px] text-slate-400">Social worker's professional assessment and formal
                                    recommendation</p>
                            </div>
                        </div>
                        <div class="section-body space-y-4">
                            <div>
                                <label class="field-label req">Recommendation</label>
                                <textarea class="field" rows="5" name="recommendation" id="recoText" maxlength="1200"
                                    oninput="countChars('recoText','recoCount',1200)"
                                    placeholder="Based on the conducted case study and assessment, the client is classified as..."></textarea>
                                <div class="char-counter" id="recoCount">0 / 1200 characters</div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center justify-end gap-3 mt-2 no-print">
                        <a href="#" onclick="return cancelForm(event)"
                            class="text-[13px] font-medium text-slate-600 border border-slate-200 rounded-xl px-5 py-2.5 hover:border-mswdo-400 hover:text-mswdo-600 transition-all">
                            Cancel
                        </a>
                        <button type="button" onclick="showCaseSummary()"
                            class="text-[13px] font-semibold text-white bg-green-700 rounded-xl px-6 py-2.5 hover:bg-green-800 transition-all">
                            View Case Summary Preview
                        </button>
                    </div>


                </form>
                </div>

                <!-- =====================================================
                     IN-PAGE FORMAL CASE SUMMARY VIEW
                     ===================================================== -->
                <div id="caseSummaryView" aria-hidden="true">
                    <div class="formal-summary-actions no-print" id="caseSummaryActions">
                        <span id="caseSummaryStatus" class="summary-status">Reviewing current form values</span>
                        <button type="button" onclick="saveCaseStudyFromSummary()"
                            class="text-[12px] font-semibold text-white bg-green-700 rounded-lg px-4 py-2.5 hover:bg-mswdo-800 transition-all">
                            <i class="fas fa-save mr-2"></i>Save Case Study
                        </button>
                        <button type="button" onclick="previewCaseSummaryPDF()"
                            class="text-[12px] font-semibold text-white bg-green-700 rounded-lg px-4 py-2.5 hover:bg-mswdo-800 transition-all">
                            <i class="fas fa-file-pdf mr-2"></i>View Case Summary
                        </button>
                    </div>

                    <div id="caseSummaryGenerating" class="case-summary-generating" aria-live="polite">
                        <div class="case-summary-generating-box">
                            <div class="case-summary-spinner"></div>
                            <strong>Generating Case Study Summary...</strong>
                            <p>Your official Case Summary is being prepared.</p>
                        </div>
                    </div>
                    <div id="caseSummaryPdfHost" class="case-summary-pdf-host" aria-hidden="true">
                        <iframe id="caseSummaryPdfFrame" title="Case Summary Preview"></iframe>
                    </div>

                    <article class="formal-paper" id="formalCaseSummary">
                        <div class="formal-letterhead">
                            <img class="formal-logo left" src="data:image/jpeg;base64,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" alt="Municipal seal">
                            <img class="formal-logo right" src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTr0BysTwczGeP214v9nOSrBcKalK7hRBFzi_5NZkbpVw&s=10" alt="DSWD logo">
                            <div class="formal-heading">
                                <div class="line">Republic of the Philippines</div>
                                <div class="line">Province of Negros Occidental</div>
                                <div class="line">Municipality of San Enrique</div>
                                <div class="office">MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICE</div>
                            </div>
                        </div>

                        <div class="formal-title">SOCIAL CASE SUMMARY</div>
                        <div id="formalInterviewDate" class="formal-date">—</div>

                        <section class="formal-section">
                            <h2 class="formal-section-title">I. IDENTIFYING DATA</h2>
                            <div class="formal-identifying">
                                <div class="label">Name</div><div id="formalName" class="value">—</div>
                                <div class="label">Age</div><div id="formalAge" class="value">—</div>
                                <div class="label">Sex</div><div id="formalSex" class="value">—</div>
                                <div class="label">Civil Status</div><div id="formalCivilStatus" class="value">—</div>
                                <div class="label">Address</div><div id="formalAddress" class="value">—</div>
                                <div class="label">Type of Case Study</div><div id="formalCaseType" class="value">—</div>
                            </div>
                        </section>

                        <section class="formal-section">
                            <h2 class="formal-section-title">II. FAMILY COMPOSITION</h2>
                            <table class="formal-family-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>NAME</th>
                                        <th>AGE</th>
                                        <th>RELATION TO CLIENT</th>
                                        <th>EDUCATIONAL ATTAINMENT</th>
                                        <th>OCCUPATION</th>
                                    </tr>
                                </thead>
                                <tbody id="formalFamilyBody"></tbody>
                            </table>
                        </section>

                        <section class="formal-section">
                            <h2 class="formal-section-title">III. PROBLEM PRESENTED</h2>
                            <p id="formalProblem" class="formal-paragraph">—</p>
                        </section>

                        <section class="formal-section">
                            <h2 class="formal-section-title">IV. HOME AND ECONOMIC CONDITION</h2>
                            <p id="formalHomeCondition" class="formal-paragraph">—</p>
                        </section>

                        <section class="formal-section">
                            <h2 class="formal-section-title">V. EVALUATION / RECOMMENDATION</h2>
                            <p id="formalRecommendation" class="formal-paragraph">—</p>
                        </section>

                        <div class="formal-signatory">
                            <div class="prepared">Prepared by:</div>
                            <div id="formalPreparedBy" class="name">MA. TERESA C. PONCLARA, RSW</div>
                            <div id="formalDesignation" class="role">MSWDO</div>
                            <div id="formalPRC" class="role">PRC License # 0011198</div>
                            <div id="formalLicenseValidity" class="role">Valid until August 2025</div>
                        </div>
                    </article>

                    <div id="caseSummaryBottomActions" class="formal-bottom-actions no-print">
                        <button type="button" onclick="editCaseStudy()"
                            class="text-[12px] font-semibold text-mswdo-800 bg-white border border-mswdo-200 rounded-lg px-5 py-2.5 hover:border-green-400 hover:text-green-800 transition-all">
                            <i class="fas fa-pen mr-2"></i>Edit Case Study
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <footer
            class="border-t border-slate-200 bg-white px-6 py-3 flex items-center text-[11px] text-slate-400 no-print">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <!-- Toast -->
    <div id="toast"
        class="fixed bottom-6 right-6 bg-mswdo-600 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <span class="text-emerald-400 text-base">✓</span>
        <span id="toastMsg">Saved!</span>
    </div>

    <script>
        //  Patient toggle 
        let patientOn = false;
        function togglePatient() {
            patientOn = !patientOn;
            document.getElementById('ptTrack').classList.toggle('bg-mswdo-600', patientOn);
            document.getElementById('ptTrack').classList.toggle('bg-slate-200', !patientOn);
            document.getElementById('ptThumb').style.transform = patientOn ? 'translateX(20px)' : '';
            document.getElementById('ptLabel').textContent = patientOn ? 'Yes' : 'No';
            document.getElementById('patientInfoFields').classList.toggle('hidden', !patientOn);
        }

        //  Indigency selector 
        function setIndig(el, enumValue, cssClass) {
            document.querySelectorAll('#indigSelector .indig-opt').forEach(e => {
                e.className = 'indig-opt border-2 border-slate-200 rounded-2xl p-4 text-center';
                e.querySelector('p').className = 'text-[13px] font-semibold text-slate-600';
            });
            el.classList.add(cssClass);
            document.getElementById('indigValue').value = enumValue;
        }

        // Recalculate the existing financial fields without seeding demo values.
        function income() {
            const sec3Input = document.getElementById('sec3CombinedIncome');
            if (sec3Input && !sec3Input.value) sec3Input.value = '';
            calcNet();
        }

        // totals income & expense ledger, updates summary cards 
        function calcNet() {
            const incomeInputs = document.querySelectorAll('.ledger-group .calc-row input[type=number]');
            const expenseInputs = document.querySelectorAll('.expense-group .calc-row input[type=number]');

            let inc = 0, exp = 0;
            incomeInputs.forEach(inp => inc += parseFloat(inp.value) || 0);
            expenseInputs.forEach(inp => exp += parseFloat(inp.value) || 0);

            document.getElementById('totalIncomeSum').textContent = '₱' + inc.toLocaleString();
            document.getElementById('totalExpenses').textContent = '₱' + exp.toLocaleString();

            const net = inc - exp;
            const netEl = document.getElementById('netMonthly');
            netEl.textContent = (net < 0 ? '-₱' : '₱') + Math.abs(net).toLocaleString();
            netEl.className = `text-[18px] font-bold ${net < 0 ? 'text-red-600' : net < 500 ? 'text-amber-600' : 'text-mswdo-600'}`;

            document.getElementById('hiddenIncome').value = inc;
            document.getElementById('hiddenExpenses').value = exp;
        }
        //  Character counters 
        function countChars(id, countId, max) {
            const len = document.getElementById(id).value.length;
            const el = document.getElementById(countId);
            el.textContent = `${len} / ${max} characters`;
            el.className = `char-counter ${len > max * .9 ? 'limit' : len > max * .75 ? 'warn' : ''}`;
        }

        //  Recommendation templates 
        const templates = {
            Medical: 'Based on the conducted case study and assessment, the client is classified as INDIGENT under the DOH/DSWD Assessment Tool. It is therefore respectfully recommended that the client be granted AICS Medical Assistance in the amount of [AMOUNT IN WORDS] (₱____.__) to help defray the cost of ongoing medical treatment.',
            Financial: 'Based on the conducted case study, the client is classified as INDIGENT. It is respectfully recommended that the client be provided Financial Assistance amounting to [AMOUNT IN WORDS] (₱____.__) to address the immediate financial crisis of the household.',
            Educational: "Based on the case study conducted, the client is classified as INDIGENT. It is recommended that the client's dependent be granted AICS Educational Assistance in the amount of [AMOUNT IN WORDS] (₱____.__) to cover school fees for the school year.",
            Burial: 'Based on the conducted case study, the bereaved family is classified as INDIGENT. It is respectfully recommended that the client be granted AICS Burial Assistance in the amount of [AMOUNT IN WORDS] (₱____.__) to help defray funeral and burial expenses.',
            Livelihood: 'Based on the case study, the client is classified as INDIGENT. It is recommended that the client be granted AICS Livelihood Assistance in the amount of [AMOUNT IN WORDS] (₱____.__) as seed capital for the proposed [business type].',
            PWD: 'Based on the conducted case study, the client is classified as INDIGENT. It is respectfully recommended that the client, a person with disability, be granted appropriate AICS Assistance in the amount of [AMOUNT IN WORDS] (₱____.__) to address identified needs.',
            'Senior Citizen': 'Based on the conducted case study, the client is classified as INDIGENT. It is respectfully recommended that the client, a senior citizen, be granted appropriate AICS Assistance in the amount of [AMOUNT IN WORDS] (₱____.__) to address identified needs.',
            'Solo Parent': 'Based on the conducted case study, the client is classified as INDIGENT. It is respectfully recommended that the client, a solo parent, be granted appropriate AICS Assistance in the amount of [AMOUNT IN WORDS] (₱____.__) to address the needs of the household.',
            Others: 'Based on the conducted case study and assessment, the client is classified as INDIGENT. It is respectfully recommended that the client be granted appropriate assistance in the amount of [AMOUNT IN WORDS] (₱____.__) to address identified needs.',
        };
        function applyCaseStudyTemplate(type) {
            const template = templates[type];
            if (!template) return;
            const textarea = document.getElementById('recoText');
            if (textarea.value.trim() && textarea.value !== template) {
                if (!confirm('Replace the current Recommendation text with the ' + type + ' template?')) {
                    return;
                }
            }
            textarea.value = template;
            countChars('recoText', 'recoCount', 1200);
        }

        function showToast(msg) {
            document.getElementById('toastMsg').textContent = msg;
            const t = document.getElementById('toast');
            t.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
            t.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => {
                t.classList.add('opacity-0', 'translate-y-4');
                t.classList.remove('opacity-100', 'translate-y-0');
            }, 3000);
        }


        // Database-backed save: the real POST is handled by casestudy.php.
        function saveCaseStudyLocal(event) {
            if (event) event.preventDefault();
            const form = document.getElementById('caseStudyForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return false;
            }
            calcNet();
            form.submit();
            return false;
        }

        function saveCaseStudyFromSummary() {
            saveCaseStudyLocal();
        }

        function summaryText(value) {
            return value === null || value === undefined || String(value).trim() === ''
                ? '—'
                : String(value).trim();
        }

        function formatSummaryDate(value) {
            if (!value) return '—';
            const d = new Date(value + 'T00:00:00');
            if (Number.isNaN(d.getTime())) return value;
            return d.toLocaleDateString('en-PH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        function setFormalText(id, value) {
            const el = document.getElementById(id);
            if (el) el.textContent = summaryText(value);
        }

        function getFormalClientData() {
            const firstRow = document.querySelector('#famBody tr.fam-row') ||
                [...document.querySelectorAll('#famBody tr')].find(r => r.querySelectorAll('td').length >= 9);
            const cells = firstRow
                ? [...firstRow.querySelectorAll('td')].map(td => td.textContent.replace(/\s+/g, ' ').trim())
                : [];

            // The standalone form stores the core client identity in the first
            // family row. Address is part of the existing client banner.
            const banner = document.querySelector('#caseStudyFormView form > div:first-child');
            const bannerLines = banner
                ? [...banner.querySelectorAll('p')].map(p => p.textContent.replace(/\s+/g, ' ').trim())
                : [];
            const meta = bannerLines[1] || '';
            const metaParts = meta.split('·').map(v => v.trim()).filter(Boolean);

            return {
                name: cells[1] || bannerLines[0] || '—',
                age: cells[3] || '—',
                sex: cells[4] || '—',
                civilStatus: cells[5] || '—',
                address: metaParts.length >= 2 ? metaParts[1] : '—'
            };
        }

        function renderFormalFamily() {
            const body = document.getElementById('formalFamilyBody');
            if (!body) return;
            body.innerHTML = '';

            const rows = [...document.querySelectorAll('#famBody tr')]
                .filter(row => row.querySelectorAll('td').length >= 9);

            if (!rows.length) {
                const tr = document.createElement('tr');
                tr.innerHTML = '<td>1</td><td>—</td><td>—</td><td>—</td><td>—</td><td>—</td>';
                body.appendChild(tr);
                return;
            }

            rows.forEach((row, index) => {
                const cells = [...row.querySelectorAll('td')].map(td =>
                    td.textContent.replace(/\s+/g, ' ').trim()
                );
                const tr = document.createElement('tr');
                const values = [
                    String(index + 1),
                    cells[1] || '—',
                    cells[3] || '—',
                    cells[2] || '—',
                    cells[6] || '—',
                    cells[7] || '—'
                ];
                values.forEach((value, cellIndex) => {
                    const td = document.createElement('td');
                    td.textContent = summaryText(value);
                    if (cellIndex === 1 && index === 0) td.style.fontWeight = '700';
                    tr.appendChild(td);
                });
                body.appendChild(tr);
            });
        }

        function renderFormalCaseSummary() {
            calcNet();

            const client = getFormalClientData();
            setFormalText('formalName', client.name);
            setFormalText('formalAge', client.age ? client.age + ' YEARS OLD' : '—');
            setFormalText('formalSex', client.sex);
            setFormalText('formalCivilStatus', client.civilStatus);
            setFormalText('formalAddress', client.address);
            setFormalText('formalCaseType', document.querySelector('[name="type_of_case_study"]')?.value);
            setFormalText('formalInterviewDate', formatSummaryDate(document.querySelector('[name="interview_date"]')?.value));

            renderFormalFamily();

            setFormalText('formalProblem', document.querySelector('[name="problem_presented"]')?.value);
            setFormalText('formalHomeCondition', document.querySelector('[name="home_condition"]')?.value);
            setFormalText('formalRecommendation', document.querySelector('[name="recommendation"]')?.value);

            // These are the signatory details used by the formatted reference.
            setFormalText('formalPreparedBy', 'MA. TERESA C. PONCLARA, RSW');
            setFormalText('formalDesignation', 'MSWDO');
            setFormalText('formalPRC', 'PRC License # 0011198');
            setFormalText('formalLicenseValidity', 'Valid until August 2025');

            document.getElementById('caseSummaryStatus').textContent =
                'Reviewing the current case-study values. Click Save Case Study to store them in the database.';
        }

        async function showCaseSummary() {
            const form = document.getElementById('caseStudyForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            document.getElementById('caseStudyFormView').style.display = 'none';
            const summary = document.getElementById('caseSummaryView');
            summary.style.display = 'block';
            summary.setAttribute('aria-hidden', 'false');

            // Use the native formal document already present in this page.
            // This avoids embedding a generated PDF in an iframe (which can be blocked
            // by the browser) while keeping the same final-summary layout and data.
            const loader = document.getElementById('caseSummaryGenerating');
            const pdfHost = document.getElementById('caseSummaryPdfHost');
            const formal = document.getElementById('formalCaseSummary');

            loader.style.display = 'flex';
            pdfHost.style.display = 'none';
            pdfHost.setAttribute('aria-hidden', 'true');
            formal.style.display = 'none';
            window.scrollTo({ top: 0, behavior: 'smooth' });

            await new Promise(resolve => setTimeout(resolve, 350));

            try {
                renderFormalCaseSummary();
                loader.style.display = 'none';
                formal.style.display = 'block';
            } catch (error) {
                console.error('Case Summary render error:', error);
                loader.innerHTML = '<div class="case-summary-generating-box"><strong>Unable to generate Case Summary.</strong><p>Please try again.</p></div>';
            }
        }

        function editCaseStudy() {
            document.getElementById('caseSummaryView').style.display = 'none';
            document.getElementById('caseSummaryView').setAttribute('aria-hidden', 'true');
            document.getElementById('caseStudyFormView').style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        const LOGO_LEFT_PATH = 'data:image/jpeg;base64,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';
        const LOGO_RIGHT_PATH = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTr0BysTwczGeP214v9nOSrBcKalK7hRBFzi_5NZkbpVw&s=10';

        function safePdf(value) {
            if (value === null || value === undefined || String(value).trim() === '') return '';
            return String(value);
        }

        function imageToSquareDataURL(url) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = function () {
                    try {
                        const squareSize = Math.min(img.naturalWidth, img.naturalHeight);
                        const canvas = document.createElement('canvas');
                        canvas.width = squareSize; canvas.height = squareSize;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, (img.naturalWidth-squareSize)/2, (img.naturalHeight-squareSize)/2, squareSize, squareSize, 0, 0, squareSize, squareSize);
                        resolve(canvas.toDataURL('image/jpeg', 0.92));
                    } catch (e) { reject(e); }
                };
                img.onerror = reject;
                img.src = url;
            });
        }

        function getCaseStudyDataForFinalPDF() {
            calcNet();
            const rows = [...document.querySelectorAll('#famBody tr')].filter(row => row.querySelectorAll('td').length >= 9);
            const family = rows.map(row => {
                const cells = [...row.querySelectorAll('td')].map(td => td.textContent.replace(/\s+/g, ' ').trim());
                return { name: cells[1] || '', age: cells[3] || '', relationship: cells[2] || '', sex: cells[4] || '', civilStatus: cells[5] || '', education: cells[6] || '', occupation: cells[7] || '', income: cells[8] || '' };
            });
            const first = family[0] || {};
            const bannerPs = [...document.querySelectorAll('#caseStudyFormView form > div:first-child p')];
            const bannerText = bannerPs[1]?.textContent || '';
            const bannerParts = bannerText.split('·').map(v => v.trim());
            return {
                client: { fullName: first.name || bannerPs[0]?.textContent.trim() || '', age: first.age || '', sex: first.sex || '', dateOfBirth: '', address: bannerParts[1] || '', barangay: '', nearestKin: '' },
                interview: { date: document.querySelector('[name="interview_date"]')?.value ? formatSummaryDate(document.querySelector('[name="interview_date"]').value) : '', type: document.querySelector('[name="type_of_case_study"]')?.value || '' },
                patient: { fullName: document.querySelector('[name="patient_name"]')?.value || '', relation: document.querySelector('[name="patient_relationship"]')?.value || '' },
                family,
                finances: { insurance: document.querySelector('[name="insurance_coverage"]')?.value || '', savings: document.querySelector('[name="savings"]')?.value || '', emergencyFund: document.querySelector('[name="emergency_fund_available"]')?.value || '' },
                assessment: { problemPresented: document.querySelector('[name="problem_presented"]')?.value || '', homeCondition: document.querySelector('[name="home_condition"]')?.value || '', indigency: document.querySelector('[name="indigency_assessment"]')?.value || '' },
                recommendation: document.querySelector('[name="recommendation"]')?.value || '',
                submitted: { preparedBy: 'MA. TERESA C. PONCLARA, RSW', designation: 'MSWDO', prcLicense: '0011198', licenseValidity: 'August 2025' }
            };
        }

        async function previewCaseSummaryPDF(openInNewTab = true) {

            // Same PDF generation as casestudyview(2). The caller can either
            // open the finished PDF in a new tab or embed the exact same PDF
            // in the in-page Case Summary viewer.
            const caseStudyData = getCaseStudyDataForFinalPDF();

            let previewWindow = null;
            if (openInNewTab) {
                previewWindow = window.open('', '_blank');

                if (!previewWindow) {
                    alert(
                        'Please allow pop-ups for this page to view the Case Summary PDF.'
                    );
                    return;
                }
            }



            if (openInNewTab) {
                previewWindow.document.write(`

        <!DOCTYPE html>

        <html>

        <head>

            <title>
                Preparing Case Summary...
            </title>

            <style>

                body {

                    margin: 0;

                    min-height: 100vh;

                    display: flex;

                    align-items: center;

                    justify-content: center;

                    background: #f4f7f5;

                    font-family: Arial, sans-serif;

                    color: #14532d;

                }

                .box {

                    text-align: center;

                    background: white;

                    border: 1px solid #d7e4da;

                    border-radius: 16px;

                    padding: 32px 42px;

                    box-shadow:
                        0 10px 30px
                        rgba(20,83,45,.08);

                }

                .spin {

                    width: 32px;

                    height: 32px;

                    border: 3px solid #dcfce7;

                    border-top-color: #15803d;

                    border-radius: 50%;

                    margin: 0 auto 16px;

                    animation: spin 1s linear infinite;

                }

                @keyframes spin {

                    to {
                        transform: rotate(360deg);
                    }

                }

                p {

                    font-size: 12px;

                    color: #64748b;

                }

            </style>

        </head>

        <body>

            <div class="box">

                <div class="spin"></div>

                <strong>
                    Preparing Case Summary...
                </strong>

                <p>
                    Your official PDF preview is being generated.
                </p>

            </div>

        </body>

        </html>

    `);

            previewWindow.document.close();
            }


            /* BUTTON LOADING STATE */

            const buttons =
                document.querySelectorAll(
                    '[onclick^="previewCaseSummaryPDF"]'
                );


            buttons.forEach(button => {

                button.disabled = true;

                button.innerHTML =
                    '<i class="fas fa-spinner fa-spin mr-2"></i> Preparing...';

            });


            try {

                /* CHECK jsPDF */

                if (
                    !window.jspdf ||
                    !window.jspdf.jsPDF
                ) {

                    throw new Error(
                        'PDF library is unavailable. Please refresh the page.'
                    );

                }


                const { jsPDF } =
                    window.jspdf;


                /* LOAD THE REMOTE LOGOS */

                const LOGO_LEFT =
                    await imageToSquareDataURL(
                        LOGO_LEFT_PATH
                    );


                const LOGO_RIGHT =
                    await imageToSquareDataURL(
                        LOGO_RIGHT_PATH
                    );


                /* CREATE PDF */

                const doc =
                    new jsPDF({

                        orientation: 'portrait',

                        unit: 'mm',

                        format: 'legal',

                        compress: true

                    });


                const pageW =
                    doc.internal.pageSize.getWidth();


                const pageH =
                    doc.internal.pageSize.getHeight();


                const margin = 17;


                const contentW =
                    pageW - margin * 2;


                let y = 12;


                /* COLORS */

                const DARK =
                    [20, 20, 20];


                const GRAY =
                    [95, 95, 95];


                const HEADER_FILL =
                    [232, 240, 234];


                const BORDER =
                    [135, 145, 138];


                const GREEN =
                    [21, 128, 61];


                /* SAFE PAGE SPACE */

                function ensureSpace(height) {

                    if (
                        y + height >
                        pageH - 18
                    ) {

                        doc.addPage();

                        y = 16;

                    }

                }


                /* WRAPPED TEXT */

                function addWrapped(
                    text,
                    x,
                    width,
                    fontSize = 10.5,
                    lineHeight = 5.1,
                    bold = false
                ) {

                    doc.setFont(
                        'helvetica',
                        bold ? 'bold' : 'normal'
                    );


                    doc.setFontSize(
                        fontSize
                    );


                    doc.setTextColor(
                        ...DARK
                    );


                    const lines =
                        doc.splitTextToSize(
                            safePdf(text),
                            width
                        );


                    ensureSpace(
                        lines.length *
                        lineHeight +
                        2
                    );


                    doc.text(
                        lines,
                        x,
                        y,
                        {
                            baseline: 'top'
                        }
                    );


                    y +=
                        lines.length *
                        lineHeight;


                    return lines.length;

                }


                /* PDF SECTION TITLE */

                function sectionTitle(
                    number,
                    title
                ) {

                    ensureSpace(12);


                    doc.setFont(
                        'helvetica',
                        'bold'
                    );


                    doc.setFontSize(
                        10.5
                    );


                    doc.setTextColor(
                        ...DARK
                    );


                    doc.text(
                        number + '.',
                        margin,
                        y
                    );


                    doc.text(
                        title.toUpperCase(),
                        margin + 10,
                        y
                    );


                    y += 7;

                }


                /* FORMAL LETTERHEAD */

                try {


                    const logoSize = 22;


                    const headerTop = 8;

                    const headerHeight = 24;



                    const logoY =
                        headerTop +
                        (
                            (headerHeight - logoSize) /
                            2
                        );


                    /* LEFT LOGO */

                    doc.addImage(

                        LOGO_LEFT,

                        'JPEG',

                        margin + 2,

                        logoY,

                        logoSize,

                        logoSize

                    );


                    /* RIGHT LOGO */

                    doc.addImage(

                        LOGO_RIGHT,

                        'JPEG',

                        pageW -
                        margin -
                        2 -
                        logoSize,

                        logoY,

                        logoSize,

                        logoSize

                    );


                } catch (logoError) {

                    console.warn(
                        'Logo could not be added to PDF:',
                        logoError
                    );

                }


                /* LETTERHEAD TEXT */

                doc.setTextColor(
                    ...DARK
                );


                doc.setFont(
                    'helvetica',
                    'bold'
                );


                doc.setFontSize(
                    10.5
                );


                doc.text(
                    'Republic of the Philippines',
                    pageW / 2,
                    11,
                    {
                        align: 'center'
                    }
                );


                doc.text(
                    'Province of Negros Occidental',
                    pageW / 2,
                    16,
                    {
                        align: 'center'
                    }
                );


                doc.text(
                    'Municipality of San Enrique',
                    pageW / 2,
                    21,
                    {
                        align: 'center'
                    }
                );


                doc.setFontSize(
                    11.5
                );


                doc.text(
                    'MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICE',
                    pageW / 2,
                    27,
                    {
                        align: 'center'
                    }
                );


                /* DOCUMENT TITLE */

                y = 42;


                doc.setFont(
                    'helvetica',
                    'bold'
                );


                doc.setFontSize(
                    11.5
                );


                doc.text(
                    'SOCIAL CASE SUMMARY',
                    pageW / 2,
                    y,
                    {
                        align: 'center'
                    }
                );


                y += 6;


                doc.setFont(
                    'helvetica',
                    'normal'
                );


                doc.setFontSize(
                    9.5
                );


                doc.text(
                    safePdf(
                        caseStudyData.interview?.date
                    ),
                    pageW / 2,
                    y,
                    {
                        align: 'center'
                    }
                );


                y += 11;


                /*  I. IDENTIFYING DATA */

                sectionTitle(
                    'I',
                    'IDENTIFYING DATA'
                );


                const c =
                    caseStudyData.client || {};


                const identifying = [

                    [
                        'Name',
                        safePdf(c.fullName)
                    ],

                    [
                        'Age',
                        safePdf(
                            c.age
                                ? c.age + ' YEARS OLD'
                                : ''
                        )
                    ],

                    [
                        'Sex',
                        safePdf(c.sex)
                    ],

                    [
                        'Date of Birth',
                        safePdf(c.dateOfBirth)
                    ],

                    [
                        'Address',
                        safePdf(
                            c.address ||
                            c.barangay
                        )
                    ],

                    [
                        'Nearest Kin',
                        safePdf(c.nearestKin)
                    ]

                ];


                identifying.forEach(
                    ([label, value]) => {

                        ensureSpace(6);


                        doc.setFont(
                            'helvetica',
                            'bold'
                        );


                        doc.setFontSize(
                            10
                        );


                        doc.text(
                            label,
                            margin,
                            y
                        );


                        doc.text(
                            ':',
                            margin + 48,
                            y
                        );


                        doc.setFont(
                            'helvetica',
                            'normal'
                        );


                        const lines =
                            doc.splitTextToSize(
                                value,
                                contentW - 53
                            );


                        doc.text(
                            lines,
                            margin + 52,
                            y
                        );


                        y += Math.max(
                            5.1,
                            lines.length * 4.8
                        );

                    }
                );


                y += 5;


                /* II. FAMILY COMPOSITION */

                sectionTitle(
                    'II',
                    'FAMILY COMPOSITION'
                );


                const family =
                    caseStudyData.family || [];


                const rows =
                    family.map(
                        (m, i) => [

                            String(i + 1),

                            safePdf(m.name),

                            safePdf(m.age),

                            safePdf(
                                m.relationship
                            ),

                            safePdf(
                                m.education
                            ),

                            safePdf(
                                m.occupation
                            )

                        ]
                    );


                if (
                    typeof doc.autoTable !==
                    'function'
                ) {

                    throw new Error(
                        'AutoTable plugin is not loaded.'
                    );

                }


                doc.autoTable({

                    startY: y,

                    margin: {
                        left: margin,
                        right: margin
                    },

                    tableWidth: contentW,

                    head: [[

                        '',

                        'NAME',

                        'AGE',

                        'RELATION TO CLIENT',

                        'EDUCATIONAL ATTAINMENT',

                        'OCCUPATION'

                    ]],

                    body:
                        rows.length
                            ? rows
                            : [
                                [
                                    '1',
                                    '—',
                                    '—',
                                    '—',
                                    '—',
                                    '—'
                                ]
                            ],

                    theme: 'grid',

                    styles: {

                        font: 'helvetica',

                        fontSize: 8.1,

                        textColor: DARK,

                        cellPadding: 2.2,

                        lineColor: BORDER,

                        lineWidth: 0.25,

                        valign: 'middle'

                    },

                    headStyles: {

                        fillColor:
                            HEADER_FILL,

                        textColor:
                            DARK,

                        fontStyle:
                            'bold',

                        fontSize:
                            7.3,

                        halign:
                            'center'

                    },

                    columnStyles: {

                        0: {
                            cellWidth: 8,
                            halign: 'center'
                        },

                        1: {
                            cellWidth: 42
                        },

                        2: {
                            cellWidth: 15
                        },

                        3: {
                            cellWidth: 34
                        },

                        4: {
                            cellWidth: 43
                        },

                        5: {
                            cellWidth: 38
                        }

                    }

                });


                y =
                    doc.lastAutoTable.finalY +
                    9;


                /* III. PROBLEM PRESENTED */

                sectionTitle(
                    'III',
                    'PROBLEM PRESENTED'
                );


                addWrapped(
                    caseStudyData.assessment
                        ?.problemPresented,
                    margin,
                    contentW,
                    10.5,
                    5.1
                );


                y += 8;


                /* IV. HOME AND ECONOMIC CONDITION */

                sectionTitle(
                    'IV',
                    'HOME AND ECONOMIC CONDITION'
                );


                addWrapped(
                    caseStudyData.assessment
                        ?.homeCondition,
                    margin,
                    contentW,
                    10.5,
                    5.1
                );


                y += 7;


                /* V. EVALUATION / RECOMMENDATION */

                sectionTitle(
                    'V',
                    'EVALUATION / RECOMMENDATION'
                );


                addWrapped(
                    caseStudyData.recommendation,
                    margin,
                    contentW,
                    10.5,
                    5.1
                );


                y += 13;


                /* PREPARED BY */

                ensureSpace(35);


                doc.setFont(
                    'helvetica',
                    'normal'
                );


                doc.setFontSize(
                    10.5
                );


                doc.text(
                    'Prepared by:',
                    margin,
                    y
                );


                y += 20;


                doc.setFont(
                    'helvetica',
                    'bold'
                );


                doc.text(

                    safePdf(
                        caseStudyData.submitted
                            ?.preparedBy ||
                        caseStudyData.submitted
                            ?.submittedBy
                    ),

                    margin,
                    y

                );


                y += 5;


                doc.setFont(
                    'helvetica',
                    'normal'
                );


                doc.text(

                    safePdf(
                        caseStudyData.submitted
                            ?.designation ||
                        'MSWDO'
                    ),

                    margin,
                    y

                );


                y += 5;


                if (
                    caseStudyData.submitted
                        ?.prcLicense
                ) {

                    doc.text(

                        'PRC License # ' +
                        caseStudyData.submitted
                            .prcLicense,

                        margin,
                        y

                    );

                    y += 5;

                }


                if (
                    caseStudyData.submitted
                        ?.licenseValidity
                ) {

                    doc.text(

                        'Valid until ' +
                        caseStudyData.submitted
                            .licenseValidity,

                        margin,
                        y

                    );

                }


                /* FOOTER */

                const totalPages =
                    doc.getNumberOfPages();


                for (
                    let page = 1;
                    page <= totalPages;
                    page++
                ) {

                    doc.setPage(page);


                    doc.setFont(
                        'helvetica',
                        'normal'
                    );


                    doc.setFontSize(
                        7
                    );


                    doc.setTextColor(
                        145,
                        145,
                        145
                    );


                    doc.text(

                        'MSWDO San Enrique Information System',

                        margin,

                        pageH - 7

                    );


                    doc.text(

                        `Page ${page} of ${totalPages}`,

                        pageW - margin,

                        pageH - 7,

                        {
                            align: 'right'
                        }

                    );

                }


                /* OPEN PDF PREVIEW */

                const pdfBlob =
                    doc.output('blob');


                const pdfUrl =
                    URL.createObjectURL(
                        pdfBlob
                    );


                if (openInNewTab) {
                    previewWindow.location.href = pdfUrl;
                } else {
                    const pdfFrame = document.getElementById('caseSummaryPdfFrame');
                    const pdfHost = document.getElementById('caseSummaryPdfHost');
                    const loader = document.getElementById('caseSummaryGenerating');
                    const formal = document.getElementById('formalCaseSummary');

                    pdfFrame.onload = function () {
                        loader.style.display = 'none';
                        formal.style.display = 'none';
                        pdfHost.style.display = 'block';
                        pdfHost.setAttribute('aria-hidden', 'false');
                    };
                    pdfFrame.src = pdfUrl;
                }


                /*
                 * Do NOT call URL.revokeObjectURL() immediately because the
                 * browser PDF viewer is still using the URL.
                 */

            } catch (error) {

                console.error(
                    'Case Summary PDF error:',
                    error
                );


                if (openInNewTab && previewWindow) {
                    previewWindow.document.body.innerHTML = `

            <div style="
                font-family:Arial;
                padding:40px;
                text-align:center;
                color:#991b1b;
            ">

                <h3>
                    Unable to create the Case Summary PDF.
                </h3>

                <p style="
                    color:#64748b;
                ">
                    Please refresh the page and try again.
                </p>

                <p style="
                    font-size:11px;
                    color:#94a3b8;
                ">
                    ${safePdf(
                    error.message ||
                    'Unknown error'
                )}
                </p>

            </div>

        `;
                } else {
                    const loader = document.getElementById('caseSummaryGenerating');
                    loader.innerHTML = `<div class="case-summary-generating-box"><strong>Unable to create the Case Summary.</strong><p>${safePdf(error.message || 'Unknown error')}</p></div>`;
                }

            } finally {

                buttons.forEach(button => {

                    button.disabled = false;

                    button.innerHTML =
                        '<i class="fas fa-file-pdf mr-2"></i> View Case Summary';

                });

            }

        }

        function cancelForm(event) {
            if (event) event.preventDefault();
            if (confirm('Clear this case study form?')) {
                document.getElementById('caseStudyForm').reset();
                document.getElementById('patientInfoFields').classList.add('hidden');
                document.getElementById('ptTrack').classList.remove('bg-mswdo-600');
                document.getElementById('ptTrack').classList.add('bg-slate-200');
                document.getElementById('ptThumb').style.transform = '';
                document.getElementById('ptLabel').textContent = 'No';
                document.getElementById('indigValue').value = '';
                document.querySelectorAll('#indigSelector .indig-opt').forEach(e => {
                    e.className = 'indig-opt border-2 border-slate-200 rounded-2xl p-4 text-center';
                    e.querySelector('p').className = 'text-[13px] font-semibold text-slate-600';
                });
                income();
                ['problemText','homeText','recoText'].forEach(id => {
                    const map = {problemText:'problemCount',homeText:'homeCount',recoText:'recoCount'};
                    countChars(id, map[id], id === 'problemText' ? 1000 : id === 'homeText' ? 800 : 1200);
                });
                document.getElementById('caseSummaryView').style.display = 'none';
                document.getElementById('caseSummaryView').setAttribute('aria-hidden', 'true');
                document.getElementById('caseStudyFormView').style.display = 'block';
            }
            return false;
        }

        function renderRegistrationFamily() {
            const body = document.getElementById('famBody');
            if (!body) return;

            const family = <?= $familyJsonForJs ?> || [];
            body.innerHTML = '';

            if (!family.length) {
                body.innerHTML = '<tr><td colspan="9" class="px-3 py-4 text-center text-[11px] text-slate-400 italic">No family members have been entered.</td></tr>';
                return;
            }

            family.forEach((m, index) => {
                const tr = document.createElement('tr');
                if (index === 0) tr.className = 'fam-row client-row';
                else tr.className = 'fam-row';

                const vals = [
                    index + 1,
                    m.name || '—',
                    m.relationship || m.relation || '—',
                    m.age ?? '—',
                    m.sex || '—',
                    m.civil_status || m.civilStatus || '—',
                    m.education || '—',
                    m.occupation || '—',
                    Number(m.income || 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})
                ];

                vals.forEach((value, i) => {
                    const td = document.createElement('td');
                    td.className = 'px-3 py-2.5';
                    td.textContent = value;
                    tr.appendChild(td);
                });

                body.appendChild(tr);
            });

            const total = family.reduce((sum, m) => sum + (Number(m.income) || 0), 0);
            const sec3 = document.getElementById('sec3CombinedIncome');
            if (sec3) sec3.value = total;
            const totalIncome = document.getElementById('totalIncome');
            if (totalIncome) totalIncome.textContent = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            calcNet();
        }

        window.addEventListener('DOMContentLoaded', () => {
            renderRegistrationFamily();
            income();
            countChars('problemText', 'problemCount', 1000);
            countChars('homeText', 'homeCount', 800);
            countChars('recoText', 'recoCount', 1200);
        });
    </script>
</body>

</html>