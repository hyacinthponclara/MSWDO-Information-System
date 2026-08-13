<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';

$availment_id = (int) ($_GET['availment_id'] ?? 0);
if ($availment_id <= 0) {
    header('Location: funds_aics.php');
    exit;
}

// ── Base availment + client + barangay + program + creator ──
$stmt = $pdo->prepare("
    SELECT
        a.availment_id, a.av_amount, a.av_date_applied, a.av_date_approved, a.av_date_created,
        a.av_date_released, a.av_status, a.av_remarks,
        c.client_id, c.cl_firstname, c.cl_lastname, c.cl_age,
        b.barangay_name,
        p.program_name,
        u.user_firstname AS creator_firstname, u.user_lastname AS creator_lastname
    FROM availment a
    JOIN client c ON c.client_id = a.client_id
    LEFT JOIN barangay b ON b.barangay_id = c.brgy_id
    JOIN program p ON p.program_id = a.program_id
    LEFT JOIN mswdo_user u ON u.user_id = a.user_id
    WHERE a.availment_id = ?
");
$stmt->execute([$availment_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header('Location: funds_aics.php');
    exit;
}

// ── Figure out which AICS subtype this availment belongs to ──
$subtypeMap = [
    'Medical'     => ['table' => 'aics_medical',     'badge' => 'badge-medical'],
    'Financial'   => ['table' => 'aics_financial',   'badge' => 'badge-financial'],
    'Educational' => ['table' => 'aics_educational', 'badge' => 'badge-educational'],
    'Livelihood'  => ['table' => 'aics_livelihood',  'badge' => 'badge-livelihood'],
    'Burial'      => ['table' => 'aics_burial',      'badge' => 'badge-burial'],
];

$type = null;
$subtypeData = null;
foreach ($subtypeMap as $label => $info) {
    $subStmt = $pdo->prepare("SELECT * FROM {$info['table']} WHERE availment_id = ? LIMIT 1");
    $subStmt->execute([$availment_id]);
    $found = $subStmt->fetch(PDO::FETCH_ASSOC);
    if ($found) {
        $type = $label;
        $subtypeData = $found;
        break;
    }
}

if ($type === null) {
    // AVAILMENT row exists but has no matching AICS subtype record.
    // Shouldn't happen in normal use, but don't fatal-error on bad data.
    header('Location: funds_aics.php');
    exit;
}

function formatAicsDate(?string $date): ?string
{
    return $date ? date('F j, Y', strtotime($date)) : null;
}

$clientName = trim($row['cl_firstname'] . ' ' . $row['cl_lastname']);

$availment = [
    'availment_id'  => 'AV-' . date('Y', strtotime($row['av_date_applied'])) . '-' . str_pad((string) $row['availment_id'], 3, '0', STR_PAD_LEFT),
    'client'        => $clientName,
    'client_id'     => $row['client_id'],
    'barangay'      => $row['barangay_name'] ?? '—',
    'type'          => $type,
    'type_badge'    => $subtypeMap[$type]['badge'],
    'budget_source' => $row['program_name'],
    'amount'        => (float) $row['av_amount'],
    'date_applied'  => formatAicsDate($row['av_date_applied']) ?? '—',
    'date_approved' => formatAicsDate($row['av_date_approved']),
    'date_released' => formatAicsDate($row['av_date_released']),
    'status'        => $row['av_status'],
    'remarks'       => $row['av_remarks'],
    'created_by'    => $row['creator_firstname'] ? trim($row['creator_firstname'] . ' ' . $row['creator_lastname']) : '—',
    'date_created'  => !empty($row['av_date_created']) ? date('F d, Y h:i A', strtotime($row['av_date_created'])) : '—',
    'last_updated'  => !empty($row['av_date_created'])
        ? date('F d, Y h:i A', strtotime($row['av_date_created']))
        : '—',
];

// ── Subtype-specific fields ──
switch ($type) {
    case 'Medical':
        $availment['patient_name'] = $subtypeData['amed_patient_name'] ?: $clientName;
        $availment['patient_age'] = $subtypeData['amed_patient_age'] ?? ($row['cl_age'] ?? null);
        $availment['patient_relationship'] = $subtypeData['amed_patient_relationship'] ?: 'Self';
        break;

    case 'Financial':
        // Financial Details intentionally has no Purpose/Approval Date section.
        // Those were derived/mock values and are not actual financial subtype fields.
        break;

    case 'Educational':
        $availment['educational_level'] = $subtypeData['aed_educational_level'] ?? null;
        $availment['school_name'] = $subtypeData['aed_school_name'] ?? null;
        $availment['semester'] = $subtypeData['aed_semester'] ?? null;
        $availment['school_year'] = $subtypeData['aed_school_year'] ?? null;
        $availment['purpose'] = $subtypeData['aed_purpose'] ?? null;
        break;

    case 'Livelihood':
        $availment['business_name'] = $subtypeData['aliv_business_name'] ?? null;
        $availment['business_type'] = $subtypeData['aliv_business_type'] ?? null;
        $availment['business_location'] = $subtypeData['aliv_business_location'] ?? null;
        $availment['startup_cost'] = isset($subtypeData['aliv_start_up_cost'])
            ? '₱' . number_format((float) $subtypeData['aliv_start_up_cost'], 2)
            : null;
        $availment['target_start_date'] = !empty($subtypeData['aliv_target_start_date'])
            ? formatAicsDate($subtypeData['aliv_target_start_date'])
            : null;
        break;

    case 'Burial':
        $availment['deceased_name'] = $subtypeData['ab_deceased_name'] ?? null;
        $availment['date_of_death'] = !empty($subtypeData['ab_date_of_death'])
            ? formatAicsDate($subtypeData['ab_date_of_death'])
            : null;
        $availment['relationship_to_claimant'] = $subtypeData['ab_relationship_to_claimant'] ?? null;
        $availment['funeral_home'] = $subtypeData['ab_funeral_home'] ?? null;
        $availment['funeral_cost'] = isset($subtypeData['ab_funeral_cost']) && $subtypeData['ab_funeral_cost'] !== null
            ? '₱' . number_format((float) $subtypeData['ab_funeral_cost'], 2)
            : null;
        break;
}

// ── Uploaded documents: every non-null file column for this subtype ──
$docLabels = [
    'amed_med_cert' => 'Medical Certificate / Abstract',
    'amed_lab_result' => 'Laboratory Results / Resita',
    'amed_valid_id' => 'Valid ID',
    'amed_cert_indigency' => 'Barangay Indigency Certificate',
    'amed_hospital_bill' => 'Hospital Bill',
    'amed_discharge_summary' => 'Discharge Summary',
    'amed_med_quotation' => 'Medical Quotation (Dialysis)',
    'amed_chemo_protocol' => 'Medical Protocol (Chemo)',
    'amed_mayors_approval' => "Mayor's Approval",

    'afin_approval' => "Mayor's Approval",
    'afin_valid_id' => 'Valid ID',
    'afin_supporting_docs' => 'Barangay Indigency Certificate',
    'afin_supporting_docs_2' => 'Supporting Document',

    'aed_grades' => 'Grades / Report Card',
    'aed_cert_enrollment' => 'Certificate of Enrollment',
    'aed_cert_indigency' => 'Barangay Indigency Certificate',
    'aed_cert_residency' => 'Certificate of Residency',
    'aed_student_id' => 'Student ID',
    'aed_claimant_id' => "Claimant's Valid ID",

    'aliv_letter_intent' => 'Letter of Intent',
    'aliv_livelihood_proposal' => 'Livelihood Proposal',
    'aliv_valid_id' => 'Valid ID',
    'aliv_cert_indigency' => 'Barangay Indigency Certificate',
    'aliv_cert_residency' => 'Certificate of Residency',
    'aliv_training_certificate' => 'Training Certificate',

    'ab_death_cert' => 'Death Certificate',
    'ab_funeral_contract' => 'Funeral Contract',
    'ab_valid_id' => 'Valid ID',
    'ab_brgy_indigency' => 'Barangay Indigency Certificate',
    'ab_mayors_approval' => "Mayor's Approval",
];

$uploadFolders = [
    'Medical'     => 'uploads/aics/medical/',
    'Financial'   => 'uploads/aics/financial/',
    'Educational' => 'uploads/aics/educational/',
    'Livelihood'  => 'uploads/aics/livelihood/',
    'Burial'      => 'uploads/aics/burial/',
];
$folder = $uploadFolders[$type];

$documents = [];
foreach ($subtypeData as $col => $value) {
    if ($value === null || $value === '' || !isset($docLabels[$col])) {
        continue;
    }
    // Multi-file columns (e.g. amed_med_cert) are stored as comma-separated filenames
    foreach (explode(',', $value) as $filename) {
        $filename = trim($filename);
        if ($filename === '') {
            continue;
        }
        $fullPath = $folder . $filename;
        $documents[] = [
            'name' => $docLabels[$col],
            'path' => $fullPath,
            'size' => file_exists($fullPath) ? round(filesize($fullPath) / 1024) . ' KB' : '—',
            'date' => file_exists($fullPath) ? date('M j, Y', filemtime($fullPath)) : ($availment['date_applied'] ?? '—'),
        ];
    }
}
$availment['documents'] = $documents;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AICS Availment Details – MSWDO San Enrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['DM Sans', 'sans-serif'], serif: ['DM Serif Display', 'serif'] },
                    colors: {
                        green: {
                            DEFAULT: '#1A5C3A',
                            50: '#EEF6F0',
                            100: '#D4E8DC',
                            200: '#A8D0B8',
                            300: '#7DB895',
                            400: '#52A071',
                            500: '#1A5C3A',
                            600: '#154A2E',
                            700: '#103722',
                            800: '#0A2517',
                            900: '#05120B'
                        },
                        gold: { DEFAULT: '#C49A2A', 400: '#C49A2A' },
                        sage: '#F0F6F2',
                    },
                    keyframes: {
                        fadeUp: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.35s ease both',
                        'fade-up-1': 'fadeUp 0.35s 0.05s ease both',
                        'fade-up-2': 'fadeUp 0.35s 0.10s ease both',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'DM Sans', sans-serif;
            background: #F0F6F2;
        }

        .sidebar-item {
            transition: all .15s;
        }
        .sidebar-item:hover {
            background: rgba(26, 92, 58, .08);
            color: #1A5C3A;
        }
        .sidebar-item.active {
            background: rgba(26, 92, 58, .12);
            border-left-color: #C49A2A;
            color: #1A5C3A;
        }

        .section-card {
            background: #fff;
            border-radius: 1rem;
            border: 1px solid #D4E8DC;
            overflow: hidden;
            margin-bottom: 1.25rem;
        }
        .section-head {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .875rem 1.5rem;
            border-bottom: 1px solid #D4E8DC;
            background: #EEF6F0;
        }
        .section-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #1A5C3A;
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

        .field-label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #4A7A5A;
            margin-bottom: 4px;
        }

        .field-value {
            font-size: 14px;
            color: #1e293b;
            padding: .5rem .75rem;
            background: #FAFCFB;
            border-radius: .75rem;
            border: 1px solid #D4E8DC;
            min-height: 42px;
            word-wrap: break-word;
        }

        .attachment-item {
            transition: all .15s;
        }
        .attachment-item:hover {
            background: #EEF6F0;
            border-color: #1A5C3A;
        }

        .print-only {
            display: none;
        }

        .badge-subtype {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-medical { background: #DBEAFE; color: #1E40AF; }
        .badge-financial { background: #D1FAE5; color: #065F46; }
        .badge-educational { background: #FEF3C7; color: #92400E; }
        .badge-livelihood { background: #EDE9FE; color: #5B21B6; }
        .badge-burial { background: #FEE2E2; color: #DC2626; }

        .status-pending { background: #FEF3C7; color: #D97706; }
        .status-approved { background: #D1FAE5; color: #059669; }
        .status-released { background: #DBEAFE; color: #1D4ED8; }
        .status-denied { background: #FEE2E2; color: #DC2626; }

        .btn-view {
            background: #EEF6F0;
            color: #1A5C3A;
            border: 1px solid #D4E8DC;
            transition: all .15s;
        }
        .btn-view:hover {
            background: #1A5C3A;
            color: #fff;
            border-color: #1A5C3A;
        }

        .btn-download {
            background: #EEF6F0;
            color: #1A5C3A;
            border: 1px solid #D4E8DC;
            transition: all .15s;
        }
        .btn-download:hover {
            background: #1A5C3A;
            color: #fff;
            border-color: #1A5C3A;
        }

        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: #fff !important; }
            .section-card { border: 1px solid #ccc !important; box-shadow: none !important; }
            .field-value { background: #fff !important; border-color: #ccc !important; }
        }

        ::-webkit-scrollbar {
            width: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(26, 92, 58, .2);
            border-radius: 2px;
        }
    </style>
</head>

<body class="min-h-screen flex">

    <?php require 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="ml-64 flex-1 flex flex-col min-h-screen">

        <!-- Top Bar -->
        <header class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20 no-print">
            <div class="flex items-center gap-2 text-[13px]">
                <a href="funds_aics.php" class="text-slate-400 hover:text-green-600">
                    <i class="fas fa-arrow-left mr-1"></i> AICS Transactions
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-green-600 font-semibold">AICS Details</span>
            </div>
        </header>

        <main class="p-6 overflow-y-auto">
            <div class="max-w-3xl mx-auto space-y-5">

                <!-- Page Title -->
                <div class="animate-fade-up flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-serif text-green-600">AICS Availment Details</h1>
                        <p class="text-[13px] text-slate-500 mt-0.5">Complete AICS transaction record – copy of your submitted availment.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="badge-subtype <?= $availment['type_badge'] ?>">
                            <?= htmlspecialchars((string) $availment['type'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="status-<?= strtolower($availment['status']) ?> px-3 py-1 rounded-full text-[11px] font-semibold">
                            <?= htmlspecialchars($availment['status'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <?php if ($availment['status'] === 'Released' && $availment['date_released']): ?>
                        <span class="text-[11px] text-slate-400">
                            <i class="far fa-clock mr-1"></i> Released <?= htmlspecialchars($availment['date_released'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <?php else: ?>
                        <span class="text-[11px] text-slate-400">
                            <i class="far fa-clock mr-1"></i> <?= htmlspecialchars($availment['last_updated'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ── AVAILMENT CONTENT ── -->
                <div class="space-y-4">

                    <!-- Client Information -->
                    <div class="section-card animate-fade-up-1">
                        <div class="section-head">
                            <div class="section-num"><i class="fas fa-user"></i></div>
                            <h2 class="text-[14px] font-semibold text-green-600">Client Information</h2>
                        </div>
                        <div class="section-body">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div>
                                    <label class="field-label">Client Name</label>
                                    <div class="field-value font-semibold text-green-700"><?= htmlspecialchars((string) $availment['client'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Client ID</label>
                                    <div class="field-value font-mono"><?= $availment['client_id'] ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Barangay</label>
                                    <div class="field-value"><?= htmlspecialchars((string) $availment['barangay'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Availment ID</label>
                                    <div class="field-value font-mono font-semibold text-green-700"><?= $availment['availment_id'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Details (Date Released removed) -->
                    <div class="section-card animate-fade-up-1">
                        <div class="section-head">
                            <div class="section-num"><i class="fas fa-receipt"></i></div>
                            <h2 class="text-[14px] font-semibold text-green-600">Transaction Details</h2>
                        </div>
                        <div class="section-body">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="field-label">Budget Source</label>
                                    <div class="field-value font-medium"><?= htmlspecialchars((string) $availment['budget_source'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Type</label>
                                    <div class="field-value">
                                        <span class="badge-subtype <?= $availment['type_badge'] ?>">
                                            <?= htmlspecialchars((string) $availment['type'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <label class="field-label">Amount</label>
                                    <div class="field-value font-bold text-green-600">₱<?= number_format($availment['amount'], 2) ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Date Applied</label>
                                    <div class="field-value"><?= $availment['date_applied'] ?></div>
                                </div>
                                <!-- Date Approved intentionally hidden from Transaction Details. -->
                            </div>
                        </div>
                    </div>

                    <!-- Subtype-Specific Section (ONLY the chosen subtype is shown) -->

                    <?php if ($availment['type'] === 'Medical'): ?>
                    <!-- Medical Section -->
                    <div class="section-card animate-fade-up-2">
                        <div class="section-head">
                            <div class="section-num"><i class="fas fa-user-md"></i></div>
                            <h2 class="text-[14px] font-semibold text-green-600">Medical Details</h2>
                        </div>
                        <div class="section-body">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Patient Name</label>
                                    <div class="field-value"><?= $availment['patient_name'] ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Age</label>
                                    <div class="field-value"><?= $availment['patient_age'] ?> years old</div>
                                </div>
                                <div>
                                    <label class="field-label">Relationship to Client</label>
                                    <div class="field-value"><?= $availment['patient_relationship'] ?></div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <?php elseif ($availment['type'] === 'Financial'): ?>
                    <!-- No Financial Details section: Purpose and Approval Date were removed per panel recommendation. -->

                    <?php elseif ($availment['type'] === 'Educational'): ?>
                    <!-- Educational Section -->
                    <div class="section-card animate-fade-up-2">
                        <div class="section-head">
                            <div class="section-num"><i class="fas fa-graduation-cap"></i></div>
                            <h2 class="text-[14px] font-semibold text-green-600">Educational Details</h2>
                        </div>
                        <div class="section-body">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Educational Level</label>
                                    <div class="field-value"><?= $availment['educational_level'] ?? '—' ?></div>
                                </div>
                                <div>
                                    <label class="field-label">School Name</label>
                                    <div class="field-value"><?= $availment['school_name'] ?? '—' ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Semester</label>
                                    <div class="field-value"><?= $availment['semester'] ?? '—' ?></div>
                                </div>
                                <div>
                                    <label class="field-label">School Year</label>
                                    <div class="field-value"><?= $availment['school_year'] ?? '—' ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Purpose</label>
                                    <div class="field-value"><?= $availment['purpose'] ?? '—' ?></div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <?php elseif ($availment['type'] === 'Livelihood'): ?>
                    <!-- Livelihood Section -->
                    <div class="section-card animate-fade-up-2">
                        <div class="section-head">
                            <div class="section-num"><i class="fas fa-briefcase"></i></div>
                            <h2 class="text-[14px] font-semibold text-green-600">Livelihood Details</h2>
                        </div>
                        <div class="section-body">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Business Name</label>
                                    <div class="field-value"><?= $availment['business_name'] ?? '—' ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Business Type</label>
                                    <div class="field-value"><?= $availment['business_type'] ?? '—' ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Business Location</label>
                                    <div class="field-value"><?= $availment['business_location'] ?? '—' ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Start-up Cost</label>
                                    <div class="field-value"><?= $availment['startup_cost'] ?? '—' ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Target Start Date</label>
                                    <div class="field-value"><?= $availment['target_start_date'] ?? '—' ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php elseif ($availment['type'] === 'Burial'): ?>
                    <!-- Burial Section -->
                    <div class="section-card animate-fade-up-2">
                        <div class="section-head">
                            <div class="section-num"><i class="fas fa-dove"></i></div>
                            <h2 class="text-[14px] font-semibold text-green-600">Burial Details</h2>
                        </div>
                        <div class="section-body">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Deceased Name</label>
                                    <div class="field-value"><?= $availment['deceased_name'] ?? '—' ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Date of Death</label>
                                    <div class="field-value"><?= $availment['date_of_death'] ?? '—' ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Relationship to Claimant</label>
                                    <div class="field-value"><?= $availment['relationship_to_claimant'] ?? '—' ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Funeral Home</label>
                                    <div class="field-value"><?= $availment['funeral_home'] ?? '—' ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Funeral Cost</label>
                                    <div class="field-value"><?= $availment['funeral_cost'] ?? '—' ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php endif; ?>

                    <!-- Remarks -->
                    <?php if (!empty($availment['remarks'])): ?>
                    <div class="section-card animate-fade-up-2">
                        <div class="section-head">
                            <div class="section-num"><i class="fas fa-comment"></i></div>
                            <h2 class="text-[14px] font-semibold text-green-600">Remarks</h2>
                        </div>
                        <div class="section-body">
                            <div class="field-value"><?= $availment['remarks'] ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Uploaded Documents -->
                    <div class="section-card animate-fade-up-2">
                        <div class="section-head">
                            <div class="section-num"><i class="fas fa-paperclip"></i></div>
                            <h2 class="text-[14px] font-semibold text-green-600">Uploaded Documents</h2>
                        </div>
                        <div class="section-body">
                            <div class="space-y-2">
                                <?php if (empty($availment['documents'])): ?>
                                    <p class="text-[12px] text-slate-400 italic">No documents on file for this availment.</p>
                                <?php endif; ?>
                                <?php foreach ($availment['documents'] as $doc): ?>
                                    <div class="attachment-item flex items-center gap-4 p-3 bg-slate-50 border border-slate-200 rounded-xl hover:border-green-400 transition-all">
                                        <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center text-red-500 flex-shrink-0">
                                            <i class="fas fa-file-pdf text-xl"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[13px] font-medium text-slate-700 truncate"><?= $doc['name'] ?></p>
                                            <p class="text-[11px] text-slate-400"><?= $doc['size'] ?> • Uploaded: <?= $doc['date'] ?></p>
                                        </div>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <a href="<?= htmlspecialchars($doc['path']) ?>" target="_blank" class="btn-view px-3 py-1.5 rounded-lg text-[12px] font-medium flex items-center gap-2">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="<?= htmlspecialchars($doc['path']) ?>" download class="btn-download px-3 py-1.5 rounded-lg text-[12px] font-medium flex items-center gap-2">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-3">
                                <i class="fas fa-info-circle mr-1"></i> All uploaded documents are stored securely.
                            </p>
                        </div>
                    </div>

                    <!-- Availment Metadata -->
                    <div class="section-card animate-fade-up-3">
                        <div class="section-head">
                            <div class="section-num"><i class="fas fa-info-circle"></i></div>
                            <h2 class="text-[14px] font-semibold text-green-600">Availment Metadata</h2>
                        </div>
                        <div class="section-body">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Availment ID</label>
                                    <div class="field-value font-mono font-semibold text-green-700"><?= $availment['availment_id'] ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Status</label>
                                    <div class="field-value">
                                        <span class="status-<?= strtolower($availment['status']) ?> px-3 py-1 rounded-full text-[11px] font-semibold">
                                            <?= $availment['status'] ?>
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <label class="field-label">Created By</label>
                                    <div class="field-value"><?= $availment['created_by'] ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Date Created</label>
                                    <div class="field-value text-slate-500"><?= $availment['date_created'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Print-only footer -->
                    <div class="print-only text-center text-[10px] text-slate-400 border-t border-slate-200 pt-4 mt-4">
                        <p>Generated by MSWDO San Enrique Information System • <?= date('F d, Y h:i A') ?></p>
                        <p>This is a computer-generated document. No signature required.</p>
                    </div>

                </div>

            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white px-6 py-3 text-[11px] text-slate-400 no-print">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <!-- Toast -->
    <div id="toast" class="fixed bottom-6 right-6 bg-green-700 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-green-300"></i>
        <span id="toastMsg">Printed successfully!</span>
    </div>

    <script>
        function showToast(msg) {
            const t = document.getElementById('toast');
            document.getElementById('toastMsg').textContent = msg;
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