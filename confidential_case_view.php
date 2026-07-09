<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';

$caseNumber = trim($_GET['id'] ?? '');
if ($caseNumber === '') {
    header('Location: confidential_case_search.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT wc.*,
           c.cl_firstname, c.cl_lastname,
           b.barangay_name,
           u.user_firstname, u.user_lastname
    FROM woman_and_children wc
    LEFT JOIN CLIENT c ON c.client_id = wc.client_id
    LEFT JOIN BARANGAY b ON b.barangay_id = c.brgy_id
    LEFT JOIN MSWDO_USER u ON u.user_id = wc.user_id
    WHERE wc.wc_case_number = ?
    LIMIT 1
");
$stmt->execute([$caseNumber]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header('Location: confidential_case_search.php');
    exit;
}

// wc_attachments is stored as JSON, e.g. {"blotter":"file.pdf","photos":["a.jpg","b.jpg"]}.
// Turn it into a flat list the template can loop over, with a friendly label per type
// and the real file size read straight off disk.
$attachmentLabels = [
    'blotter' => 'Police Blotter / Incident Report',
    'medical' => 'Medical / Medico-Legal Records',
    'court'   => 'Court / Legal Documents',
    'photos'  => 'Photograph / Visual Evidence',
    'other'   => 'Other Supporting Document',
];
$supportingDocs = [];
if (!empty($row['wc_attachments'])) {
    $decoded = json_decode($row['wc_attachments'], true);
    if (is_array($decoded)) {
        foreach ($decoded as $key => $val) {
            $files = is_array($val) ? $val : [$val];
            foreach ($files as $filename) {
                $path = __DIR__ . '/Uploads/confidential/' . $filename;
                $supportingDocs[] = [
                    'name' => $attachmentLabels[$key] ?? ucfirst($key),
                    'file' => $filename,
                    'size' => file_exists($path) ? round(filesize($path) / 1024) . ' KB' : 'File not found',
                ];
            }
        }
    }
}

$case = [
    'case_id'         => $row['wc_case_number'] ?? ('WC-' . $row['protection_id']),
    'case_type'       => $row['wc_case_type'],
    'status'          => $row['wc_status'],
    'client'          => trim(($row['cl_firstname'] ?? '') . ' ' . ($row['cl_lastname'] ?? '')) ?: 'Unknown Client',
    'client_id'       => $row['client_id'] ?? '—',
    'barangay'        => $row['barangay_name'] ?? '—',
    'incident_date'   => $row['wc_incident_date'],
    'incident_place'  => $row['wc_incident_place'],
    'narrative'       => $row['wc_narrative'],
    'offender_info'   => $row['wc_offender_info'] ?: 'None reported',
    'witness_info'    => $row['wc_witness_info'] ?: 'None reported',
    'actions_taken'   => $row['wc_actions_taken'] ?: 'None recorded',
    'assigned_worker' => $row['wc_assigned_worker'],
    'date_created'    => $row['wc_date_created'] ? (new DateTime($row['wc_date_created']))->format('F j, Y g:i A') : '—',
    'created_by'      => trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? '')) ?: '—',
    'supporting_docs' => $supportingDocs,
];

$flashMsg = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Case Summary – MSWDO San Enrique</title>
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
                        slate2: '#F4F7FC',
                    },
                    keyframes: {
                        fadeUp: { '0%': { opacity: '0', transform: 'translateY(10px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
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
        }

        .sidebar-item {
            transition: all .15s;
        }
        .sidebar-item:hover {
            background: rgba(255, 255, 255, .07);
            color: rgba(255, 255, 255, .95);
        }
        .sidebar-item.active {
            background: rgba(26, 92, 58, .25);
            border-left-color: #C49A2A;
            color: #fff;
        }

        .summary-card {
            background: #fff;
            border-radius: 1rem;
            border: 1px solid #D4E8DC;
            overflow: hidden;
        }
        .summary-header {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .875rem 1.5rem;
            border-bottom: 1px solid #D4E8DC;
            background: #EEF6F0;
        }
        .summary-header .icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #1A5C3A;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }
        .summary-body {
            padding: 1.5rem;
        }

        .field-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #4A7A5A;
            margin-bottom: 2px;
            display: block;
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
        .field-value-readonly {
            background: #F8FAFC;
            border-color: #E2E8F0;
            color: #475569;
        }

        .badge-vawc { background: #FEE2E2; color: #DC2626; }
        .badge-cicl { background: #FEF3C7; color: #D97706; }
        .badge-childabuse { background: #F3E8FF; color: #6D28D9; }
        .badge-car { background: #DBEAFE; color: #1D4ED8; }

        .status-active { background: #FEE2E2; color: #DC2626; }
        .status-monitoring { background: #FEF3C7; color: #D97706; }
        .status-resolved { background: #D1FAE5; color: #059669; }
        .status-closed { background: #E2E8F0; color: #475569; }
        .status-referred { background: #DBEAFE; color: #1D4ED8; }

        .confidential-badge {
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FCA5A5;
        }

        .attachment-item {
            transition: all .15s;
            cursor: pointer;
        }
        .attachment-item:hover {
            background: #EEF6F0;
            border-color: #1A5C3A;
        }

        .print-only {
            display: none;
        }

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
            .summary-card { border: 1px solid #ccc !important; box-shadow: none !important; }
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

<body class="bg-slate2 min-h-screen flex">

    <?php require 'sidebar.php'; ?>

    <div class="ml-64 flex-1 flex flex-col min-h-screen">

        <!-- Topbar -->
        <header class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20 animate-fade-up no-print">
            <div class="flex items-center gap-2 text-[13px]">
                <a href="confidential_case_search.php" class="text-slate-400 hover:text-green-600">
                    <i class="fas fa-arrow-left mr-1"></i> Confidential Cases
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-green-600 font-semibold">Case Summary</span>
            </div>
        </header>

        <main class="flex-1 p-6 space-y-5 overflow-y-auto">

            <!-- ── PAGE TITLE ── -->
            <?php
            $typeBadgeCls = [
                'VAWC' => 'badge-vawc', 'CICL' => 'badge-cicl',
                'Child Abuse' => 'badge-childabuse', 'CAR' => 'badge-car',
            ][$case['case_type']] ?? 'bg-slate-100 text-slate-700';
            $statusBadgeCls = [
                'Active' => 'status-active', 'Monitoring' => 'status-monitoring',
                'Resolved' => 'status-resolved', 'Closed' => 'status-closed', 'Referred' => 'status-referred',
            ][$case['status']] ?? 'bg-slate-100 text-slate-700';
            ?>
            <div class="animate-fade-up flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h1 class="text-xl font-serif text-green-600">Confidential Case Summary</h1>
                        <span class="<?= $typeBadgeCls ?> px-2.5 py-0.5 rounded text-[11px] font-semibold"><?= htmlspecialchars($case['case_type']) ?></span>
                        <span class="<?= $statusBadgeCls ?> px-2.5 py-0.5 rounded-full text-[11px] font-semibold"><?= htmlspecialchars($case['status']) ?></span>
                    </div>
                    <p class="text-[13px] text-slate-500 mt-0.5">Complete case record – copy of your submitted case information.</p>
                </div>
            </div>

            <!-- ── CASE SUMMARY CONTENT ── -->
            <div class="space-y-4">

                <!-- Case Header -->
                <div class="summary-card animate-fade-up-1">
                    <div class="summary-header">
                        <div class="icon"><i class="fas fa-folder-open"></i></div>
                        <h2 class="text-[14px] font-semibold text-green-600">Case Overview</h2>
                    </div>
                    <div class="summary-body">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="field-label">Case ID</label>
                                <div class="field-value font-mono font-semibold text-green-700"><?= htmlspecialchars($case['case_id']) ?></div>
                            </div>
                            <div>
                                <label class="field-label">Client Name</label>
                                <div class="field-value font-semibold"><?= htmlspecialchars($case['client']) ?></div>
                            </div>
                            <div>
                                <label class="field-label">Client ID</label>
                                <div class="field-value font-mono"><?= htmlspecialchars((string)$case['client_id']) ?></div>
                            </div>
                            <div>
                                <label class="field-label">Barangay</label>
                                <div class="field-value"><?= htmlspecialchars($case['barangay']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Incident Details -->
                <div class="summary-card animate-fade-up-1">
                    <div class="summary-header">
                        <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                        <h2 class="text-[14px] font-semibold text-green-600">Incident Details</h2>
                    </div>
                    <div class="summary-body">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="field-label">Incident Date</label>
                                <div class="field-value"><?= htmlspecialchars($case['incident_date']) ?></div>
                            </div>
                            <div>
                                <label class="field-label">Incident Place</label>
                                <div class="field-value"><?= htmlspecialchars($case['incident_place']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Narrative Report -->
                <div class="summary-card animate-fade-up-2">
                    <div class="summary-header">
                        <div class="icon"><i class="fas fa-file-alt"></i></div>
                        <h2 class="text-[14px] font-semibold text-green-600">Narrative Report</h2>
                    </div>
                    <div class="summary-body">
                        <div class="field-value whitespace-pre-wrap leading-relaxed"><?= nl2br(htmlspecialchars($case['narrative'])) ?></div>
                    </div>
                </div>

                <!-- Offender & Witness -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="summary-card animate-fade-up-2">
                        <div class="summary-header">
                            <div class="icon"><i class="fas fa-user-slash"></i></div>
                            <h2 class="text-[14px] font-semibold text-green-600">Offender Information</h2>
                        </div>
                        <div class="summary-body">
                            <div class="field-value text-[13px] leading-relaxed"><?= nl2br(htmlspecialchars($case['offender_info'])) ?></div>
                        </div>
                    </div>
                    <div class="summary-card animate-fade-up-2">
                        <div class="summary-header">
                            <div class="icon"><i class="fas fa-users"></i></div>
                            <h2 class="text-[14px] font-semibold text-green-600">Witness Information</h2>
                        </div>
                        <div class="summary-body">
                            <div class="field-value text-[13px] leading-relaxed"><?= nl2br(htmlspecialchars($case['witness_info'])) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Actions Taken -->
                <div class="summary-card animate-fade-up-3">
                    <div class="summary-header">
                        <div class="icon"><i class="fas fa-check-double"></i></div>
                        <h2 class="text-[14px] font-semibold text-green-600">Actions Taken</h2>
                    </div>
                    <div class="summary-body">
                        <div class="field-value whitespace-pre-wrap leading-relaxed"><?= nl2br(htmlspecialchars($case['actions_taken'])) ?></div>
                    </div>
                </div>

                <!-- Supporting Documents -->
                <div class="summary-card animate-fade-up-3">
                    <div class="summary-header">
                        <div class="icon"><i class="fas fa-paperclip"></i></div>
                        <h2 class="text-[14px] font-semibold text-green-600">Supporting Documents</h2>
                    </div>
                    <div class="summary-body">
                        <?php if (empty($case['supporting_docs'])): ?>
                            <p class="text-[12px] text-slate-400 italic">No supporting documents were attached to this case.</p>
                        <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($case['supporting_docs'] as $doc): ?>
                                <div class="attachment-item flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-lg hover:border-green-400 transition-all">
                                    <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center text-red-500 flex-shrink-0">
                                        <i class="fas fa-file-pdf text-xl"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[13px] font-medium text-slate-700 truncate"><?= htmlspecialchars($doc['name']) ?></p>
                                        <p class="text-[11px] text-slate-400"><?= htmlspecialchars($doc['size']) ?> · Case created: <?= htmlspecialchars($case['date_created']) ?></p>
                                    </div>
                                    <a href="Uploads/confidential/<?= urlencode($doc['file']) ?>" target="_blank" class="btn-view px-4 py-2 rounded-lg text-[12px] font-medium flex items-center gap-2">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="Uploads/confidential/<?= urlencode($doc['file']) ?>" download class="btn-download px-4 py-2 rounded-lg text-[12px] font-medium flex items-center gap-2">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <p class="text-[11px] text-slate-400 mt-3">
                            <i class="fas fa-info-circle mr-1"></i> Documents are stored securely. Access is restricted to authorized personnel.
                        </p>
                    </div>
                </div>

                <!-- Case Metadata -->
                <div class="summary-card animate-fade-up-3">
                    <div class="summary-header">
                        <div class="icon"><i class="fas fa-info-circle"></i></div>
                        <h2 class="text-[14px] font-semibold text-green-600">Case Metadata</h2>
                    </div>
                    <div class="summary-body">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="field-label">Assigned Worker</label>
                                <div class="field-value font-medium"><?= htmlspecialchars($case['assigned_worker']) ?></div>
                            </div>
                            <div>
                                <label class="field-label">Date Created</label>
                                <div class="field-value text-slate-500"><?= htmlspecialchars($case['date_created']) ?></div>
                            </div>
                            <div>
                                <label class="field-label">Created By</label>
                                <div class="field-value text-slate-500"><?= htmlspecialchars($case['created_by']) ?></div>
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

        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400 no-print">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-6 right-6 bg-green-700 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-green-300"></i>
        <span id="toastMsg">Printed successfully!</span>
    </div>

    <script>
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
            }
        });

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

        <?php if ($flashMsg): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showToast(<?= json_encode($flashMsg) ?>);
        });
        <?php endif; ?>
    </script>
</body>

</html>