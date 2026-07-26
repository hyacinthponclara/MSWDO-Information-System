<?php
require 'auth.php';
requireRole(['Admin', 'Staff']); 
require 'db_connect.php';
require 'budget_helpers.php';

// ── Project-proposal-backed programs ────────────────────────────────────
// Maps the short display label (used by the existing badgeClasses/filter
// dropdown in the JS below — unchanged) to the real PROGRAM.program_name
// string each funds_*.php page filters on. Confirmed against funds_4ps.php,
// funds_daycare.php, funds_pwd.php, funds_senior.php, funds_sfp.php,
// funds_slp.php, funds_soloparents.php, funds_wac.php.
$programNameMap = [
    '4Ps'             => '4Ps',
    'Solo Parents'    => 'Solo Parent Program',
    'Senior Citizen'  => 'Senior Citizen Program',
    'PWD'             => 'PWD Program',
    'Day Care'        => 'Day Care Center Program',
    'SFP'             => 'SFP',
    'SLP'             => 'SLP',
    'Women and Children' => 'Women and Child Protection',
];

$allRequests = [];

foreach ($programNameMap as $label => $dbProgramName) {
    foreach (getFundRequests($pdo, $dbProgramName) as $r) {
        $allRequests[] = [
            'program'      => $label,
            'title'        => $r['title'],
            'duration'     => $r['duration'],
            'venue'        => $r['venue'],
            'participants' => $r['participants'],
            'budget'       => $r['budget'],
            'source'       => $r['fundSource'],
            'date'         => $r['date'],
        ];
    }
}

// ── AICS availments ──────────────────────────────────────────────────────
// AICS assistance lives in AVAILMENT + 5 subtype tables, not
// PROJECT_PROPOSAL, and has no per-row fund-source column — same structure
// funds_aics.php already relies on. Union all 5 subtypes together here too,
// rather than inventing a different query shape for this report.
$aicsSubtypes = [
    ['table' => 'aics_financial',   'type' => 'Financial'],
    ['table' => 'aics_burial',      'type' => 'Burial'],
    ['table' => 'aics_medical',     'type' => 'Medical'],
    ['table' => 'aics_livelihood',  'type' => 'Livelihood'],
    ['table' => 'aics_educational', 'type' => 'Educational'],
];

foreach ($aicsSubtypes as $sub) {
    $stmt = $pdo->prepare("
        SELECT
            a.av_amount,
            a.av_date_applied,
            c.cl_firstname,
            c.cl_lastname
        FROM {$sub['table']} t
        JOIN availment a ON a.availment_id = t.availment_id
        JOIN client c ON c.client_id = a.client_id
    ");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $beneficiary = trim($row['cl_firstname'] . ' ' . $row['cl_lastname']);
        $allRequests[] = [
            'program'      => 'AICS',
            'title'        => $beneficiary . ' - ' . $sub['type'],
            'duration'     => 'N/A',
            'venue'        => 'MSWDO Office',
            'participants' => '1',
            'budget'       => (float) $row['av_amount'],
            // AICS has no fund-source column of its own; this reflects
            // that AICS FBML/Educational are jointly funded, not a
            // per-transaction value pulled from the database.
            'source'       => 'LGU + DSWD',
            'date'         => (new DateTime($row['av_date_applied']))->format('Y-m-d'),
        ];
    }
}

usort($allRequests, fn($a, $b) => strcmp($b['date'], $a['date']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Program Reports – MSWDO San Enrique</title>
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

        .badge-aics {
            background: #DBEAFE;
            color: #1D4ED8;
        }
        .badge-4ps {
            background: #F3E8FF;
            color: #6D28D9;
        }
        .badge-solo {
            background: #CCFBF1;
            color: #0F766E;
        }
        .badge-senior {
            background: #FEF3C7;
            color: #B45309;
        }
        .badge-pwd {
            background: #DBEAFE;
            color: #1D4ED8;
        }
        .badge-daycare {
            background: #FFEDD5;
            color: #C2410C;
        }
        .badge-sfp {
            background: #D1FAE5;
            color: #15803D;
        }
        .badge-slp {
            background: #FEF3C7;
            color: #D97706;
        }
        .badge-women {
            background: #F3E8FF;
            color: #6D28D9;
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
                <span class="text-green-600 font-semibold">Program Reports</span>
            </div>
        </header>

        <main class="flex-1 p-6 space-y-5 overflow-y-auto">

            <!-- Page Title -->
            <div class="flex flex-wrap items-center justify-between gap-3 animate-fade-up">
                <div>
                    <h1 class="text-xl font-serif text-green-600">Program Reports</h1>
                    <p class="text-[13px] text-slate-500 mt-0.5">Consolidated view of all program reports.</p>
                </div>
                <button onclick="exportCSV()" class="btn-action text-[12px] font-semibold text-white bg-green-600 rounded-lg px-3 py-1.5 hover:bg-green-700">
                    <i class="fas fa-file-csv mr-1"></i> Export CSV
                </button>
            </div>

            <!-- Summary Statistics -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 animate-fade-up-1">
                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total Requests</p>
                    <p class="text-2xl font-bold text-green-600" id="totalRequests">0</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total Budget</p>
                    <p class="text-2xl font-bold text-green-600" id="totalBudget">₱0</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">Programs Covered</p>
                    <p class="text-2xl font-bold text-green-600" >9</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3 animate-fade-up-2 bg-white rounded-2xl border border-slate-200 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div>
                        <label class="text-[10px] uppercase tracking-wider text-slate-400 block">Program</label>
                        <select id="filterProgram" class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none" onchange="applyFilters()">
                            <option value="all">All Programs</option>
                            <option value="AICS">AICS</option>
                            <option value="4Ps">4Ps</option>
                            <option value="Solo Parents">Solo Parents</option>
                            <option value="Senior Citizen">Senior Citizen</option>
                            <option value="PWD">PWD</option>
                            <option value="Day Care">Day Care</option>
                            <option value="SFP">SFP</option>
                            <option value="SLP">SLP</option>
                            <option value="Women and Children">Women and Children</option>
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
                    <span class="text-[11px] text-slate-400" id="rowCount">Showing 0 requests</span>
                </div>
            </div>

            <!-- Fund Requests Table -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-up-3">
                <div class="overflow-x-auto">
                    <table class="w-full text-[12px]" id="reportTable">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Program</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Title / Beneficiary</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Duration</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Venue</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Participants</th>
                                <th class="sortable text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold" data-sort="amount" onclick="sortTable('amount')">
                                    Budget <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                </th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Source</th>
                                <th class="sortable text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold" data-sort="date" onclick="sortTable('date')">
                                    Date <span class="sort-icon"><i class="fas fa-sort"></i></span>
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
        // ── Consolidated Data from All Programs ──
        const allData = <?= json_encode($allRequests) ?>;

        // ── Program Badge Classes ──
        const badgeClasses = {
            'AICS': 'badge-aics',
            '4Ps': 'badge-4ps',
            'Solo Parents': 'badge-solo',
            'Senior Citizen': 'badge-senior',
            'PWD': 'badge-pwd',
            'Day Care': 'badge-daycare',
            'SFP': 'badge-sfp',
            'SLP': 'badge-slp',
            'Women and Children': 'badge-women'
        };

        let currentSort = { key: 'date', dir: 'asc' };
        let filteredData = [...allData];

        function getBadgeClass(program) {
            return badgeClasses[program] || 'bg-slate-100 text-slate-700';
        }

        function renderTable(data) {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            let totalBudget = 0;
            const programs = new Set();

            data.forEach(row => {
                const tr = document.createElement('tr');
                tr.className = 'table-row';
                const badgeClass = getBadgeClass(row.program);
                tr.innerHTML = `
                    <td class="px-5 py-3"><span class="${badgeClass} px-2 py-0.5 rounded text-[10px] font-semibold">${row.program}</span></td>
                    <td class="px-5 py-3 font-medium text-green-700">${row.title}</td>
                    <td class="px-5 py-3 text-slate-600">${row.duration}</td>
                    <td class="px-5 py-3 text-slate-600">${row.venue}</td>
                    <td class="px-5 py-3 text-slate-600">${row.participants}</td>
                    <td class="px-5 py-3 font-semibold text-slate-700">₱${row.budget.toLocaleString()}</td>
                    <td class="px-5 py-3"><span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px] font-semibold">${row.source}</span></td>
                    <td class="px-5 py-3 text-slate-400">${row.date}</td>
                `;
                tbody.appendChild(tr);
                totalBudget += row.budget;
                programs.add(row.program);
            });

            // Update summary stats
            document.getElementById('totalRequests').textContent = data.length;
            document.getElementById('totalBudget').textContent = '₱' + totalBudget.toLocaleString();
            document.getElementById('totalPrograms').textContent = programs.size;

            // Date range
            if (data.length > 0) {
                const dates = data.map(r => r.date).sort();
                const from = dates[0];
                const to = dates[dates.length - 1];
                document.getElementById('dateRange').textContent = from + ' to ' + to;
            } else {
                document.getElementById('dateRange').textContent = 'No data';
            }

            document.getElementById('rowCount').textContent = `Showing ${data.length} requests`;
            document.getElementById('paginationInfo').textContent = `Showing 1–${data.length} of ${data.length}`;
        }

        function applyFilters() {
            const programFilter = document.getElementById('filterProgram').value;
            const fromDate = document.getElementById('filterFrom').value;
            const toDate = document.getElementById('filterTo').value;

            filteredData = allData.filter(row => {
                if (programFilter !== 'all' && row.program !== programFilter) return false;
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

            const programFilter = document.getElementById('filterProgram');
            const fromDate = document.getElementById('filterFrom').value;
            const toDate = document.getElementById('filterTo').value;

            const programLabel = programFilter.options[programFilter.selectedIndex].text;

            let csv = '';
            csv += 'Municipal Social Welfare and Development Office\n';
            csv += 'San Enrique, Negros Occidental\n';
            csv += 'Program Report\n\n';

            csv += 'Program Filter: ' + programLabel + '\n';
            if (fromDate) csv += 'Date From: ' + fromDate + '\n';
            if (toDate) csv += 'Date To: ' + toDate + '\n';
            if (!fromDate && !toDate) csv += 'Date Range: All\n';
            csv += '\n';

            csv += 'Program,Title,Duration,Venue,Participants,Budget,Source of Fund,Date Submitted\n';
            data.forEach(row => {
                csv +=
                    `"${row.program}","${row.title}",${row.duration},"${row.venue}",${row.participants},${row.budget},${row.source},${row.date}\n`;
            });

            csv += '\nGenerated on: ' + new Date().toLocaleString('en-PH', { timeZone: 'Asia/Manila' }) + '\n';

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Program_Reports_' + new Date().toISOString().slice(0, 10) + '.csv';
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