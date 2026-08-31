<?php
require 'auth.php';
requireRole(['Admin', 'Staff']); 
require 'db_connect.php';
require 'budget_helpers.php';
require 'export_prepared_by.php';


// ── BUDGET SUMMARY CARDS
$aicsFbmlBudget = getProgramBudget($pdo, ['AICS FBML']);
$aicsEduBudget  = getProgramBudget($pdo, ['AICS Educational']);

$aicsSubtypes = [
    ['table' => 'aics_financial',   'type' => 'Financial',   'source' => 'FBML'],
    ['table' => 'aics_burial',      'type' => 'Burial',      'source' => 'FBML'],
    ['table' => 'aics_medical',     'type' => 'Medical',     'source' => 'FBML'],
    ['table' => 'aics_livelihood',  'type' => 'Livelihood',  'source' => 'FBML'],
    ['table' => 'aics_educational', 'type' => 'Educational', 'source' => 'Educational'],
];

$aicsTransactions = [];
$rowId = 1;
foreach ($aicsSubtypes as $sub) {
    $stmt = $pdo->prepare("
        SELECT
            a.availment_id,
            a.av_amount,
            a.av_date_applied,
            a.av_status,
            a.av_date_released,
            c.cl_firstname,
            c.cl_lastname
        FROM {$sub['table']} t
        JOIN availment a ON a.availment_id = t.availment_id
        JOIN client c ON c.client_id = a.client_id
        ORDER BY a.av_date_applied DESC
    ");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $aicsTransactions[] = [
            'id'           => $rowId++,
            'availmentId'  => (int) $row['availment_id'],
            'beneficiary'  => trim($row['cl_firstname'] . ' ' . $row['cl_lastname']),
            'budgetSource' => $sub['source'],
            'type'         => $sub['type'],
            'amount'       => (float) $row['av_amount'],
            'status'       => $row['av_status'],
            'date'         => $row['av_date_applied'],
            'dateReleased' => $row['av_date_released'],
        ];
    }
}
usort($aicsTransactions, fn($a, $b) => strcmp($b['date'], $a['date']));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AICS Transactions – MSWDO San Enrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
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
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.4s ease both',
                        'fade-up-1': 'fadeUp 0.4s ease 0.05s both',
                        'fade-up-2': 'fadeUp 0.4s ease 0.1s both',
                        'fade-up-3': 'fadeUp 0.4s ease 0.15s both',
                        'fade-up-4': 'fadeUp 0.4s ease 0.2s both',
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

        ::-webkit-scrollbar {
            width: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(26, 92, 58, .2);
            border-radius: 2px;
        }

        th.sortable {
            cursor: pointer;
            user-select: none;
        }
        th.sortable:hover {
            background: #E2E8F0;
        }
        th.sortable .sort-icon {
            margin-left: 4px;
            font-size: 10px;
            opacity: 0.5;
        }
        th.sortable.asc .sort-icon {
            opacity: 1;
        }
        th.sortable.desc .sort-icon {
            opacity: 1;
        }

        .export-dropdown {
            position: relative;
            display: inline-block;
            z-index: 100000 !important;
            isolation: isolate;
        }

        .export-dropdown-content {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            background: #fff;
            min-width: 240px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16);
            border-radius: 0.5rem;
            border: 1px solid #D4E8DC;
            z-index: 100001 !important;
            overflow: visible;
        }

        .export-dropdown-content a {
            position: relative;
            z-index: 100002;
        }

        .export-dropdown-content a {
            color: #1A5C3A;
            padding: 0.7rem 1rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.875rem;
            transition: background 0.15s;
            cursor: pointer;
        }

        .export-dropdown-content a:hover {
            background: #EEF6F0;
        }

        .export-dropdown-content a i {
            width: 18px;
            text-align: center;
            font-size: 0.9rem;
        }

        .export-dropdown.active .export-dropdown-content {
            display: block;
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
                <span class="text-green-600 font-semibold">AICS Transactions</span>
            </div>
        </header>

        <main class="flex-1 p-6 space-y-5 overflow-y-auto">

            <!-- Page Title -->
            <div class="relative z-50 flex flex-wrap items-center justify-between gap-3 animate-fade-up">
                <div>
                    <h1 class="text-xl font-serif text-green-600">AICS Transaction History</h1>
                    <p class="text-[13px] text-slate-500 mt-0.5">View, filter, and export all AICS transactions (FBML &amp; Educational).</p>
                </div>
                <div class="export-dropdown relative z-[9999]" id="exportDropdownContainer">
                    <button type="button"
                        class="btn-action text-[12px] font-semibold text-white bg-green-600 rounded-lg px-3 py-1.5 hover:bg-green-700"
                        id="exportDropdownBtn">
                        <i class="fas fa-download mr-1"></i>
                        Export
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div class="export-dropdown-content">
                        <a id="exportPdf">
                            <i class="fas fa-file-pdf"></i>
                            PDF Document (.pdf)
                        </a>
                        <a id="exportDocx">
                            <i class="fas fa-file-word"></i>
                            Word Document (.docx)
                        </a>
                        <a id="exportXlsx">
                            <i class="fas fa-file-excel"></i>
                            Microsoft Excel (.xlsx)
                        </a>
                    </div>
                </div>
            </div>

            <!-- Budget Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 animate-fade-up-1">
                <!-- AICS FBML -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[13px] font-semibold text-green-600"><i class="fas fa-pills mr-1.5 text-green-400"></i>AICS FBML</h3>
                        <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">Budget</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total</p>
                            <p class="text-xl font-bold text-green-600">₱<?= number_format($aicsFbmlBudget['total'], 0) ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Spent</p>
                            <p class="text-xl font-bold text-amber-600">₱<?= number_format($aicsFbmlBudget['spent'], 0) ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Remaining</p>
                            <p class="text-xl font-bold text-blue-600">₱<?= number_format($aicsFbmlBudget['remaining'], 0) ?></p>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <div class="flex justify-between text-[10px] text-slate-400">
                            <span>Used: <?= $aicsFbmlBudget['pct_used'] ?>%</span>
                            <span>Remaining: <?= 100 - $aicsFbmlBudget['pct_used'] ?>%</span>
                        </div>
                        <div class="bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                            <div class="h-1.5 rounded-full bg-green-500" style="width:<?= $aicsFbmlBudget['pct_used'] ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- AICS Educational -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[13px] font-semibold text-green-600"><i class="fas fa-graduation-cap mr-1.5 text-green-400"></i>AICS Educational</h3>
                        <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">Budget</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total</p>
                            <p class="text-xl font-bold text-green-600">₱<?= number_format($aicsEduBudget['total'], 0) ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Spent</p>
                            <p class="text-xl font-bold text-amber-600">₱<?= number_format($aicsEduBudget['spent'], 0) ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Remaining</p>
                            <p class="text-xl font-bold text-blue-600">₱<?= number_format($aicsEduBudget['remaining'], 0) ?></p>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <div class="flex justify-between text-[10px] text-slate-400">
                            <span>Used: <?= $aicsEduBudget['pct_used'] ?>%</span>
                            <span>Remaining: <?= 100 - $aicsEduBudget['pct_used'] ?>%</span>
                        </div>
                        <div class="bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                            <div class="h-1.5 rounded-full bg-green-500" style="width:<?= $aicsEduBudget['pct_used'] ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters & Sorting -->
            <div class="flex flex-wrap items-center gap-3 animate-fade-up-2 bg-white rounded-2xl border border-slate-200 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div>
                        <label class="text-[10px] uppercase tracking-wider text-slate-400 block">Search</label>
                        <div class="relative">
                            <i
                                class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[11px]"></i>
                            <input type="search" id="filterSearch" placeholder="Search beneficiary, type, amount, status..."
                                autocomplete="off"
                                class="w-56 text-[12px] border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                                oninput="applyFilters()" />
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] uppercase tracking-wider text-slate-400 block">Budget Source</label>
                        <select id="filterBudget" class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none" onchange="updateTypeOptions(); applyFilters();">
                            <option value="all">All</option>
                            <option value="FBML">AICS FBML</option>
                            <option value="Educational">AICS Educational</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] uppercase tracking-wider text-slate-400 block">Type</label>
                        <select id="filterType" class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none" onchange="applyFilters()">
                            <!-- Options dynamically populated -->
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] uppercase tracking-wider text-slate-400 block">From</label>
                        <input type="date" id="filterFrom" class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none" onchange="applyFilters()" />
                    </div>
                    <div>
                        <label class="text-[10px] uppercase tracking-wider text-slate-400 block">To</label>
                        <input type="date" id="filterTo" class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none" onchange="applyFilters()" />
                    </div>
                </div>
                <div class="flex-1"></div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-slate-400" id="rowCount">Showing 0 transactions</span>
                </div>
            </div>

            <!-- Transactions Table with Action Column -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-up-3">
                <div class="overflow-x-auto">
                    <table class="w-full text-[12px]" id="transactionTable">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Beneficiary</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Budget Source</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Type</th>
                                <th class="sortable text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold" data-sort="amount" onclick="sortTable('amount')">
                                    Amount <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                </th>
                                <th class="sortable text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold" data-sort="date" onclick="sortTable('date')">
                                    Date Applied <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                </th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Status
                                </th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Date Released
                                </th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="tableBody">
                            <!-- Rows injected by JS -->
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="flex items-center justify-between px-5 py-3 border-t border-slate-100">
                    <span class="text-[11px] text-slate-400" id="paginationInfo">Showing 1–10 of 10</span>
                    <div class="flex items-center gap-1">
                        <button class="text-[11px] text-slate-400 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors">Previous</button>
                        <button class="text-[11px] font-medium text-white bg-green-600 rounded-lg px-3 py-1">1</button>
                        <button class="text-[11px] text-slate-600 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors">Next</button>
                    </div>
                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-6 right-6 bg-green-700 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-green-300"></i>
        <span id="toastMsg">Action completed!</span>
    </div>

    <script>
        // ── Live data from the database ──
        const transactions = <?= json_encode($aicsTransactions) ?>;

        let currentSort = { key: 'date', dir: 'desc' };
        let filteredData = [...transactions];

        // ── Type options based on budget source ──
        function getTypeOptions(budgetSource) {
            const allTypes = ['All', 'Medical', 'Burial', 'Financial', 'Livelihood', 'Educational'];
            const fbmlTypes = ['All', 'Medical', 'Burial', 'Financial', 'Livelihood'];
            const eduTypes = ['All', 'Educational'];
            if (budgetSource === 'FBML') return fbmlTypes;
            if (budgetSource === 'Educational') return eduTypes;
            return allTypes;
        }

        function updateTypeOptions() {
            const budgetVal = document.getElementById('filterBudget').value;
            const typeSelect = document.getElementById('filterType');
            const types = getTypeOptions(budgetVal);
            const currentType = typeSelect.value;
            typeSelect.innerHTML = '';
            types.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t === 'All' ? 'all' : t;
                opt.textContent = t;
                if ((t === 'All' && currentType === 'all') || t === currentType) {
                    opt.selected = true;
                }
                typeSelect.appendChild(opt);
            });
            const availableValues = types.map(t => t === 'All' ? 'all' : t);
            if (!availableValues.includes(currentType) && types.length > 0) {
                typeSelect.value = 'all';
            }
        }

        function statusClass(status) {
            if (status === 'Released') {
                return 'bg-blue-100 text-blue-700';
            }

            if (status === 'Approved') {
                return 'bg-emerald-100 text-emerald-700';
            }

            if (status === 'Denied') {
                return 'bg-red-100 text-red-700';
            }

            return 'bg-slate-100 text-slate-600';
        }

        function statusLabel(status) {
            return status || 'Approved';
        }

        function renderTable(data) {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            data.forEach(row => {
                const tr = document.createElement('tr');
                tr.className = 'table-row';
                tr.innerHTML = `
                    <td class="px-5 py-3 font-medium text-green-700">${row.beneficiary}</td>
                    <td class="px-5 py-3"><span class="${row.budgetSource === 'FBML' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'} px-2 py-0.5 rounded text-[10px] font-semibold">AICS ${row.budgetSource}</span></td>
                    <td class="px-5 py-3 text-slate-600">${row.type}</td>
                    <td class="px-5 py-3 font-semibold text-slate-700">₱${row.amount.toLocaleString()}</td>
                    <td class="px-5 py-3 text-slate-400">${row.date}</td>
                    <td class="px-5 py-3">
                        <span class="px-2 py-1 rounded-full text-[10px] font-semibold ${statusClass(row.status)}">
                            ${statusLabel(row.status)}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-slate-400">
                        ${row.dateReleased ? row.dateReleased : '—'}
                    </td>
                    <td class="px-5 py-3">
                        <a href="aics_view.php?availment_id=${row.availmentId}" class="text-[12px] font-medium text-green-600 bg-green-50 border border-green-200 rounded-lg px-3 py-1.5 hover:bg-green-100 transition-colors inline-flex items-center gap-1.5">
                             View
                        </a>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            document.getElementById('rowCount').textContent = `Showing ${data.length} transactions`;
            document.getElementById('paginationInfo').textContent = `Showing 1–${data.length} of ${data.length}`;
        }

        function normalizeDateForFilter(value) {
    if (!value) return '';

    const raw = String(value).trim();

    // Already YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        return raw;
    }

    const date = new Date(raw);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}


function applyFilters(resetPage = true) {

    const searchTerm =
        document.getElementById('filterSearch')?.value
            .trim()
            .toLowerCase() || '';

    const budgetFilter =
        document.getElementById('filterBudget').value;

    const typeFilter =
        document.getElementById('filterType').value;

    const fromDate =
        document.getElementById('filterFrom').value;

    const toDate =
        document.getElementById('filterTo').value;


    // Invalid date range
    if (fromDate && toDate && fromDate > toDate) {
        filteredData = [];
        renderTable(filteredData);
        return;
    }


    filteredData = transactions.filter(row => {

        // -----------------------------
        // BUDGET SOURCE FILTER
        // -----------------------------
        if (
            budgetFilter !== 'all' &&
            String(row.budgetSource || '').toLowerCase() !==
            String(budgetFilter || '').toLowerCase()
        ) {
            return false;
        }


        // -----------------------------
        // TYPE FILTER
        // -----------------------------
        if (
            typeFilter !== 'all' &&
            String(row.type || '').toLowerCase() !==
            String(typeFilter || '').toLowerCase()
        ) {
            return false;
        }


        // -----------------------------
        // DATE FILTER
        // -----------------------------
        const rowDate = normalizeDateForFilter(row.date);

        if (fromDate && (!rowDate || rowDate < fromDate)) {
            return false;
        }

        if (toDate && (!rowDate || rowDate > toDate)) {
            return false;
        }


        // -----------------------------
        // SEARCH
        // -----------------------------
        if (searchTerm) {

            const searchableText = [
                row.availmentId,
                row.beneficiary,
                row.budgetSource,
                row.type,
                row.amount,
                row.status,
                row.date,
                row.dateReleased
            ]
                .map(value => String(value ?? ''))
                .join(' ')
                .toLowerCase();

            if (!searchableText.includes(searchTerm)) {
                return false;
            }
        }


        return true;
    });


    if (resetPage) {
        // Reset to first page if pagination is added later
        currentPage = 1;
    }

    sortData();
}

        function sortData() {
            const key = currentSort.key;
            const dir = currentSort.dir;
            filteredData.sort((a, b) => {
                let valA = a[key];
                let valB = b[key];
                if (key === 'amount') {
                    valA = parseFloat(valA);
                    valB = parseFloat(valB);
                } else if (key === 'date') {
                    valA = new Date(valA);
                    valB = new Date(valB);
                }
                if (valA < valB) return dir === 'asc' ? -1 : 1;
                if (valA > valB) return dir === 'asc' ? 1 : -1;
                return 0;
            });
            renderTable(filteredData);
            // Update sort icons
            document.querySelectorAll('th.sortable').forEach(th => {
                th.classList.remove('asc', 'desc');
                const icon = th.querySelector('.sort-icon i');
                if (th.dataset.sort === key) {
                    th.classList.add(dir);
                    icon.className = dir === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
                } else {
                    icon.className = 'fas fa-sort';
                }
            });
        }

        function sortTable(key) {
            if (currentSort.key === key) {
                currentSort.dir = currentSort.dir === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.key = key;
                currentSort.dir = 'asc';
            }
            sortData();
        }

        // ============================================================
// EXPORT HELPERS
// ============================================================

const PREPARED_BY_NAME =
    <?= json_encode($preparedByName, JSON_UNESCAPED_UNICODE) ?>;

const PREPARED_BY_TITLE =
    <?= json_encode($preparedByPosition, JSON_UNESCAPED_UNICODE) ?>;


function getDateOnly() {
    return new Date().toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}


function getTimeOnly() {
    return new Date().toLocaleTimeString('en-PH', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}


function getFooterTimestamp() {
    return `Generated on ${getDateOnly()} at ${getTimeOnly()}`;
}


function formatDate(value) {

    if (!value) return '';

    const raw = String(value).trim();

    if (!raw) return '';

    // Keep YYYY-MM-DD values clean
    if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        const [year, month, day] = raw.split('-');

        return new Date(
            Number(year),
            Number(month) - 1,
            Number(day)
        ).toLocaleDateString('en-PH', {
            year: 'numeric',
            month: 'short',
            day: '2-digit'
        });
    }

    const parsed = new Date(raw);

    if (Number.isNaN(parsed.getTime())) {
        return raw;
    }

    return parsed.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: '2-digit'
    });
}


function formatPeso(value) {

    return `₱${Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`;
}


// ============================================================
// GET FILTERED EXPORT DATA
// IMPORTANT: exports ALL filtered records,
// NOT just records currently visible in the table.
// ============================================================

function getExportData() {

    return filteredData.map(row => ({

        'Beneficiary':
            row.beneficiary || '',

        'Budget Source':
            `AICS ${row.budgetSource || ''}`,

        'Type':
            row.type || '',

        'Amount':
            Number(row.amount || 0),

        'Date Applied':
            row.date || '',

        'Status':
            row.status || 'Approved',

        'Date Released':
            row.dateReleased || ''

    }));
}

// ============================================================
// EXCEL EXPORT
// ============================================================

async function exportToXlsx() {

    try {

        if (!window.ExcelJS) {
            throw new Error('ExcelJS library not loaded.');
        }

        const rows = getExportData();

        if (rows.length === 0) {
            showToast('No data to export.', 'error');
            return;
        }


        const wb = new ExcelJS.Workbook();

        wb.creator = 'MSWDO San Enrique Information System';
        wb.modified = new Date();


        const ws = wb.addWorksheet('AICS Transactions');


        // LANDSCAPE
        ws.pageSetup = {
            paperSize: ws.PAPERSIZE_LEGAL,
            orientation: 'landscape',
            fitToPage: true,
            fitToWidth: 1,
            fitToHeight: 0,

            margins: {
                left: 0.2,
                right: 0.2,
                top: 0.3,
                bottom: 0.3,
                header: 0.1,
                footer: 0.1
            }
        };


        const columns = [

            {
                header: 'BENEFICIARY',
                key: 'Beneficiary',
                width: 35
            },

            {
                header: 'BUDGET SOURCE',
                key: 'Budget Source',
                width: 22
            },

            {
                header: 'TYPE',
                key: 'Type',
                width: 18
            },

            {
                header: 'AMOUNT',
                key: 'Amount',
                width: 20
            },

            {
                header: 'DATE APPLIED',
                key: 'Date Applied',
                width: 18
            },

            {
                header: 'STATUS',
                key: 'Status',
                width: 18
            },

            {
                header: 'DATE RELEASED',
                key: 'Date Released',
                width: 18
            }

        ];


        // --------------------------------------------------------
        // HEADER
        // --------------------------------------------------------

        const titleRows = [
            'Republic of the Philippines',
            'Province of Negros Occidental',
            'Municipality of San Enrique',
            'Municipal Social Welfare and Development Office',
            'AICS TRANSACTION REPORT',
            `Calendar Year ${new Date().getFullYear()}`
        ];


        titleRows.forEach((text, index) => {

            const rowNumber = index + 1;

            ws.mergeCells(
                rowNumber,
                1,
                rowNumber,
                columns.length
            );

            const cell = ws.getCell(rowNumber, 1);

            cell.value = text;

            cell.font = {
                name: 'Arial',
                size: rowNumber === 5 ? 13 : 11,
                bold: rowNumber !== 1 && rowNumber !== 6
            };

            cell.alignment = {
                horizontal: 'center',
                vertical: 'middle'
            };

        });


        // --------------------------------------------------------
        // FILTER SUMMARY
        // --------------------------------------------------------

        const budgetSelect =
            document.getElementById('filterBudget');

        const typeSelect =
            document.getElementById('filterType');

        const fromDate =
            document.getElementById('filterFrom').value;

        const toDate =
            document.getElementById('filterTo').value;


        ws.mergeCells(7, 1, 7, columns.length);

        ws.getCell(7, 1).value =
            `Budget Source: ${budgetSelect.options[budgetSelect.selectedIndex].text} | ` +
            `Type: ${typeSelect.options[typeSelect.selectedIndex].text}` +
            `${fromDate ? ` | From: ${formatDate(fromDate)}` : ''}` +
            `${toDate ? ` | To: ${formatDate(toDate)}` : ''}`;

        ws.getCell(7, 1).font = {
            name: 'Arial',
            size: 10
        };

        ws.getCell(7, 1).alignment = {
            horizontal: 'center'
        };


        // --------------------------------------------------------
        // TABLE HEADER
        // --------------------------------------------------------

        const headerRow = 9;

        columns.forEach((column, index) => {

            const cell =
                ws.getCell(headerRow, index + 1);

            cell.value = column.header;

            cell.font = {
                name: 'Arial',
                size: 10,
                bold: true
            };

            cell.alignment = {
                horizontal: 'center',
                vertical: 'middle',
                wrapText: true
            };

            cell.fill = {
                type: 'pattern',
                pattern: 'solid',
                fgColor: {
                    argb: 'FFEFEFEF'
                }
            };

            cell.border = {
                top: { style: 'thin' },
                left: { style: 'thin' },
                bottom: { style: 'thin' },
                right: { style: 'thin' }
            };

            ws.getColumn(index + 1).width =
                column.width;

        });


        // --------------------------------------------------------
        // DATA
        // --------------------------------------------------------

        let currentRow = headerRow + 1;
        let totalAmount = 0;


        rows.forEach(data => {

            const row =
                ws.getRow(currentRow++);

            columns.forEach((column, index) => {

                const cell =
                    row.getCell(index + 1);

                let value =
                    data[column.key] ?? '';


                if (column.key === 'Amount') {

                    value =
                        Number(value || 0);

                    totalAmount += value;

                }


                if (
                    column.key === 'Date Applied' ||
                    column.key === 'Date Released'
                ) {

                    value =
                        formatDate(value);

                }


                cell.value = value;

                cell.font = {
                    name: 'Arial',
                    size: 10
                };

                cell.alignment = {
                    horizontal:
                        column.key === 'Amount'
                            ? 'right'
                            : 'center',

                    vertical: 'middle',

                    wrapText: true
                };

                cell.border = {
                    top: { style: 'thin' },
                    left: { style: 'thin' },
                    bottom: { style: 'thin' },
                    right: { style: 'thin' }
                };


                if (column.key === 'Amount') {

                    cell.numFmt =
                        '₱#,##0.00';

                }

            });

        });


        // --------------------------------------------------------
        // TOTAL
        // --------------------------------------------------------

        const totalRow =
            ws.getRow(currentRow++);


        totalRow.getCell(1).value =
            'TOTAL';

        totalRow.getCell(1).font = {
            name: 'Arial',
            size: 10,
            bold: true
        };


        totalRow.getCell(4).value =
            totalAmount;

        totalRow.getCell(4).numFmt =
            '₱#,##0.00';

        totalRow.getCell(4).font = {
            name: 'Arial',
            size: 10,
            bold: true
        };


        for (
            let i = 1;
            i <= columns.length;
            i++
        ) {

            const cell =
                totalRow.getCell(i);

            cell.fill = {
                type: 'pattern',
                pattern: 'solid',
                fgColor: {
                    argb: 'FFEFEFEF'
                }
            };

            cell.border = {
                top: { style: 'thin' },
                left: { style: 'thin' },
                bottom: { style: 'thin' },
                right: { style: 'thin' }
            };

        }


        // --------------------------------------------------------
        // FOOTER
        // --------------------------------------------------------

        const footerRow =
            currentRow + 2;

        ws.mergeCells(
            footerRow,
            1,
            footerRow,
            3
        );

        ws.getCell(
            footerRow,
            1
        ).value = 'Prepared by:';


        ws.mergeCells(
            footerRow + 3,
            1,
            footerRow + 3,
            3
        );

        ws.getCell(
            footerRow + 3,
            1
        ).value = PREPARED_BY_NAME;

        ws.getCell(
            footerRow + 3,
            1
        ).font = {
            name: 'Arial',
            size: 10,
            bold: true
        };


        ws.mergeCells(
            footerRow + 4,
            1,
            footerRow + 4,
            3
        );

        ws.getCell(
            footerRow + 4,
            1
        ).value = PREPARED_BY_TITLE;


        const buffer =
            await wb.xlsx.writeBuffer();


        const blob = new Blob(
            [buffer],
            {
                type:
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            }
        );


        const url =
            URL.createObjectURL(blob);

        const a =
            document.createElement('a');

        a.href = url;

        a.download =
            `MSWDO_San_Enrique_AICS_Transactions_${new Date().toISOString().slice(0, 10)}.xlsx`;

        document.body.appendChild(a);

        a.click();

        a.remove();

        setTimeout(() => {
            URL.revokeObjectURL(url);
        }, 1000);


        showToast('Excel exported successfully!');

    } catch (err) {

        console.error(
            'Excel export failed:',
            err
        );

        alert(
            'Excel export failed: ' +
            (err.message || err)
        );

    }

}

// ============================================================
// PDF EXPORT
// ============================================================

async function exportToPdf() {

    try {

        if (!window.jspdf) {
            throw new Error(
                'jsPDF library not loaded.'
            );
        }

        const { jsPDF } = window.jspdf;

        const rows = getExportData();

        if (rows.length === 0) {
            showToast('No data to export.', 'error');
            return;
        }


        // ALWAYS LANDSCAPE
        const doc =
            new jsPDF(
                'l',
                'pt',
                'legal'
            );


        const pageWidth =
            doc.internal.pageSize.getWidth();

        const margin = 30;


        // --------------------------------------------------------
        // HEADER
        // --------------------------------------------------------

        doc.setFont(
            'helvetica',
            'normal'
        );

        doc.setFontSize(11);

        doc.text(
            'Republic of the Philippines',
            pageWidth / 2,
            36,
            { align: 'center' }
        );


        doc.setFont(
            'helvetica',
            'bold'
        );

        doc.text(
            'Province of Negros Occidental',
            pageWidth / 2,
            54,
            { align: 'center' }
        );

        doc.text(
            'Municipality of San Enrique',
            pageWidth / 2,
            72,
            { align: 'center' }
        );

        doc.text(
            'Municipal Social Welfare and Development Office',
            pageWidth / 2,
            90,
            { align: 'center' }
        );


        doc.setFontSize(14);

        doc.text(
            'AICS TRANSACTION REPORT',
            pageWidth / 2,
            120,
            { align: 'center' }
        );


        doc.setFont(
            'helvetica',
            'normal'
        );

        doc.setFontSize(10);

        doc.text(
            `Calendar Year ${new Date().getFullYear()}`,
            pageWidth / 2,
            138,
            { align: 'center' }
        );


        // --------------------------------------------------------
        // FILTER SUMMARY
        // --------------------------------------------------------

        const budgetSelect =
            document.getElementById('filterBudget');

        const typeSelect =
            document.getElementById('filterType');

        const fromDate =
            document.getElementById('filterFrom').value;

        const toDate =
            document.getElementById('filterTo').value;


        const filterText =
            `Budget: ${budgetSelect.options[budgetSelect.selectedIndex].text} | ` +
            `Type: ${typeSelect.options[typeSelect.selectedIndex].text}` +
            `${fromDate ? ` | From: ${formatDate(fromDate)}` : ''}` +
            `${toDate ? ` | To: ${formatDate(toDate)}` : ''}`;


        doc.text(
            filterText,
            pageWidth / 2,
            153,
            { align: 'center' }
        );


        // --------------------------------------------------------
        // TABLE
        // --------------------------------------------------------

        let totalAmount = 0;

        const tableData =
            rows.map(row => {

                totalAmount +=
                    Number(row.Amount || 0);

                return [

                    row.Beneficiary,

                    row['Budget Source'],

                    row.Type,

                    formatPeso(row.Amount),

                    formatDate(row['Date Applied']),

                    row.Status,

                    formatDate(row['Date Released'])

                ];

            });


        tableData.push([
            '',
            '',
            'TOTAL',
            formatPeso(totalAmount),
            '',
            '',
            ''
        ]);


        doc.autoTable({

            startY: 170,

            head: [[
                'Beneficiary',
                'Budget Source',
                'Type',
                'Amount',
                'Date Applied',
                'Status',
                'Date Released'
            ]],

            body: tableData,

            theme: 'grid',

            styles: {
                font: 'helvetica',
                fontSize: 9,
                cellPadding: 4,
                valign: 'middle'
            },

            headStyles: {
                fillColor: [240, 240, 240],
                textColor: [0, 0, 0],
                fontStyle: 'bold',
                fontSize: 9,
                halign: 'center'
            },

            columnStyles: {

                0: {
                    cellWidth: 150,
                    halign: 'left'
                },

                1: {
                    cellWidth: 100,
                    halign: 'center'
                },

                2: {
                    cellWidth: 85,
                    halign: 'center'
                },

                3: {
                    cellWidth: 100,
                    halign: 'right'
                },

                4: {
                    cellWidth: 95,
                    halign: 'center'
                },

                5: {
                    cellWidth: 85,
                    halign: 'center'
                },

                6: {
                    cellWidth: 95,
                    halign: 'center'
                }

            },

            margin: {
                left: margin,
                right: margin
            },


            didParseCell: function(data) {

                // TOTAL ROW
                if (
                    data.row.index ===
                    tableData.length - 1
                ) {

                    data.cell.styles.fontStyle =
                        'bold';

                    data.cell.styles.fillColor =
                        [240, 240, 240];

                }

            }

        });


        // --------------------------------------------------------
        // FOOTER
        // --------------------------------------------------------

        const finalY =
            doc.lastAutoTable.finalY + 30;


        doc.setFontSize(10);

        doc.setFont(
            'helvetica',
            'normal'
        );

        doc.text(
            'Prepared by:',
            margin,
            finalY
        );


        doc.setFont(
            'helvetica',
            'bold'
        );

        doc.text(
            PREPARED_BY_NAME,
            margin,
            finalY + 30
        );


        doc.line(
            margin,
            finalY + 20,
            margin + 200,
            finalY + 20
        );


        doc.setFont(
            'helvetica',
            'normal'
        );

        doc.text(
            PREPARED_BY_TITLE,
            margin,
            finalY + 44
        );


        doc.setFontSize(8);

        doc.setFont(
            'helvetica',
            'italic'
        );

        doc.text(
            getFooterTimestamp(),
            pageWidth - margin,
            doc.internal.pageSize.getHeight() - 20,
            { align: 'right' }
        );


        // --------------------------------------------------------
        // DOWNLOAD
        // --------------------------------------------------------

        doc.save(
            `MSWDO_San_Enrique_AICS_Transactions_${new Date().toISOString().slice(0, 10)}.pdf`
        );


        showToast(
            'PDF exported successfully!'
        );


    } catch (err) {

        console.error(
            'PDF export failed:',
            err
        );

        alert(
            'PDF export failed: ' +
            (err.message || err)
        );

    }

}

// ============================================================
// WORD EXPORT
// ============================================================

async function exportToDocx() {

    try {

        if (!window.JSZip) {
            throw new Error(
                'JSZip library not loaded.'
            );
        }


        const rows =
            getExportData();


        if (rows.length === 0) {
            showToast(
                'No data to export.',
                'error'
            );
            return;
        }


        const zip =
            new JSZip();


        const esc = value =>
            String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&apos;');


        const columns = [
            'Beneficiary',
            'Budget Source',
            'Type',
            'Amount',
            'Date Applied',
            'Status',
            'Date Released'
        ];


        let totalAmount = 0;


        let tableRows = '';


        // HEADER ROW

        tableRows += `
            <w:tr>
                ${columns.map(col => `
                    <w:tc>
                        <w:tcPr>
                            <w:shd w:fill="EFEFEF"/>
                            <w:tcW w:w="2000" w:type="dxa"/>
                        </w:tcPr>

                        <w:p>
                            <w:pPr>
                                <w:jc w:val="center"/>
                            </w:pPr>

                            <w:r>
                                <w:rPr>
                                    <w:b/>
                                    <w:rFonts
                                        w:ascii="Arial"
                                        w:hAnsi="Arial"/>
                                    <w:sz w:val="18"/>
                                </w:rPr>

                                <w:t>
                                    ${esc(col.toUpperCase())}
                                </w:t>
                            </w:r>
                        </w:p>
                    </w:tc>
                `).join('')}
            </w:tr>
        `;


        // DATA ROWS

        rows.forEach(row => {

            totalAmount +=
                Number(row.Amount || 0);


            const values = [

                row.Beneficiary,

                row['Budget Source'],

                row.Type,

                formatPeso(row.Amount),

                formatDate(row['Date Applied']),

                row.Status,

                formatDate(row['Date Released'])

            ];


            tableRows += `
                <w:tr>

                    ${values.map((value, index) => `
                        <w:tc>

                            <w:tcPr>
                                <w:tcW
                                    w:w="2000"
                                    w:type="dxa"/>

                                <w:tcBorders>
                                    <w:top
                                        w:val="single"
                                        w:sz="4"
                                        w:color="000000"/>

                                    <w:left
                                        w:val="single"
                                        w:sz="4"
                                        w:color="000000"/>

                                    <w:bottom
                                        w:val="single"
                                        w:sz="4"
                                        w:color="000000"/>

                                    <w:right
                                        w:val="single"
                                        w:sz="4"
                                        w:color="000000"/>
                                </w:tcBorders>
                            </w:tcPr>

                            <w:p>

                                <w:pPr>
                                    <w:jc
                                        w:val="${index === 3 ? 'right' : 'center'}"/>
                                </w:pPr>

                                <w:r>

                                    <w:rPr>
                                        <w:rFonts
                                            w:ascii="Arial"
                                            w:hAnsi="Arial"/>

                                        <w:sz w:val="18"/>
                                    </w:rPr>

                                    <w:t>
                                        ${esc(value)}
                                    </w:t>

                                </w:r>

                            </w:p>

                        </w:tc>
                    `).join('')}

                </w:tr>
            `;

        });


        // TOTAL ROW

        tableRows += `
            <w:tr>

                <w:tc>
                    <w:tcPr>
                        <w:shd w:fill="EFEFEF"/>
                    </w:tcPr>

                    <w:p>
                        <w:r>
                            <w:rPr>
                                <w:b/>
                            </w:rPr>

                            <w:t>TOTAL</w:t>
                        </w:r>
                    </w:p>
                </w:tc>

                <w:tc>
                    <w:p/>
                </w:tc>

                <w:tc>
                    <w:p/>
                </w:tc>

                <w:tc>
                    <w:tcPr>
                        <w:shd w:fill="EFEFEF"/>
                    </w:tcPr>

                    <w:p>
                        <w:pPr>
                            <w:jc w:val="right"/>
                        </w:pPr>

                        <w:r>
                            <w:rPr>
                                <w:b/>
                            </w:rPr>

                            <w:t>
                                ${esc(formatPeso(totalAmount))}
                            </w:t>
                        </w:r>
                    </w:p>
                </w:tc>

                <w:tc><w:p/></w:tc>
                <w:tc><w:p/></w:tc>
                <w:tc><w:p/></w:tc>

            </w:tr>
        `;


        // --------------------------------------------------------
        // DOCUMENT XML
        // --------------------------------------------------------

        const documentXml = `
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>

<w:document
    xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">

    <w:body>

        <w:p>
            <w:pPr>
                <w:jc w:val="center"/>
            </w:pPr>

            <w:r>
                <w:rPr>
                    <w:rFonts
                        w:ascii="Arial"
                        w:hAnsi="Arial"/>
                    <w:sz w:val="22"/>
                </w:rPr>

                <w:t>
                    Republic of the Philippines
                </w:t>
            </w:r>
        </w:p>


        <w:p>
            <w:pPr>
                <w:jc w:val="center"/>
            </w:pPr>

            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:rFonts
                        w:ascii="Arial"
                        w:hAnsi="Arial"/>
                    <w:sz w:val="22"/>
                </w:rPr>

                <w:t>
                    Province of Negros Occidental
                </w:t>
            </w:r>
        </w:p>


        <w:p>
            <w:pPr>
                <w:jc w:val="center"/>
            </w:pPr>

            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:rFonts
                        w:ascii="Arial"
                        w:hAnsi="Arial"/>
                    <w:sz w:val="22"/>
                </w:rPr>

                <w:t>
                    Municipality of San Enrique
                </w:t>
            </w:r>
        </w:p>


        <w:p>
            <w:pPr>
                <w:jc w:val="center"/>
            </w:pPr>

            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:rFonts
                        w:ascii="Arial"
                        w:hAnsi="Arial"/>
                    <w:sz w:val="22"/>
                </w:rPr>

                <w:t>
                    Municipal Social Welfare and Development Office
                </w:t>
            </w:r>
        </w:p>


        <w:p>
            <w:pPr>
                <w:jc w:val="center"/>
            </w:pPr>

            <w:r>
                <w:rPr>
                    <w:b/>
                    <w:rFonts
                        w:ascii="Arial"
                        w:hAnsi="Arial"/>
                    <w:sz w:val="28"/>
                </w:rPr>

                <w:t>
                    AICS TRANSACTION REPORT
                </w:t>
            </w:r>
        </w:p>


        <w:p>
            <w:pPr>
                <w:jc w:val="center"/>
            </w:pPr>

            <w:r>
                <w:rPr>
                    <w:rFonts
                        w:ascii="Arial"
                        w:hAnsi="Arial"/>
                    <w:sz w:val="20"/>
                </w:rPr>

                <w:t>
                    Calendar Year ${new Date().getFullYear()}
                </w:t>
            </w:r>
        </w:p>


        <w:p/>

        <w:tbl>

            <w:tblPr>

                <w:tblW
                    w:w="15800"
                    w:type="dxa"/>

                <w:tblLayout
                    w:type="fixed"/>

            </w:tblPr>


            ${tableRows}

        </w:tbl>


        <w:p/>

        <w:p>
            <w:r>
                <w:t>Prepared by:</w:t>
            </w:r>
        </w:p>


        <w:p>
            <w:r>
                <w:rPr>
                    <w:b/>
                </w:rPr>

                <w:t>
                    ${esc(PREPARED_BY_NAME)}
                </w:t>
            </w:r>
        </w:p>


        <w:p>
            <w:r>
                <w:t>
                    ${esc(PREPARED_BY_TITLE)}
                </w:t>
            </w:r>
        </w:p>


        <w:p>
            <w:r>
                <w:rPr>
                    <w:i/>
                </w:rPr>

                <w:t>
                    ${esc(getFooterTimestamp())}
                </w:t>
            </w:r>
        </w:p>


        <w:sectPr>

            <!-- LANDSCAPE -->

            <w:pgSz
                w:w="15840"
                w:h="12240"
                w:orient="landscape"/>

            <w:pgMar
                w:top="500"
                w:right="500"
                w:bottom="500"
                w:left="500"/>

        </w:sectPr>

    </w:body>

</w:document>
`;


        // --------------------------------------------------------
        // DOCX FILE STRUCTURE
        // --------------------------------------------------------

        const contentTypes = `
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>

<Types
    xmlns="http://schemas.openxmlformats.org/package/2006/content-types">

    <Default
        Extension="rels"
        ContentType="application/vnd.openxmlformats-package.relationships+xml"/>

    <Default
        Extension="xml"
        ContentType="application/xml"/>

    <Override
        PartName="/word/document.xml"
        ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>

</Types>
`;


        const relationships = `
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>

<Relationships
    xmlns="http://schemas.openxmlformats.org/package/2006/relationships">

    <Relationship
        Id="rId1"
        Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"
        Target="word/document.xml"/>

</Relationships>
`;


        const wordRelationships = `
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>

<Relationships
    xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
</Relationships>
`;


        zip.file(
            '[Content_Types].xml',
            contentTypes
        );

        zip.folder('_rels')
            .file(
                '.rels',
                relationships
            );

        zip.folder('word')
            .file(
                'document.xml',
                documentXml
            );

        zip.folder('word')
            .folder('_rels')
            .file(
                'document.xml.rels',
                wordRelationships
            );


        const blob =
            await zip.generateAsync({
                type: 'blob',
                mimeType:
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            });


        const url =
            URL.createObjectURL(blob);

        const a =
            document.createElement('a');

        a.href = url;

        a.download =
            `MSWDO_San_Enrique_AICS_Transactions_${new Date().toISOString().slice(0, 10)}.docx`;

        document.body.appendChild(a);

        a.click();

        a.remove();


        setTimeout(() => {
            URL.revokeObjectURL(url);
        }, 1000);


        showToast(
            'Word exported successfully!'
        );


    } catch (err) {

        console.error(
            'Word export failed:',
            err
        );

        alert(
            'Word export failed: ' +
            (err.message || err)
        );

    }

}

// ============================================================
// EXPORT BUTTON EVENT LISTENERS
// ============================================================

document
    .getElementById('exportXlsx')
    .addEventListener('click', async (e) => {

        e.preventDefault();

        document
            .getElementById('exportDropdownContainer')
            .classList.remove('active');

        await exportToXlsx();

    });


document
    .getElementById('exportPdf')
    .addEventListener('click', async (e) => {

        e.preventDefault();

        document
            .getElementById('exportDropdownContainer')
            .classList.remove('active');

        await exportToPdf();

    });


document
    .getElementById('exportDocx')
    .addEventListener('click', async (e) => {

        e.preventDefault();

        document
            .getElementById('exportDropdownContainer')
            .classList.remove('active');

        await exportToDocx();

    });


// ============================================================
// EXPORT DROPDOWN
// ============================================================

document
    .getElementById('exportDropdownBtn')
    .addEventListener('click', (e) => {

        e.preventDefault();
        e.stopPropagation();

        document
            .getElementById('exportDropdownContainer')
            .classList.toggle('active');

    });


document.addEventListener('click', (e) => {

    const dropdown =
        document.getElementById(
            'exportDropdownContainer'
        );

    if (!dropdown.contains(e.target)) {

        dropdown.classList.remove(
            'active'
        );

    }

});



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
            }, 3000);
        }

        // ── Initialise ──
        updateTypeOptions();
        applyFilters();
    </script>

</body>

</html>