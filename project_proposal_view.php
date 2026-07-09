<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fund Request Details – MSWDO San Enrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            content: [
                './*.php',
                './**/*.php',
                './*.html'
            ]
        }
    </script>
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

        .program-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-4ps { background: #EDE9FE; color: #5B21B6; }
        .badge-solo { background: #CCFBF1; color: #0F766E; }
        .badge-senior { background: #FEF3C7; color: #92400E; }
        .badge-pwd { background: #DBEAFE; color: #1E40AF; }
        .badge-daycare { background: #FFEDD5; color: #C2410C; }
        .badge-slp { background: #FEF3C7; color: #D97706; }
        .badge-sfp { background: #D1FAE5; color: #15803D; }
        .badge-women { background: #F3E8FF; color: #6D28D9; }

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
                <a href="fund_requests.php" class="text-slate-400 hover:text-green-600">
                    <i class="fas fa-arrow-left mr-1"></i> Fund Requests
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-green-600 font-semibold">Request Details</span>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="text-[12px] font-medium text-slate-600 border border-slate-200 rounded-lg px-3 py-1.5 hover:border-green-400 hover:text-green-600 transition-all">
                    <i class="fas fa-print mr-1"></i> Print
                </button>
                <a href="fund_request_update.php?id=FR-2026-001" class="text-[12px] font-semibold text-white bg-green-600 rounded-lg px-4 py-1.5 hover:bg-green-500 transition-all flex items-center gap-1.5">
                    <i class="fas fa-edit"></i> Update Request
                </a>
            </div>
        </header>

        <main class="p-6 overflow-y-auto">
            <div class="max-w-3xl mx-auto space-y-5">

                <!-- Page Title -->
                <div class="animate-fade-up flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-serif text-green-600">Fund Request Details</h1>
                        <p class="text-[13px] text-slate-500 mt-0.5">Complete fund request record – copy of your submitted information.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="program-badge <?= $request['program_badge'] ?>">
                            <?= $request['program'] ?>
                        </span>
                        <span class="text-[11px] text-slate-400">
                            <i class="far fa-clock mr-1"></i> <?= $request['date_submitted'] ?>
                        </span>
                    </div>
                </div>

                <!-- ── FUND REQUEST CONTENT ── -->
                <div class="space-y-4">

                    <!-- Project Information -->
                    <div class="section-card animate-fade-up-1">
                        <div class="section-head">
                            <div class="section-num">I</div>
                            <h2 class="text-[14px] font-semibold text-green-600">Project Information</h2>
                            <div class="ml-auto text-[11px] text-slate-400">
                                <i class="fas fa-check-circle text-emerald-500 mr-1"></i>
                                Status: <span class="font-medium text-emerald-600"><?= $request['status'] ?></span>
                            </div>
                        </div>
                        <div class="section-body space-y-4">

                            <div>
                                <label class="field-label">A. Title / Name of Project</label>
                                <div class="field-value font-semibold"><?= $request['title'] ?></div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="field-label">B. Duration</label>
                                    <div class="field-value"><?= $request['duration'] ?></div>
                                </div>
                                <div>
                                    <label class="field-label">From</label>
                                    <div class="field-value"><?= $request['date_from'] ?></div>
                                </div>
                                <div>
                                    <label class="field-label">To</label>
                                    <div class="field-value"><?= $request['date_to'] ?></div>
                                </div>
                            </div>

                            <div>
                                <label class="field-label">C. Venue</label>
                                <div class="field-value"><?= $request['venue'] ?></div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">D. No. of Participants</label>
                                    <div class="field-value font-semibold"><?= $request['num_participants'] ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Description of Participants</label>
                                    <div class="field-value"><?= $request['participant_desc'] ?></div>
                                </div>
                            </div>

                            <div>
                                <label class="field-label">E. Budgetary Requirement (₱)</label>
                                <div class="field-value font-bold text-green-600">₱<?= number_format($request['budget'], 2) ?></div>
                            </div>

                            <div>
                                <label class="field-label">F. Source of Fund</label>
                                <div class="field-value"><?= $request['fund_source'] ?></div>
                            </div>

                        </div>
                    </div>

                    <!-- Attached Document -->
                    <div class="section-card animate-fade-up-2">
                        <div class="section-head">
                            <div class="section-num"><i class="fas fa-file-upload"></i></div>
                            <h2 class="text-[14px] font-semibold text-green-600">Attached Document</h2>
                        </div>
                        <div class="section-body">
                            <div class="attachment-item flex items-center gap-4 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                                <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center text-red-500 flex-shrink-0">
                                    <i class="fas fa-file-pdf text-2xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[13px] font-semibold text-slate-800 truncate"><?= $request['document_name'] ?></p>
                                    <p class="text-[11px] text-slate-400"><?= $request['document_size'] ?> • Uploaded: <?= $request['date_submitted'] ?></p>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <a href="#" class="btn-view px-4 py-2 rounded-lg text-[12px] font-medium flex items-center gap-2">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="#" class="btn-download px-4 py-2 rounded-lg text-[12px] font-medium flex items-center gap-2">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-3">
                                <i class="fas fa-info-circle mr-1"></i> The complete project proposal document is attached above.
                            </p>
                        </div>
                    </div>

                    <!-- Request Metadata -->
                    <div class="section-card animate-fade-up-2">
                        <div class="section-head">
                            <div class="section-num"><i class="fas fa-info-circle"></i></div>
                            <h2 class="text-[14px] font-semibold text-green-600">Request Metadata</h2>
                        </div>
                        <div class="section-body">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Request ID</label>
                                    <div class="field-value font-mono font-semibold text-green-700"><?= $request['id'] ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Program</label>
                                    <div class="field-value">
                                        <span class="program-badge <?= $request['program_badge'] ?>">
                                            <?= $request['program'] ?>
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <label class="field-label">Submitted By</label>
                                    <div class="field-value"><?= $request['submitted_by'] ?></div>
                                </div>
                                <div>
                                    <label class="field-label">Date Submitted</label>
                                    <div class="field-value text-slate-500"><?= $request['date_submitted'] ?></div>
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