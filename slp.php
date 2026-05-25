<?php
require 'auth.php';
requireRole(['Admin', 'Social Worker', 'Staff']);
require 'db_connect.php';

// ── Session user ──────────────────────────────────────────────────────────────
$user_id = $_SESSION['user_id'] ?? null;

// ── Resolve & validate client ─────────────────────────────────────────────────
$client_id = (int)($_GET['client_id'] ?? 0);
if ($client_id <= 0) { header("Location: clientslist.php"); exit; }

$stmt = $pdo->prepare("SELECT cl_firstname, cl_lastname FROM CLIENT WHERE client_id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();
if (!$client) { header("Location: clientslist.php"); exit; }
$client_name = htmlspecialchars($client['cl_firstname'] . ' ' . $client['cl_lastname']);

// ── Fetch SLP program row ─────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM PROGRAM WHERE program_name = 'SLP' LIMIT 1");
$stmt->execute();
$program     = $stmt->fetch();
$program_id  = $program['program_id']          ?? null;
$annual      = $program['prog_annual_budget']   ?? 0;
$remaining   = $program['prog_remaining_budget']?? 0;
$spent       = $annual - $remaining;
$pct_used    = $annual > 0 ? round(($spent / $annual) * 100, 1) : 0;

// ── SLP-specific eligibility checks ──────────────────────────────────────────
// SLP rule: client can only get ONE project. Additional funding only if
// previous project was profitable. We check if ANY prior SLP availment exists.
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM AVAILMENT
    WHERE client_id = ? AND program_id = ?
");
$stmt->execute([$client_id, $program_id]);
$prior_slp_count = (int) $stmt->fetchColumn();
$has_prior_slp   = $prior_slp_count > 0;

// Budget status badge
if ($pct_used >= 90) {
    $badge_cls  = 'text-red-500 bg-red-50 border-red-200';
    $badge_icon = 'fa-exclamation-triangle';
    $badge_text = 'Critical — ' . round(100 - $pct_used, 1) . '% remaining';
    $bar_color  = 'bg-red-400';
} elseif ($pct_used >= 70) {
    $badge_cls  = 'text-amber-600 bg-amber-50 border-amber-200';
    $badge_icon = 'fa-exclamation-circle';
    $badge_text = 'Moderate — ' . round(100 - $pct_used, 1) . '% remaining';
    $bar_color  = 'bg-amber-400';
} else {
    $badge_cls  = 'text-emerald-600 bg-emerald-50 border-emerald-200';
    $badge_icon = 'fa-check-circle';
    $badge_text = 'Healthy — ' . round(100 - $pct_used, 1) . '% remaining';
    $bar_color  = 'bg-emerald-400';
}

// ── Handle POST (Submit) ──────────────────────────────────────────────────────
$post_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount        = (float)($_POST['amount']        ?? 0);
    $date_applied  = $_POST['date_applied']          ?? '';
    $date_released = !empty($_POST['date_released']) ? $_POST['date_released'] : null;
    $remarks       = $_POST['remarks']               ?? '';
    $livelihood_type  = $_POST['livelihood_type']    ?? '';
    $other_livelihood = $_POST['other_livelihood']   ?? '';
    $business_name    = $_POST['business_name']      ?? '';
    $slp_training     = isset($_POST['slp_training']) ? 1 : 0;

    // If "Other" is chosen, use the custom text
    $final_livelihood_type = ($livelihood_type === 'Other' && !empty($other_livelihood))
        ? $other_livelihood
        : $livelihood_type;

    // File paths (store filename only; handle actual upload if needed)
    $file_intent   = !empty($_FILES['file_intent']['name'])   ? basename($_FILES['file_intent']['name'])   : null;
    $file_proposal = !empty($_FILES['file_proposal']['name']) ? basename($_FILES['file_proposal']['name']) : null;
    $file_id       = !empty($_FILES['file_id']['name'])       ? basename($_FILES['file_id']['name'])       : null;

    // Validation
    if (!$program_id)                      $post_errors[] = 'SLP program not found in database. Please seed the PROGRAM table.';
    if ($amount <= 0)                      $post_errors[] = 'Please enter a valid start-up assistance amount.';
    if (empty($date_applied))             $post_errors[] = 'Date Applied is required.';
    if (empty($final_livelihood_type))    $post_errors[] = 'Please select a Livelihood Type.';
    if (empty($business_name))            $post_errors[] = 'Business Name is required.';

    if (empty($post_errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Insert into AVAILMENT
            $ins = $pdo->prepare("
                INSERT INTO AVAILMENT
                    (client_id, program_id, user_id, av_date_applied, av_date_released, av_amount, av_status, av_remarks)
                VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)
            ");
            $ins->execute([$client_id, $program_id, $user_id, $date_applied, $date_released, $amount, $remarks]);
            $availment_id = $pdo->lastInsertId();

            // 2. Insert into SLP
            $ins2 = $pdo->prepare("
                INSERT INTO SLP (availment_id, user_id, slp_livelihood_type, slp_business_proposal, slp_letter, slp_training)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $ins2->execute([
                $availment_id,
                $user_id,
                $final_livelihood_type,
                $file_proposal,
                $file_intent,
                $slp_training,
            ]);

            // 3. Deduct from PROGRAM remaining budget
            $pdo->prepare("
                UPDATE PROGRAM SET prog_remaining_budget = prog_remaining_budget - ?
                WHERE program_id = ?
            ")->execute([$amount, $program_id]);

            $pdo->commit();
            header("Location: clientprofile.php?id={$client_id}&saved=slp");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $post_errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SLP Availment – MSWDO San Enrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet" />
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
                        'fade-up':   'fadeUp 0.35s ease both',
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
        body { font-family: 'DM Sans', sans-serif; }
        .sidebar-item { transition: all .15s; }
        .sidebar-item:hover { background: rgba(255,255,255,.07); color: rgba(255,255,255,.95); }
        .sidebar-item.active { background: rgba(29,111,164,.28); border-left-color: #C49A2A; color: #fff; }
        .screen-panel { display: block; animation: fadeUp 0.3s ease both; }
        @keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
        .field {
            display: block; width: 100%; border-radius: .75rem;
            border: 1.5px solid #E2E8F0; background: #F8FAFC;
            padding: .625rem .875rem; font-size: 13px; color: #1e293b;
            outline: none; font-family: 'DM Sans', sans-serif; transition: all .2s;
        }
        .field:focus { border-color: #3A5F93; background: #fff; box-shadow: 0 0 0 3px rgba(58,95,147,.1); }
        .field::placeholder { color: #94A3B8; }
        select.field {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236B7280' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 10px center; background-size: 16px; appearance: none;
        }
        .field-label { display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #64748B; margin-bottom: 6px; }
        .req::after { content: '*'; color: #EF4444; margin-left: 2px; }
        .upload-zone {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-height: 130px; border: 2px dashed #CBD5E1; border-radius: 0.875rem;
            padding: 1.25rem 1rem; text-align: center; cursor: pointer;
            transition: all .2s; background: #F8FAFC; width: 100%; box-sizing: border-box;
        }
        .upload-zone:hover { border-color: #3A5F93; background: #EBF4FB; }
        .upload-zone.has-file { border-color: #0B2545; background: #E8EDF5; border-style: solid; }
        .upload-zone input[type=file] { display: none; }
        .upload-zone .upload-content { display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; gap: 0.25rem; }
        .upload-zone .upload-icon { font-size: 1.75rem; line-height: 1; margin-bottom: 0.25rem; color: #94A3B8; }
        .upload-zone .upload-title { font-size: 12px; font-weight: 500; color: #475569; line-height: 1.3; }
        .upload-zone .upload-hint { font-size: 11px; color: #94A3B8; line-height: 1.3; }
        .upload-zone.has-file .upload-icon { font-size: 1.5rem; color: #0B2545; }
        .upload-zone.has-file .upload-title { color: #0B2545; font-weight: 600; font-size: 12px; word-break: break-all; }
        .upload-zone.has-file .upload-hint { color: #3A5F93; font-size: 10px; }
        .copy-badge { display: inline-flex; padding: 1px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; background: #FEF3C7; color: #92400E; margin-left: 6px; }
        .limit-row { transition: background .1s; }
        .limit-row:hover { background: #F8FAFC; }
        .budget-bar-fill { transition: width 1s cubic-bezier(.4,0,.2,1); }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 2px; }
        #toast { transition: all .3s ease; }
    </style>
</head>
<body class="bg-slate2 min-h-screen flex">

<?php require 'sidebar.php'; ?>

<div class="ml-64 flex-1 flex flex-col min-h-screen">

    <!-- Header -->
    <header class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
        <div class="flex items-center gap-2 text-[13px]">
            <a href="clientslist.php" class="text-slate-400 hover:text-navy-600">Clients</a>
            <span class="text-slate-300">/</span>
            <a href="clientprofile.php?id=<?= $client_id ?>" class="text-slate-400 hover:text-navy-600"><?= $client_name ?></a>
            <span class="text-slate-300">/</span>
            <a href="programavailmentselection.php?client_id=<?= $client_id ?>" class="text-slate-400 hover:text-navy-600">Program Selection</a>
            <span class="text-slate-300">/</span>
            <span class="text-navy-600 font-semibold">SLP Availment</span>
        </div>
        <a href="programavailmentselection.php?client_id=<?= $client_id ?>"
            class="text-[12px] text-slate-500 hover:text-navy-600 flex items-center gap-1.5 transition-colors">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </header>

    <main class="p-6 overflow-y-auto flex-1">
        <div class="max-w-3xl mx-auto space-y-5">

            <!-- Page title -->
            <div class="animate-fade-up">
                <span class="text-[12px] text-slate-400">Sustainable Livelihood Program</span>
                <h1 class="text-xl font-serif text-navy-600 mt-0.5">SLP Availment Form</h1>
                <p class="text-[13px] text-slate-500 mt-1">
                    Livelihood assistance to help families build sustainable income-generating activities for
                    <span class="font-semibold text-navy-600"><?= $client_name ?></span>.
                </p>
            </div>

            <?php if (!empty($post_errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl px-5 py-3.5 flex items-start gap-3">
                <i class="fas fa-exclamation-circle text-red-400 text-lg mt-0.5"></i>
                <div>
                    <p class="text-[13px] font-semibold text-red-700 mb-1">Please fix the following:</p>
                    <ul class="list-disc list-inside text-[12px] text-red-600 space-y-0.5">
                        <?php foreach ($post_errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$program_id): ?>
            <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-3.5 flex items-start gap-3">
                <i class="fas fa-exclamation-triangle text-amber-400 text-lg mt-0.5"></i>
                <div>
                    <p class="text-[13px] font-semibold text-amber-800">SLP program not found in the PROGRAM table.</p>
                    <p class="text-[12px] text-amber-700 mt-0.5">Run this in phpMyAdmin:</p>
                    <code class="block mt-2 bg-amber-100 text-amber-900 text-[11px] rounded-lg px-3 py-2 font-mono">
                        INSERT INTO PROGRAM (program_name, prog_category, prog_annual_budget, prog_remaining_budget, prog_funding_source)<br>
                        VALUES ('SLP', 'Livelihood', 500000.00, 500000.00, 'DSWD');
                    </code>
                </div>
            </div>
            <?php endif; ?>

            <!-- SLP Rule Warning -->
            <div class="animate-fade-up-1 <?= $has_prior_slp ? 'bg-red-50 border-red-200' : 'bg-navy-50 border-navy-200' ?> border rounded-xl px-4 py-3 flex items-start gap-3">
                <i class="fas <?= $has_prior_slp ? 'fa-ban text-red-500' : 'fa-exclamation-triangle text-navy-500' ?> text-lg mt-0.5"></i>
                <div class="text-[12px] <?= $has_prior_slp ? 'text-red-800' : 'text-navy-800' ?>">
                    <?php if ($has_prior_slp): ?>
                        <strong class="font-semibold block mb-0.5 text-red-700">⚠ Prior SLP Record Found</strong>
                        <?= $client_name ?> has <strong><?= $prior_slp_count ?> existing SLP availment(s)</strong>.
                        A new livelihood <strong>project cannot be created</strong>. Submission is only allowed
                        as <strong>additional funding</strong> for an existing successful project.
                        Confirm with the social worker before proceeding.
                    <?php else: ?>
                        <strong class="font-semibold block mb-0.5">No New Project · Additional Funding Only</strong>
                        A client who has already availed SLP <strong>cannot apply for a new livelihood project</strong>.
                        They may only request additional funds for the same project if it was profitable / income-generating.
                    <?php endif; ?>
                </div>
            </div>

            <!-- Budget Card -->
            <div class="animate-fade-up-1 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-[13px] font-semibold text-navy-600">SLP Budget Status</h2>
                    <?php if ($program_id): ?>
                    <span class="text-[10px] font-semibold border px-2.5 py-0.5 rounded-full <?= $badge_cls ?>">
                        <i class="fas <?= $badge_icon ?> mr-1"></i><?= $badge_text ?>
                    </span>
                    <?php else: ?>
                    <span class="text-[10px] font-semibold border px-2.5 py-0.5 rounded-full text-slate-400 bg-slate-50 border-slate-200">No program data</span>
                    <?php endif; ?>
                </div>
                <div class="px-5 py-4 grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold mb-1">Annual Budget</p>
                        <p class="text-[18px] font-bold text-navy-600">₱<?= number_format($annual, 2) ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold mb-1">Spent</p>
                        <p class="text-[18px] font-bold text-slate-700">₱<?= number_format($spent, 2) ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold mb-1">Remaining</p>
                        <p class="text-[18px] font-bold <?= $remaining <= 0 ? 'text-red-500' : 'text-emerald-600' ?>">
                            ₱<?= number_format($remaining, 2) ?>
                        </p>
                    </div>
                </div>
                <div class="px-5 pb-4">
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="budget-bar-fill h-2 rounded-full <?= $bar_color ?>" style="width:0%" data-target="<?= min($pct_used, 100) ?>%"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-slate-400 mt-1.5">
                        <span>0%</span>
                        <span class="font-semibold"><?= $pct_used ?>% utilized</span>
                        <span>100%</span>
                    </div>
                </div>
            </div>

            <!-- FORM -->
            <form method="POST" action="slp.php?client_id=<?= $client_id ?>" enctype="multipart/form-data" id="slpForm">

                <!-- Transaction Details -->
                <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden mb-5">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Transaction Details</h2>
                            <p class="text-[11px] text-slate-400">Amount, dates, and eligibility check</p>
                        </div>
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="field-label req">Start-up Assistance (₱)</label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-medium text-[13px]">₱</span>
                                    <input type="number" min="0" name="amount" class="field pl-7" id="amountField"
                                        placeholder="0.00" oninput="checkAmount(this)"
                                        value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>">
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1.5">No fixed min/max · subject to budget</p>
                            </div>
                            <div>
                                <label class="field-label req">Date Applied</label>
                                <input type="date" name="date_applied" class="field" id="dateApplied"
                                    value="<?= htmlspecialchars($_POST['date_applied'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="field-label">Date Released</label>
                                <input type="date" name="date_released" class="field" id="dateReleased"
                                    value="<?= htmlspecialchars($_POST['date_released'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- SLP Eligibility Check -->
                        <div class="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden">
                            <div class="px-4 py-2.5 bg-slate-100/80 border-b border-slate-200">
                                <p class="text-[11px] font-semibold text-navy-600">SLP Eligibility Check — <?= $client_name ?></p>
                            </div>
                            <div class="divide-y divide-slate-100">
                                <div class="limit-row flex items-center justify-between px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-history text-slate-400 text-sm"></i>
                                        <span class="text-[12px] text-slate-600">Previous SLP availed</span>
                                    </div>
                                    <span class="text-[12px] font-semibold <?= $has_prior_slp ? 'text-amber-600' : 'text-emerald-600' ?>">
                                        <?= $has_prior_slp
                                            ? '⚠ ' . $prior_slp_count . ' record(s) found — additional funding only'
                                            : '✓ None on record — eligible for new project' ?>
                                    </span>
                                </div>
                                <div class="limit-row flex items-center justify-between px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-chart-line text-slate-400 text-sm"></i>
                                        <span class="text-[12px] text-slate-600">Budget sufficient</span>
                                    </div>
                                    <span class="text-[12px] font-semibold <?= $remaining <= 0 ? 'text-red-500' : 'text-emerald-600' ?>">
                                        <?= $remaining <= 0 ? '✗ No budget remaining' : '✓ ₱' . number_format($remaining, 2) . ' remaining' ?>
                                    </span>
                                </div>
                                <div class="limit-row flex items-center justify-between px-4 py-2.5" id="amountCheck">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-peso-sign text-slate-400 text-sm"></i>
                                        <span class="text-[12px] text-slate-600">Amount vs. remaining budget</span>
                                    </div>
                                    <span class="text-[12px] font-semibold text-slate-400">— Enter amount above</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="field-label">Remarks</label>
                            <textarea name="remarks" class="field resize-none" rows="3"
                                placeholder="Optional notes about this SLP transaction..."><?= htmlspecialchars($_POST['remarks'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Business / Livelihood Information -->
                <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden mb-5">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-store"></i>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Business / Livelihood Information</h2>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="field-label req">Livelihood Type</label>
                                <select name="livelihood_type" class="field" id="livelihoodType" onchange="handleLivelihoodChange()">
                                    <option value="">Select</option>
                                    <option value="Sari-sari Store"  <?= ($_POST['livelihood_type'] ?? '') === 'Sari-sari Store'  ? 'selected' : '' ?>>Sari-sari Store</option>
                                    <option value="Rice Retailing"   <?= ($_POST['livelihood_type'] ?? '') === 'Rice Retailing'   ? 'selected' : '' ?>>Rice Retailing</option>
                                    <option value="Frozen Goods"     <?= ($_POST['livelihood_type'] ?? '') === 'Frozen Goods'     ? 'selected' : '' ?>>Frozen Goods</option>
                                    <option value="Food Vending"     <?= ($_POST['livelihood_type'] ?? '') === 'Food Vending'     ? 'selected' : '' ?>>Food Vending</option>
                                    <option value="Farming"          <?= ($_POST['livelihood_type'] ?? '') === 'Farming'          ? 'selected' : '' ?>>Farming</option>
                                    <option value="Livestock"        <?= ($_POST['livelihood_type'] ?? '') === 'Livestock'        ? 'selected' : '' ?>>Livestock</option>
                                    <option value="Handicrafts"      <?= ($_POST['livelihood_type'] ?? '') === 'Handicrafts'      ? 'selected' : '' ?>>Handicrafts</option>
                                    <option value="Other"            <?= ($_POST['livelihood_type'] ?? '') === 'Other'            ? 'selected' : '' ?>>Other</option>
                                </select>
                                <input type="text" name="other_livelihood" id="otherLivelihood"
                                    class="field mt-2 <?= ($_POST['livelihood_type'] ?? '') !== 'Other' ? 'hidden' : '' ?>"
                                    placeholder="Specify livelihood type"
                                    value="<?= htmlspecialchars($_POST['other_livelihood'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="field-label req">Business Name</label>
                                <input type="text" name="business_name" class="field" placeholder="Proposed business name"
                                    value="<?= htmlspecialchars($_POST['business_name'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="field-label">Business Location</label>
                                <input type="text" name="business_location" class="field" placeholder="Address or description"
                                    value="<?= htmlspecialchars($_POST['business_location'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="field-label">Target Start Date</label>
                                <input type="date" name="target_start_date" class="field"
                                    value="<?= htmlspecialchars($_POST['target_start_date'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Training Toggle -->
                        <div class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                            <div>
                                <p class="text-[13px] font-semibold text-slate-700">Training Completed</p>
                                <p class="text-[11px] text-slate-400">Has the client completed any livelihood training?</p>
                            </div>
                            <label class="relative cursor-pointer flex items-center gap-3">
                                <input type="checkbox" name="slp_training" id="trainingToggle" class="sr-only"
                                    onchange="toggleTraining()" <?= !empty($_POST['slp_training']) ? 'checked' : '' ?>>
                                <div class="w-11 h-6 <?= !empty($_POST['slp_training']) ? 'bg-navy-600' : 'bg-slate-200' ?> rounded-full relative transition-colors" id="trainingTrack">
                                    <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                        id="trainingThumb" style="<?= !empty($_POST['slp_training']) ? 'transform:translateX(20px)' : '' ?>"></div>
                                </div>
                                <span id="trainingLabel" class="text-[12px] font-medium <?= !empty($_POST['slp_training']) ? 'text-navy-600' : 'text-slate-500' ?>">
                                    <?= !empty($_POST['slp_training']) ? 'Yes' : 'No' ?>
                                </span>
                            </label>
                        </div>
                        <div id="trainingDetails" class="<?= !empty($_POST['slp_training']) ? 'grid' : 'hidden' ?> grid-cols-2 gap-4">
                            <div>
                                <label class="field-label">Training Program</label>
                                <input type="text" name="training_program" class="field" placeholder="e.g. TESDA NC II – Food Processing"
                                    value="<?= htmlspecialchars($_POST['training_program'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="field-label">Date Completed</label>
                                <input type="date" name="training_date" class="field"
                                    value="<?= htmlspecialchars($_POST['training_date'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Required Documents -->
                <div class="animate-fade-up-4 bg-white rounded-2xl border border-slate-200 overflow-hidden mb-5">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-paperclip"></i>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Required Documents</h2>
                            <p class="text-[11px] text-slate-400">DSWD standard: 1 original + 2 photocopies each</p>
                        </div>
                    </div>
                    <div class="p-6 grid grid-cols-2 gap-4">
                        <!-- Letter of Intent → slp_letter -->
                        <div>
                            <div class="field-label flex items-center">Letter of Intent <span class="copy-badge">1 orig + 2 copies</span></div>
                            <label class="upload-zone" id="uz-slp-intent">
                                <input type="file" name="file_intent" accept=".pdf,.jpg,.jpeg,.png" onchange="fileSelected(this,'uz-slp-intent')">
                                <div class="upload-content">
                                    <i class="fas fa-pen-alt upload-icon"></i>
                                    <p class="upload-title">Click to upload</p>
                                    <p class="upload-hint">PDF, JPG, PNG</p>
                                </div>
                            </label>
                        </div>
                        <!-- Business Proposal → slp_business_proposal -->
                        <div>
                            <div class="field-label flex items-center">Business Proposal <span class="copy-badge">1 orig + 2 copies</span></div>
                            <label class="upload-zone" id="uz-slp-proposal">
                                <input type="file" name="file_proposal" accept=".pdf,.jpg,.jpeg,.png" onchange="fileSelected(this,'uz-slp-proposal')">
                                <div class="upload-content">
                                    <i class="fas fa-chart-pie upload-icon"></i>
                                    <p class="upload-title">Click to upload</p>
                                    <p class="upload-hint">PDF, JPG, PNG</p>
                                </div>
                            </label>
                        </div>
                        <!-- Valid ID (stored as file path; no direct SLP column — store in remarks or extend table) -->
                        <div>
                            <div class="field-label flex items-center">Valid ID <span class="copy-badge">1 orig + 2 copies</span></div>
                            <label class="upload-zone" id="uz-slp-id">
                                <input type="file" name="file_id" accept=".pdf,.jpg,.jpeg,.png" onchange="fileSelected(this,'uz-slp-id')">
                                <div class="upload-content">
                                    <i class="fas fa-id-card upload-icon"></i>
                                    <p class="upload-title">Click to upload</p>
                                    <p class="upload-hint">PDF, JPG, PNG</p>
                                </div>
                            </label>
                        </div>
                        <!-- Certificate of Indigency -->
                        <div>
                            <div class="field-label flex items-center">Certificate of Indigency <span class="copy-badge">1 orig + 2 copies</span></div>
                            <label class="upload-zone" id="uz-slp-indigency">
                                <input type="file" name="file_indigency" accept=".pdf,.jpg,.jpeg,.png" onchange="fileSelected(this,'uz-slp-indigency')">
                                <div class="upload-content">
                                    <i class="fas fa-file-alt upload-icon"></i>
                                    <p class="upload-title">Click to upload</p>
                                    <p class="upload-hint">PDF, JPG, PNG</p>
                                </div>
                            </label>
                        </div>
                        <!-- Certificate of Residency -->
                        <div>
                            <div class="field-label flex items-center">Certificate of Residency <span class="copy-badge">1 orig + 2 copies</span></div>
                            <label class="upload-zone" id="uz-slp-residency">
                                <input type="file" name="file_residency" accept=".pdf,.jpg,.jpeg,.png" onchange="fileSelected(this,'uz-slp-residency')">
                                <div class="upload-content">
                                    <i class="fas fa-home upload-icon"></i>
                                    <p class="upload-title">Click to upload</p>
                                    <p class="upload-hint">PDF, JPG, PNG</p>
                                </div>
                            </label>
                        </div>
                        <!-- Training Certificate (optional) -->
                        <div>
                            <div class="field-label flex items-center">
                                Training Certificate
                                <span class="ml-2 text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full">If applicable</span>
                            </div>
                            <label class="upload-zone" id="uz-slp-training">
                                <input type="file" name="file_training" accept=".pdf,.jpg,.jpeg,.png" onchange="fileSelected(this,'uz-slp-training')">
                                <div class="upload-content">
                                    <i class="fas fa-certificate upload-icon"></i>
                                    <p class="upload-title">Click to upload</p>
                                    <p class="upload-hint">PDF, JPG, PNG</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3">
                    <button type="submit"
                        class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500 transition-all">
                        Submit SLP Availment ✓
                    </button>
                </div>

            </form><!-- end slpForm -->

        </div>
    </main>

    <footer class="border-t border-slate-200 bg-white px-6 py-3 text-[11px] text-slate-400">
        MSWDO San Enrique Information System
    </footer>
</div>

<!-- Toast -->
<div id="toast"
    class="fixed bottom-6 right-6 bg-navy-600 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
    <i class="fas fa-check-circle text-emerald-400"></i>
    <span id="toastMsg">Saved!</span>
</div>

<script>
    const budgetRemaining = <?= (float)$remaining ?>;

    // ── Training toggle ───────────────────────────────────────────────────────
    let trainingOn = <?= !empty($_POST['slp_training']) ? 'true' : 'false' ?>;
    function toggleTraining() {
        trainingOn = !trainingOn;
        document.getElementById('trainingTrack').classList.toggle('bg-navy-600', trainingOn);
        document.getElementById('trainingTrack').classList.toggle('bg-slate-200', !trainingOn);
        document.getElementById('trainingThumb').style.transform = trainingOn ? 'translateX(20px)' : '';
        document.getElementById('trainingLabel').textContent = trainingOn ? 'Yes' : 'No';
        document.getElementById('trainingLabel').className = trainingOn
            ? 'text-[12px] font-medium text-navy-600'
            : 'text-[12px] font-medium text-slate-500';
        document.getElementById('trainingDetails').classList.toggle('hidden', !trainingOn);
        document.getElementById('trainingDetails').classList.toggle('grid', trainingOn);
    }

    // ── Amount check vs remaining budget ─────────────────────────────────────
    function checkAmount(input) {
        const val = parseFloat(input.value);
        const el  = document.getElementById('amountCheck').querySelector('span');
        if (!val || isNaN(val)) {
            el.innerHTML = '— Enter amount above';
            el.className = 'text-[12px] font-semibold text-slate-400';
        } else if (val > budgetRemaining) {
            el.innerHTML = `<i class="fas fa-times-circle text-red-500 mr-1"></i> Exceeds remaining budget (₱${budgetRemaining.toLocaleString()})`;
            el.className = 'text-[12px] font-semibold text-red-500';
        } else {
            el.innerHTML = `<i class="fas fa-check-circle text-emerald-600 mr-1"></i> ₱${val.toLocaleString()} — within budget`;
            el.className = 'text-[12px] font-semibold text-emerald-600';
        }
    }

    // ── Livelihood type "Other" handler ───────────────────────────────────────
    function handleLivelihoodChange() {
        const select = document.getElementById('livelihoodType');
        const other  = document.getElementById('otherLivelihood');
        if (select.value === 'Other') {
            other.classList.remove('hidden');
            other.focus();
        } else {
            other.classList.add('hidden');
            other.value = '';
        }
    }

    // ── Date Released guard ───────────────────────────────────────────────────
    document.getElementById('dateApplied').addEventListener('change', function () {
        const released = document.getElementById('dateReleased');
        released.min = this.value;
        if (released.value && released.value < this.value) {
            released.value = '';
            showToast('Date Released cleared — cannot be before Date Applied.');
        }
    });
    window.addEventListener('load', () => {
        const applied = document.getElementById('dateApplied').value;
        if (applied) document.getElementById('dateReleased').min = applied;
    });

    // ── File upload feedback ──────────────────────────────────────────────────
    function fileSelected(input, zoneId) {
        if (!input.files || !input.files[0]) return;
        const zone = document.getElementById(zoneId);
        zone.classList.add('has-file');
        zone.querySelector('.upload-content').innerHTML =
            `<i class="fas fa-check-circle upload-icon" style="color:#0B2545"></i>
             <p class="upload-title">${input.files[0].name}</p>
             <p class="upload-hint">File ready</p>`;
    }

    // ── Toast ─────────────────────────────────────────────────────────────────
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

    // ── Animate budget bar ────────────────────────────────────────────────────
    requestAnimationFrame(() => setTimeout(() => {
        document.querySelectorAll('.budget-bar-fill').forEach(el => el.style.width = el.dataset.target);
    }, 400));
</script>
</body>
</html>