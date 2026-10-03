<?php
require 'auth.php';
requireRole(['Admin', 'Social Worker', 'Staff']);
require 'db_connect.php';

$barangay_id = (int) ($_GET['barangay_id'] ?? 0);
if ($barangay_id <= 0) {
    header("Location: barangaylist.php");
    exit;
}

$barangayDB = $pdo->prepare("SELECT barangay_id, barangay_name FROM BARANGAY WHERE barangay_id = ?");
$barangayDB->execute([$barangay_id]);
$barangay = $barangayDB->fetch(PDO::FETCH_ASSOC);
if (!$barangay) {
    header("Location: barangaylist.php");
    exit;
}
$barangay_name = htmlspecialchars($barangay['barangay_name']);

$programDB = $pdo->prepare("
    SELECT prog_annual_budget, prog_remaining_budget
    FROM PROGRAM
    WHERE program_name = 'Day Care Center Program'
    LIMIT 1
");
$programDB->execute();
$prog = $programDB->fetch(PDO::FETCH_ASSOC);

$totalBudget = $prog ? (float) $prog['prog_annual_budget'] : 0;
$remainingBudget = $prog ? (float) $prog['prog_remaining_budget'] : 0;

$progID_DB = $pdo->prepare("SELECT program_id FROM PROGRAM WHERE program_name = 'Day Care Center Program' LIMIT 1");
$progID_DB->execute();
$program_id = (int) ($progID_DB->fetchColumn() ?: 0);

$clientsDB = $pdo->prepare("
    SELECT client_id,
           CONCAT(cl_firstname, ' ', COALESCE(cl_middlename, ''), ' ', cl_lastname,
                  CASE WHEN cl_suffix IS NOT NULL AND cl_suffix != '' THEN CONCAT(' ', cl_suffix) ELSE '' END
           ) AS full_name
    FROM CLIENT
    WHERE brgy_id = ?
    ORDER BY cl_lastname, cl_firstname
");
$clientsDB->execute([$barangay_id]);
$clients = $clientsDB->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_fund'])) {

    $av_amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $av_date_applied = trim($_POST['dateApplied'] ?? '');
    $event_date = trim($_POST['eventDate'] ?? '');
    $event_name = trim($_POST['eventName'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $claimant_name = trim($_POST['claimantName'] ?? '');
    $claimant_role = trim($_POST['claimantRole'] ?? '');
    $client_id_raw = trim($_POST['client_id'] ?? '');
    $client_id = ($client_id_raw !== '') ? (int) $client_id_raw : null;
    $household_num = trim($_POST['householdNum'] ?? '');
    $daycare_status = trim($_POST['daycareStatus'] ?? 'Active');
    $save_as_draft = isset($_POST['save_draft']);

    if (!$av_amount || $av_amount <= 0)
        $errors[] = 'Enter a valid requested amount.';
    if ($av_amount > $remainingBudget)
        $errors[] = 'Requested amount exceeds the remaining budget (₱' . number_format($remainingBudget, 2) . ').';
    if (!$av_date_applied)
        $errors[] = 'Select the Application Date.';
    if (!$event_date)
        $errors[] = 'Select the Event Date.';
    if ($event_name === '')
        $errors[] = 'Enter the name of the event / activity.';
    if ($claimant_name === '')
        $errors[] = 'Enter the claimant name.';

    $upload_dir = __DIR__ . '/uploads/daycare/';
    if (!is_dir($upload_dir))
        mkdir($upload_dir, 0755, true);

    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
    $doc_paths = [];
    $doc_keys = ['proposal', 'valid_id', 'indigency', 'residency'];

    foreach ($doc_keys as $key) {
        if (!empty($_FILES[$key]['name'])) {
            $ftype = mime_content_type($_FILES[$key]['tmp_name']);
            if (!in_array($ftype, $allowed_types)) {
                $errors[] = "Invalid file type for " . ucfirst(str_replace('_', ' ', $key)) . ". Only PDF/images allowed.";
            } else {
                $ext = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
                $fname = $key . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES[$key]['tmp_name'], $upload_dir . $fname)) {
                    $doc_paths[$key] = 'uploads/daycare/' . $fname;
                } else {
                    $errors[] = "Failed to upload " . ucfirst(str_replace('_', ' ', $key)) . ". Check folder permissions.";
                }
            }
        } else {
            $doc_paths[$key] = null;
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $av_status = $save_as_draft ? 'Pending' : 'Pending'; // always Pending on submit, the Admin approves later

            $remarks = json_encode([
                'event_name' => $event_name,
                'event_date' => $event_date,
                'purpose' => $purpose,
                'claimant_name' => $claimant_name,
                'claimant_role' => $claimant_role,
                'is_draft' => $save_as_draft,
                'docs' => $doc_paths,
            ], JSON_UNESCAPED_UNICODE);

            // Insert into AVAILMENT
            $avDB = $pdo->prepare("
                INSERT INTO AVAILMENT
                    (client_id, program_id, user_id, av_date_applied, av_amount, av_status, av_remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $avDB->execute([
                $client_id,
                $program_id ?: null,
                $_SESSION['user_id'] ?? null,
                $av_date_applied,
                $av_amount,
                $av_status,
                $remarks,
            ]);
            $availment_id = (int) $pdo->lastInsertId();

            // Insert into daycare
            $dcDB = $pdo->prepare("
                INSERT INTO daycare
                    (availment_id, user_id, daycare_household_num, daycare_status)
                VALUES (?, ?, ?, ?)
            ");
            $dcDB->execute([
                $availment_id,
                $_SESSION['user_id'] ?? null,
                $household_num ?: null,
                $daycare_status,
            ]);

            // Update remaining budget in PROGRAM
            if ($program_id && !$save_as_draft) {
                $updBudget = $pdo->prepare("
                    UPDATE PROGRAM
                    SET prog_remaining_budget = prog_remaining_budget - ?
                    WHERE program_id = ?
                ");
                $updBudget->execute([$av_amount, $program_id]);
                $remainingBudget -= $av_amount; // reflect in page
            }

            $pdo->commit();
            $success = $save_as_draft ? 'draft' : 'submitted';

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Database error: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Day Care Center Program Fund Request – MSWDO San Enrique</title>
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

        .limit-row {
            transition: background .1s;
        }

        .limit-row:hover {
            background: #F8FAFC;
        }

        /* Autocomplete dropdown */
        #claimant-dropdown {
            position: absolute;
            z-index: 50;
            background: #fff;
            border: 1.5px solid #E2E8F0;
            border-radius: 0.75rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.10);
            max-height: 220px;
            overflow-y: auto;
            width: 100%;
        }

        #claimant-dropdown .dd-item {
            padding: 9px 14px;
            font-size: 13px;
            cursor: pointer;
            border-bottom: 1px solid #F1F5F9;
            color: #1e293b;
        }

        #claimant-dropdown .dd-item:last-child {
            border-bottom: none;
        }

        #claimant-dropdown .dd-item:hover,
        #claimant-dropdown .dd-item.active {
            background: #E8EDF5;
        }

        #claimant-dropdown .dd-item .dd-id {
            font-size: 10px;
            color: #94A3B8;
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

        <!-- Header -->
        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <a href="barangaylist.php" class="text-slate-400 hover:text-navy-600">Barangay List</a>
                <span class="text-slate-300">/</span>
                <a href="barangayfunds.php?barangay_id=<?= $barangay_id ?>"
                    class="text-slate-400 hover:text-navy-600"><?= $barangay_name ?></a>
                <span class="text-slate-300">/</span>
                <span class="text-navy-600 font-semibold">Day Care Center Fund Request</span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="triggerDraft()"
                    class="text-[12px] font-medium text-navy-600 border border-navy-200 bg-navy-50 rounded-lg px-3 py-1.5 hover:bg-navy-100">
                    Save Draft
                </button>
            </div>
        </header>

        <main class="p-6 overflow-y-auto">
            <div class="max-w-3xl mx-auto space-y-5">

                <!-- Title -->
                <div class="animate-fade-up">
                    <h1 class="text-xl font-serif text-navy-600">Day Care Center Fund Request</h1>
                    <p class="text-[13px] text-slate-500 mt-1">
                        Request funds for day care center events and improvements
                        &mdash; <span class="font-semibold text-navy-600"><?= $barangay_name ?></span>
                    </p>
                </div>

                <!-- Success / Error Alerts -->
                <?php if ($success === 'submitted'): ?>
                    <div
                        class="animate-fade-up bg-green-50 border border-green-200 text-green-700 rounded-xl px-5 py-3.5 flex items-center gap-3 text-[13px]">
                        <i class="fas fa-check-circle text-green-500 text-lg"></i>
                        <span>Day Care Center fund request submitted successfully! It is now pending approval.</span>
                    </div>
                <?php elseif ($success === 'draft'): ?>
                    <div
                        class="animate-fade-up bg-blue-50 border border-blue-200 text-blue-700 rounded-xl px-5 py-3.5 flex items-center gap-3 text-[13px]">
                        <i class="fas fa-save text-blue-500 text-lg"></i>
                        <span>Draft saved successfully.</span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div
                        class="animate-fade-up bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-3.5 text-[13px]">
                        <div class="flex items-center gap-2 font-semibold mb-2">
                            <i class="fas fa-exclamation-circle"></i> Please fix the following:
                        </div>
                        <ul class="list-disc list-inside space-y-1">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form method="POST" enctype="multipart/form-data" id="fundForm">
                    <input type="hidden" name="save_draft" id="saveDraftInput" value="">

                    <!-- Transaction Details -->
                    <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden mb-5">
                        <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                            <div
                                class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Transaction Details</h2>
                        </div>
                        <div class="p-6 space-y-5">

                            <!-- Row 1: Amount / Dates -->
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="field-label req">Requested Amount (₱)</label>
                                    <div class="relative">
                                        <span
                                            class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[13px]">₱</span>
                                        <input type="number" min="0" step="0.01" name="amount" class="field pl-7"
                                            id="amountField" placeholder="0.00" oninput="checkAmount(this)"
                                            value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>">
                                    </div>
                                </div>
                                <div>
                                    <label class="field-label req">Application Date</label>
                                    <input type="date" name="dateApplied" class="field" id="dateApplied"
                                        value="<?= htmlspecialchars($_POST['dateApplied'] ?? '') ?>">
                                </div>
                                <div>
                                    <label class="field-label req">Event Date</label>
                                    <input type="date" name="eventDate" class="field" id="eventDate"
                                        value="<?= htmlspecialchars($_POST['eventDate'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Row 2: Event / Purpose -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label req">Name of Event / Activity</label>
                                    <input type="text" name="eventName" class="field" id="eventName"
                                        placeholder="e.g. Christmas Party 2025"
                                        value="<?= htmlspecialchars($_POST['eventName'] ?? '') ?>">
                                </div>
                                <div>
                                    <label class="field-label">Purpose / Description</label>
                                    <textarea name="purpose" class="field" rows="2"
                                        placeholder="Brief description of the activity"><?= htmlspecialchars($_POST['purpose'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <!-- Row 3: Claimant (with autocomplete) / Role -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label req">Claimant</label>
                                    <p class="text-[11px] text-slate-400 mb-1.5">
                                        Search from registered clients or type a custom name.
                                    </p>
                                    <!-- Hidden client_id (populated when selecting from dropdown) -->
                                    <input type="hidden" name="client_id" id="clientIdField"
                                        value="<?= htmlspecialchars($_POST['client_id'] ?? '') ?>">
                                    <div class="relative">
                                        <input type="text" name="claimantName" class="field pr-8" id="claimantName"
                                            placeholder="Search or type claimant name" autocomplete="off"
                                            value="<?= htmlspecialchars($_POST['claimantName'] ?? '') ?>"
                                            oninput="filterClients(this.value)" onfocus="filterClients(this.value)"
                                            onblur="closeDropdownDelayed()">
                                        <span id="clientBadge"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-navy-600 bg-navy-50 px-1.5 py-0.5 rounded-full hidden">
                                            Linked
                                        </span>
                                        <div id="claimant-dropdown" class="hidden"></div>
                                    </div>
                                </div>
                                <div>
                                    <label class="field-label req">Role</label>
                                    <input type="text" name="claimantRole" class="field"
                                        placeholder="Position or role of the claimant"
                                        value="<?= htmlspecialchars($_POST['claimantRole'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Row 4: Day Care-specific fields -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Household Number</label>
                                    <input type="text" name="householdNum" class="field"
                                        placeholder="Day Care Household Number (optional)"
                                        value="<?= htmlspecialchars($_POST['householdNum'] ?? '') ?>">
                                </div>
                                <div>
                                    <label class="field-label req">Day Care Status</label>
                                    <select name="daycareStatus" class="field">
                                        <option value="Active" <?= (($_POST['daycareStatus'] ?? 'Active') === 'Active') ? 'selected' : '' ?>>Active</option>
                                        <option value="Graduated" <?= (($_POST['daycareStatus'] ?? '') === 'Graduated') ? 'selected' : '' ?>>Graduated</option>
                                        <option value="Suspended" <?= (($_POST['daycareStatus'] ?? '') === 'Suspended') ? 'selected' : '' ?>>Suspended</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Budget Check Panel -->
                            <div class="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden" id="limitPanel">
                                <div
                                    class="px-4 py-2.5 bg-slate-100/80 border-b border-slate-200 flex items-center justify-between">
                                    <p class="text-[11px] font-semibold text-navy-600">Automatic Budget Check — Day Care Center
                                        Fund</p>
                                    <button type="button" onclick="runLimitCheck()"
                                        class="text-[11px] text-blue-600 font-medium hover:underline">Recheck</button>
                                </div>
                                <div id="limitRows" class="divide-y divide-slate-100">
                                    <div class="limit-row flex items-center justify-between px-4 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-chart-line text-slate-500 text-sm"></i>
                                            <span class="text-[12px] text-slate-600">Budget available</span>
                                        </div>
                                        <span class="text-[12px] font-semibold text-navy-600">
                                            ₱<?= number_format($remainingBudget, 2) ?> remaining
                                            <?php if ($totalBudget > 0): ?>
                                                <span class="text-slate-400 font-normal">/
                                                    ₱<?= number_format($totalBudget, 2) ?> total</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="limit-row flex items-center justify-between px-4 py-2.5"
                                        id="amountCheck">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-dollar-sign text-slate-500 text-sm"></i>
                                            <span class="text-[12px] text-slate-600">Amount within budget</span>
                                        </div>
                                        <span class="text-[12px] font-semibold text-slate-400" id="amountCheckVal">—
                                            Enter amount above</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div><!-- /Transaction Details -->

                    <!-- Required Documents -->
                    <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden mb-5">
                        <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                            <div
                                class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                                <i class="fas fa-paperclip"></i>
                            </div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Required Documents</h2>
                        </div>
                        <div class="p-6 grid grid-cols-2 gap-4">
                            <div>
                                <div class="field-label">Activity Proposal</div>
                                <label class="upload-zone" id="uz-proposal">
                                    <input type="file" name="proposal" accept=".pdf,.jpg,.jpeg,.png"
                                        onchange="fileSelected(this,'uz-proposal')">
                                    <div class="upload-content">
                                        <i class="fas fa-file-alt text-2xl mb-1 text-slate-400"></i>
                                        <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                        <p class="text-[10px] text-slate-400">PDF or image</p>
                                    </div>
                                </label>
                            </div>
                            <div>
                                <div class="field-label">Valid ID</div>
                                <label class="upload-zone" id="uz-id">
                                    <input type="file" name="valid_id" accept=".pdf,.jpg,.jpeg,.png"
                                        onchange="fileSelected(this,'uz-id')">
                                    <div class="upload-content">
                                        <i class="fas fa-id-card text-2xl mb-1 text-slate-400"></i>
                                        <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                        <p class="text-[10px] text-slate-400">PDF or image</p>
                                    </div>
                                </label>
                            </div>
                            <div>
                                <div class="field-label">Certificate of Indigency</div>
                                <label class="upload-zone" id="uz-indigency">
                                    <input type="file" name="indigency" accept=".pdf,.jpg,.jpeg,.png"
                                        onchange="fileSelected(this,'uz-indigency')">
                                    <div class="upload-content">
                                        <i class="fas fa-file-alt text-2xl mb-1 text-slate-400"></i>
                                        <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                        <p class="text-[10px] text-slate-400">PDF or image</p>
                                    </div>
                                </label>
                            </div>
                            <div>
                                <div class="field-label">Certificate of Residency</div>
                                <label class="upload-zone" id="uz-residency">
                                    <input type="file" name="residency" accept=".pdf,.jpg,.jpeg,.png"
                                        onchange="fileSelected(this,'uz-residency')">
                                    <div class="upload-content">
                                        <i class="fas fa-home text-2xl mb-1 text-slate-400"></i>
                                        <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                        <p class="text-[10px] text-slate-400">PDF or image</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div><!-- /Documents -->

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <a href="barangayfunds.php?barangay_id=<?= $barangay_id ?>"
                            class="text-[13px] font-medium text-slate-500 border border-slate-200 bg-white rounded-xl px-5 py-2.5 hover:bg-slate-50">
                            Cancel
                        </a>
                        <button type="submit" name="submit_fund"
                            class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500 transition-colors">
                            Submit Fund Request
                        </button>
                    </div>

                </form>

            </div>
        </main>

        <footer class="border-t border-slate-200 bg-white px-6 py-3 text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <!-- Toast -->
    <div id="toast"
        class="fixed bottom-6 right-6 bg-navy-600 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-navy-400" id="toastIcon"></i>
        <span id="toastMsg">Saved!</span>
    </div>

    <script>

        const remainingBudget = <?= json_encode($remainingBudget) ?>;

        // Client list for autocomplete 
        const clientList = <?= json_encode(
            array_map(fn($c) => [
                'id' => $c['client_id'],
                'name' => trim(preg_replace('/\s+/', ' ', $c['full_name'])),
            ], $clients)
        ) ?>;

        let dropdownOpen = false;
        let selectedClientId = document.getElementById('clientIdField').value || null;

        function filterClients(query) {
            const dropdown = document.getElementById('claimant-dropdown');
            const q = query.trim().toLowerCase();

            // Show all when focused empty, or filter by name
            const filtered = q.length === 0
                ? clientList.slice(0, 10)
                : clientList.filter(c => c.name.toLowerCase().includes(q)).slice(0, 10);

            if (filtered.length === 0 && q.length > 0) {
                dropdown.classList.add('hidden');
                return;
            }

            let html = '';
            filtered.forEach((c, i) => {
                html += `<div class="dd-item" data-id="${c.id}" data-name="${escapeHtml(c.name)}"
                              onmousedown="selectClient(${c.id}, '${escapeHtml(c.name)}')">${c.name}
                           <div class="dd-id">Client ID: ${c.id}</div>
                         </div>`;
            });
            if (q.length > 0) {
                html += `<div class="dd-item text-slate-400 italic" onmousedown="useCustomName('${escapeHtml(query)}')">
                            <i class="fas fa-pencil-alt mr-1 text-[10px]"></i>Use "<strong>${escapeHtml(query)}</strong>" as custom name
                         </div>`;
            }

            dropdown.innerHTML = html;
            dropdown.classList.remove('hidden');
            dropdownOpen = true;
        }

        function selectClient(id, name) {
            document.getElementById('claimantName').value = name;
            document.getElementById('clientIdField').value = id;
            selectedClientId = id;
            showClientBadge(true);
            closeDropdown();
        }

        function useCustomName(name) {
            document.getElementById('claimantName').value = name;
            document.getElementById('clientIdField').value = '';
            selectedClientId = null;
            showClientBadge(false);
            closeDropdown();
        }

        function showClientBadge(show) {
            const badge = document.getElementById('clientBadge');
            if (show) badge.classList.remove('hidden');
            else badge.classList.add('hidden');
        }

        function closeDropdown() {
            document.getElementById('claimant-dropdown').classList.add('hidden');
            dropdownOpen = false;
        }

        function closeDropdownDelayed() {
            setTimeout(closeDropdown, 150);
        }

        // Clear linked client if user manually edits the field
        document.getElementById('claimantName').addEventListener('input', function () {
            document.getElementById('clientIdField').value = '';
            selectedClientId = null;
            showClientBadge(false);
        });

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        //  Budget check 
        function checkAmount(input) {
            const val = parseFloat(input.value);
            const el = document.getElementById('amountCheckVal');
            if (!val || isNaN(val)) {
                el.innerHTML = '— Enter amount above';
                el.className = 'text-[12px] font-semibold text-slate-400';
            } else if (val > remainingBudget) {
                el.innerHTML = '<i class="fas fa-times-circle text-red-500 mr-1"></i> Exceeds remaining budget';
                el.className = 'text-[12px] font-semibold text-red-500';
            } else {
                el.innerHTML = `<i class="fas fa-check-circle text-navy-600 mr-1"></i> ₱${val.toLocaleString()} — within budget`;
                el.className = 'text-[12px] font-semibold text-navy-600';
            }
        }

        function runLimitCheck() {
            const input = document.getElementById('amountField');
            if (input) checkAmount(input);
            showToast('Budget check refreshed ✓');
        }

        //  File upload UI 
        function fileSelected(input, zoneId) {
            if (!input.files || !input.files[0]) return;
            const zone = document.getElementById(zoneId);
            const name = input.files[0].name;
            const size = (input.files[0].size / 1024).toFixed(1) + ' KB';
            zone.classList.add('has-file');
            zone.querySelector('.upload-content').innerHTML =
                `<i class="fas fa-check-circle text-navy-600 text-2xl mb-1"></i>
                 <p class="text-[12px] font-semibold text-navy-700">${name}</p>
                 <p class="text-[10px] text-navy-500">${size} — File ready</p>`;
        }

        //  Toast 
        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            const icon = document.getElementById('toastIcon');
            document.getElementById('toastMsg').textContent = msg;
            icon.className = type === 'error'
                ? 'fas fa-exclamation-circle text-red-400'
                : 'fas fa-check-circle text-navy-400';
            t.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
            t.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => {
                t.classList.add('opacity-0', 'translate-y-4');
                t.classList.remove('opacity-100', 'translate-y-0');
            }, 3000);
        }

        //  Draft / Submit 
        function triggerDraft() {
            document.getElementById('saveDraftInput').name = 'save_draft';
            document.getElementById('saveDraftInput').value = '1';
            document.getElementById('fundForm').submit();
        }

        // Auto-show toast on page load if PHP set success
        <?php if ($success === 'submitted'): ?>
            window.addEventListener('DOMContentLoaded', () => showToast('Day Care Center fund request submitted ✓'));
        <?php elseif ($success === 'draft'): ?>
            window.addEventListener('DOMContentLoaded', () => showToast('Draft saved!'));
        <?php endif; ?>

        // Pre-fill budget check if amount was posted back
        window.addEventListener('DOMContentLoaded', () => {
            const af = document.getElementById('amountField');
            if (af && af.value) checkAmount(af);
            // Restore client badge if client_id was posted back
            const cid = document.getElementById('clientIdField').value;
            if (cid) showClientBadge(true);
        });
    </script>
</body>

</html>