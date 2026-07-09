<?php
require 'auth.php';
requireRole(['Admin', 'Social Worker']); 
require 'db_connect.php';
require 'budget_helpers.php';

// -- BUDGET SUMMARY CARD (same formula as every other budget page) --
$fundBudget = getProgramBudget($pdo, ['Women and Child Protection']);

// -- FUND REQUESTS TABLE (live from PROJECT_PROPOSAL) --
$fundRequestsPhp = getFundRequests($pdo, 'Women and Child Protection');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Women and Children Fund Requests – MSWDO San Enrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                <span class="text-green-600 font-semibold">Women and Children Fund Requests</span>
            </div>
        </header>

        <main class="flex-1 p-6 space-y-5 overflow-y-auto">

            <!-- Page Title -->
            <div class="flex flex-wrap items-center justify-between gap-3 animate-fade-up">
                <div>
                    <h1 class="text-xl font-serif text-green-600">Women and Children Fund Requests</h1>
                    <p class="text-[13px] text-slate-500 mt-0.5">View, filter, and export all Women and Children Protection fund requests.</p>
                </div>
                <button onclick="exportCSV()" class="btn-action text-[12px] font-semibold text-white bg-green-600 rounded-lg px-3 py-1.5 hover:bg-green-700">
                    <i class="fas fa-file-csv mr-1"></i> Export CSV
                </button>
            </div>

            <!-- Budget Summary Card -->
            <div class="grid grid-cols-1 md:grid-cols-1 gap-4 animate-fade-up-1">
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[13px] font-semibold text-green-600"><i class="fas fa-shield-alt mr-1.5 text-green-400"></i>Women and Children Budget</h3>
                        <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">LGU</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total</p>
                            <p class="text-xl font-bold text-green-600">₱<?= number_format($fundBudget['total'], 0) ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Allocated</p>
                            <p class="text-xl font-bold text-amber-600">₱<?= number_format($fundBudget['spent'], 0) ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Remaining</p>
                            <p class="text-xl font-bold text-blue-600">₱<?= number_format($fundBudget['remaining'], 0) ?></p>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <div class="flex justify-between text-[10px] text-slate-400">
                            <span>Used: <?= $fundBudget['pct_used'] ?>%</span>
                            <span>Remaining: <?= 100 - $fundBudget['pct_used'] ?>%</span>
                        </div>
                        <div class="bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                            <div class="h-1.5 rounded-full bg-green-500" style="width:<?= $fundBudget['pct_used'] ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3 animate-fade-up-2 bg-white rounded-2xl border border-slate-200 p-4">
                <div class="flex flex-wrap items-center gap-3">
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
                    <span class="text-[11px] text-slate-400" id="rowCount">Showing 0 fund requests</span>
                </div>
            </div>

            <!-- Fund Requests Table -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-up-3">
                <div class="overflow-x-auto">
                    <table class="w-full text-[12px]" id="fundRequestTable">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Fund Request Title</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Duration</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Venue</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Participants</th>
                                <th class="sortable text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold" data-sort="amount" onclick="sortTable('amount')">
                                    Budget <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                </th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Source of Fund</th>
                                <th class="sortable text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold" data-sort="date" onclick="sortTable('date')">
                                    Date Submitted <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                </th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Action</th>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="tableBody">
                            <!-- Rows injected by JS -->
                        </tbody>
                    </table>
                </div>
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
        // ── Sample Data (Women and Children fund requests) ──
        const fundRequests = <?= json_encode($fundRequestsPhp) ?>;

        let currentSort = { key: 'date', dir: 'asc' };
        let filteredData = [...fundRequests];

        function renderTable(data) {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            data.forEach(row => {
                const tr = document.createElement('tr');
                tr.className = 'table-row';
                tr.innerHTML = `
                    <td class="px-5 py-3 font-medium text-green-700">${row.title}</td>
                    <td class="px-5 py-3 text-slate-600">${row.duration}</td>
                    <td class="px-5 py-3 text-slate-600">${row.venue}</td>
                    <td class="px-5 py-3 text-slate-600">${row.participants}</td>
                    <td class="px-5 py-3 font-semibold text-slate-700">₱${row.budget.toLocaleString()}</td>
                    <td class="px-5 py-3"><span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px] font-semibold">${row.fundSource}</span></td>
                    <td class="px-5 py-3 text-slate-400">${row.date}</td>
                    <td class="px-5 py-3">
        <a href="project_proposal_view.php" class="text-[12px] font-medium text-green-600 bg-green-50 border border-green-200 rounded-lg px-3 py-1.5 hover:bg-green-100 transition-colors inline-flex items-center gap-1.5">
             View
        </a>
    </td>
                `;
                tbody.appendChild(tr);
            });
            document.getElementById('rowCount').textContent = `Showing ${data.length} fund requests`;
            document.getElementById('paginationInfo').textContent = `Showing 1–${data.length} of ${data.length}`;
        }

        function applyFilters() {
            const fromDate = document.getElementById('filterFrom').value;
            const toDate = document.getElementById('filterTo').value;

            filteredData = fundRequests.filter(row => {
                if (fromDate && row.date < fromDate) return false;
                if (toDate && row.date > toDate) return false;
                return true;
            });
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

        // ── CSV Export ──
        function exportCSV() {
            const data = filteredData;
            if (data.length === 0) {
                showToast('No data to export.', 'error');
                return;
            }

            const fromDate = document.getElementById('filterFrom').value;
            const toDate = document.getElementById('filterTo').value;

            let csv = '';
            csv += 'Municipal Social Welfare and Development Office\n';
            csv += 'San Enrique, Negros Occidental\n';
            csv += 'Women and Children Fund Requests Report\n\n';

            if (fromDate) csv += 'Date From: ' + fromDate + '\n';
            if (toDate) csv += 'Date To: ' + toDate + '\n';
            if (!fromDate && !toDate) csv += 'Date Range: All\n';
            csv += '\n';

            csv += 'Fund Request Title,Duration,Venue,Participants,Budget,Source of Fund,Date Submitted\n';
            data.forEach(row => {
                csv += `"${row.title}",${row.duration},"${row.venue}",${row.participants},${row.budget},${row.fundSource},${row.date}\n`;
            });

            csv += '\nGenerated on: ' + new Date().toLocaleString('en-PH', { timeZone: 'Asia/Manila' }) + '\n';

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Women_and_Children_Fund_Requests_' + new Date().toISOString().slice(0, 10) + '.csv';
            a.click();
            URL.revokeObjectURL(url);
            showToast('CSV exported successfully!');
        }

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
        applyFilters();
    </script>

</body>

</html>