<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';
require 'export_prepared_by.php';

//  Flash message (set by budgetmanagementaction.php after a redirect) 
$flashMsg = $_GET['msg'] ?? '';
$flashType = $_GET['type'] ?? 'success';

$programStmt = $pdo->query("
SELECT
    p.program_id,
    p.program_name,
    p.prog_period,
    p.prog_annual_budget,
    p.prog_start_date,
    p.prog_end_date,
    p.prog_period_started_at,

    COALESCE(p.prog_current_period, 1) AS current_period,
    COALESCE(p.prog_early_end_count, 0) AS early_end_count,

    COALESCE((
        SELECT SUM(a.av_amount)
        FROM availment a
        WHERE a.program_id = p.program_id
          AND a.av_status = 'Released'
          AND a.av_date_released IS NOT NULL
          AND a.av_date_released >= COALESCE(p.prog_period_started_at, '1970-01-01')
    ), 0) AS spent_availment,

    COALESCE((
        SELECT SUM(pp.pp_budget)
        FROM project_proposal pp
        WHERE pp.program_id = p.program_id
          AND pp.pp_status = 'Released'
          AND pp.pp_date_released IS NOT NULL
          AND pp.pp_date_released >= COALESCE(p.prog_period_started_at, '1970-01-01')
    ), 0) AS spent_proposals,

    COALESCE((
        SELECT SUM(a2.av_amount)
        FROM availment a2
        WHERE a2.program_id = p.program_id
          AND a2.av_status = 'Released'
          AND a2.av_date_released IS NOT NULL
          AND a2.av_date_released >= COALESCE(p.prog_period_started_at, '1970-01-01')
    ), 0)
    +
    COALESCE((
        SELECT SUM(pp2.pp_budget)
        FROM project_proposal pp2
        WHERE pp2.program_id = p.program_id
          AND pp2.pp_status = 'Released'
          AND pp2.pp_date_released IS NOT NULL
          AND pp2.pp_date_released >= COALESCE(p.prog_period_started_at, '1970-01-01')
    ), 0) AS spent,

    COALESCE((
        SELECT COUNT(DISTINCT a3.client_id)
        FROM availment a3
        WHERE a3.program_id = p.program_id
          AND a3.av_status IN ('Approved', 'Released')
    ), 0)
    +
    COALESCE((
        SELECT SUM(pp3.pp_num_participants)
        FROM project_proposal pp3
        WHERE pp3.program_id = p.program_id
          AND pp3.pp_status IN ('Approved', 'Released')
    ), 0) AS beneficiaries

FROM program p
ORDER BY p.program_id
");

$programs = $programStmt->fetchAll(PDO::FETCH_ASSOC);

$budgetDataPhp = array_map(function ($p) {
    return [
        'id' => (int) $p['program_id'],
        'program' => $p['program_name'],
        'period' => $p['prog_period'],
        'totalBudget' => (float) $p['prog_annual_budget'],
        'spent' => (float) $p['spent'],
        'currentPeriod' => (int) $p['current_period'],
        'earlyEndCount' => (int) $p['early_end_count'],
        'beneficiaries' => (int) $p['beneficiaries']
    ];
}, $programs);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Budget Management – MSWDO San Enrique</title>
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
                    fontFamily: {
                        sans: ['DM Sans', 'sans-serif'],
                        serif: ['DM Serif Display', 'serif'],
                    },
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
                        gold: {
                            DEFAULT: '#C49A2A',
                            50: '#FBF5E6',
                            100: '#F5E4B3',
                            400: '#C49A2A',
                        },
                        slate2: '#F4F7FC',
                    },
                    keyframes: {
                        fadeUp: { '0%': { opacity: '0', transform: 'translateY(12px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        modalIn: { '0%': { opacity: '0', transform: 'scale(0.95) translateY(10px)' }, '100%': { opacity: '1', transform: 'scale(1) translateY(0)' } },
                        pulse2: { '0%,100%': { opacity: '1' }, '50%': { opacity: '.5' } },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.4s ease both',
                        'fade-up-1': 'fadeUp 0.4s ease 0.05s both',
                        'fade-up-2': 'fadeUp 0.4s ease 0.1s both',
                        'fade-up-3': 'fadeUp 0.4s ease 0.15s both',
                        'modal-in': 'modalIn 0.3s ease both',
                        'pulse2': 'pulse2 2s ease-in-out infinite',
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
            transition: all .15s ease;
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

        .stat-card {
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(26, 92, 58, .1);
        }

        .btn-action {
            transition: all .15s ease;
        }

        .btn-action:hover {
            transform: translateY(-1px);
        }

        .table-row {
            transition: background .12s;
        }

        .table-row:hover {
            background: #EEF6F0;
        }

        .field {
            display: block;
            width: 100%;
            border-radius: .75rem;
            border: 1.5px solid #D4E8DC;
            background: #FAFCFB;
            padding: .625rem .875rem;
            font-size: 13px;
            color: #1e293b;
            outline: none;
            font-family: 'DM Sans', sans-serif;
            transition: all .2s;
        }

        .field:focus {
            border-color: #1A5C3A;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(26, 92, 58, .12);
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

        textarea.field {
            resize: vertical;
            line-height: 1.7;
        }

        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #4A7A5A;
            margin-bottom: 6px;
        }

        .req::after {
            content: '*';
            color: #EF4444;
            margin-left: 2px;
        }

        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(26, 92, 58, .2);
            border-radius: 2px;
        }

        .modal-backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .budget-bar-fill {
            transition: width 1s cubic-bezier(.4, 0, .2, 1);
        }

        .status-critical {
            background: #FEE2E2;
            color: #DC2626;
        }

        .status-warning {
            background: #FEF3C7;
            color: #D97706;
        }

        .status-ok {
            background: #D1FAE5;
            color: #059669;
        }

        .badge-period {
            background: #E0E7FF;
            color: #4338CA;
        }

        .forecast-table th {
            background: #EEF6F0;
            font-weight: 600;
            color: #1A5C3A;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 8px 12px;
            border-bottom: 1px solid #D4E8DC;
            text-align: left;
        }

        .forecast-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #D4E8DC;
            font-size: 12px;
        }

        .forecast-table tr:hover td {
            background: #EEF6F0;
        }

        .forecast-total {
            background: #EEF6F0;
            font-weight: 700;
            border-top: 2px solid #1A5C3A;
        }

        .forecast-total td {
            padding: 10px 12px;
            font-size: 13px;
        }

        .forecast-note {
            background: #F0FDF4;
            border: 1px solid #A7F3D0;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 12px;
            color: #065F46;
            margin-top: 12px;
        }

        .forecast-note strong {
            color: #065F46;
        }

        .bg-gray-100 {
            background-color: #F3F4F6;
        }

        .cursor-not-allowed {
            cursor: not-allowed;
        }

        .opacity-75 {
            opacity: 0.75;
        }
    </style>
</head>

<body class="bg-slate2 min-h-screen flex">

    <!-- ══════════════════════════ SIDEBAR ══════════════════════════ -->
    <?php require 'sidebar.php'; ?>
    <!-- ══════════════════════════ MAIN ══════════════════════════════ -->
    <div class="ml-64 flex-1 flex flex-col min-h-screen">

        <!-- Top Bar -->
        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <span class="text-green-600 font-semibold">Budget Management</span>
            </div>
        </header>

        <main class="flex-1 p-6 space-y-5 overflow-y-auto">

            <!-- Page Title -->
            <div class="flex flex-wrap items-center justify-between gap-3 animate-fade-up">
                <div>
                    <h1 class="text-xl font-serif text-green-600">Budget Management</h1>
                    <p class="text-[13px] text-slate-500 mt-0.5">Monitor program budgets, augment funds, advance periods
                        early, and forecast next year's budget.</p>
                </div>
            </div>

            <!-- ── Summary Stats ── -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 animate-fade-up-1">
                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total Budget</p>
                    <p class="text-2xl font-bold text-green-600" id="totalBudgetAll">₱0</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total Spent</p>
                    <p class="text-2xl font-bold text-amber-600" id="totalSpentAll">₱0</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">Current Period Remaining</p>
                    <p class="text-2xl font-bold text-blue-600" id="totalRemainingAll">₱0</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">Avg Utilization</p>
                    <p class="text-2xl font-bold text-green-600" id="avgUtilization">0%</p>
                </div>
            </div>

            <!-- ── Budget Table ── -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-up-2">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h2 class="text-[13px] font-semibold text-green-600">Program Budgets</h2>
                    <div class="flex items-center gap-2">
                        <button onclick="openAugmentModal()"
                            class="btn-action text-[12px] font-semibold text-white bg-amber-600 rounded-lg px-3 py-1.5 hover:bg-amber-700 transition-all flex items-center gap-1.5">
                            <i class="fas fa-hand-holding-usd"></i> Augmentation
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-[12px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Program</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Period</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Total Budget</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Total Period Budget</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Spent</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Remaining</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Utilization</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Status</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="budgetTableBody">
                            <!-- Rows injected by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── Budget Analysis & Forecast ── -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 animate-fade-up-3">

                <!-- Budget Analysis Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <h2 class="text-[13px] font-semibold text-green-600 mb-4"><i
                            class="fas fa-chart-pie mr-1.5 text-green-400"></i>Budget Analysis</h2>
                    <div class="space-y-3" id="analysisContent">
                        <!-- Injected by JS -->
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <button onclick="showAnalysisReport()"
                            class="text-[11px] font-medium text-green-600 hover:text-green-800 transition-colors">
                            <i class="fas fa-file-alt mr-1"></i> View Full Analysis Report
                        </button>
                    </div>
                </div>

                <!-- Forecast Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-[13px] font-semibold text-green-600"><i
                                class="fas fa-chart-line mr-1.5 text-green-400"></i>Forecast</h2>
                    </div>
                    <div id="forecastContent">
                        <!-- Injected by JS -->
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <button onclick="openForecastModal()"
                            class="text-[11px] font-medium text-emerald-600 hover:text-emerald-800 transition-colors">
                            <i class="fas fa-calculator mr-1"></i> Propose Next Year's Budget
                        </button>
                    </div>
                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer
            class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <!-- ══════════════════════════ AUGMENT BUDGET MODAL ══════════════════════════ -->
    <div id="augmentModal" class="fixed inset-0 z-50 flex items-center justify-center modal-backdrop hidden">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto animate-modal-in">
            <div
                class="sticky top-0 bg-white z-10 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-[16px] font-semibold text-green-600">Augment Budget</h2>
                <button onclick="closeAugmentModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <form id="augmentForm" method="POST" action="budgetmanagementaction.php"
                    onsubmit="return validateAugmentationForm();">
                    <input type="hidden" name="action" value="augment">
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
                        <p class="text-[12px] text-slate-600">Augmentation adds funds from savings to a program. You can
                            either add funds from an external source or transfer from another program's remaining
                            budget.</p>
                    </div>
                    <div>
                        <label class="field-label req">Program to Augment</label>
                        <select name="target_program_id" id="augmentTargetProgram" class="field" required>
                            <option value="">Select Program</option>
                            <?php foreach ($programs as $p): ?>
                                <option value="<?= $p['program_id'] ?>"><?= htmlspecialchars($p['program_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="field-label req">Additional Amount (₱)</label>
                        <input type="number" min="0" step="0.01" name="amount" id="augmentAmount" class="field"
                            placeholder="0.00" required />
                    </div>
                    <div>
                        <label class="field-label req">Source of Augmentation</label>
                        <select name="source" id="augmentSource" class="field" required
                            onchange="toggleAugmentSourceFields()">
                            <option value="">Select Source</option>
                            <option value="From another program">From another program</option>
                            <option value="LGU Supplemental Budget">LGU Supplemental Budget</option>
                            <option value="Mayor's Office">Mayor's Office</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <!-- Dynamic fields for "From another program" -->
                    <div id="augmentTransferFields" style="display:none;">
                        <div>
                            <label class="field-label req">Transfer From Program</label>
                            <select name="donor_program_id" id="augmentDonorProgram" class="field" disabled>
                                <option value="">Select Program</option>
                                <?php foreach ($programs as $p): ?>
                                    <option value="<?= $p['program_id'] ?>"><?= htmlspecialchars($p['program_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-[10px] text-slate-400 mt-1" id="donorRemainingDisplay">Program remaining: ₱0
                            </p>
                        </div>
                    </div>

                    <!-- Dynamic fields for "Other" -->
                    <div id="augmentOtherFields" style="display:none;">
                        <div>
                            <label class="field-label req">Specify Source</label>
                            <input type="text" name="other_source" id="augmentOtherSource" class="field" disabled
                                placeholder="e.g., DSWD Emergency Fund" />
                        </div>
                    </div>

                    <div>
                        <label class="field-label">Reason</label>
                        <textarea name="reason" id="augmentReason" class="field" rows="2"
                            placeholder="Reason for augmentation..."></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" onclick="closeAugmentModal()"
                            class="text-[13px] font-medium text-slate-600 border border-slate-200 rounded-xl px-5 py-2 hover:border-green-400 hover:text-green-600 transition-all">
                            Cancel
                        </button>
                        <button type="submit"
                            class="text-[13px] font-semibold text-white bg-amber-600 rounded-xl px-6 py-2 hover:bg-amber-500 transition-all">
                            <i class="fas fa-hand-holding-usd mr-1.5"></i> Augment Budget
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
     BUDGET FORECAST MODAL
════════════════════════════════════════════════════════════════ -->
    <div id="forecastModal" class="fixed inset-0 z-50 flex items-center justify-center modal-backdrop hidden">

        <div
            class="bg-white rounded-2xl shadow-2xl w-[96vw] max-w-[1450px] mx-4 max-h-[94vh] overflow-y-auto animate-modal-in">

            <!-- Header -->
            <div
                class="sticky top-0 bg-white z-10 px-7 py-5 border-b border-slate-200 flex items-center justify-between">

                <div>
                    <h2 class="text-[18px] font-semibold text-green-600">
                        Budget Forecast
                    </h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Projected budget recommendation based on current utilization and demand.
                    </p>
                </div>

                <button onclick="closeForecastModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>

            </div>

            <!-- Content -->
            <div class="p-7 space-y-6">

                <!-- Methodology -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">

                    <p class="text-[13px] font-semibold text-blue-700">
                        <i class="fas fa-info-circle mr-1.5"></i>
                        Forecast Methodology
                    </p>

                    <p class="text-[12px] text-slate-600 mt-2 leading-relaxed">
                        The system evaluates financial utilization together with beneficiary
                        volume. Beneficiaries indicate demand volume only; no fixed
                        peso-per-beneficiary amount is assumed.
                    </p>

                    <p class="text-[12px] text-slate-600 mt-2 leading-relaxed">
                        High utilization with meaningful beneficiary volume may justify
                        an increase. Low utilization does not automatically justify
                        additional budget.
                    </p>

                    <p class="text-[12px] text-slate-600 mt-2 leading-relaxed">
                        The increase percentage is fully customizable. The default is
                        <strong>0%</strong>, meaning no additional increase is applied
                        unless the user enters one.
                    </p>

                </div>

                <!-- Per Program Forecast -->
                <div>

                    <div class="flex items-center justify-between mb-3">

                        <div>
                            <h3 class="text-[14px] font-semibold text-green-600">
                                Per-Program Forecast
                            </h3>

                            <p class="text-[11px] text-slate-400 mt-1">
                                Current budget utilization and recommended budget adjustment.
                            </p>
                        </div>

                    </div>

                    <div id="forecastDetails" class="border border-slate-200 rounded-xl overflow-hidden">
                        <!-- Injected by JavaScript -->
                    </div>

                </div>

                <!-- Bottom Controls -->
                <div class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-5">

                    <!-- Custom Increase -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-5">

                        <label class="field-label">
                            Custom Increase (%)
                        </label>

                        <div class="relative">

                            <input type="number" id="forecastIncreasePct" class="field pr-10 text-lg font-semibold"
                                min="0" max="100" step="0.1" value="0" oninput="updateForecastRecommendation()" />

                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 font-semibold">
                                %
                            </span>

                        </div>

                        <p class="text-[11px] text-slate-400 mt-2 leading-relaxed">
                            Enter the percentage only when an increase is justified.
                            Default is <strong>0%</strong>.
                        </p>

                    </div>

                    <!-- Calculation -->
                    <div id="forecastCalculation" class="bg-emerald-50 border border-emerald-200 rounded-xl p-5">

                        <div class="flex items-center justify-between mb-4">

                            <div>
                                <h3 class="text-[14px] font-semibold text-emerald-700">
                                    Forecast Calculation
                                </h3>

                                <p class="text-[11px] text-slate-500 mt-1">
                                    The increase is calculated from the program's eligible
                                    period budget.
                                </p>
                            </div>

                            <i class="fas fa-calculator text-emerald-500 text-lg"></i>

                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                            <div class="bg-white rounded-lg border border-emerald-100 p-4">
                                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Base Budget
                                </p>

                                <p id="forecastBaseBudget" class="text-[18px] font-bold text-slate-700 mt-1">
                                    ₱0.00
                                </p>
                            </div>

                            <div class="bg-white rounded-lg border border-emerald-100 p-4">
                                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Increase Amount
                                </p>

                                <p id="forecastIncreaseAmount" class="text-[18px] font-bold text-emerald-600 mt-1">
                                    ₱0.00
                                </p>
                            </div>

                            <div class="bg-white rounded-lg border border-emerald-100 p-4">
                                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Recommended Budget
                                </p>

                                <p id="forecastRecommended" class="text-[18px] font-bold text-green-700 mt-1">
                                    ₱0.00
                                </p>
                            </div>

                        </div>

                        <div id="forecastFormula"
                            class="mt-4 pt-4 border-t border-emerald-200 text-[12px] text-slate-600">
                            Formula:
                            <strong>Base Budget × Increase % = Increase Amount</strong>
                        </div>

                    </div>

                </div>

                <!-- Footer -->
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-5 border-t border-slate-200">

                    <p class="text-[11px] text-slate-400">
                        The forecast does not automatically change the actual program budget.
                    </p>

                    <button type="button" onclick="exportForecastPDF()"
                        class="text-[13px] font-semibold text-white bg-blue-600 rounded-xl px-7 py-3 hover:bg-blue-500 transition-all flex items-center justify-center gap-2">

                        <i class="fas fa-file-pdf"></i>
                        Generate Forecast PDF

                    </button>

                </div>

            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast"
        class="fixed bottom-6 right-6 bg-green-700 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-green-300"></i>
        <span id="toastMsg">Action completed!</span>
    </div>

    <script>
        // ── Real Budget Data (from PROGRAM table via PHP) ──
        let budgetData = <?= json_encode($budgetDataPhp) ?>;

        // ── Status functions ──
        function getStatus(remaining, total) {
            const pct = total > 0 ? (remaining / total) * 100 : 0;
            if (pct <= 15) return { label: 'Critical', class: 'status-critical' };
            if (pct <= 30) return { label: 'Warning', class: 'status-warning' };
            return { label: 'OK', class: 'status-ok' };
        }

        // ── Currency / period helpers ──
        function peso(value) {
            return '₱' + Number(value || 0).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function periodCount(period) {
            if (period === 'Quarterly') return 4;
            if (period === 'Half-Year') return 2;
            return 1;
        }

        function periodLabel(period, currentPeriod) {
            if (period === 'Quarterly') return `Q${currentPeriod}`;
            if (period === 'Half-Year') return currentPeriod === 1 ? '1st Half' : '2nd Half';
            return 'Annual';
        }

        function periodBudget(budget) {
            return budget.totalBudget / periodCount(budget.period);
        }

        // ── Render budget table ──
        function renderBudgets() {
            const tbody = document.getElementById('budgetTableBody');
            tbody.innerHTML = '';

            let totalBudget = 0, totalSpent = 0, totalRemaining = 0;

            budgetData.forEach(budget => {
                const totalPeriodBudget = periodBudget(budget);
                const spent = Number(budget.spent || 0);
                const remaining = Math.max(0, totalPeriodBudget - spent);
                const pct = totalPeriodBudget > 0 ? Math.min((spent / totalPeriodBudget) * 100, 100) : 0;
                const status = getStatus(remaining, totalPeriodBudget);
                const periods = periodCount(budget.period);
                const canEndEarly = budget.period !== 'Annually' && budget.currentPeriod < periods;
                const earlyUsesLeft = Math.max(0, periods - 1 - Number(budget.earlyEndCount || 0));

                totalBudget += budget.totalBudget;
                totalSpent += spent;
                totalRemaining += remaining;

                const tr = document.createElement('tr');
                tr.className = 'table-row';

                tr.innerHTML = `
                    <td class="px-5 py-3 font-medium text-green-700">${escapeHtml(budget.program)}</td>
                    <td class="px-5 py-3"><span class="badge-period px-2 py-0.5 rounded text-[10px] font-semibold">${escapeHtml(budget.period)} · ${periodLabel(budget.period, budget.currentPeriod)}</span></td>
                    <td class="px-5 py-3 font-semibold text-slate-700">${peso(budget.totalBudget)}</td>
                    <td class="px-5 py-3 font-semibold text-blue-700">${peso(totalPeriodBudget)}</td>
                    <td class="px-5 py-3 text-slate-600">${peso(spent)}</td>
                    <td class="px-5 py-3 font-semibold ${remaining <= totalPeriodBudget * 0.15 ? 'text-red-500' : remaining <= totalPeriodBudget * 0.30 ? 'text-amber-500' : 'text-green-600'}">${peso(remaining)}</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-semibold">${Math.round(pct)}%</span>
                            <div class="flex-1 bg-slate-100 rounded-full h-1.5 overflow-hidden max-w-20">
                                <div class="budget-bar-fill h-1.5 rounded-full ${pct > 85 ? 'bg-red-500' : pct > 65 ? 'bg-amber-400' : 'bg-emerald-500'}" style="width:0%" data-target="${pct}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3"><span class="${status.class} px-2.5 py-0.5 rounded-full text-[10px] font-semibold">${status.label}</span></td>
                    <td class="px-5 py-3">
                        <div class="flex flex-col gap-1.5">
                            ${canEndEarly ? `
                            <form method="POST" action="budgetmanagementaction.php" onsubmit='return confirmEndPeriod(${JSON.stringify(budget.program)}, ${remaining}, ${JSON.stringify(periodLabel(budget.period, budget.currentPeriod))}, ${earlyUsesLeft});' style="display:inline;">
                                <input type="hidden" name="action" value="end_period">
                                <input type="hidden" name="program_id" value="${budget.id}">
                                <button type="submit" class="text-[11px] font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg px-2.5 py-1 hover:bg-red-100 transition-colors">
                                    <i class="fas fa-forward mr-1"></i> End Early / Advance
                                </button>
                            </form>` : `
                            <span class="text-[10px] text-slate-400">
                                ${budget.period === 'Annually' ? 'Not applicable' : 'Final period'}
                            </span>`}
                            ${budget.period !== 'Annually' && budget.currentPeriod < periods ? `
                            <span class="text-[10px] text-slate-400">${earlyUsesLeft} early advance${earlyUsesLeft === 1 ? '' : 's'} left</span>
                            ` : ''}
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('totalBudgetAll').textContent = peso(totalBudget);
            document.getElementById('totalSpentAll').textContent = peso(totalSpent);
            document.getElementById('totalRemainingAll').textContent = peso(totalRemaining);
            document.getElementById('avgUtilization').textContent =
                totalBudget > 0 ? Math.round((totalSpent / totalBudget) * 100) + '%' : '0%';

            requestAnimationFrame(() => {
                setTimeout(() => {
                    document.querySelectorAll('.budget-bar-fill').forEach(el => {
                        el.style.width = el.dataset.target;
                    });
                }, 300);
            });

            updateAnalysis();
            updateForecast();
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, ch => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[ch]));
        }

        // AICS FBML and AICS Educational are separate fund allocations,
        // but they represent ONE logical program for summary/report counts.
        function logicalProgramKey(programName) {
            const name = String(programName || '').trim();
            if (name === 'AICS FBML' || name === 'AICS Educational') return 'AICS';
            return name;
        }

        function logicalProgramCount() {
            return new Set(budgetData.map(b => logicalProgramKey(b.program))).size;
        }

        // ── Update Analysis ──
        function updateAnalysis() {
            const container = document.getElementById('analysisContent');
            const critical = budgetData.filter(b => {
                const r = periodBudget(b) - b.spent;
                return r <= periodBudget(b) * 0.15;
            });
            const warning = budgetData.filter(b => {
                const r = periodBudget(b) - b.spent;
                return r > periodBudget(b) * 0.15 && r <= periodBudget(b) * 0.30;
            });
            const ok = budgetData.filter(b => {
                const r = periodBudget(b) - b.spent;
                return r > periodBudget(b) * 0.30;
            });

            const totalBeneficiaries = budgetData.reduce((s, b) => s + Number(b.beneficiaries || 0), 0);

            container.innerHTML = `
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                    <span class="text-[12px] text-slate-600">Total Programs</span>
                    <span class="font-bold text-green-600">${logicalProgramCount()}</span>
                </div>
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                    <span class="text-[12px] text-slate-600">Beneficiaries Served</span>
                    <span class="font-bold text-blue-600">${totalBeneficiaries.toLocaleString()}</span>
                </div>
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                    <span class="text-[12px] text-slate-600"><span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500 mr-1.5"></span>Critical</span>
                    <span class="font-bold text-red-500">${critical.length}</span>
                </div>
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                    <span class="text-[12px] text-slate-600"><span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-400 mr-1.5"></span>Warning</span>
                    <span class="font-bold text-amber-500">${warning.length}</span>
                </div>
                <div class="flex items-center justify-between py-1.5">
                    <span class="text-[12px] text-slate-600"><span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 mr-1.5"></span>OK</span>
                    <span class="font-bold text-emerald-600">${ok.length}</span>
                </div>
            `;

            window._criticalPrograms = critical;
            window._warningPrograms = warning;
        }

        // ── Update Forecast ──
        function updateForecast() {
            const container = document.getElementById('forecastContent');
            const totalBudget = budgetData.reduce((sum, b) => sum + b.totalBudget, 0);
            const totalSpent = budgetData.reduce((sum, b) => sum + Number(b.spent || 0), 0);

            let totalSuggested = 0;
            const perProgram = budgetData.map(b => {
                const period = periodBudget(b);
                const spent = Number(b.spent || 0);
                const utilization = period > 0 ? (spent / period) * 100 : 0;
                const beneficiaries = Number(b.beneficiaries || 0);

                let recommendation = 'Review';

                // Beneficiary count is a demand-volume indicator only.
                // It is never converted into a fixed peso amount.
                // Low financial utilization means there is no evidence that
                // additional budget is needed, even when beneficiary volume is high.
                if (utilization <= 30) {
                    recommendation = 'No Increase Needed';
                } else if (utilization >= 70 && beneficiaries > 0) {
                    recommendation = 'Consider Increase';
                }

                // No fixed peso-per-beneficiary amount and no automatic 15% multiplier.
                const suggested = recommendation === 'Consider Increase' ? period : 0;
                totalSuggested += suggested;

                return {
                    program: b.program,
                    period: b.period,
                    periodLabel: periodLabel(b.period, b.currentPeriod),
                    budget: b.totalBudget,
                    periodBudget: period,
                    spent,
                    utilization: Math.round(utilization * 10) / 10,
                    beneficiaries,
                    suggested,
                    recommendation
                };
            });

            const totalBeneficiaries = perProgram.reduce((s, p) => s + p.beneficiaries, 0);

            container.innerHTML = `
                <div class="mb-3 text-[12px] text-slate-600 space-y-1">
                    <p><strong>Total Beneficiaries:</strong> ${totalBeneficiaries.toLocaleString()}</p>
                    <p><strong>Current Spending:</strong> ${peso(totalSpent)}</p>
                    <p class="text-emerald-700 font-semibold">Programs for Review/Increase: ${perProgram.filter(p => p.recommendation === 'Consider Increase').length}</p>
                    <p class="text-[11px] text-slate-400">Beneficiary count indicates demand volume; it is not assigned a fixed peso value.</p>
                </div>
                <div class="border border-slate-200 rounded-lg overflow-hidden max-h-64 overflow-y-auto">
                    <table class="forecast-table w-full text-[11px]">
                        <thead>
                            <tr>
                                <th>Program</th>
                                <th>Period</th>
                                <th>Beneficiaries</th>
                                <th>Period Budget</th>
                                <th>Spent</th>
                                <th>Utilization</th>
                                <th>Recommendation</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${perProgram.map(p => `
                                <tr>
                                    <td class="font-medium text-green-700">${escapeHtml(p.program)}</td>
                                    <td>${p.periodLabel}</td>
                                    <td>${p.beneficiaries.toLocaleString()}</td>
                                    <td>${peso(p.periodBudget)}</td>
                                    <td>${peso(p.spent)}</td>
                                    <td>${p.utilization.toFixed(1)}%</td>
                                    <td class="${p.recommendation === 'Consider Increase' ? 'text-blue-600' : p.recommendation === 'No Increase Needed' ? 'text-emerald-600' : 'text-amber-600'} font-semibold">${p.recommendation}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;

            window._forecastData = {
                recommendedBudget: totalSuggested,
                totalSpent,
                totalBeneficiaries,
                perProgram
            };
        }

        // ── Run Forecast ──
        function runForecast() {
            updateForecast();
            showToast('Forecast recalculated!');
        }

        // ── Toggle Augment Source Fields ──
        function toggleAugmentSourceFields() {
            const source = document.getElementById('augmentSource').value;

            const transferFields = document.getElementById('augmentTransferFields');
            const otherFields = document.getElementById('augmentOtherFields');

            const donorProgram = document.getElementById('augmentDonorProgram');
            const otherSource = document.getElementById('augmentOtherSource');

            const isTransfer = source === 'From another program';
            const isOther = source === 'Other';

            // Show only the fields needed by the selected source.
            transferFields.style.display = isTransfer ? 'block' : 'none';
            otherFields.style.display = isOther ? 'block' : 'none';

            // IMPORTANT:
            // Disabled fields are ignored by browser validation and are not
            // submitted. This prevents hidden conditional fields from
            // blocking augmentation.
            donorProgram.disabled = !isTransfer;
            donorProgram.required = isTransfer;

            otherSource.disabled = !isOther;
            otherSource.required = isOther;

            if (isTransfer) {
                updateDonorRemaining();
            } else {
                document.getElementById('donorRemainingDisplay').textContent =
                    'Donor remaining: ₱0';
            }

            if (!isOther) {
                otherSource.value = '';
            }

            if (!isTransfer) {
                donorProgram.value = '';
            }
        }

        // ── Update Donor Remaining ──
        function updateDonorRemaining() {
            const donorId = document.getElementById('augmentDonorProgram').value;
            const donor = budgetData.find(b => String(b.id) === String(donorId));
            if (donor) {
                const remaining = donor.totalBudget - donor.spent;
                document.getElementById('donorRemainingDisplay').textContent = `Donor remaining: ₱${remaining.toLocaleString()}`;
            } else {
                document.getElementById('donorRemainingDisplay').textContent = 'Donor remaining: ₱0';
            }
        }

        // ── Open Augment Modal ──
        function openAugmentModal() {
            const form = document.getElementById('augmentForm');
            const donorProgram = document.getElementById('augmentDonorProgram');
            const otherSource = document.getElementById('augmentOtherSource');

            form.reset();

            donorProgram.disabled = true;
            donorProgram.required = false;
            otherSource.disabled = true;
            otherSource.required = false;

            document.getElementById('augmentTransferFields').style.display = 'none';
            document.getElementById('augmentOtherFields').style.display = 'none';
            document.getElementById('donorRemainingDisplay').textContent = 'Donor remaining: ₱0';

            document.getElementById('augmentModal').classList.remove('hidden');
            document.getElementById('augmentModal').style.display = 'flex';
        }

        function openAugmentModalForProgram(budgetId) {
            const budget = budgetData.find(b => b.id === budgetId);
            if (!budget) return;

            openAugmentModal();
            document.getElementById('augmentTargetProgram').value = budget.id;
        }

        function closeAugmentModal() {
            const form = document.getElementById('augmentForm');
            const donorProgram = document.getElementById('augmentDonorProgram');
            const otherSource = document.getElementById('augmentOtherSource');

            document.getElementById('augmentModal').classList.add('hidden');
            document.getElementById('augmentModal').style.display = 'none';

            form.reset();

            donorProgram.disabled = true;
            donorProgram.required = false;
            otherSource.disabled = true;
            otherSource.required = false;

            document.getElementById('augmentTransferFields').style.display = 'none';
            document.getElementById('augmentOtherFields').style.display = 'none';
            document.getElementById('donorRemainingDisplay').textContent = 'Donor remaining: ₱0';
        }

        // ── Confirm before ending a period early (the actual DB update now
        //    happens server-side in budgetmanagementaction.php after this returns true) ──
        function confirmEndPeriod(programName, remaining, currentPeriod, usesLeft) {
            const confirmMsg =
                `End ${currentPeriod} early for ${programName}?\n\n` +
                `Remaining period budget: ${peso(remaining)}\n` +
                `The unused amount will be recorded as returned to the Accounting Office.\n` +
                `The program will advance to the next period.\n\n` +
                `Early advances remaining after this: ${Math.max(0, usesLeft - 1)}.`;
            return confirm(confirmMsg);
        }

        // ── Show Analysis Report ──
        function showAnalysisReport() {
            window.open('budget_analysis_report.php', '_blank', 'noopener');
        }

        // ── Open Forecast Modal ──
        function openForecastModal() {

            const data = window._forecastData || {
                perProgram: [],
                recommendedBudget: 0
            };

            let forecastDetails = `
        <div class="overflow-x-auto">
            <table class="forecast-table w-full text-[12px]">
                <thead>
                    <tr>
                        <th>Program</th>
                        <th>Period</th>
                        <th>Beneficiaries</th>
                        <th>Period Budget</th>
                        <th>Spent</th>
                        <th>Utilization</th>
                        <th>Recommendation</th>
                    </tr>
                </thead>

                <tbody>
                    ${data.perProgram.map(p => `
                        <tr>

                            <td class="font-medium text-green-700">
                                ${escapeHtml(p.program)}
                            </td>

                            <td>
                                ${p.periodLabel}
                            </td>

                            <td>
                                ${Number(p.beneficiaries || 0).toLocaleString()}
                            </td>

                            <td>
                                ${peso(p.periodBudget)}
                            </td>

                            <td>
                                ${peso(p.spent)}
                            </td>

                            <td>
                                <div class="flex items-center gap-2">

                                    <span class="min-w-[45px]">
                                        ${Number(p.utilization || 0).toFixed(1)}%
                                    </span>

                                    <div class="w-20 h-2 bg-slate-100 rounded-full overflow-hidden">

                                        <div
                                            class="h-full rounded-full ${Number(p.utilization || 0) >= 70
                    ? 'bg-amber-500'
                    : 'bg-emerald-500'
                }"
                                            style="width:${Math.min(
                    100,
                    Number(p.utilization || 0)
                )}%">
                                        </div>

                                    </div>

                                </div>
                            </td>

                            <td class="${p.recommendation === 'Consider Increase'
                    ? 'text-blue-600'
                    : p.recommendation === 'No Increase Needed'
                        ? 'text-emerald-600'
                        : 'text-amber-600'
                } font-semibold">

                                ${escapeHtml(p.recommendation)}

                            </td>

                        </tr>
                    `).join('')}
                </tbody>

            </table>
        </div>
    `;

            document.getElementById('forecastDetails').innerHTML = forecastDetails;

            // Always start at 0% when the modal opens.
            document.getElementById('forecastIncreasePct').value = '0';

            updateForecastRecommendation();

            document.getElementById('forecastModal').classList.remove('hidden');
            document.getElementById('forecastModal').style.display = 'flex';
        }

        function updateForecastRecommendation() {

            const data = window._forecastData || {
                perProgram: []
            };

            const pctInput = document.getElementById('forecastIncreasePct');

            let pct = Number(pctInput ? pctInput.value : 0);

            if (!Number.isFinite(pct)) {
                pct = 0;
            }

            pct = Math.max(0, Math.min(100, pct));

            /*
             * Only programs classified as "Consider Increase"
             * are eligible for the custom increase.
             *
             * Programs with:
             * - No Increase Needed
             * - Review
             *
             * receive ₱0 increase.
             */

            let baseBudget = 0;

            data.perProgram.forEach(p => {

                if (p.recommendation === 'Consider Increase') {

                    baseBudget += Number(p.periodBudget || 0);

                }

            });

            const increaseAmount =
                baseBudget * (pct / 100);

            const recommendedBudget =
                baseBudget + increaseAmount;

            // Update calculation display
            const baseEl = document.getElementById('forecastBaseBudget');
            const increaseEl = document.getElementById('forecastIncreaseAmount');
            const recommendedEl = document.getElementById('forecastRecommended');
            const formulaEl = document.getElementById('forecastFormula');

            if (baseEl) {
                baseEl.textContent = peso(baseBudget);
            }

            if (increaseEl) {
                increaseEl.textContent = peso(increaseAmount);
            }

            if (recommendedEl) {
                recommendedEl.textContent = peso(recommendedBudget);
            }

            if (formulaEl) {

                formulaEl.innerHTML = `
            Formula:
            <strong>
                ${peso(baseBudget)} × ${pct.toFixed(1)}%
                = ${peso(increaseAmount)}
            </strong>
            <br>

            <span class="text-slate-500">
                ${peso(baseBudget)}
                +
                ${peso(increaseAmount)}
                =
                <strong class="text-emerald-700">
                    ${peso(recommendedBudget)}
                </strong>
            </span>
        `;

            }

            return recommendedBudget;
        }
        function closeForecastModal() {
            document.getElementById('forecastModal').classList.add('hidden');
            document.getElementById('forecastModal').style.display = 'none';
        }

        // ── Generate Forecast PDF ──
        function exportForecastPDF() {

            const pctInput = document.getElementById('forecastIncreasePct');

            let pct = Number(
                pctInput ? pctInput.value : 0
            );

            if (!Number.isFinite(pct)) {
                pct = 0;
            }

            pct = Math.max(0, Math.min(100, pct));

            const url =
                `budget_forecast_report.php?increase_pct=${encodeURIComponent(pct)}`;

            window.open(
                url,
                '_blank',
                'noopener'
            );
        }
        // ── Augmentation client-side validation ──
        function validateAugmentationForm() {
            const source = document.getElementById('augmentSource').value;
            const targetId = document.getElementById('augmentTargetProgram').value;
            const donorId = document.getElementById('augmentDonorProgram').value;
            const amount = Number(document.getElementById('augmentAmount').value || 0);

            if (!targetId || amount <= 0 || !source) {
                return true; // Let normal HTML5 validation handle these fields.
            }

            if (source === 'From another program' && donorId === targetId) {
                alert('Cannot transfer from the same program.');
                return false;
            }

            return true;
        }

        // ── Toast ──
        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            document.getElementById('toastMsg').textContent = msg;
            t.querySelector('i').className = type === 'error' ? 'fas fa-exclamation-circle text-red-300' :
                'fas fa-check-circle text-green-300';
            t.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
            t.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => {
                t.classList.add('opacity-0', 'translate-y-4');
                t.classList.remove('opacity-100', 'translate-y-0');
            }, 3500);
        }

        // ── Event listeners for donor program change ──
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('augmentDonorProgram')
                .addEventListener('change', updateDonorRemaining);
        });

        // ── Flash message from budgetmanagementaction.php (after a redirect) ──
        <?php if ($flashMsg): ?>
            document.addEventListener('DOMContentLoaded', function () {
                showToast(<?= json_encode($flashMsg) ?>, <?= json_encode($flashType) ?>);
            });
        <?php endif; ?>

        // ── Initialise ──
        renderBudgets();
    </script>

</body>

</html>