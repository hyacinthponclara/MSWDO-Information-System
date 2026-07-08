<?php
require 'auth.php';
requireRole(['Admin', 'Social Worker']); 
require 'db_connect.php';
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
        <header class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <span class="text-green-600 font-semibold">Budget Management</span>
            </div>
        </header>

        <main class="flex-1 p-6 space-y-5 overflow-y-auto">

            <!-- Page Title -->
            <div class="flex flex-wrap items-center justify-between gap-3 animate-fade-up">
                <div>
                    <h1 class="text-xl font-serif text-green-600">Budget Management</h1>
                    <p class="text-[13px] text-slate-500 mt-0.5">Monitor program budgets, augment funds, end periods early, and forecast next year's budget.</p>
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
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total Remaining</p>
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
                        <button onclick="openAugmentModal()" class="btn-action text-[12px] font-semibold text-white bg-amber-600 rounded-lg px-3 py-1.5 hover:bg-amber-700 transition-all flex items-center gap-1.5">
                            <i class="fas fa-hand-holding-usd"></i> Augmentation
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-[12px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Program</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Funding Source</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Period</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Total Budget</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Spent</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Remaining</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Utilization</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Status</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Actions</th>
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
                    <h2 class="text-[13px] font-semibold text-green-600 mb-4"><i class="fas fa-chart-pie mr-1.5 text-green-400"></i>Budget Analysis</h2>
                    <div class="space-y-3" id="analysisContent">
                        <!-- Injected by JS -->
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <button onclick="showAnalysisReport()" class="text-[11px] font-medium text-green-600 hover:text-green-800 transition-colors">
                            <i class="fas fa-file-alt mr-1"></i> View Full Analysis Report
                        </button>
                    </div>
                </div>

                <!-- Forecast Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-[13px] font-semibold text-green-600"><i class="fas fa-chart-line mr-1.5 text-green-400"></i>Forecast</h2>
                    </div>
                    <div id="forecastContent">
                        <!-- Injected by JS -->
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <button onclick="openForecastModal()" class="text-[11px] font-medium text-emerald-600 hover:text-emerald-800 transition-colors">
                            <i class="fas fa-calculator mr-1"></i> Propose Next Year's Budget
                        </button>
                    </div>
                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <!-- ══════════════════════════ AUGMENT BUDGET MODAL ══════════════════════════ -->
    <div id="augmentModal" class="fixed inset-0 z-50 flex items-center justify-center modal-backdrop hidden">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto animate-modal-in">
            <div class="sticky top-0 bg-white z-10 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-[16px] font-semibold text-green-600">Augment Budget</h2>
                <button onclick="closeAugmentModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <form id="augmentForm" onsubmit="augmentBudget(event)">
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
                        <p class="text-[12px] text-slate-600">Augmentation adds funds from savings to a program. You can either add funds from an external source or transfer from another program's remaining budget.</p>
                    </div>
                    <div>
                        <label class="field-label req">Program to Augment</label>
                        <select id="augmentTargetProgram" class="field" required>
                            <option value="">Select Program</option>
                            <option value="AICS FBML">AICS FBML</option>
                            <option value="AICS Educational">AICS Educational</option>
                            <option value="4Ps">4Ps</option>
                            <option value="SLP">SLP</option>
                            <option value="SFP">SFP</option>
                            <option value="Day Care">Day Care</option>
                            <option value="Senior Citizen">Senior Citizen</option>
                            <option value="PWD">PWD</option>
                            <option value="Solo Parent">Solo Parent</option>
                            <option value="Women and Children">Women and Children</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label req">Additional Amount (₱)</label>
                        <input type="number" min="0" step="0.01" id="augmentAmount" class="field" placeholder="0.00" required />
                    </div>
                    <div>
                        <label class="field-label req">Source of Augmentation</label>
                        <select id="augmentSource" class="field" required onchange="toggleAugmentSourceFields()">
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
                            <select id="augmentDonorProgram" class="field" required>
                                <option value="">Select Program</option>
                                <option value="AICS FBML">AICS FBML</option>
                                <option value="AICS Educational">AICS Educational</option>
                                <option value="4Ps">4Ps</option>
                                <option value="SLP">SLP</option>
                                <option value="SFP">SFP</option>
                                <option value="Day Care">Day Care</option>
                                <option value="Senior Citizen">Senior Citizen</option>
                                <option value="PWD">PWD</option>
                                <option value="Solo Parent">Solo Parent</option>
                                <option value="Women and Children">Women and Children</option>
                            </select>
                            <p class="text-[10px] text-slate-400 mt-1" id="donorRemainingDisplay">Program remaining: ₱0</p>
                        </div>
                    </div>

                    <!-- Dynamic fields for "Other" -->
                    <div id="augmentOtherFields" style="display:none;">
                        <div>
                            <label class="field-label req">Specify Source</label>
                            <input type="text" id="augmentOtherSource" class="field" placeholder="e.g., DSWD Emergency Fund" />
                        </div>
                    </div>

                    <div>
                        <label class="field-label">Reason</label>
                        <textarea id="augmentReason" class="field" rows="2" placeholder="Reason for augmentation..."></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" onclick="closeAugmentModal()" class="text-[13px] font-medium text-slate-600 border border-slate-200 rounded-xl px-5 py-2 hover:border-green-400 hover:text-green-600 transition-all">
                            Cancel
                        </button>
                        <button type="submit" class="text-[13px] font-semibold text-white bg-amber-600 rounded-xl px-6 py-2 hover:bg-amber-500 transition-all">
                            <i class="fas fa-hand-holding-usd mr-1.5"></i> Augment Budget
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════ FORECAST MODAL ══════════════════════════ -->
    <div id="forecastModal" class="fixed inset-0 z-50 flex items-center justify-center modal-backdrop hidden">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto animate-modal-in">
            <div class="sticky top-0 bg-white z-10 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-[16px] font-semibold text-green-600">Budget Forecast</h2>
                <button onclick="closeForecastModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
                    <p class="text-[12px] text-slate-600">Based on current spending patterns, the system calculates the recommended budget for each program and the total for next year. A <strong>15% buffer</strong> is added to account for inflation and unexpected needs.</p>
                </div>

                <div id="forecastDetails">
                    <!-- Injected by JS -->
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <p class="text-[12px] font-semibold text-blue-700"><i class="fas fa-info-circle mr-1.5"></i>Forecast Methodology</p>
                    <p class="text-[11px] text-slate-600 mt-1">The recommended budget is calculated using the formula: <strong>(Current Annual Spending + 15% Buffer)</strong>. This ensures that programs have enough funds to continue operations without running out. The buffer accounts for inflation, increased demand, and unforeseen expenses.</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Next Year's Period</label>
                        <select id="forecastPeriod" class="field">
                            <option value="Quarterly">Quarterly</option>
                            <option value="Half-Year">Half-Year</option>
                            <option value="Annually" selected>Annually</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Total Recommended Budget</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-medium">₱</span>
                            <input type="text" id="forecastRecommended" class="field pl-8 bg-gray-100 cursor-not-allowed opacity-75" readonly />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                    <button type="button" onclick="exportForecastCSV()" class="text-[13px] font-semibold text-white bg-blue-600 rounded-xl px-6 py-2 hover:bg-blue-500 transition-all">
                        <i class="fas fa-file-csv mr-1.5"></i> Export CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-6 right-6 bg-green-700 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-green-300"></i>
        <span id="toastMsg">Action completed!</span>
    </div>

    <script>
        // ── Sample Budget Data ──
        let budgetData = [{
            id: 1,
            program: 'AICS FBML',
            fundingSource: 'LGU',
            period: 'Quarterly',
            totalBudget: 450000,
            spent: 350000,
            startDate: '2026-01-01',
            endDate: '2026-03-31',
            notes: ''
        }, {
            id: 2,
            program: 'AICS Educational',
            fundingSource: 'LGU',
            period: 'Quarterly',
            totalBudget: 50000,
            spent: 48000,
            startDate: '2026-01-01',
            endDate: '2026-03-31',
            notes: ''
        }, {
            id: 3,
            program: 'Senior Citizen',
            fundingSource: 'LGU',
            period: 'Annually',
            totalBudget: 800000,
            spent: 520000,
            startDate: '2026-01-01',
            endDate: '2026-12-31',
            notes: ''
        }, {
            id: 4,
            program: 'PWD',
            fundingSource: 'LGU',
            period: 'Half-Year',
            totalBudget: 200000,
            spent: 130000,
            startDate: '2026-01-01',
            endDate: '2026-06-30',
            notes: ''
        }, {
            id: 5,
            program: 'SFP',
            fundingSource: 'LGU',
            period: 'Annually',
            totalBudget: 200000,
            spent: 195000,
            startDate: '2026-01-01',
            endDate: '2026-12-31',
            notes: ''
        }, {
            id: 6,
            program: '4Ps',
            fundingSource: 'LGU',
            period: 'Annually',
            totalBudget: 30000,
            spent: 18500,
            startDate: '2026-01-01',
            endDate: '2026-12-31',
            notes: ''
        }, {
            id: 7,
            program: 'Solo Parent',
            fundingSource: 'LGU',
            period: 'Quarterly',
            totalBudget: 150000,
            spent: 72000,
            startDate: '2026-01-01',
            endDate: '2026-03-31',
            notes: ''
        }, {
            id: 8,
            program: 'Women and Children',
            fundingSource: 'LGU',
            period: 'Annually',
            totalBudget: 100000,
            spent: 28000,
            startDate: '2026-01-01',
            endDate: '2026-12-31',
            notes: ''
        }, {
            id: 9,
            program: 'SLP',
            fundingSource: 'LGU',
            period: 'Annually',
            totalBudget: 450000,
            spent: 150000,
            startDate: '2026-01-01',
            endDate: '2026-12-31',
            notes: ''
        }, {
            id: 10,
            program: 'Day Care',
            fundingSource: 'LGU',
            period: 'Annually',
            totalBudget: 350000,
            spent: 200000,
            startDate: '2026-01-01',
            endDate: '2026-12-31',
            notes: ''
        }, ];

        let nextBudgetId = 11;

        // ── Status functions ──
        function getStatus(remaining, total) {
            const pct = total > 0 ? (remaining / total) * 100 : 0;
            if (pct <= 15) return { label: 'Critical', class: 'status-critical' };
            if (pct <= 30) return { label: 'Warning', class: 'status-warning' };
            return { label: 'OK', class: 'status-ok' };
        }

        // ── Render budget table ──
        function renderBudgets() {
            const tbody = document.getElementById('budgetTableBody');
            tbody.innerHTML = '';
            let totalBudget = 0,
                totalSpent = 0,
                totalRemaining = 0;

            budgetData.forEach(budget => {
                const remaining = budget.totalBudget - budget.spent;
                const pct = budget.totalBudget > 0 ? (budget.spent / budget.totalBudget) * 100 : 0;
                const status = getStatus(remaining, budget.totalBudget);
                totalBudget += budget.totalBudget;
                totalSpent += budget.spent;
                totalRemaining += remaining;

                const tr = document.createElement('tr');
                tr.className = 'table-row';
                tr.innerHTML = `
                    <td class="px-5 py-3 font-medium text-green-700">${budget.program}</td>
                    <td class="px-5 py-3 text-slate-600">${budget.fundingSource}</td>
                    <td class="px-5 py-3"><span class="badge-period px-2 py-0.5 rounded text-[10px] font-semibold">${budget.period}</span></td>
                    <td class="px-5 py-3 font-semibold text-slate-700">₱${budget.totalBudget.toLocaleString()}</td>
                    <td class="px-5 py-3 text-slate-600">₱${budget.spent.toLocaleString()}</td>
                    <td class="px-5 py-3 font-semibold ${remaining <= (budget.totalBudget * 0.15) ? 'text-red-500' : remaining <= (budget.totalBudget * 0.30) ? 'text-amber-500' : 'text-green-600'}">₱${remaining.toLocaleString()}</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-semibold">${Math.round(pct)}%</span>
                            <div class="flex-1 bg-slate-100 rounded-full h-1.5 overflow-hidden max-w-20">
                                <div class="budget-bar-fill h-1.5 rounded-full ${pct > 85 ? 'bg-red-500' : pct > 65 ? 'bg-amber-400' : 'bg-emerald-500'}" style="width:0%" data-target="${Math.min(pct, 100)}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3"><span class="${status.class} px-2.5 py-0.5 rounded-full text-[10px] font-semibold">${status.label}</span></td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-1.5">
                            <button onclick="endPeriodEarly(${budget.id})" class="text-[11px] font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg px-2.5 py-1 hover:bg-red-100 transition-colors" title="End Period Early">
                                <i class="fas fa-stop-circle mr-1"></i> End Early
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            // Update summary stats
            document.getElementById('totalBudgetAll').textContent = '₱' + totalBudget.toLocaleString();
            document.getElementById('totalSpentAll').textContent = '₱' + totalSpent.toLocaleString();
            document.getElementById('totalRemainingAll').textContent = '₱' + totalRemaining.toLocaleString();
            document.getElementById('avgUtilization').textContent = totalBudget > 0 ? Math.round((totalSpent / totalBudget) * 100) + '%' : '0%';

            // Animate bars
            requestAnimationFrame(() => {
                setTimeout(() => {
                    document.querySelectorAll('.budget-bar-fill').forEach(el => {
                        el.style.width = el.dataset.target;
                    });
                }, 300);
            });

            // Update analysis and forecast
            updateAnalysis();
            updateForecast();
        }

        // ── Update Analysis ──
        function updateAnalysis() {
            const container = document.getElementById('analysisContent');
            const critical = budgetData.filter(b => b.totalBudget - b.spent <= (b.totalBudget * 0.15));
            const warning = budgetData.filter(b => b.totalBudget - b.spent > (b.totalBudget * 0.15) && b.totalBudget - b.spent <= (b
                .totalBudget * 0.30));
            const ok = budgetData.filter(b => b.totalBudget - b.spent > (b.totalBudget * 0.30));

            let html = `
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                    <span class="text-[12px] text-slate-600">Total Programs</span>
                    <span class="font-bold text-green-600">${budgetData.length}</span>
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
            container.innerHTML = html;

            // Store for forecast
            window._criticalPrograms = critical;
            window._warningPrograms = warning;
        }

        // ── Update Forecast ──
        function updateForecast() {
            const container = document.getElementById('forecastContent');
            const totalBudget = budgetData.reduce((sum, b) => sum + b.totalBudget, 0);
            const totalSpent = budgetData.reduce((sum, b) => sum + b.spent, 0);
            const avgUtilization = totalBudget > 0 ? (totalSpent / totalBudget) * 100 : 0;

            let forecastRows = '';
            let totalRecommended = 0;
            budgetData.forEach(b => {
                const yearlySpent = b.period === 'Quarterly' ? b.spent * 4 :
                    b.period === 'Half-Year' ? b.spent * 2 :
                    b.spent;
                const recommended = Math.round(yearlySpent * 1.15);
                totalRecommended += recommended;
                const status = getStatus(b.totalBudget - b.spent, b.totalBudget);
                forecastRows += `
                    <tr>
                        <td class="font-medium text-green-700">${b.program}</td>
                        <td>${b.period}</td>
                        <td>₱${b.totalBudget.toLocaleString()}</td>
                        <td>₱${b.spent.toLocaleString()}</td>
                        <td>${Math.round((b.spent / b.totalBudget) * 100)}%</td>
                        <td class="font-semibold text-blue-600">₱${recommended.toLocaleString()}</td>
                        <td><span class="${status.class} px-2 py-0.5 rounded text-[10px] font-semibold">${status.label}</span></td>
                    </tr>
                `;
            });

            const totalRecommendedFormatted = '₱' + totalRecommended.toLocaleString();
            const totalSpentFormatted = '₱' + totalSpent.toLocaleString();

            container.innerHTML = `
                <div class="mb-3 text-[12px] text-slate-600">
                    <p><strong>Current Spending Rate:</strong> ${Math.round(avgUtilization)}%</p>
                    <p><strong>Total Spent (YTD):</strong> ${totalSpentFormatted}</p>
                    <p class="text-emerald-700 font-semibold">Recommended Total: ${totalRecommendedFormatted}</p>
                    <p class="text-[11px] text-slate-400">(Includes 15% buffer for inflation and unexpected needs)</p>
                </div>
                <div class="border border-slate-200 rounded-lg overflow-hidden max-h-64 overflow-y-auto">
                    <table class="forecast-table w-full text-[11px]">
                        <thead>
                            <tr>
                                <th>Program</th>
                                <th>Period</th>
                                <th>Budget</th>
                                <th>Spent</th>
                                <th>Used</th>
                                <th>Recommended</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${forecastRows}
                            <tr class="forecast-total">
                                <td colspan="5" class="text-right font-bold">TOTAL RECOMMENDED</td>
                                <td class="font-bold text-blue-600">${totalRecommendedFormatted}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="forecast-note">
                    <i class="fas fa-lightbulb mr-1.5"></i>
                    <strong>Recommendation:</strong> Based on current spending patterns (${Math.round(avgUtilization)}% utilization), the recommended budget for next year is <strong>${totalRecommendedFormatted}</strong>. This ensures all programs have sufficient funds and a 15% buffer for emergencies.
                    ${window._criticalPrograms && window._criticalPrograms.length > 0 ? `<br><span class="text-red-600">⚠️ Critical programs: ${window._criticalPrograms.map(b => b.program).join(', ')}</span>` : ''}
                </div>
            `;

            // Store forecast data
            window._forecastData = {
                recommendedBudget: totalRecommended,
                avgUtilization,
                totalSpent,
                criticalPrograms: window._criticalPrograms || [],
                perProgram: budgetData.map(b => {
                    const yearlySpent = b.period === 'Quarterly' ? b.spent * 4 :
                        b.period === 'Half-Year' ? b.spent * 2 :
                        b.spent;
                    return {
                        program: b.program,
                        period: b.period,
                        budget: b.totalBudget,
                        spent: b.spent,
                        utilization: Math.round((b.spent / b.totalBudget) * 100),
                        recommended: Math.round(yearlySpent * 1.15),
                        status: getStatus(b.totalBudget - b.spent, b.totalBudget)
                    };
                })
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

            transferFields.style.display = source === 'From another program' ? 'block' : 'none';
            otherFields.style.display = source === 'Other' ? 'block' : 'none';

            // Update donor remaining when donor program changes
            if (source === 'From another program') {
                updateDonorRemaining();
            }
        }

        // ── Update Donor Remaining ──
        function updateDonorRemaining() {
            const donorProgram = document.getElementById('augmentDonorProgram').value;
            const donor = budgetData.find(b => b.program === donorProgram);
            if (donor) {
                const remaining = donor.totalBudget - donor.spent;
                document.getElementById('donorRemainingDisplay').textContent = `Donor remaining: ₱${remaining.toLocaleString()}`;
            } else {
                document.getElementById('donorRemainingDisplay').textContent = 'Donor remaining: ₱0';
            }
        }

        // ── Open Augment Modal ──
        function openAugmentModal() {
            document.getElementById('augmentForm').reset();
            document.getElementById('augmentTransferFields').style.display = 'none';
            document.getElementById('augmentOtherFields').style.display = 'none';
            document.getElementById('augmentModal').classList.remove('hidden');
            document.getElementById('augmentModal').style.display = 'flex';
        }

        function openAugmentModalForProgram(budgetId) {
            const budget = budgetData.find(b => b.id === budgetId);
            if (!budget) return;

            openAugmentModal();
            document.getElementById('augmentTargetProgram').value = budget.program;
        }

        function closeAugmentModal() {
            document.getElementById('augmentModal').classList.add('hidden');
            document.getElementById('augmentModal').style.display = 'none';
            document.getElementById('augmentForm').reset();
            document.getElementById('augmentTransferFields').style.display = 'none';
            document.getElementById('augmentOtherFields').style.display = 'none';
        }

        // ── Augment Budget ──
        function augmentBudget(event) {
            event.preventDefault();

            const targetProgram = document.getElementById('augmentTargetProgram').value;
            const amount = parseFloat(document.getElementById('augmentAmount').value) || 0;
            const source = document.getElementById('augmentSource').value;
            const reason = document.getElementById('augmentReason').value;

            // Validation
            if (!targetProgram) {
                showToast('Please select a program to augment.', 'error');
                return;
            }
            if (!amount || amount <= 0) {
                showToast('Please enter a valid amount.', 'error');
                return;
            }
            if (!source) {
                showToast('Please select a source of augmentation.', 'error');
                return;
            }

            // Find target budget
            const target = budgetData.find(b => b.program === targetProgram);
            if (!target) {
                showToast('Target program not found.', 'error');
                return;
            }

            let sourceLabel = source;

            // If source is "From another program"
            if (source === 'From another program') {
                const donorProgram = document.getElementById('augmentDonorProgram').value;
                if (!donorProgram) {
                    showToast('Please select the program to transfer from.', 'error');
                    return;
                }
                if (donorProgram === targetProgram) {
                    showToast('Cannot transfer from the same program.', 'error');
                    return;
                }

                const donor = budgetData.find(b => b.program === donorProgram);
                if (!donor) {
                    showToast('Donor program not found.', 'error');
                    return;
                }

                const donorRemaining = donor.totalBudget - donor.spent;
                if (amount > donorRemaining) {
                    showToast(`Insufficient funds. Donor only has ₱${donorRemaining.toLocaleString()} remaining.`, 'error');
                    return;
                }

                // Transfer: deduct from donor, add to target
                donor.spent += amount;
                sourceLabel = `Transfer from ${donorProgram}`;
            }

            // If source is "Other"
            if (source === 'Other') {
                const otherSource = document.getElementById('augmentOtherSource').value.trim();
                if (!otherSource) {
                    showToast('Please specify the source.', 'error');
                    return;
                }
                sourceLabel = otherSource;
            }

            // Augment the target budget
            target.totalBudget += amount;

            closeAugmentModal();
            renderBudgets();
            showToast(`Budget for ${targetProgram} augmented by ₱${amount.toLocaleString()} from ${sourceLabel}.`);
        }

        // ── End Period Early ──
        function endPeriodEarly(budgetId) {
            const budget = budgetData.find(b => b.id === budgetId);
            if (!budget) return;

            const remaining = budget.totalBudget - budget.spent;
            const confirmMsg =
                `End the current ${budget.period} period early for ${budget.program}?\n\nRemaining budget: ₱${remaining.toLocaleString()}\nThis will be returned to the LGU.`;

            if (confirm(confirmMsg)) {
                const returned = remaining;
                budget.spent = budget.totalBudget;

                renderBudgets();
                showToast(
                    `${budget.program} period ended early. ₱${returned.toLocaleString()} returned to LGU. Please start a new period to continue.`
                );
            }
        }

        // ── Show Analysis Report ──
        function showAnalysisReport() {
            let report = '=== BUDGET ANALYSIS REPORT ===\n\n';
            report += 'Program Budget Analysis\n';
            report += '='.repeat(50) + '\n\n';

            budgetData.forEach(b => {
                const remaining = b.totalBudget - b.spent;
                const pct = b.totalBudget > 0 ? (b.spent / b.totalBudget) * 100 : 0;
                const status = getStatus(remaining, b.totalBudget);
                report += `${b.program}\n`;
                report += `  Total: ₱${b.totalBudget.toLocaleString()}\n`;
                report += `  Spent: ₱${b.spent.toLocaleString()}\n`;
                report += `  Remaining: ₱${remaining.toLocaleString()}\n`;
                report += `  Utilization: ${Math.round(pct)}%\n`;
                report += `  Status: ${status.label}\n`;
                report += `  Period: ${b.period}\n\n`;
            });

            report += '='.repeat(50) + '\n';
            report += 'RECOMMENDATIONS:\n';
            const critical = budgetData.filter(b => b.totalBudget - b.spent <= (b.totalBudget * 0.15));
            if (critical.length > 0) {
                report += `- Consider augmentation for: ${critical.map(b => b.program).join(', ')}\n`;
            }
            report += `- Next year recommended budget: ₱${window._forecastData?.recommendedBudget?.toLocaleString() || 'N/A'}\n`;

            const textarea = document.createElement('textarea');
            textarea.value = report;
            textarea.style.width = '100%';
            textarea.style.height = '400px';
            textarea.style.fontSize = '13px';
            textarea.style.fontFamily = 'monospace';
            textarea.style.border = '1px solid #D4E8DC';
            textarea.style.borderRadius = '8px';
            textarea.style.padding = '12px';

            const modal = document.createElement('div');
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.background = 'rgba(0,0,0,0.5)';
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.style.zIndex = '9999';

            const box = document.createElement('div');
            box.style.background = 'white';
            box.style.borderRadius = '16px';
            box.style.padding = '24px';
            box.style.maxWidth = '600px';
            box.style.width = '90%';
            box.style.maxHeight = '90vh';
            box.style.overflow = 'auto';
            box.style.boxShadow = '0 25px 50px -12px rgba(0,0,0,0.25)';

            const title = document.createElement('h2');
            title.textContent = 'Budget Analysis Report';
            title.style.fontSize = '18px';
            title.style.fontWeight = '700';
            title.style.color = '#1A5C3A';
            title.style.marginBottom = '12px';

            const closeBtn = document.createElement('button');
            closeBtn.textContent = 'Close';
            closeBtn.style.marginTop = '16px';
            closeBtn.style.padding = '8px 24px';
            closeBtn.style.background = '#1A5C3A';
            closeBtn.style.color = 'white';
            closeBtn.style.border = 'none';
            closeBtn.style.borderRadius = '8px';
            closeBtn.style.fontWeight = '600';
            closeBtn.style.cursor = 'pointer';
            closeBtn.onclick = () => modal.remove();

            box.appendChild(title);
            box.appendChild(textarea);
            box.appendChild(closeBtn);
            modal.appendChild(box);
            document.body.appendChild(modal);
        }

        // ── Open Forecast Modal ──
        function openForecastModal() {
            const data = window._forecastData || { recommendedBudget: 0, perProgram: [] };

            // Build detailed forecast table for modal
            let forecastDetails = '';
            if (data.perProgram && data.perProgram.length > 0) {
                forecastDetails = `
                    <h3 class="text-[13px] font-semibold text-green-600 mb-3">Per-Program Forecast Calculation</h3>
                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <table class="forecast-table w-full text-[11px]">
                            <thead>
                                <tr>
                                    <th>Program</th>
                                    <th>Period</th>
                                    <th>Current Budget</th>
                                    <th>Spent (YTD)</th>
                                    <th>Utilization</th>
                                    <th>Yearly Spend</th>
                                    <th>Recommended (15% buffer)</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.perProgram.map(p => `
                                    <tr>
                                        <td class="font-medium text-green-700">${p.program}</td>
                                        <td>${p.period}</td>
                                        <td>₱${p.budget.toLocaleString()}</td>
                                        <td>₱${p.spent.toLocaleString()}</td>
                                        <td>${p.utilization}%</td>
                                        <td>₱${Math.round(p.period === 'Quarterly' ? p.spent * 4 : p.period === 'Half-Year' ? p.spent * 2 : p.spent).toLocaleString()}</td>
                                        <td class="font-semibold text-blue-600">₱${p.recommended.toLocaleString()}</td>
                                    </tr>
                                `).join('')}
                                <tr class="forecast-total">
                                    <td colspan="6" class="text-right font-bold">TOTAL RECOMMENDED</td>
                                    <td class="font-bold text-blue-600">₱${(data.recommendedBudget || 0).toLocaleString()}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-[12px]">
                        <p><strong>Calculation Method:</strong></p>
                        <p class="text-slate-600 mt-1">For each program, we calculate the yearly spending (adjusting based on the period) and add a 15% buffer.</p>
                        <p class="text-slate-600">Formula: <strong>Recommended = (Spent × Period Multiplier) × 1.15</strong></p>
                        <ul class="text-slate-500 mt-2 text-[11px] list-disc list-inside">
                            <li>Quarterly: × 4 (annualized)</li>
                            <li>Half-Year: × 2 (annualized)</li>
                            <li>Annually: × 1 (already annual)</li>
                        </ul>
                    </div>
                `;
            } else {
                forecastDetails =
                    `<p class="text-slate-500 text-[12px]">Run the forecast calculation first to see per-program recommendations.</p>`;
            }

            document.getElementById('forecastDetails').innerHTML = forecastDetails;
            document.getElementById('forecastRecommended').value = '₱' + (data.recommendedBudget || 0).toLocaleString();
            document.getElementById('forecastModal').classList.remove('hidden');
            document.getElementById('forecastModal').style.display = 'flex';
        }

        function closeForecastModal() {
            document.getElementById('forecastModal').classList.add('hidden');
            document.getElementById('forecastModal').style.display = 'none';
        }

        // ── Export Forecast CSV ──
        function exportForecastCSV() {
            const data = window._forecastData;
            if (!data || !data.perProgram || data.perProgram.length === 0) {
                showToast('No forecast data to export.', 'error');
                return;
            }

            let csv = 'Municipal Social Welfare and Development Office\n';
            csv += 'San Enrique, Negros Occidental\n';
            csv += 'Budget Forecast Report\n\n';
            csv += 'Program,Period,Current Budget,Spent (YTD),Utilization,Yearly Spend,Recommended (15% Buffer),Status\n';

            data.perProgram.forEach(p => {
                csv +=
                    `${p.program},${p.period},${p.budget},${p.spent},${p.utilization}%,${Math.round(p.period === 'Quarterly' ? p.spent * 4 : p.period === 'Half-Year' ? p.spent * 2 : p.spent)},${p.recommended},${p.status.label}\n`;
            });

            csv += `\nTOTAL RECOMMENDED,${data.recommendedBudget}\n`;
            csv += `\nGenerated on: ${new Date().toLocaleString('en-PH', { timeZone: 'Asia/Manila' })}\n`;

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Budget_Forecast_Report_' + new Date().toISOString().slice(0, 10) + '.csv';
            a.click();
            URL.revokeObjectURL(url);
            showToast('CSV exported successfully!');
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
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('augmentDonorProgram').addEventListener('change', updateDonorRemaining);
        });

        // ── Initialise ──
        renderBudgets();
    </script>

</body>

</html>