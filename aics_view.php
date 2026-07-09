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
                <a href="aics_transactions.php" class="text-slate-400 hover:text-green-600">
                    <i class="fas fa-arrow-left mr-1"></i> AICS Transactions
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-green-600 font-semibold">AICS Details</span>
            </div>
        </header>

        <main class="p-6 overflow-y-auto">
            <div class="max-w-3xl mx-auto space-y-5">

                <?php
                // ── Sample AICS Availment Data ──
                // Change $availment['type'] to test different subtypes:
                // 'Medical', 'Financial', 'Educational', 'Livelihood', 'Burial'
                $availment = [
                    'availment_id' => 'AV-2026-018',
                    'client' => 'Maria Santos',
                    'client_id' => 1001,
                    'barangay' => 'Poblacion',
                    'type' => 'Medical',          // Change this to test different subtypes
                    'type_badge' => 'badge-medical',
                    'budget_source' => 'AICS FBML',
                    'amount' => 3500.00,
                    'date_applied' => 'April 10, 2026',
                    'date_approved' => 'April 12, 2026',
                    'status' => 'Released',
                    'status_badge' => 'status-released',
                    'remarks' => 'Patient admitted for pneumonia. Medical certificate and hospital bill submitted.',

                    // Medical-specific fields
                    'patient_name' => 'Maria Santos',
                    'patient_age' => 45,
                    'patient_relationship' => 'Self',
                    'medical_abstract' => 'Patient diagnosed with community-acquired pneumonia. Admitted for 3 days.',
                    'hospital_bill' => '₱12,500.00',

                    // Financial-specific fields (only used if type = Financial)
                    'financial_purpose' => 'Emergency cash assistance for medical expenses',
                    'financial_approval_date' => 'April 11, 2026',

                    // Educational-specific fields (only used if type = Educational)
                    'educational_level' => 'College',
                    'school_name' => 'University of Negros Occidental',
                    'semester' => '2nd Semester',
                    'school_year' => '2025-2026',
                    'purpose' => 'Tuition Fee',
                    'amount_breakdown' => 'Tuition: ₱8,000, Misc: ₱2,000',

                    // Livelihood-specific fields (only used if type = Livelihood)
                    'business_name' => 'Maria\'s Sari-Sari Store',
                    'business_type' => 'Sari-sari Store',
                    'startup_cost' => '₱15,000.00',
                    'target_start_date' => 'May 1, 2026',

                    // Burial-specific fields (only used if type = Burial)
                    'deceased_name' => 'Juan Santos',
                    'date_of_death' => 'April 8, 2026',
                    'relationship_to_claimant' => 'Spouse',
                    'funeral_home' => 'St. Peter Memorial Chapel',
                    'funeral_cost' => '₱25,000.00',

                    'documents' => [
                        ['name' => 'Medical Certificate (RHU)', 'file' => 'medcert_2026-018.pdf', 'size' => '189 KB', 'date' => 'Apr 10, 2026'],
                        ['name' => 'Laboratory Results', 'file' => 'lab_2026-018.pdf', 'size' => '245 KB', 'date' => 'Apr 10, 2026'],
                        ['name' => 'Valid ID (Maria Santos)', 'file' => 'id_2026-018.pdf', 'size' => '120 KB', 'date' => 'Apr 10, 2026'],
                        ['name' => 'Barangay Indigency Certificate', 'file' => 'indigency_2026-018.pdf', 'size' => '95 KB', 'date' => 'Apr 10, 2026'],
                        ['name' => 'Hospital Bill', 'file' => 'hospital_2026-018.pdf', 'size' => '320 KB', 'date' => 'Apr 11, 2026'],
                        ['name' => 'Discharge Summary', 'file' => 'discharge_2026-018.pdf', 'size' => '156 KB', 'date' => 'Apr 12, 2026'],
                    ],
                    'created_by' => 'Rosa T. Villanueva, RSW',
                    'date_created' => 'April 10, 2026 09:30 AM',
                    'last_updated' => 'April 14, 2026 02:00 PM',
                ];
                ?>

                <!-- Page Title -->
                <div class="animate-fade-up flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-serif text-green-600">AICS Availment Details</h1>
                        <p class="text-[13px] text-slate-500 mt-0.5">Complete AICS transaction record – copy of your submitted availment.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="badge-subtype <?= $availment['type_badge'] ?>">
                            <?= $availment['type'] ?>
                        </span>
                        <span class="status-<?= strtolower($availment['status']) ?> px-3 py-1 rounded-full text-[11px] font-semibold">
                            <?= $availment['status'] ?>
                        </span>
                        <span class="text-[11px] text-slate-400">
                            <i class="far fa-clock mr-1"></i> <?= $availment['last_updated'] ?>
                        </span>
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
                                    <div class="field-value font-semibold text-green-700"><?= $availment['client'] ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Client ID</label>
                                    <div class="field-value font-mono"><?= $availment['client_id'] ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Barangay</label>
                                    <div class="field-value"><?= $availment['barangay'] ?></div>
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
                                    <div class="field-value font-medium"><?= $availment['budget_source'] ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Type</label>
                                    <div class="field-value">
                                        <span class="badge-subtype <?= $availment['type_badge'] ?>">
                                            <?= $availment['type'] ?>
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
                                <div>
                                    <label class="field-label">Date Approved</label>
                                    <div class="field-value"><?= $availment['date_approved'] ?? '—' ?></div>
                                </div>
                                <!-- Date Released REMOVED -->
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
                                <div>
                                    <label class="field-label">Medical Abstract</label>
                                    <div class="field-value"><?= $availment['medical_abstract'] ?? '—' ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Hospital Bill</label>
                                    <div class="field-value"><?= $availment['hospital_bill'] ?? '—' ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php elseif ($availment['type'] === 'Financial'): ?>
                    <!-- Financial Section -->
                    <div class="section-card animate-fade-up-2">
                        <div class="section-head">
                            <div class="section-num"><i class="fas fa-coins"></i></div>
                            <h2 class="text-[14px] font-semibold text-green-600">Financial Details</h2>
                        </div>
                        <div class="section-body">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Purpose</label>
                                    <div class="field-value"><?= $availment['financial_purpose'] ?? '—' ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Approval Date</label>
                                    <div class="field-value"><?= $availment['financial_approval_date'] ?? '—' ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                <div>
                                    <label class="field-label">Amount Breakdown</label>
                                    <div class="field-value"><?= $availment['amount_breakdown'] ?? '—' ?></div>
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
                                            <a href="#" class="btn-view px-3 py-1.5 rounded-lg text-[12px] font-medium flex items-center gap-2">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="#" class="btn-download px-3 py-1.5 rounded-lg text-[12px] font-medium flex items-center gap-2">
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