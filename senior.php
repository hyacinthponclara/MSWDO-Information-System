<?php
require 'auth.php';
requireRole(['Admin', 'Social Worker', 'Staff']);
require 'db_connect.php';

$client_id = (int)($_GET['client_id'] ?? 0);
if ($client_id <= 0) {
    header("Location: clientslist.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT cl_firstname, cl_middlename, cl_lastname, cl_suffix, cl_birthdate, cl_age, cl_sex, cl_is_senior, b.barangay_name
    FROM CLIENT c
    LEFT JOIN BARANGAY b ON b.barangay_id = c.brgy_id
    WHERE c.client_id = ?
");
$stmt->execute([$client_id]);
$client = $stmt->fetch();
if (!$client) {
    header("Location: clientslist.php");
    exit;
}

$cl_fullname = htmlspecialchars(trim(
    $client['cl_firstname'] . ' ' .
    ($client['cl_middlename'] ? $client['cl_middlename'][0] . '. ' : '') .
    $client['cl_lastname'] .
    ($client['cl_suffix'] ? ' ' . $client['cl_suffix'] : '')
));

$cl_initials = strtoupper(substr($client['cl_firstname'], 0, 1) . substr($client['cl_lastname'], 0, 1));
$cl_age = (int)$client['cl_age'];
$cl_sex = htmlspecialchars($client['cl_sex']);
$cl_barangay = htmlspecialchars($client['barangay_name'] ?? 'Unknown');
$cl_eligible = $cl_age >= 60;

$barangay = $pdo->prepare("SELECT program_id FROM PROGRAM WHERE program_name = 'Senior Citizen' LIMIT 1");
$barangay->execute();
$seniorProgram     = $barangay->fetch();
$senior_program_id = $seniorProgram ? $seniorProgram['program_id'] : null;

function fileUpload(string $field): ?string {
    if (empty($_FILES[$field]['name'])) return null;
    $uploadDir = __DIR__ . '/uploads/senior/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!in_array($ext, $allowed)) return null;
    $filename = uniqid('sen_', true) . '.' . $ext; //to assign random text strings as filenames, prevents duplication
    move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . $filename);
    return 'uploads/senior/' . $filename;
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit') {
    try {
        if (!$senior_program_id) {
            throw new Exception("'Senior Citizen' program not found in the PROGRAM table. Please add it first.");
        }
        if (!$cl_eligible) {
            throw new Exception("Client is {$cl_age} years old and does not meet the 60+ eligibility requirement.");
        }

        $user_id = $_SESSION['user_id'];
        $svc_type = $_POST['svcType'] ?? ''; //service(svc) type
        $av_date  = date('Y-m-d');  //availment(av)
        $av_amount = 0.00;

        $sen_pension = null;
        $sen_centenarian_benefit = null;
        $sen_is_indigent = false;
        $sen_is_sick = false;
        $sen_is_bedridden = false;
        $sen_sss_gsis = false;
        $sen_cent_birth_cert = null;
        $sen_cent_marriage_cert = null;
        $sen_cent_baptismal = null;
        $sen_first_born_birth_cert = null;
        $sen_first_born_death_cert = null;
        $av_remarks = '';

        if ($svc_type === 'scid') {
            $scid_number = trim($_POST['scidNumber'] ?? '');
            $scid_date = $_POST['scidDate'] ?? '';
            $scid_type_val = $_POST['scidType'] ?? '';
            if (!$scid_number) throw new Exception("SCID Number is required.");
            if (!$scid_date) throw new Exception("Date Issued is required.");
            $av_remarks = json_encode([
                'service' => 'SCID Issuance',
                'scid_number' => $scid_number,
                'scid_date' => $scid_date,
                'scid_type' => $scid_type_val,
            ]);

        } elseif ($svc_type === 'pension') {
            $pension_amount = (float)($_POST['pensionAmount'] ?? 0);
            if ($pension_amount <= 0) throw new Exception("Pension amount must be greater than zero.");
            $sen_is_indigent = !empty($_POST['chk_indigent']);
            $sen_is_sick = !empty($_POST['chk_sick']);
            $sen_is_bedridden = !empty($_POST['chk_bedridden']);
            // chk_no_sss checked = client has NO sss/gsis — sen_sss_gsis stores FALSE
            $sen_sss_gsis = empty($_POST['chk_no_sss']);
            $sen_pension = $pension_amount;
            $av_amount = $pension_amount;
            $av_remarks = json_encode([
                'service' => 'Pension Top-up',
                'remarks' => trim($_POST['pensionRemarks'] ?? ''),
            ]);

        } elseif ($svc_type === 'centenarian') {
            if ($cl_age < 100) throw new Exception("Centenarian benefit requires client to be 100 years or older (client is {$cl_age}).");
            $cent_amount = (float)($_POST['centAmount'] ?? 0);
            $cent_dob = $_POST['centDOB'] ?? ''; //dob(date of birth)
            if ($cent_amount <= 0) throw new Exception("Centenarian benefit amount must be greater than zero.");
            if (!$cent_dob) throw new Exception("Verified Date of Birth is required for centenarian benefit.");
            $sen_centenarian_benefit = $cent_amount;
            $av_amount = $cent_amount;
            $sen_cent_birth_cert = fileUpload('cent_birth_cert');
            $sen_cent_marriage_cert = fileUpload('cent_marr_cert');
            $sen_cent_baptismal = fileUpload('cent_baptismal');
            $sen_first_born_birth_cert = fileUpload('cent_fb_birth');
            $sen_first_born_death_cert = fileUpload('cent_fb_death');
            $av_remarks = json_encode(['service' => 'Centenarian Benefit', 'verified_dob' => $cent_dob]);

        } else {
            throw new Exception("Please select a service type before submitting.");
        }

        $pdo->beginTransaction();

        // 1. Insert AVAILMENT
        $avStmt = $pdo->prepare("
            INSERT INTO AVAILMENT
                (client_id, program_id, user_id, av_date_applied, av_amount, av_status, av_remarks)
            VALUES (?, ?, ?, ?, ?, 'Pending', ?)
        ");
        $avStmt->execute([$client_id, $senior_program_id, $user_id, $av_date, $av_amount, $av_remarks]);
        $availment_id = $pdo->lastInsertId();

        // 2. Insert SENIOR
        $senStmt = $pdo->prepare("
            INSERT INTO SENIOR
                (availment_id, user_id, sen_pension, sen_centenarian_benefit, sen_is_indigent, sen_is_sick, sen_is_bedridden, sen_sss_gsis,
                 sen_cent_birth_cert, sen_cent_marriage_cert, sen_cent_baptismal, sen_first_born_birth_cert, sen_first_born_death_cert)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $senStmt->execute([
            $availment_id, $user_id, $sen_pension, $sen_centenarian_benefit, (int)$sen_is_indigent, (int)$sen_is_sick, (int)$sen_is_bedridden, (int)$sen_sss_gsis,
            $sen_cent_birth_cert, $sen_cent_marriage_cert, $sen_cent_baptismal, $sen_first_born_birth_cert, $sen_first_born_death_cert,
        ]);

        $pdo->commit();
        $svc_labels  = ['scid' => 'SCID Issuance', 'pension' => 'Pension Top-up', 'centenarian' => 'Centenarian Benefit'];
        $success_msg = "Senior Citizen record (<strong>{$svc_labels[$svc_type]}</strong>) for <strong>{$cl_fullname}</strong> submitted successfully.";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error_msg = htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Senior Citizen – MSWDO San Enrique</title>
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

        .screen-panel {
            display: block;
            animation: fadeUp 0.3s ease both;
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

        .field {
            display: block;
            width: 100%;
            border-radius: .75rem;
            border: 1.5px solid #E2E8F0;
            background: #F8FAFC;
            padding: .625rem .875rem;
            font-size: 13px;
            color: #1e293b;
            outline: none;
            font-family: 'DM Sans', sans-serif;
            transition: all .2s;
        }

        .field:focus {
            border-color: #3A5F93;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(58, 95, 147, .1);
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

        .svc-tab {
            transition: all .18s;
            cursor: pointer;
        }

        .svc-tab:hover {
            border-color: #94A3B8;
        }

        .svc-tab.active {
            border-color: #0B2545 !important;
            background: #E8EDF5;
        }

        .svc-tab.active .st-title {
            color: #0B2545;
            font-weight: 700;
        }

        .svc-tab.active .st-icon {
            background: rgba(11, 37, 69, .1);
        }

        .verif-check {
            transition: all .15s;
            cursor: pointer;
        }

        .verif-check:has(input:checked) {
            border-color: #0B2545;
            background: #E8EDF5;
        }

        .verif-check:has(input:checked) span {
            color: #0B2545;
            font-weight: 500;
        }

        .upload-zone {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 130px;
            border: 2px dashed #CBD5E1;
            border-radius: 0.875rem;
            padding: 1.25rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            background: #F8FAFC;
            width: 100%;
            box-sizing: border-box;
        }

        .upload-zone:hover {
            border-color: #3A5F93;
            background: #EBF4FB;
        }

        .upload-zone.has-file {
            border-color: #0B2545;
            background: #E8EDF5;
            border-style: solid;
        }

        .upload-zone input[type=file] {
            display: none;
        }

        .upload-zone .upload-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            gap: 0.25rem;
        }

        .upload-zone .upload-icon {
            font-size: 1.75rem;
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .upload-zone .upload-title {
            font-size: 12px;
            font-weight: 500;
            color: #475569;
            line-height: 1.3;
        }

        .upload-zone .upload-hint {
            font-size: 11px;
            color: #94A3B8;
            line-height: 1.3;
        }

        .upload-zone.has-file .upload-icon {
            font-size: 1.5rem;
        }

        .upload-zone.has-file .upload-title {
            color: #0B2545;
            font-weight: 600;
            font-size: 12px;
            word-break: break-all;
            padding: 0 4px;
        }

        .upload-zone.has-file .upload-hint {
            color: #3A5F93;
            font-size: 10px;
        }

        .copy-badge {
            display: inline-flex;
            padding: 1px 7px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            background: #FEF3C7;
            color: #92400E;
            margin-left: 5px;
        }

        .opt-badge {
            display: inline-flex;
            padding: 1px 7px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 500;
            background: #F1F5F9;
            color: #64748B;
            margin-left: 5px;
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
                <a href="clientslist.php" class="text-slate-400 hover:text-navy-600">Clients</a>
                <span class="text-slate-300">/</span>
                <a href="clientprofile.php?id=<?= $client_id ?>" class="text-slate-400 hover:text-navy-600"><?= $cl_fullname ?></a>
                <span class="text-slate-300">/</span>
                <a href="programavailmentselection.php?client_id=<?= $client_id ?>" class="text-slate-400 hover:text-navy-600">Select Program</a>
                <span class="text-slate-300">/</span>
                <span class="text-navy-600 font-semibold">Senior Citizen Availment</span>
            </div>
            <a href="programavailmentselection.php?client_id=<?= $client_id ?>"
               class="text-[12px] text-slate-500 hover:text-navy-600 flex items-center gap-1.5 transition-colors">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </header>

        <main class="p-6 overflow-y-auto">
            <div class="max-w-3xl mx-auto space-y-5">
                <div class="animate-fade-up">
                    <h1 class="text-xl font-serif text-navy-600">Senior Citizen Availment Form</h1>
                    <p class="text-[13px] text-slate-500 mt-1">SCID issuance, social pension top‑up, and centenarian
                        benefit processing for <span class="font-semibold text-navy-600"><?= $cl_fullname ?></span>.</p>
                </div>

                <?php if ($success_msg): ?>
                <div class="animate-fade-up bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-3.5 flex items-center gap-3 text-[13px]">
                    <i class="fas fa-check-circle text-green-500 text-lg"></i>
                    <span><?= $success_msg ?></span>
                    <a href="clientprofile.php?id=<?= $client_id ?>" class="ml-auto text-[12px] font-semibold text-green-700 underline">Back to Profile</a>
                </div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                <div class="animate-fade-up bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-3.5 flex items-center gap-3 text-[13px]">
                    <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
                    <span><?= $error_msg ?></span>
                </div>
                <?php endif; ?>

                <!-- Client banner -->
                <div class="animate-fade-up-1 bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-4">
                    <!-- <div class="w-11 h-11 rounded-xl bg-navy-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        <?= $cl_initials ?>
                    </div> -->
                    <div class="flex-1">
                        <p class="text-[14px] font-semibold text-navy-600"><?= $cl_fullname ?></p>
                        <p class="text-[11px] text-slate-400"><?= $cl_barangay ?> · <?= $cl_age ?> yrs, <?= $cl_sex ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-slate-400 uppercase tracking-wide">Age Verification</p>
                        <?php if ($cl_eligible): ?>
                        <p class="text-[13px] font-bold text-green-600 mt-0.5"><i class="fas fa-check-circle"></i> <?= $cl_age ?> yrs — Eligible (60+)</p>
                        <?php else: ?>
                        <p class="text-[13px] font-bold text-red-500 mt-0.5"><i class="fas fa-times-circle"></i> <?= $cl_age ?> yrs — Not Eligible</p>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="POST" action="senior_citizen.php?client_id=<?= $client_id ?>" id="seniorForm" enctype="multipart/form-data">
                <input type="hidden" name="action"  value="submit">
                <input type="hidden" name="svcType" id="svcTypeInput" value="scid">

                <!-- Service Type -->
                <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-list-ul"></i></div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Service Type</h2>
                            <p class="text-[11px] text-slate-400">Select the specific senior citizen service</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-3 gap-3 mb-5" id="seniorSvcSelector">
                            <div onclick="setSeniorSvc(this,'scid')"
                                class="svc-tab active border-2 border-navy-600 bg-navy-50 rounded-2xl p-4 text-center">
                                <div
                                    class="st-icon w-10 h-10 rounded-xl bg-navy-100 flex items-center justify-center mx-auto mb-2">
                                    <i class="fas fa-id-card text-navy-600 text-lg"></i></div>
                                <p class="st-title text-[12px] font-bold text-navy-700">SCID Issuance</p>
                                <p class="text-[10px] text-slate-400 mt-1">Senior Citizen ID — once only</p>
                            </div>
                            <div onclick="setSeniorSvc(this,'pension')"
                                class="svc-tab border-2 border-slate-200 rounded-2xl p-4 text-center">
                                <div
                                    class="st-icon w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center mx-auto mb-2">
                                    <i class="fas fa-coins text-navy-500 text-lg"></i></div>
                                <p class="st-title text-[12px] font-semibold text-slate-600">Pension Top‑up</p>
                                <p class="text-[10px] text-slate-400 mt-1">Monthly — if indigent</p>
                            </div>
                            <div onclick="setSeniorSvc(this,'centenarian')"
                                class="svc-tab border-2 border-slate-200 rounded-2xl p-4 text-center">
                                <div
                                    class="st-icon w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center mx-auto mb-2">
                                    <i class="fas fa-birthday-cake text-navy-500 text-lg"></i></div>
                                <p class="st-title text-[12px] font-semibold text-slate-600">Centenarian Benefit</p>
                                <p class="text-[10px] text-slate-400 mt-1">Age 100+ only</p>
                            </div>
                        </div>

                        <!-- SCID panel -->
                        <div id="svc-scid" class="space-y-4">
                            <div class="bg-navy-50 border border-navy-100 rounded-xl px-4 py-3 flex items-start gap-3">
                                <i class="fas fa-info-circle text-navy-500 text-lg mt-0.5"></i>
                                <p class="text-[12px] text-navy-700">SCID issued <strong>once only</strong>. For lost
                                    IDs use Affidavit of Loss. For expired IDs, upload the old card.</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="field-label req">SCID Number</label><input type="text" name="scidNumber" class="field"
                                        placeholder="SC-XXXX-XXXX-XXXX" id="scidNumber"></div>
                                <div><label class="field-label req">Date Issued</label><input type="date" name="scidDate" id="scidDate" class="field">
                                </div>
                            </div>
                            <div>
                                <label class="field-label">ID Type</label>
                                <div class="flex gap-3 mt-1">
                                    <label
                                        class="flex-1 flex items-center justify-center gap-2 border-2 border-slate-200 rounded-xl py-2.5 cursor-pointer text-[12px] font-medium has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50 has-[:checked]:text-navy-700"><input
                                            type="radio" name="scidType" value="New" class="hidden"> <i
                                            class="fas fa-plus-circle"></i> New</label>
                                    <label
                                        class="flex-1 flex items-center justify-center gap-2 border-2 border-slate-200 rounded-xl py-2.5 cursor-pointer text-[12px] font-medium has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50 has-[:checked]:text-navy-700"><input
                                            type="radio" name="scidType" value="Renewal" class="hidden"> <i class="fas fa-sync-alt"></i>
                                        Renewal</label>
                                    <label
                                        class="flex-1 flex items-center justify-center gap-2 border-2 border-slate-200 rounded-xl py-2.5 cursor-pointer text-[12px] font-medium has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50 has-[:checked]:text-navy-700"><input
                                            type="radio" name="scidType" value="Replacement" class="hidden"> <i class="fas fa-file-alt"></i>
                                        Replacement</label>
                                </div>
                            </div>
                            <div>
                                <label class="field-label">Required Documents</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <div class="field-label flex items-center text-[10px]">Birth Certificate (PSA)
                                            <span class="copy-badge">1 orig + 2 copies</span></div><label
                                            class="upload-zone" id="uz-sc-birth"><input type="file" name="sc_birth_cert"
                                                onchange="fileSelected(this,'uz-sc-birth')">
                                            <div class="upload-content"><i class="fas fa-file-alt text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div>
                                        <div class="field-label flex items-center text-[10px]">Valid ID <span
                                                class="copy-badge">1 orig + 2 copies</span></div><label
                                            class="upload-zone" id="uz-sc-id"><input type="file" name="sc_valid_id"
                                                onchange="fileSelected(this,'uz-sc-id')">
                                            <div class="upload-content"><i class="fas fa-id-card text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div>
                                        <div class="field-label flex items-center text-[10px]">1×1 Photo <span
                                                class="copy-badge">2 pcs</span></div><label class="upload-zone"
                                            id="uz-sc-photo"><input type="file" name="sc_photo"
                                                onchange="fileSelected(this,'uz-sc-photo')">
                                            <div class="upload-content"><i class="fas fa-camera text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div>
                                        <div class="field-label flex items-center text-[10px]">Expired SCID <span
                                                class="opt-badge">If renewal</span></div><label class="upload-zone"
                                            id="uz-sc-old"><input type="file" name="sc_expired_id" onchange="fileSelected(this,'uz-sc-old')">
                                            <div class="upload-content"><i class="fas fa-history text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pension panel -->
                        <div id="svc-pension" class="hidden space-y-4">
                            <div class="bg-navy-50 border border-navy-100 rounded-xl px-4 py-3 flex items-start gap-3">
                                <i class="fas fa-exclamation-triangle text-navy-500 text-lg mt-0.5"></i>
                                <p class="text-[12px] text-navy-700">Requires <strong>all four eligibility
                                        criteria</strong> to be verified. Pension is released monthly.</p>
                            </div>
                            <div><label class="field-label req">Top‑up Amount (₱ / month)</label>
                                <div class="relative max-w-xs"><span
                                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[13px]">₱</span><input
                                        type="number" min="0" name="pensionAmount" id="pensionAmount" class="field pl-7" placeholder="e.g. 500"></div>
                            </div>
                            <div>
                                <label class="field-label req">Eligibility Verification — All 4 Required</label>
                                <div class="grid grid-cols-2 gap-3 mt-2">
                                    <label
                                        class="verif-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                            type="checkbox" name="chk_indigent" value="1" class="w-4 h-4 accent-navy-600 flex-shrink-0">
                                        <div><span class="text-[12px] font-medium text-slate-700 block"><i
                                                    class="fas fa-clipboard-check mr-1"></i> Classified as
                                                Indigent</span></div>
                                    </label>
                                    <label
                                        class="verif-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                            type="checkbox" name="chk_sick" value="1" class="w-4 h-4 accent-navy-600 flex-shrink-0">
                                        <div><span class="text-[12px] font-medium text-slate-700 block"><i
                                                    class="fas fa-heartbeat mr-1"></i> Sick or Frail</span></div>
                                    </label>
                                    <label
                                        class="verif-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                            type="checkbox" name="chk_bedridden" value="1" class="w-4 h-4 accent-navy-600 flex-shrink-0">
                                        <div><span class="text-[12px] font-medium text-slate-700 block"><i
                                                    class="fas fa-bed mr-1"></i> Bedridden / Low‑mobility</span></div>
                                    </label>
                                    <label
                                        class="verif-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                            type="checkbox" name="chk_no_sss" value="1" class="w-4 h-4 accent-navy-600 flex-shrink-0">
                                        <div><span class="text-[12px] font-medium text-slate-700 block"><i
                                                    class="fas fa-times-circle mr-1"></i> No SSS / GSIS Pension</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div><label class="field-label">Remarks</label><textarea class="field" name="pensionRemarks" rows="2"
                                    placeholder="Additional notes..."></textarea></div>
                        </div>

                        <!-- Centenarian panel -->
                        <div id="svc-centenarian" class="hidden space-y-4">
                            <div class="bg-navy-50 border border-navy-100 rounded-xl px-4 py-3 flex items-start gap-3">
                                <i class="fas fa-birthday-cake text-navy-500 text-lg mt-0.5"></i>
                                <p class="text-[12px] text-navy-700">Available only to clients aged <strong>100 years
                                        and above</strong>.</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="field-label req">Benefit Amount (₱)</label>
                                    <div class="relative"><span
                                            class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[13px]">₱</span><input
                                            type="number" min="0" name="centAmount" id="centAmount" class="field pl-7" placeholder="e.g. 100000"></div>
                                </div>
                                <div><label class="field-label req">Date of Birth (Verified)</label><input type="date"
                                        name="centDOB" id="centDOB" class="field"></div>
                            </div>
                            <div>
                                <label class="field-label">Required Documents</label>
                                <div class="grid grid-cols-2 gap-3 mt-1">
                                    <div>
                                        <div class="field-label flex items-center text-[10px]">PSA Birth Certificate
                                            <span class="copy-badge">Required</span></div><label class="upload-zone"
                                            id="uz-cent-birth"><input type="file" name="cent_birth_cert"
                                                onchange="fileSelected(this,'uz-cent-birth')">
                                            <div class="upload-content"><i class="fas fa-file-alt text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div>
                                        <div class="field-label flex items-center text-[10px]">PSA Marriage Contract
                                            <span class="opt-badge">If applicable</span></div><label class="upload-zone"
                                            id="uz-cent-marr"><input type="file" name="cent_marr_cert"
                                                onchange="fileSelected(this,'uz-cent-marr')">
                                            <div class="upload-content"><i class="fas fa-ring text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div>
                                        <div class="field-label flex items-center text-[10px]">Baptismal Certificate
                                            <span class="copy-badge">Original</span></div><label class="upload-zone"
                                            id="uz-cent-bap"><input type="file" name="cent_baptismal"
                                                onchange="fileSelected(this,'uz-cent-bap')">
                                            <div class="upload-content"><i class="fas fa-church text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div>
                                        <div class="field-label flex items-center text-[10px]">Birth Cert. of First Born
                                            <span class="copy-badge">Required</span></div><label class="upload-zone"
                                            id="uz-cent-fb"><input type="file" name="cent_fb_birth"
                                                onchange="fileSelected(this,'uz-cent-fb')">
                                            <div class="upload-content"><i class="fas fa-baby text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-span-2">
                                        <div class="field-label flex items-center text-[10px]">Death Cert. of First Born
                                            <span class="opt-badge">If deceased</span></div><label class="upload-zone"
                                            id="uz-cent-fd"><input type="file" name="cent_fb_death"
                                                onchange="fileSelected(this,'uz-cent-fd')">
                                            <div class="upload-content"><i
                                                    class="fas fa-file-medical-alt text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="submit" form="seniorForm"
                        class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500">Submit
                        Senior Citizen Record</button>
                </div>
                </form>
            </div>
        </main>
        <footer class="border-t border-slate-200 bg-white px-6 py-3 text-[11px] text-slate-400"><span>MSWDO San Enrique
                Information System</span></footer>
    </div>

    <div id="toast"
        class="fixed bottom-6 right-6 bg-navy-600 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-navy-400"></i><span id="toastMsg">Saved!</span>
    </div>

    <script>
        function setSeniorSvc(el, svc) {
            document.querySelectorAll('#seniorSvcSelector .svc-tab').forEach(t => {
                t.classList.remove('active', 'border-navy-600', 'bg-navy-50');
                t.querySelector('.st-title').className = 'st-title text-[12px] font-semibold text-slate-600';
            });
            el.classList.add('active', 'border-navy-600', 'bg-navy-50');
            el.querySelector('.st-title').className = 'st-title text-[12px] font-bold text-navy-700';
            ['scid', 'pension', 'centenarian'].forEach(s => {
                document.getElementById('svc-' + s).classList.toggle('hidden', s !== svc);
            });
            // Sync hidden input so PHP knows which service was selected
            document.getElementById('svcTypeInput').value = svc;
        }

        function fileSelected(input, zoneId) {
            if (!input.files || !input.files[0]) return;
            const zone = document.getElementById(zoneId);
            const name = input.files[0].name;
            zone.classList.add('has-file');
            zone.querySelector('.upload-content').innerHTML = `<i class="fas fa-check-circle text-navy-600 text-2xl mb-1"></i><p class="text-[12px] font-semibold text-navy-700">${name}</p><p class="text-[10px] text-navy-500">File ready</p>`;
        }

        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            document.getElementById('toastMsg').textContent = msg;
            t.querySelector('i').className = type === 'error' ? 'fas fa-exclamation-circle text-red-400' : 'fas fa-check-circle text-navy-400';
            t.style.backgroundColor = type === 'error' ? '#7F1D1D' : '#0B2545';
            t.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
            t.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => { t.classList.add('opacity-0', 'translate-y-4'); t.classList.remove('opacity-100', 'translate-y-0'); }, 3000);
        }

        // Client-side validation before native form submit
        document.getElementById('seniorForm').addEventListener('submit', function(e) {
            const svc = document.getElementById('svcTypeInput').value;

            if (svc === 'scid') {
                const scidNum = document.getElementById('scidNumber');
                if (!scidNum.value.trim()) {
                    e.preventDefault();
                    showToast('SCID Number is required.', 'error');
                    scidNum.focus();
                    return;
                }
                const scidDate = document.getElementById('scidDate');
                if (!scidDate.value) {
                    e.preventDefault();
                    showToast('Date Issued is required.', 'error');
                    scidDate.focus();
                    return;
                }
            } else if (svc === 'pension') {
                const amt = document.getElementById('pensionAmount');
                if (!amt.value || parseFloat(amt.value) <= 0) {
                    e.preventDefault();
                    showToast('Please enter a valid pension top-up amount.', 'error');
                    amt.focus();
                    return;
                }
            } else if (svc === 'centenarian') {
                const amt = document.getElementById('centAmount');
                if (!amt.value || parseFloat(amt.value) <= 0) {
                    e.preventDefault();
                    showToast('Please enter a valid benefit amount.', 'error');
                    amt.focus();
                    return;
                }
                const dob = document.getElementById('centDOB');
                if (!dob.value) {
                    e.preventDefault();
                    showToast('Verified Date of Birth is required.', 'error');
                    dob.focus();
                    return;
                }
            }
        });
    </script>
</body>

</html>