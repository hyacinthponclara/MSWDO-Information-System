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
    $combined_income = trim((float) $_POST['combined_income'] ?? '0');
    $monthly_expenses = trim((float) $_POST['monthly_expenses'] ?? '0');
    $emergency_fund_available = isset($_POST['emergency_fund_available']) ? 1 : 0;
    $crises_severity = trim($_POST['crises_severity'] ?? '') ?: null;
    $crises_experienced = trim($_POST['crises_experienced'] ?? '') ?: null;
    $problem_presented = trim($_POST['problem_presented'] ?? '');
    $home_condition = trim($_POST['home_condition'] ?? '') ?: null;
    $indigency_assessment = trim($_POST['indigency_assessment'] ?? '') ?: null;
    $recommendation = trim($_POST['recommendation'] ?? '') ?: null;
    $previous_dswd_assistance = isset($_POST['previous_dswd_assistance']) ? 1 : 0;
    $previous_assistance_details = trim($_POST['previous_assistance_details'] ?? '') ?: null;
    $previous_assisstance_date = trim($_POST['previous_assistance_date'] ?? '') ?: null;
    $insurance_coverage = trim($_POST['insurance_coverage'] ?? '') ?: null;
    $savings = trim((float) $_POST['savings'] ?? '0');

    $family_members = [];
    foreach ($family_members as $f => $family) {
        $family_members[] = [
            'name' => trim($family),
            'relationship' => trim($family_relationships[$f] ?? ''),
            'age' => (int) ($family_age[$f] ?? 0),
            'sex' => trim($family_sex[$f] ?? ''),
            'civil_status' => trim($family_civil_status[$f] ?? ''),
            'education' => trim($family_education[$f] ?? ''),
            'occupation' => trim($family_occupation[$f] ?? ''),
            'income' => (float) ($family_income[$f] ?? 0),
        ];
    }
    $family_composition_json = json_encode($family_members);

    $stmt = $pdo->prepare("INSERT INTO CASE_STUDY (client_id, user_id, interview_date, type_of_case_study, patient_name, patient_relationship, family_composition_json, combined_income, monthly_expenses, emergency_fund_available, crisis_severity, crises_experienced, problem_presented, home_condition, indigency_assessment, recommendation, previous_dswd_assistance, previous_assistance_details, previous_assistance_date, insurance_coverage, savings)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$client_id, $user_id, $interview_date, $type_of_case_study, $patient_name, $patient_relationship, $family_composition_json, $combined_income, $monthly_expenses, $emergency_fund_available, $crises_severity, $crises_experienced, $problem_presented, $home_condition, $indigency_assessment, $recommendation, $previous_dswd_assistance, $previous_assistance_details, $previous_assisstance_date, $insurance_coverage, $savings]);

    header("Location: clientslist.php?id={$client_id}");
    exit;

}


$initials = strtoupper(
    substr($client['cl_firstname'], 0, 1) . substr($client['cl_lastname'], 0, 1)
);


$full_name = htmlspecialchars(
    $client['cl_firstname'] . ' ' . ($client['cl_middlename'] ? $client['cl_middlename'][0] . '. ' : '') . $client['cl_lastname']
);


$barangay = htmlspecialchars($client['barangay_name'] ?? 'Unknown Barangay');
$age = $client['cl_age'] ?? '—';
$sex = htmlspecialchars($client['cl_sex'] ?? '');
$civilStat = htmlspecialchars($client['cl_civil_status'] ?? '');
$subtitle = "ID-{$client['client_id']} · {$barangay} · {$age} yrs, {$sex} · {$civilStat}";

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

        /* Sidebar */
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

        /* Fields */
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

        /* Section card */
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

        /* Step progress */
        .step-dot {
            transition: all .2s;
        }

        /* Indigency selector */
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

        /* Crisis severity */
        .sev-opt {
            transition: all .18s;
            cursor: pointer;
        }

        .sev-opt:hover {
            border-color: #94A3B8;
        }

        .sev-opt.sel {
            border-color: #0B2545;
            background: #E8EDF5;
        }

        .sev-opt.sel p {
            color: #0B2545;
            font-weight: 600;
        }

        /* Checklist */
        .crisis-check {
            transition: all .15s;
            cursor: pointer;
        }

        .crisis-check:has(input:checked) {
            border-color: #C0392B;
            background: #FEF2F2;
        }

        .crisis-check:has(input:checked) span {
            color: #991B1B;
            font-weight: 500;
        }

        .crisis-check:hover {
            border-color: #94A3B8;
        }

        /* Family table */
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

        /* Upload zone */
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

        /* Character counter */
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

        /* Income/expense calculator */
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

        /* PDF preview panel */
        #pdfPreview {
            display: none;
        }

        #pdfPreview.show {
            display: block;
            animation: fadeUp 0.3s ease both;
        }

        /* Print styles */
        @media print {

            aside,
            header,
            #pdfToggleBtn,
            .no-print,
            footer {
                display: none !important;
            }

            .ml-56 {
                margin-left: 0 !important;
            }

            #pdfPreview {
                display: block !important;
            }

            #formArea {
                display: none !important;
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

    <?php include 'sidebar.php'; ?>

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
            <div class="flex items-center gap-2 no-print">
            </div>
        </header>

        <main class="flex-1 p-6">
            <div class="max-w-4xl mx-auto">

                <div class="animate-fade-up mb-6">
                    <h1 class="text-xl font-serif text-navy-600">Case Study / Social Case Summary</h1>
                </div>

                <form method="POST" action="casestudy.php?client_id=<?= $client_id ?>">

                    <div
                        class="animate-fade-up-1 bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-4 mb-5">
                        <div class="flex-1">
                            <p class="text-[14px] font-semibold text-navy-600"><?= $full_name ?></p>
                            <p class="text-[11px] text-slate-400"><?= htmlspecialchars($subtitle) ?></p>
                        </div>
                    </div>

                    <!-- name="interview_date"      = $_POST['interview_date']      -->
                    <!-- name="type_of_case_study"  = $_POST['type_of_case_study']  -->
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
                                    <select class="field" name="type_of_case_study" required>
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

                            <!-- if patient is different -->
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

                            <!-- name="patient_name"         = $_POST['patient_name']         -->
                            <!-- name="patient_relationship" = $_POST['patient_relationship']  -->
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
                                            <th
                                                class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-6">
                                                #</th>
                                            <th
                                                class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                                Name</th>
                                            <th
                                                class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-24">
                                                Relationship</th>
                                            <th
                                                class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-12">
                                                Age</th>
                                            <th
                                                class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-10">
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
                                            <th class="w-8"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="famBody">
                                        <tr class="fam-row border-b border-slate-100">
                                            <td class="px-3 py-2 text-slate-400 font-medium">1</td>
                                            <td class="px-3 py-2">
                                                <input class="fam-input" type="text" name="family_names[]"
                                                    placeholder="Full name">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input class="fam-input" type="text" name="family_relationships[]"
                                                    placeholder="e.g. Self, Child">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input class="fam-input" type="number" name="family_ages[]"
                                                    placeholder="Age" min="0">
                                            </td>
                                            <td class="px-3 py-2">
                                                <select class="fam-select" name="family_sexes[]">
                                                    <option value="F">F</option>
                                                    <option value="M">M</option>
                                                </select>
                                            </td>
                                            <td class="px-3 py-2">
                                                <select class="fam-select" name="family_civil_status[]">
                                                    <option value="Single">Single</option>
                                                    <option value="Married">Married</option>
                                                    <option value="Widowed">Widowed</option>
                                                    <option value="Separated">Separated</option>
                                                </select>
                                            </td>
                                            <td class="px-3 py-2">
                                                <input class="fam-input" type="text" name="family_educations[]"
                                                    placeholder="High School">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input class="fam-input" type="text" name="family_occupations[]"
                                                    placeholder="Occupation">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input class="fam-input" type="number" name="family_incomes[]"
                                                    placeholder="0" oninput="income()">
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <button type="button"
                                                    onclick="this.closest('tr').remove();renumber();income()"
                                                    class="text-slate-300 hover:text-red-400 transition-colors">✕</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex items-center justify-between">
                                <button type="button" onclick="addFamRow()"
                                    class="text-[12px] font-medium text-navy-600 border-2 border-dashed border-navy-200 rounded-xl px-4 py-2 hover:border-navy-400 hover:bg-navy-50 transition-all">
                                    + Add Family Member
                                </button>
                                <div class="flex items-center gap-4 text-[12px]">
                                    <span class="text-slate-400">Combined monthly income:</span>
                                    <span id="totalIncome" class="font-bold text-navy-600 text-[14px]">₱0</span>
                                </div>
                            </div>

                            <input type="hidden" name="combined_income" id="hiddenIncome" value="0">
                            <input type="hidden" name="monthly_expenses" id="hiddenExpenses" value="0">
                        </div>
                    </div>

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
                                    <div>
                                        <div class="calc-row">
                                            <span class="calc-label">Combined family income</span>
                                            <div class="relative calc-input">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[12px]">₱</span>
                                                <input type="number" class="field pl-6 text-[12px] py-2" placeholder="0"
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
                                            <!-- name="insurance_coverage" = $_POST['insurance_coverage'] -->
                                            <label class="field-label">Insurance / PhilHealth / SSS / GSIS</label>
                                            <input type="text" class="field" name="insurance_coverage"
                                                placeholder="e.g. PhilHealth only, None">
                                        </div>
                                        <div>
                                            <label class="field-label">Savings</label>
                                            <div class="relative">
                                                <span
                                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[12px]">₱</span>
                                                <!-- name="savings" = $_POST['savings'] -->
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
                                <div>
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

                            <!-- Net summary display -->
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

                    <div class="section-card animate-fade-up-4">
                        <div class="section-head">
                            <div class="section-num">4</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-navy-600">Crisis Assessment</h2>
                                <p class="text-[11px] text-slate-400">Severity of the current crisis and recent critical
                                    events</p>
                            </div>
                        </div>
                        <div class="section-body space-y-5">

                            <input type="hidden" name="crisis_severity" id="severityValue" value="">

                            <div>
                                <label class="field-label req">Severity of Crisis</label>
                                <div class="grid grid-cols-4 gap-3 mt-2" id="sevSelector">
                                    <div onclick="setSev(this,'Recently diagnosed (≤3 months)')"
                                        class="sev-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                                        <p class="text-[12px] font-semibold text-slate-600 leading-tight">Recently
                                            Diagnosed</p>
                                        <p class="text-[10px] text-slate-400 mt-1">≤ 3 months</p>
                                    </div>
                                    <div onclick="setSev(this,'3 months to 1 year')"
                                        class="sev-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                                        <p class="text-[12px] font-semibold text-slate-600 leading-tight">3 Months – 1
                                            Year</p>
                                        <p class="text-[10px] text-slate-400 mt-1">Ongoing</p>
                                    </div>
                                    <div onclick="setSev(this,'Chronic/lifelong')"
                                        class="sev-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                                        <p class="text-[12px] font-semibold text-slate-600 leading-tight">Chronic /
                                            Lifelong</p>
                                        <p class="text-[10px] text-slate-400 mt-1">Permanent condition</p>
                                    </div>
                                    <div onclick="setSev(this,'Not applicable')"
                                        class="sev-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                                        <p class="text-[12px] font-semibold text-slate-600 leading-tight">Not Applicable
                                        </p>
                                        <p class="text-[10px] text-slate-400 mt-1">Financial / other type</p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="field-label">Crises Experienced in the Past 3 Months</label>
                                <p class="text-[11px] text-slate-400 mb-3">Check all that apply to the household</p>
                                <input type="hidden" name="crises_experienced" id="crisesValue" value="">
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    <label
                                        class="crisis-check flex items-center gap-3 p-3 border-2 border-slate-200 rounded-xl cursor-pointer"
                                        onclick="updateCrises()">
                                        <input type="checkbox" class="w-4 h-4 accent-red-500 flex-shrink-0"
                                            value="Hospitalization">
                                        <span class="text-[12px] text-slate-700">Hospitalization</span>
                                    </label>
                                    <label
                                        class="crisis-check flex items-center gap-3 p-3 border-2 border-slate-200 rounded-xl cursor-pointer"
                                        onclick="updateCrises()">
                                        <input type="checkbox" class="w-4 h-4 accent-red-500 flex-shrink-0"
                                            value="Death in family">
                                        <span class="text-[12px] text-slate-700">Death in family</span>
                                    </label>
                                    <label
                                        class="crisis-check flex items-center gap-3 p-3 border-2 border-slate-200 rounded-xl cursor-pointer"
                                        onclick="updateCrises()">
                                        <input type="checkbox" class="w-4 h-4 accent-red-500 flex-shrink-0"
                                            value="Catastrophic event">
                                        <span class="text-[12px] text-slate-700">Catastrophic event</span>
                                    </label>
                                    <label
                                        class="crisis-check flex items-center gap-3 p-3 border-2 border-slate-200 rounded-xl cursor-pointer"
                                        onclick="updateCrises()">
                                        <input type="checkbox" class="w-4 h-4 accent-red-500 flex-shrink-0"
                                            value="Disablement">
                                        <span class="text-[12px] text-slate-700">Disablement</span>
                                    </label>
                                    <label
                                        class="crisis-check flex items-center gap-3 p-3 border-2 border-slate-200 rounded-xl cursor-pointer"
                                        onclick="updateCrises()">
                                        <input type="checkbox" class="w-4 h-4 accent-red-500 flex-shrink-0"
                                            value="Loss of livelihood">
                                        <span class="text-[12px] text-slate-700">Loss of livelihood</span>
                                    </label>
                                    <label
                                        class="crisis-check flex items-center gap-3 p-3 border-2 border-slate-200 rounded-xl cursor-pointer"
                                        onclick="updateCrises()">
                                        <input type="checkbox" class="w-4 h-4 accent-red-500 flex-shrink-0"
                                            value="Others">
                                        <span class="text-[12px] text-slate-700">Others</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 5: Problem & Home Condition -->
                    <!-- name="problem_presented" - $_POST['problem_presented'] -->
                    <!-- name="home_condition"    - $_POST['home_condition']    -->
                    <div class="section-card animate-fade-up-5">
                        <div class="section-head">
                            <div class="section-num">5</div>
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

                    <!--  SECTION 6: Indigency Assessment  -->
                    <!-- 'Indigent' 'Near Poor' 'Not Indigent' 'Not Assessed' -->
                    <div class="section-card animate-fade-up-6">
                        <div class="section-head">
                            <div class="section-num">6</div>
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

                    <!--  SECTION 7: Previous DSWD Assistance  -->
                    <div class="section-card animate-fade-up-6">
                        <div class="section-head">
                            <div class="section-num">7</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-navy-600">Previous DSWD Assistance</h2>
                                <p class="text-[11px] text-slate-400">Any prior assistance received from DSWD or MSWDO
                                </p>
                            </div>
                        </div>
                        <div class="section-body space-y-4">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" id="prevDSWD" name="previous_dswd_assistance" value="1"
                                    class="w-4 h-4 accent-navy-600" onchange="togglePrevDSWD()">
                                <label for="prevDSWD" class="text-[13px] font-medium text-slate-700 cursor-pointer">
                                    Client has received previous assistance from DSWD / MSWDO
                                </label>
                            </div>
                            <div id="prevDSWDFields" class="hidden grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <!-- name="previous_assistance_details" - $_POST['previous_assistance_details'] -->
                                    <label class="field-label">Details of Previous Assistance</label>
                                    <textarea class="field" rows="2" name="previous_assistance_details"
                                        placeholder="Type of assistance, amount, program..."></textarea>
                                </div>
                                <div>
                                    <!-- name="previous_assistance_date" - $_POST['previous_assistance_date'] -->
                                    <label class="field-label">Date of Previous Assistance</label>
                                    <input type="date" class="field" name="previous_assistance_date">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--  SECTION 8: Recommendation  -->
                    <!-- name="recommendation" - $_POST['recommendation'] -->
                    <div class="section-card animate-fade-up-7">
                        <div class="section-head">
                            <div class="section-num">8</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-navy-600">Evaluation & Recommendation</h2>
                                <p class="text-[11px] text-slate-400">Social worker's professional assessment and formal
                                    recommendation</p>
                            </div>
                        </div>
                        <div class="section-body space-y-4">
                            <div>
                                <label class="field-label">Quick Templates</label>
                                <div class="flex flex-wrap gap-2 mt-1">
                                    <button type="button" onclick="fillTemplate('medical')"
                                        class="text-[11px] border border-slate-200 rounded-lg px-3 py-1.5 text-slate-600 hover:border-navy-400 hover:text-navy-600 hover:bg-navy-50 transition-all">Medical</button>
                                    <button type="button" onclick="fillTemplate('financial')"
                                        class="text-[11px] border border-slate-200 rounded-lg px-3 py-1.5 text-slate-600 hover:border-navy-400 hover:text-navy-600 hover:bg-navy-50 transition-all">Financial</button>
                                    <button type="button" onclick="fillTemplate('educational')"
                                        class="text-[11px] border border-slate-200 rounded-lg px-3 py-1.5 text-slate-600 hover:border-navy-400 hover:text-navy-600 hover:bg-navy-50 transition-all">Educational</button>
                                    <button type="button" onclick="fillTemplate('burial')"
                                        class="text-[11px] border border-slate-200 rounded-lg px-3 py-1.5 text-slate-600 hover:border-navy-400 hover:text-navy-600 hover:bg-navy-50 transition-all">Burial</button>
                                    <button type="button" onclick="fillTemplate('livelihood')"
                                        class="text-[11px] border border-slate-200 rounded-lg px-3 py-1.5 text-slate-600 hover:border-navy-400 hover:text-navy-600 hover:bg-navy-50 transition-all">Livelihood</button>
                                </div>
                            </div>
                            <div>
                                <label class="field-label req">Recommendation</label>
                                <textarea class="field" rows="5" name="recommendation" id="recoText" maxlength="1200"
                                    oninput="countChars('recoText','recoCount',1200)"
                                    placeholder="Based on the conducted case study and assessment, the client is classified as..."></textarea>
                                <div class="char-counter" id="recoCount">0 / 1200 characters</div>
                            </div>
                        </div>
                    </div>

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

    <!-- notification  -->
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


        function setSev(el, enumValue) {
            document.querySelectorAll('#sevSelector .sev-opt').forEach(e => {
                e.className = 'sev-opt border-2 border-slate-200 rounded-xl p-3 text-center';
                e.querySelector('p').className = 'text-[12px] font-semibold text-slate-600 leading-tight';
            });
            el.classList.add('sel', 'border-navy-600', 'bg-navy-50');
            el.querySelector('p').className = 'text-[12px] font-semibold text-navy-700 leading-tight';
            document.getElementById('severityValue').value = enumValue;
        }


        function setIndig(el, enumValue, cssClass) {
            document.querySelectorAll('#indigSelector .indig-opt').forEach(e => {
                e.className = 'indig-opt border-2 border-slate-200 rounded-2xl p-4 text-center';
                e.querySelector('p').className = 'text-[13px] font-semibold text-slate-600';
            });
            el.classList.add(cssClass);
            document.getElementById('indigValue').value = enumValue;
        }


        function updateCrises() {
            const checked = document.querySelectorAll('.crisis-check input:checked');
            const values = Array.from(checked).map(cb => cb.value).join(', ');
            document.getElementById('crisesValue').value = values;
        }


        let famCount = 1;
        function addFamRow() {
            famCount++;
            const tr = document.createElement('tr');
            tr.className = 'fam-row border-b border-slate-100';
            tr.innerHTML = `
                <td class="px-3 py-2 text-slate-400 font-medium">${famCount}</td>
                <td class="px-3 py-2"><input class="fam-input" type="text" name="family_names[]" placeholder="Full name"></td>
                <td class="px-3 py-2"><input class="fam-input" type="text" name="family_relationships[]" placeholder="e.g. Child"></td>
                <td class="px-3 py-2"><input class="fam-input" type="number" name="family_ages[]" placeholder="Age" min="0"></td>
                <td class="px-3 py-2"><select class="fam-select" name="family_sexes[]"><option value="F">F</option><option value="M">M</option></select></td>
                <td class="px-3 py-2"><select class="fam-select" name="family_civil_status[]"><option value="Single">Single</option><option value="Married">Married</option><option value="Widowed">Widowed</option><option value="Separated">Separated</option></select></td>
                <td class="px-3 py-2"><input class="fam-input" type="text" name="family_educations[]" placeholder="Education"></td>
                <td class="px-3 py-2"><input class="fam-input" type="text" name="family_occupations[]" placeholder="Occupation"></td>
                <td class="px-3 py-2"><input class="fam-input" type="number" name="family_incomes[]" placeholder="0" oninput="calcIncome()"></td>
                <td class="px-3 py-2 text-center"><button type="button" onclick="this.closest('tr').remove();renumber();calcIncome()" class="text-slate-300 hover:text-red-400 transition-colors">✕</button></td>
            `;
            document.getElementById('famBody').appendChild(tr);
        }

        function renumber() {
            document.querySelectorAll('#famBody tr').forEach((tr, i) => {
                tr.querySelector('td').textContent = i + 1;
            });
            famCount = document.querySelectorAll('#famBody tr').length;
        }

        function calcIncome() {
            let total = 0;
            document.querySelectorAll('#famBody input[type=number]').forEach(inp => {
                total += parseFloat(inp.value) || 0;
            });
            document.getElementById('totalIncome').textContent = '₱' + total.toLocaleString();
        }


        function calcNet() {
            const allInputs = document.querySelectorAll('.calc-row input[type=number]');
            let inc = 0, exp = 0;
            allInputs.forEach((inp, i) => {
                const val = parseFloat(inp.value) || 0;
                if (i < 4) inc += val; else exp += val;
            });

            document.getElementById('totalIncomeSum').textContent = '₱' + inc.toLocaleString();
            document.getElementById('totalExpenses').textContent = '₱' + exp.toLocaleString();
            const net = inc - exp;
            const netEl = document.getElementById('netMonthly');
            netEl.textContent = (net < 0 ? '-₱' : '₱') + Math.abs(net).toLocaleString();
            netEl.className = `text-[18px] font-bold ${net < 0 ? 'text-red-600' : net < 500 ? 'text-amber-600' : 'text-navy-600'}`;

            document.getElementById('hiddenIncome').value = inc;
            document.getElementById('hiddenExpenses').value = exp;
        }


        function countChars(id, countId, max) {
            const len = document.getElementById(id).value.length;
            const el = document.getElementById(countId);
            el.textContent = `${len} / ${max} characters`;
            el.className = `char-counter ${len > max * .9 ? 'limit' : len > max * .75 ? 'warn' : ''}`;
        }


        function togglePrevDSWD() {
            const checked = document.getElementById('prevDSWD').checked;
            document.getElementById('prevDSWDFields').classList.toggle('hidden', !checked);
        }


        //  Recommendation templates 
        const templates = {
            medical: 'Based on the conducted case study and assessment, the client is classified as INDIGENT under the DOH/DSWD Assessment Tool. It is therefore respectfully recommended that the client be granted AICS Medical Assistance in the amount of [AMOUNT IN WORDS] (₱____.__) to help defray the cost of ongoing medical treatment.',
            financial: 'Based on the conducted case study, the client is classified as INDIGENT. It is respectfully recommended that the client be provided Financial Assistance amounting to [AMOUNT IN WORDS] (₱____.__) to address the immediate financial crisis of the household.',
            educational: "Based on the case study conducted, the client is classified as INDIGENT. It is recommended that the client's dependent be granted AICS Educational Assistance in the amount of [AMOUNT IN WORDS] (₱____.__) to cover school fees for the school year.",
            burial: 'Based on the conducted case study, the bereaved family is classified as INDIGENT. It is respectfully recommended that the client be granted AICS Burial Assistance in the amount of [AMOUNT IN WORDS] (₱____.__) to help defray funeral and burial expenses.',
            livelihood: 'Based on the case study, the client is classified as INDIGENT. It is recommended that the client be granted AICS Livelihood Assistance in the amount of [AMOUNT IN WORDS] (₱____.__) as seed capital for the proposed [business type].',
        };
        function fillTemplate(type) {
            document.getElementById('recoText').value = templates[type];
            countChars('recoText', 'recoCount', 1200);
        }


        //  Toast notification 
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

    </script>
</body>

</html>