<?php
require 'auth.php';
requireRole(['Admin', 'Social Worker', 'Staff']);
require 'db_connect.php';

$client_id = intval($_GET['id'] ?? 0);
if ($client_id === 0) {
    header('Location: clientslist.php');
    exit;
}

// fetch main client data
$stmt = $pdo->prepare("
    SELECT
        c.*,
        b.barangay_name
    FROM CLIENT c
    LEFT JOIN BARANGAY b ON c.brgy_id = b.barangay_id
    WHERE c.client_id = :id
    LIMIT 1
");
$stmt->execute([':id' => $client_id]);
$client = $stmt->fetch();

if (!$client) {
    header('Location: clientslist.php');
    exit;
}

// fetch availment history for client
// AICS FBML is one shared program covering 4 subtypes (Financial, Burial, Medical,
// Livelihood), each recorded in its own subtype table keyed by availment_id — same
// pattern used in aics.php. We LEFT JOIN each subtype table and CASE off whichever
// one has a matching row to build a specific label instead of the generic
// "AICS FBML" program name. AICS Educational is its own separate program already.
$avStmt = $pdo->prepare("
    SELECT
        a.availment_id,
        a.av_date_applied,
        a.av_date_approved,
        a.av_date_released,
        a.av_amount,
        a.av_status,
        a.av_remarks,
        p.program_name,
        p.prog_category,
        CONCAT(u.user_firstname, ' ', u.user_lastname) AS encoded_by,
        CASE
            WHEN p.program_name = 'AICS FBML' THEN
                CASE
                    WHEN med.aics_medical_id    IS NOT NULL THEN 'AICS - Medical'
                    WHEN fin.aics_financial_id   IS NOT NULL THEN 'AICS - Financial'
                    WHEN bur.aics_burial_id      IS NOT NULL THEN 'AICS - Burial'
                    WHEN liv.aics_livelihood_id  IS NOT NULL THEN 'AICS - Livelihood'
                    ELSE p.program_name
                END
            WHEN p.program_name = 'AICS Educational' THEN 'AICS - Educational'
            ELSE p.program_name
        END AS program_label
    FROM AVAILMENT a
    LEFT JOIN PROGRAM p  ON a.program_id = p.program_id
    LEFT JOIN MSWDO_USER u ON a.user_id  = u.user_id
    LEFT JOIN AICS_MEDICAL    med ON med.availment_id = a.availment_id
    LEFT JOIN AICS_FINANCIAL  fin ON fin.availment_id = a.availment_id
    LEFT JOIN AICS_BURIAL     bur ON bur.availment_id = a.availment_id
    LEFT JOIN AICS_LIVELIHOOD liv ON liv.availment_id = a.availment_id
    WHERE a.client_id = :id
    ORDER BY a.av_date_applied DESC
");
$avStmt->execute([':id' => $client_id]);
$availments = $avStmt->fetchAll();

// fetch family composition from CASE_STUDY table
// Family composition is meant to be a single, read-only fact set from client
// registration — not something that forks every time a new case study is
// submitted. Each case study submission inserts its OWN new CASE_STUDY row
// (with its own snapshot of family_composition_json), so filtering only by
// "most recent" here could accidentally show a later case study's snapshot
// instead of the true registration data. We filter to the same
// 'Initial registration' row that casestudy.php reads from, so both pages
// always agree.
$famStmt = $pdo->prepare("
    SELECT family_composition_json
    FROM CASE_STUDY
    WHERE client_id = :id
      AND problem_presented = 'Initial registration'
    ORDER BY created_at ASC
    LIMIT 1
");
$famStmt->execute([':id' => $client_id]);
$caseRow = $famStmt->fetch();

$familyMembers = [];
if ($caseRow && !empty($caseRow['family_composition_json'])) {
    $familyMembers = json_decode($caseRow['family_composition_json'], true) ?? [];
}

$totalAvailments = count($availments);

// add up amounts by status — Released is money already in the client's hands;
// Approved is money that's been cleared but not yet released. Availments are
// auto-approved on submission now, so nothing sits at Pending anymore.
$totalAssistance = 0;
$totalApproved   = 0;
foreach ($availments as $av) {
    $amt = floatval($av['av_amount']);
    if ($av['av_status'] === 'Released') {
        $totalAssistance += $amt;
    } elseif ($av['av_status'] === 'Approved') {
        $totalApproved += $amt;
    }
}

// count how many availments this year
$thisYear    = date('Y');
$thisYearCount = 0;
foreach ($availments as $av) {
    if (date('Y', strtotime($av['av_date_applied'])) === $thisYear) {
        $thisYearCount++;
    }
}

// count how many this quarter
$thisQuarter   = ceil(date('n') / 3); // month / 3 rounded up = quarter number
$thisQuarterCount = 0;
foreach ($availments as $av) {
    $avQuarter = ceil(date('n', strtotime($av['av_date_applied'])) / 3);
    $avYear    = date('Y', strtotime($av['av_date_applied']));
    if ($avYear === $thisYear && $avQuarter === $thisQuarter) {
        $thisQuarterCount++;
    }
}

// combined family income (client + family members)
$combinedIncome = floatval($client['cl_monthly_income'] ?? 0);
foreach ($familyMembers as $member) {
    $combinedIncome += floatval($member['income'] ?? 0);
}

$regYear   = date('Y', strtotime($client['cl_date_registered'] ?? 'now'));
$clientIdStr = 'CLT-' . $regYear . '-' . str_pad($client['client_id'], 5, '0', STR_PAD_LEFT);

$initials = strtoupper(
    substr($client['cl_firstname'], 0, 1) .
    substr($client['cl_lastname'],  0, 1)
);

$fullName = $client['cl_firstname'];
if (!empty($client['cl_middlename'])) {
    $fullName .= ' ' . $client['cl_middlename'][0] . '.';
}
$fullName .= ' ' . $client['cl_lastname'];
if (!empty($client['cl_suffix'])) {
    $fullName .= ' ' . $client['cl_suffix'];
}

$sectorMap = [
    'cl_is_4ps'        => ['label' => '4Ps',          'icon' => 'fa-home',         'cls' => 'bg-purple-100 text-purple-700'],
    'cl_is_pwd'        => ['label' => 'PWD',           'icon' => 'fa-wheelchair',   'cls' => 'bg-blue-100 text-blue-700'],
    'cl_is_senior'     => ['label' => 'Senior Citizen','icon' => 'fa-user-friends', 'cls' => 'bg-amber-100 text-amber-700'],
    'cl_is_soloparent' => ['label' => 'Solo Parent',   'icon' => 'fa-user',         'cls' => 'bg-teal-100 text-teal-700'],
    'cl_is_indigent'   => ['label' => 'Indigent',      'icon' => 'fa-list',         'cls' => 'bg-emerald-100 text-emerald-700'],
];

// status badge colors for availment history — Pending/Denied no longer occur
// for new availments, but any legacy record with those statuses will fall
// back to a neutral gray badge via the ?? below.
$statusColors = [
    'Approved' => 'bg-blue-50 text-blue-600',
    'Released' => 'bg-emerald-50 text-emerald-600',
];

// program tag colors — keyed by program_label, so each AICS FBML subtype
// (Financial/Burial/Medical/Livelihood) and AICS Educational get their own color
$progColors = [
    'AICS'              => 'bg-blue-100 text-blue-700',
    'AICS - Financial'  => 'bg-blue-100 text-blue-700',
    'AICS - Burial'     => 'bg-slate-200 text-slate-700',
    'AICS - Medical'    => 'bg-rose-100 text-rose-700',
    'AICS - Livelihood' => 'bg-emerald-100 text-emerald-700',
    'AICS - Educational'=> 'bg-indigo-100 text-indigo-700',
    'Senior Citizen'    => 'bg-amber-100 text-amber-700',
    '4Ps'               => 'bg-purple-100 text-purple-700',
    'SLP'               => 'bg-teal-100 text-teal-700',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($fullName) ?> – MSWDO Client Profile</title>
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
            gold:   { DEFAULT: '#C49A2A' },
            slate2: '#F4F7FC',
          },
          keyframes: {
            fadeUp: { '0%': { opacity: '0', transform: 'translateY(10px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
          },
          animation: {
            'fade-up':   'fadeUp 0.35s ease both',
            'fade-up-1': 'fadeUp 0.35s ease 0.05s both',
            'fade-up-2': 'fadeUp 0.35s ease 0.10s both',
            'fade-up-3': 'fadeUp 0.35s ease 0.15s both',
          }
        }
      }
    }
  </script>
  <style>
    body { font-family: 'DM Sans', sans-serif; }

    .sidebar-item { transition: all .15s ease; }
    .sidebar-item:hover { background: rgba(255,255,255,.07); color: rgba(255,255,255,.95); }
    .sidebar-item.active { background: rgba(29,111,164,.28); border-left-color: #C49A2A; color: #fff; }

    .tab-btn { transition: all .18s ease; }
    .tab-btn.active { color: #0B2545; border-bottom-color: #0B2545; }

    .table-row { transition: background .1s; cursor: pointer; }
    .table-row:hover { background: #F8FAFC; }

    .btn-act { transition: all .15s ease; }
    .btn-act:hover { transform: translateY(-1px); }

    .tab-panel { display: none; }
    .tab-panel.active { display: block; animation: fadeUp 0.3s ease both; }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(8px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 2px; }
  </style>
</head>

<body class="bg-slate2 min-h-screen flex">

  <?php include 'sidebar.php'; ?>

  <div class="ml-64 flex-1 flex flex-col min-h-screen">

    <!-- Topbar -->
    <header class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
      <div class="flex items-center gap-2 text-[13px]">
        <a href="clientslist.php" class="text-slate-400 hover:text-navy-600 transition-colors">Clients</a>
        <span class="text-slate-300">/</span>
        <span class="text-navy-600 font-semibold"><?= htmlspecialchars($fullName) ?></span>
        <span class="bg-slate-100 text-slate-500 text-[10px] font-medium px-2 py-0.5 rounded-full ml-1">
          <?= htmlspecialchars($clientIdStr) ?>
        </span>
      </div>
      <div class="flex items-center gap-2">
        <a href="programavailmentselection.php?client_id=<?= $client_id ?>"
          class="btn-act text-[12px] font-semibold text-white bg-navy-600 rounded-lg px-4 py-1.5 hover:bg-navy-500">
          <i class="fas fa-plus"></i> New Availment
        </a>
      </div>
    </header>

    <main class="flex-1 p-6 space-y-5 max-w-5xl w-full mx-auto">

      <!-- Client Card -->
      <div class="animate-fade-up bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="h-1.5 bg-gradient-to-r from-navy-600 via-navy-400 to-teal-500"></div>
        <div class="p-6 flex flex-col sm:flex-row items-start gap-5">

          <div class="flex-shrink-0">
            <div class="w-16 h-16 rounded-2xl bg-navy-600 flex items-center justify-center text-white font-serif text-2xl">
              <?= htmlspecialchars($initials) ?>
            </div>
          </div>

          <!-- Client info -->
          <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <h1 class="text-xl font-serif text-navy-600"><?= htmlspecialchars($fullName) ?></h1>
                <p class="text-[12px] text-slate-400 mt-0.5">
                  Client ID: <?= htmlspecialchars($clientIdStr) ?> &nbsp;·&nbsp;
                  Registered: <?= $client['cl_date_registered'] ? date('F j, Y', strtotime($client['cl_date_registered'])) : '—' ?>
                </p>
              </div>

              <div class="flex flex-wrap gap-2">
                <?php foreach ($sectorMap as $col => $info): ?>
                  <?php if ($client[$col]): ?>
                    <span class="<?= $info['cls'] ?> text-[11px] font-semibold px-3 py-1 rounded-full">
                      <i class="fas <?= $info['icon'] ?>"></i> <?= $info['label'] ?>
                    </span>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4 pt-4 border-t border-slate-100">
              <div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Barangay</p>
                <p class="text-[13px] font-medium text-navy-600 mt-0.5">
                  <?= htmlspecialchars($client['barangay_name'] ?? '—') ?>
                </p>
              </div>
              <div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Age / Sex</p>
                <p class="text-[13px] font-medium text-navy-600 mt-0.5">
                  <?= $client['cl_age'] ?? '—' ?> yrs · <?= htmlspecialchars($client['cl_sex'] ?? '—') ?>
                </p>
              </div>
              <div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Civil Status</p>
                <p class="text-[13px] font-medium text-navy-600 mt-0.5">
                  <?= htmlspecialchars($client['cl_civilstatus'] ?? '—') ?>
                </p>
              </div>
              <div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Contact</p>
                <p class="text-[13px] font-medium text-navy-600 mt-0.5">
                  <?= htmlspecialchars($client['cl_contact_num'] ?? '—') ?>
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Summary -->
        <div class="px-6 pb-5 flex flex-wrap gap-3">
          <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-2 text-center">
            <p class="text-[18px] font-bold text-blue-600"><?= $totalAvailments ?></p>
            <p class="text-[10px] text-slate-500 mt-0.5">Total Availments</p>
          </div>
          <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-2 text-center">
            <p class="text-[18px] font-bold text-emerald-600">₱<?= number_format($totalAssistance, 2) ?></p>
            <p class="text-[10px] text-slate-500 mt-0.5">Total Assistance</p>
          </div>
          <div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-2 text-center">
            <p class="text-[18px] font-bold text-amber-500"><?= $thisYearCount ?></p>
            <p class="text-[10px] text-slate-500 mt-0.5">This Year</p>
          </div>
          <div class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-2 text-center">
            <p class="text-[18px] font-bold text-slate-600"><?= $thisQuarterCount ?></p>
            <p class="text-[10px] text-slate-500 mt-0.5">This Quarter</p>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="animate-fade-up-1">
        <div class="flex gap-0 border-b-2 border-slate-200 mb-5">
          <button onclick="showTab('personal',this)"
            class="tab-btn active px-5 py-3 text-[13px] font-semibold border-b-2 -mb-0.5 border-navy-600 text-navy-600">
            Personal Info
          </button>
          <button onclick="showTab('family',this)"
            class="tab-btn px-5 py-3 text-[13px] font-medium border-b-2 -mb-0.5 border-transparent text-slate-500 hover:text-slate-700">
            Family Composition
          </button>
          <button onclick="showTab('history',this)"
            class="tab-btn px-5 py-3 text-[13px] font-medium border-b-2 -mb-0.5 border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-2">
            Availment History
            <!-- real count badge -->
            <span class="bg-navy-100 text-navy-600 text-[10px] font-bold px-1.5 py-0.5 rounded-full">
              <?= $totalAvailments ?>
            </span>
          </button>
        </div>

        <!-- PERSONAL INFO TAB -->
        <div class="tab-panel active" id="tab-personal">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <!-- Personal Details card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
              <h3 class="text-[12px] font-semibold text-slate-400 uppercase tracking-wider mb-4">Personal Details</h3>
              <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                  <span class="text-[12px] text-slate-400">Full Name</span>
                  <span class="text-[13px] font-medium text-navy-600"><?= htmlspecialchars($fullName) ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                  <span class="text-[12px] text-slate-400">Birthdate</span>
                  <span class="text-[13px] font-medium text-navy-600">
                    <?= $client['cl_birthdate'] ? date('F j, Y', strtotime($client['cl_birthdate'])) : '—' ?>
                  </span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                  <span class="text-[12px] text-slate-400">Age</span>
                  <span class="text-[13px] font-medium text-navy-600"><?= $client['cl_age'] ?? '—' ?> years old</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                  <span class="text-[12px] text-slate-400">Sex</span>
                  <span class="text-[13px] font-medium text-navy-600"><?= htmlspecialchars($client['cl_sex'] ?? '—') ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                  <span class="text-[12px] text-slate-400">Civil Status</span>
                  <span class="text-[13px] font-medium text-navy-600"><?= htmlspecialchars($client['cl_civilstatus'] ?? '—') ?></span>
                </div>
                <div class="flex justify-between items-center py-2">
                  <span class="text-[12px] text-slate-400">Contact Number</span>
                  <span class="text-[13px] font-medium text-navy-600"><?= htmlspecialchars($client['cl_contact_num'] ?? '—') ?></span>
                </div>
              </div>
            </div>

            <!-- Address & Economic Status card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
              <h3 class="text-[12px] font-semibold text-slate-400 uppercase tracking-wider mb-4">Address &amp; Economic Status</h3>
              <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                  <span class="text-[12px] text-slate-400">Barangay</span>
                  <span class="text-[13px] font-medium text-navy-600"><?= htmlspecialchars($client['barangay_name'] ?? '—') ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                  <span class="text-[12px] text-slate-400">Street / House No.</span>
                  <span class="text-[13px] font-medium text-navy-600"><?= htmlspecialchars($client['cl_street'] ?? '—') ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                  <span class="text-[12px] text-slate-400">Municipality</span>
                  <span class="text-[13px] font-medium text-navy-600"><?= htmlspecialchars($client['cl_city_municipality'] ?? 'San Enrique') ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                  <span class="text-[12px] text-slate-400">Occupation</span>
                  <span class="text-[13px] font-medium text-navy-600"><?= htmlspecialchars($client['cl_occupation'] ?? '—') ?></span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                  <span class="text-[12px] text-slate-400">Monthly Income</span>
                  <span class="text-[13px] font-medium text-navy-600">
                    ₱<?= number_format(floatval($client['cl_monthly_income'] ?? 0), 2) ?>
                  </span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                  <span class="text-[12px] text-slate-400">Education</span>
                  <span class="text-[13px] font-medium text-navy-600"><?= htmlspecialchars($client['cl_educ_attain'] ?? '—') ?></span>
                </div>
                <div class="flex justify-between items-center py-2">
                  <span class="text-[12px] text-slate-400">Indigency Status</span>
                  <?php if ($client['cl_is_indigent']): ?>
                    <span class="bg-red-100 text-red-700 text-[11px] font-bold px-2.5 py-0.5 rounded-full">Indigent</span>
                  <?php else: ?>
                    <span class="bg-slate-100 text-slate-500 text-[11px] font-bold px-2.5 py-0.5 rounded-full">Not Indigent</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- FAMILY COMPOSITION TAB -->
        <div class="tab-panel" id="tab-family">
          <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
              <h3 class="text-[13px] font-semibold text-navy-600">Household Members</h3>
              <span class="text-[11px] text-slate-400 bg-slate-100 px-2.5 py-1 rounded-full">
                <!-- +1 to include the client themselves -->
                <?= count($familyMembers) + 1 ?> members
              </span>
            </div>

            <?php if (empty($familyMembers)): ?>
              <!-- if no family data was saved yet -->
              <div class="px-5 py-10 text-center text-slate-400 text-[13px]">
                <p>No family composition recorded yet.</p>
                <p class="text-[11px] mt-1">Add family members when creating a case study.</p>
              </div>
            <?php else: ?>
              <table class="w-full text-[12px]">
                <thead>
                  <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">#</th>
                    <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Name</th>
                    <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Relationship</th>
                    <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Age</th>
                    <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Occupation</th>
                    <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Income/mo</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <!-- first row is always the client themselves -->
                  <tr class="table-row">
                    <td class="px-5 py-3 text-slate-400">1</td>
                    <td class="px-5 py-3 font-semibold text-navy-600"><?= htmlspecialchars($fullName) ?></td>
                    <td class="px-5 py-3 text-slate-600">Self</td>
                    <td class="px-5 py-3"><?= $client['cl_age'] ?? '—' ?></td>
                    <td class="px-5 py-3"><?= htmlspecialchars($client['cl_occupation'] ?? '—') ?></td>
                    <td class="px-5 py-3 font-medium">₱<?= number_format(floatval($client['cl_monthly_income'] ?? 0), 2) ?></td>
                  </tr>

                  <?php foreach ($familyMembers as $i => $member): ?>
                    <tr class="table-row">
                      <td class="px-5 py-3 text-slate-400"><?= $i + 2 ?></td>
                      <td class="px-5 py-3 font-medium text-slate-700"><?= htmlspecialchars($member['name'] ?? '—') ?></td>
                      <td class="px-5 py-3 text-slate-600"><?= htmlspecialchars($member['relation'] ?? '—') ?></td>
                      <td class="px-5 py-3"><?= intval($member['age'] ?? 0) ?></td>
                      <td class="px-5 py-3"><?= htmlspecialchars($member['occupation'] ?? '—') ?></td>
                      <td class="px-5 py-3 font-medium">
                        <?php if (!empty($member['income']) && $member['income'] > 0): ?>
                          ₱<?= number_format(floatval($member['income']), 2) ?>
                        <?php else: ?>
                          <span class="text-slate-400">—</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr class="bg-slate-50 border-t border-slate-200">
                    <td colspan="5" class="px-5 py-3 text-[11px] font-semibold text-slate-500 text-right">Combined Monthly Income</td>
                    <td class="px-5 py-3 text-[13px] font-bold text-navy-600">₱<?= number_format($combinedIncome, 2) ?></td>
                  </tr>
                </tfoot>
              </table>
            <?php endif; ?>

          </div>
        </div>

        <!-- AVAILMENT HISTORY TAB -->
        <div class="tab-panel" id="tab-history">
          <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-100 flex flex-wrap items-center gap-3">
              <span class="text-[12px] font-semibold text-slate-500">All records for this client</span>
              <button class="ml-auto flex items-center gap-1.5 text-[12px] font-medium text-navy-600 border border-navy-200 bg-navy-50 rounded-lg px-3 py-1.5 hover:bg-navy-100 transition-all">
                ⬇ Export
              </button>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-[12px]">
                <thead>
                  <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Date Applied</th>
                    <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Program</th>
                    <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Category</th>
                    <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Amount</th>
                    <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Status</th>
                    <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Encoded By</th>
                    <th class="px-5 py-3"></th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <?php if (empty($availments)): ?>
                    <tr>
                      <td colspan="7" class="px-5 py-10 text-center text-slate-400 text-[13px]">
                        No availment records yet.
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($availments as $av): ?>
                      <?php
                        $sc  = $statusColors[$av['av_status']] ?? 'bg-slate-50 text-slate-500';
                        $tag = $progColors[$av['program_label']] ?? 'bg-slate-100 text-slate-600';
                      ?>
                      <tr class="table-row">
                        <td class="px-5 py-3 text-slate-500">
                          <?= $av['av_date_applied'] ? date('M j, Y', strtotime($av['av_date_applied'])) : '—' ?>
                        </td>
                        <td class="px-5 py-3">
                          <span class="<?= $tag ?> px-2.5 py-0.5 rounded text-[10px] font-semibold">
                            <?= htmlspecialchars($av['program_label'] ?? '—') ?>
                          </span>
                        </td>
                        <td class="px-5 py-3 text-slate-600"><?= htmlspecialchars($av['prog_category'] ?? '—') ?></td>
                        <td class="px-5 py-3 font-semibold text-slate-700">
                          <?= $av['av_amount'] > 0 ? '₱' . number_format(floatval($av['av_amount']), 2) : '—' ?>
                        </td>
                        <td class="px-5 py-3">
                          <span class="<?= $sc ?> px-2.5 py-0.5 rounded-full text-[10px] font-semibold">
                            <?= htmlspecialchars($av['av_status']) ?>
                          </span>
                        </td>
                        <td class="px-5 py-3 text-slate-400"><?= htmlspecialchars($av['encoded_by'] ?? '—') ?></td>
                        <td class="px-5 py-3 text-right">
                          <a href="availmentdetail.php?id=<?= $av['availment_id'] ?>"
                            class="text-[11px] text-navy-500 hover:underline font-medium">View</a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <!-- footer summary -->
            <div class="px-5 py-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-[11px] text-slate-400">
              <span><?= $totalAvailments ?> record<?= $totalAvailments !== 1 ? 's' : '' ?> found</span>
              <div class="flex items-center gap-4">
                <?php if ($totalApproved > 0): ?>
                  <span class="text-blue-600 font-semibold">
                    Total approved: ₱<?= number_format($totalApproved, 2) ?>
                  </span>
                <?php endif; ?>
                <span class="text-emerald-600 font-semibold">
                  Total released: ₱<?= number_format($totalAssistance, 2) ?>
                </span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </main>

    <footer class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
      <span>MSWDO San Enrique Information System — Version 1.0.0</span>
    </footer>
  </div>

  <script>
    function showTab(name, btn) {
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active', 'text-navy-600', 'border-navy-600');
        b.classList.add('text-slate-500', 'border-transparent');
      });
      document.getElementById('tab-' + name).classList.add('active');
      btn.classList.add('active');
      btn.classList.remove('text-slate-500', 'border-transparent');
      btn.classList.add('text-navy-600', 'border-navy-600');
    }
  </script>

</body>
</html>