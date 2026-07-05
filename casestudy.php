<?php
require 'auth.php';
requireRole(['Social Worker']);
require 'db_connect.php';

$client_id = (int) ($_GET['client_id'] ?? 0);

if ($client_id <= 0) {
    header('Location: clientslist.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM CLIENT WHERE client_id = ? LIMIT 1");
$stmt->execute([$client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    header("Location: clientslist.php");
    exit;
}                                                                       

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $client_id = (int) ($_GET['client_id'] ?? 0);
    $interview_date = trim($_POST['interview_date'] ?? '');
    $type_of_case_study = trim($_POST['type_of_case_study'] ?? '');
    $patient_name = trim($_POST['patient_name'] ?? '');
    $patient_relationship = trim($_POST['patient_relationship'] ?? '');
    $combined_income = (float) ($_POST['combined_income'] ?? 0);
    $monthly_expenses = (float) ($_POST['monthly_expenses'] ?? 0);
    $emergency_fund_available = isset($_POST['emergency_fund_available']) ? (int) $_POST['emergency_fund_available'] : 0;
    $problem_presented = trim($_POST['problem_presented'] ?? '');
    $home_condition = trim($_POST['home_condition'] ?? '') ?: null;
    $indigency_assessment = trim($_POST['indigency_assessment'] ?? '') ?: null;
    $recommendation = trim($_POST['recommendation'] ?? '') ?: null;
    $previous_dswd_assistance = ((int) ($_POST['previous_dswd_assistance'] ?? 0)) === 1 ? 1 : 0;
    $previous_assistance_details = trim($_POST['previous_assistance_details'] ?? '') ?: null;
    $previous_assistance_date = trim($_POST['previous_assistance_date'] ?? '') ?: null;
    $insurance_coverage = trim($_POST['insurance_coverage'] ?? '') ?: null;
    $savings = (float) ($_POST['savings'] ?? 0);

    $famStmt2 = $pdo->prepare("
        SELECT family_composition_json FROM CASE_STUDY
        WHERE client_id = ? AND problem_presented = 'Initial registration'
        ORDER BY created_at ASC LIMIT 1
    ");
    $famStmt2->execute([$client_id]);
    $famRow2 = $famStmt2->fetch(PDO::FETCH_ASSOC);
    $regFamily2 = ($famRow2 && !empty($famRow2['family_composition_json']))
        ? (json_decode($famRow2['family_composition_json'], true) ?? [])
        : [];

    // the client as row 1
    $clientRow = [
        'name'         => trim($client['cl_firstname'] . ' ' . $client['cl_lastname']),
        'relationship' => 'Client (Self)',
        'age'          => $client['cl_age'],
        'sex'          => $client['cl_sex'],
        'civil_status' => $client['cl_civilstatus'],
        'education'    => $client['cl_educ_attain'] ?? '',
        'occupation'   => $client['cl_occupation'] ?? '',
        'income'       => (float) ($client['cl_monthly_income'] ?? 0),
    ];
    $family_composition_json = json_encode(array_merge([$clientRow], $regFamily2));

    $stmt = $pdo->prepare("
        INSERT INTO CASE_STUDY (
            client_id, user_id, interview_date, type_of_case_study,
            patient_name, patient_relationship, family_composition_json,
            combined_income, monthly_expenses, emergency_fund_available,
            problem_presented,
            home_condition, indigency_assessment, recommendation,
            previous_dswd_assistance, previous_assistance_details,
            previous_assistance_date, insurance_coverage, savings
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $client_id,
        $user_id,
        $interview_date,
        $type_of_case_study,
        $patient_name,
        $patient_relationship,
        $family_composition_json,
        $combined_income,
        $monthly_expenses,
        $emergency_fund_available,
        $problem_presented,
        $home_condition,
        $indigency_assessment,
        $recommendation,
        $previous_dswd_assistance,
        $previous_assistance_details,
        $previous_assistance_date,
        $insurance_coverage,
        $savings
    ]);

    header("Location: clientprofile.php?id={$client_id}&saved=casestudy");
    exit;
}

$full_name = htmlspecialchars(
    $client['cl_firstname'] . ' ' .
    ($client['cl_middlename'] ? $client['cl_middlename'][0] . '. ' : '') .
    $client['cl_lastname']
);

$barangay = htmlspecialchars($client['barangay_name'] ?? 'Unknown Barangay');
$age = $client['cl_age'] ?? '—';
$sex = htmlspecialchars($client['cl_sex'] ?? '');
$civilStat = htmlspecialchars($client['cl_civilstatus'] ?? '');
$subtitle = "ID-{$client['client_id']} · {$barangay} · {$age} yrs, {$sex} · {$civilStat}";

$client_income = (float) ($client['cl_monthly_income'] ?? 0);

$famStmt = $pdo->prepare("
    SELECT family_composition_json
    FROM CASE_STUDY
    WHERE client_id = ?
      AND problem_presented = 'Initial registration'
    ORDER BY created_at ASC
    LIMIT 1
");
$famStmt->execute([$client_id]);
$famRow = $famStmt->fetch(PDO::FETCH_ASSOC);
$registrationFamily = [];
if ($famRow && !empty($famRow['family_composition_json'])) {
    $registrationFamily = json_decode($famRow['family_composition_json'], true) ?? [];
}

//  Auto-detect previous DSWD/MSWDO assistance from availment history 
$prevStmt = $pdo->prepare("
    SELECT AVAILMENT.availment_id, PROGRAM.program_name, AVAILMENT.av_amount, AVAILMENT.av_date_applied
    FROM AVAILMENT
    JOIN PROGRAM ON AVAILMENT.program_id = PROGRAM.program_id
    WHERE AVAILMENT.client_id = ?
      AND AVAILMENT.av_status IN ('Approved', 'Released')
    ORDER BY AVAILMENT.av_date_applied DESC
");
$prevStmt->execute([$client_id]);
$prevAvailments = $prevStmt->fetchAll(PDO::FETCH_ASSOC);
$has_prev_dswd_assistance = count($prevAvailments) > 0;

// "AICS FBML" is one shared PROGRAM row for Financial/Burial/Medical/Livelihood -
// the actual subtype only lives in which AICS_* table has a matching availment_id.
function resolveAicsFbmlSubtype(PDO $pdo, int $availment_id): ?string
{
    $subtypeTables = [
        'AICS_MEDICAL' => 'AICS Medical',
        'AICS_FINANCIAL' => 'AICS Financial',
        'AICS_BURIAL' => 'AICS Burial',
        'AICS_LIVELIHOOD' => 'AICS Livelihood',
    ];
    foreach ($subtypeTables as $table => $label) {
        $stmt = $pdo->prepare("SELECT 1 FROM {$table} WHERE availment_id = ? LIMIT 1");
        $stmt->execute([$availment_id]);
        if ($stmt->fetchColumn()) {
            return $label;
        }
    }
    return null;
}

$prev_assistance_details_auto = '';
$prev_assistance_date_auto = '';
if ($has_prev_dswd_assistance) {
    $lines = [];
    foreach ($prevAvailments as $row) {
        $label = $row['program_name'];
        if ($label === 'AICS FBML') {
            $label = resolveAicsFbmlSubtype($pdo, (int) $row['availment_id']) ?? $label;
        }
        $lines[] = $label . ' — ₱' . number_format((float) $row['av_amount'], 2)
            . ' (' . date('M j, Y', strtotime($row['av_date_applied'])) . ')';
    }
    $prev_assistance_details_auto = implode("\n", $lines);
    $prev_assistance_date_auto = $prevAvailments[0]['av_date_applied']; // most recent
}
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
                        navy: { DEFAULT: '#0B2545', 50: '#E8EDF5', 100: '#C5D1E6', 400: '#3A5F93', 500: '#163566', 600: '#0B2545', 700: '#091D38' },
                        gold: { DEFAULT: '#C49A2A', 400: '#C49A2A' },
                        slate2: '#F4F7FC',
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
            background: rgba(29, 111, 164, .28);
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
            background: #0B2545;
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
            border-bottom: 1.5px solid #3A5F93;
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
            border-color: #3A5F93;
            background: #EBF4FB;
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
    </style>
</head>

<body class="bg-slate2 min-h-screen flex">

    <?php require 'sidebar.php'; ?>

    <div class="ml-64 flex-1 flex flex-col min-h-screen">

        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <a href="clientslist.php" class="text-slate-400 hover:text-navy-600 transition-colors">Clients</a>
                <span class="text-slate-300">/</span>
                <a href="clientprofile.php?id=<?= $client_id ?>"
                    class="text-slate-400 hover:text-navy-600 transition-colors"><?= $full_name ?></a>
                <span class="text-slate-300">/</span>
                <span class="text-navy-600 font-semibold">Case Study</span>
            </div>
        </header>

        <main class="flex-1 p-6">
            <div class="max-w-4xl mx-auto">

                <div class="animate-fade-up mb-6">
                    <h1 class="text-xl font-serif text-navy-600">Case Study / Social Case Summary</h1>
                </div>

                <form method="POST" action="casestudy.php?client_id=<?= $client_id ?>">

                    <!-- Client banner -->
                    <div
                        class="animate-fade-up-1 bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-4 mb-5">
                        <div class="flex-1">
                            <p class="text-[14px] font-semibold text-navy-600"><?= $full_name ?></p>
                            <p class="text-[11px] text-slate-400"><?= htmlspecialchars($subtitle) ?></p>
                        </div>
                    </div>

                    <!--  Section 1: Interview Details  -->
                    <div class="section-card animate-fade-up-1">
                        <div class="section-head">
                            <div class="section-num">1</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-navy-600">Interview Details</h2>
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
                                        <option value="PWD">PWD</option>
                                        <option value="Senior Citizen">Senior Citizen</option>
                                        <option value="Solo Parent">Solo Parent</option>
                                        <option value="Others">Others</option>
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
                                <h2 class="text-[14px] font-semibold text-navy-600">Family Composition</h2>
                                <p class="text-[11px] text-slate-400">All household members — names, relationships,
                                    ages, education, occupation, and income</p>
                            </div>
                        </div>
                        <div class="section-body">
                            <div class="rounded-xl border border-slate-200 overflow-hidden mb-3">
                                <table class="w-full text-[11px]">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-200">
                                            <th class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-6">#</th>
                                            <th class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Name</th>
                                            <th class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-28">Relationship</th>
                                            <th class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-14">Age</th>
                                            <th class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-16">Sex</th>
                                            <th class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-24">Civil Status</th>
                                            <th class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-24">Education</th>
                                            <th class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Occupation</th>
                                            <th class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-24">Income/mo (₱)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="famBody">
                                        <!-- Row 1: the client (always locked) -->
                                        <tr class="fam-row border-b border-slate-100 bg-navy-50/40">
                                            <td class="px-3 py-2 text-slate-400 font-medium">1</td>
                                            <td class="px-3 py-2 font-semibold text-navy-700"><?= htmlspecialchars($client['cl_firstname'] . ' ' . $client['cl_lastname']) ?></td>
                                            <td class="px-3 py-2 text-navy-500 italic">Client (Self)</td>
                                            <td class="px-3 py-2"><?= htmlspecialchars($client['cl_age'] ?? '—') ?></td>
                                            <td class="px-3 py-2"><?= htmlspecialchars($client['cl_sex'] ?? '—') ?></td>
                                            <td class="px-3 py-2"><?= htmlspecialchars($client['cl_civilstatus'] ?? '—') ?></td>
                                            <td class="px-3 py-2"><?= htmlspecialchars($client['cl_educ_attain'] ?? '—') ?></td>
                                            <td class="px-3 py-2"><?= htmlspecialchars($client['cl_occupation'] ?? '—') ?></td>
                                            <td class="px-3 py-2 font-medium text-navy-700">₱<?= number_format($client_income, 2) ?></td>
                                        </tr>
                                        <!-- Registered family members — view only -->
                                        <?php if (empty($registrationFamily)): ?>
                                        <tr>
                                            <td colspan="9" class="px-3 py-4 text-center text-[11px] text-slate-400 italic">
                                                No family members were added during registration.
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($registrationFamily as $i => $member): ?>
                                        <tr class="fam-row border-b border-slate-100">
                                            <td class="px-3 py-2 text-slate-400 font-medium"><?= $i + 2 ?></td>
                                            <td class="px-3 py-2"><?= htmlspecialchars($member['name'] ?? '—') ?></td>
                                            <td class="px-3 py-2 text-slate-500"><?= htmlspecialchars($member['relation'] ?? $member['relationship'] ?? '—') ?></td>
                                            <td class="px-3 py-2"><?= htmlspecialchars($member['age'] ?? '—') ?></td>
                                            <td class="px-3 py-2">—</td>
                                            <td class="px-3 py-2">—</td>
                                            <td class="px-3 py-2">—</td>
                                            <td class="px-3 py-2"><?= htmlspecialchars($member['occupation'] ?? '—') ?></td>
                                            <td class="px-3 py-2 font-medium">₱<?= number_format((float)($member['income'] ?? 0), 2) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Combined income display -->
                            <div class="flex items-center justify-end mt-3 gap-4 text-[12px]">
                                <span class="text-slate-400">Combined monthly income:</span>
                                <span id="totalIncome" class="font-bold text-navy-600 text-[14px]">₱<?= number_format($client_income, 2) ?></span>
                            </div>

                            <!-- Hidden fields submitted to DB -->
                            <input type="hidden" name="combined_income" id="hiddenIncome" value="<?= $client_income ?>">
                            <input type="hidden" name="monthly_expenses" id="hiddenExpenses" value="0">
                        </div>
                    </div>

                    <!--  Section 3: Income & Financial Resources  -->
                    <div class="section-card animate-fade-up-3">
                        <div class="section-head">
                            <div class="section-num">3</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-navy-600">Income & Financial Resources</h2>
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
                                <h2 class="text-[14px] font-semibold text-navy-600">Problem Presented & Home Condition
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
                        </div>
                    </div>

                    <!--  Section 5: Indigency Assessment  -->
                    <div class="section-card animate-fade-up-5">
                        <div class="section-head">
                            <div class="section-num">5</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-navy-600">Indigency Assessment</h2>
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
                        <a href="clientprofile.php?id=<?= $client_id ?>"
                            class="text-[13px] font-medium text-slate-600 border border-slate-200 rounded-xl px-5 py-2.5 hover:border-navy-400 hover:text-navy-600 transition-all">
                            Cancel
                        </a>
                        <button type="submit"
                            class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500 transition-all">
                            Save Case Study
                        </button>
                    </div>

                </form>
            </div>
        </main>

        <footer
            class="border-t border-slate-200 bg-white px-6 py-3 flex items-center text-[11px] text-slate-400 no-print">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <!-- Toast -->
    <div id="toast"
        class="fixed bottom-6 right-6 bg-navy-600 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <span class="text-emerald-400 text-base">✓</span>
        <span id="toastMsg">Saved!</span>
    </div>

    <script>
        //  Patient toggle 
        let patientOn = false;
        function togglePatient() {
            patientOn = !patientOn;
            document.getElementById('ptTrack').classList.toggle('bg-navy-600', patientOn);
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

        //  Seed Section 3 combined income field from client income on load 
        function income() {
            const clientIncome = <?= $client_income ?>;
            const sec3Input = document.getElementById('sec3CombinedIncome');
            if (sec3Input) sec3Input.value = clientIncome;
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
            netEl.className = `text-[18px] font-bold ${net < 0 ? 'text-red-600' : net < 500 ? 'text-amber-600' : 'text-navy-600'}`;

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

        window.addEventListener('DOMContentLoaded', () => income());
    </script>
</body>

</html>