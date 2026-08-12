<?php
require 'auth.php';
requireRole(['Admin', 'Staff']);
require 'db_connect.php';

/*
|--------------------------------------------------------------------------
| PROGRAM REPORT GROUPING
|--------------------------------------------------------------------------
| There are 10 PROGRAM rows in the database because AICS FBML and
| AICS Educational are stored as separate program/fund-source entries.
| For reporting purposes, they are ONE program: AICS.
|
| Therefore:
| - Project-proposal programs = 8
| - AICS = 1 combined program
| - Total Programs Covered = 9
|--------------------------------------------------------------------------
*/

$programNameMap = [
    '4Ps'                => '4Ps',
    'Solo Parents'       => 'Solo Parent Program',
    'Senior Citizen'     => 'Senior Citizen Program',
    'PWD'                => 'PWD Program',
    'Day Care'           => 'Day Care Center Program',
    'SFP'                => 'SFP',
    'SLP'                => 'SLP',
    'Women and Children' => 'Women and Child Protection',
];

$allRequests = [];

/*
|--------------------------------------------------------------------------
| PROJECT PROPOSALS
|--------------------------------------------------------------------------
*/
$proposalStmt = $pdo->prepare("
    SELECT
        pp.proposal_id,
        pp.pp_title,
        pp.pp_date_from,
        pp.pp_date_to,
        pp.pp_venue,
        pp.pp_num_participants,
        pp.pp_participant_desc,
        pp.pp_budget,
        pp.pp_fund_source,
        pp.pp_date_submitted,
        pp.pp_status,
        pp.pp_date_released,
        p.program_name
    FROM project_proposal pp
    JOIN program p
        ON p.program_id = pp.program_id
    WHERE p.program_name = ?
    ORDER BY pp.pp_date_submitted DESC
");

foreach ($programNameMap as $label => $dbProgramName) {
    $proposalStmt->execute([$dbProgramName]);

    foreach ($proposalStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $from = new DateTime($r['pp_date_from']);
        $to   = new DateTime($r['pp_date_to']);
        $days = $from->diff($to)->days + 1;

        $allRequests[] = [
            'id'            => 'PP-' . (int) $r['proposal_id'],
            'type'          => 'Project Proposal',
            'program'       => $label,
            'title'         => $r['pp_title'],
            'duration'      => $days . ' ' . ($days === 1 ? 'day' : 'days'),
            'venue'         => $r['pp_venue'],
            'participants'  => (int) preg_replace('/[^0-9].*$/', '', (string) $r['pp_num_participants']),
            'budget'        => (float) $r['pp_budget'],
            'source'        => $r['pp_fund_source'],
            'date'          => (new DateTime($r['pp_date_submitted']))->format('Y-m-d'),
            'status'        => $r['pp_status'] ?? 'Approved',
            'dateReleased'  => !empty($r['pp_date_released'])
                ? (new DateTime($r['pp_date_released']))->format('Y-m-d')
                : null,
        ];
    }
}

/*
|--------------------------------------------------------------------------
| AICS AVAILMENTS
|--------------------------------------------------------------------------
| AICS FBML + AICS Educational remain separate fund sources in the
| database, but are grouped under ONE report program: AICS.
|--------------------------------------------------------------------------
*/
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
            a.availment_id,
            a.av_amount,
            a.av_date_applied,
            a.av_status,
            a.av_date_released,
            c.cl_firstname,
            c.cl_lastname,
            p.program_name
        FROM {$sub['table']} t
        JOIN availment a
            ON a.availment_id = t.availment_id
        JOIN client c
            ON c.client_id = a.client_id
        JOIN program p
            ON p.program_id = a.program_id
        ORDER BY a.av_date_applied DESC
    ");
    $stmt->execute();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $beneficiary = trim($row['cl_firstname'] . ' ' . $row['cl_lastname']);

        $allRequests[] = [
            'id'            => 'AV-' . (int) $row['availment_id'],
            'type'          => 'AICS Availment',
            'program'       => 'AICS',
            'title'         => $beneficiary . ' - ' . $sub['type'],
            'duration'      => 'N/A',
            'venue'         => 'MSWDO Office',
            'participants'  => 1,
            'budget'        => (float) $row['av_amount'],
            // This is the actual AICS fund-source/program row.
            // AICS remains one report program even though the DB has
            // separate AICS FBML and AICS Educational program rows.
            'source'        => $row['program_name'] ?: 'AICS',
            'date'          => (new DateTime($row['av_date_applied']))->format('Y-m-d'),
            'status'        => $row['av_status'] ?? 'Approved',
            'dateReleased'  => !empty($row['av_date_released'])
                ? (new DateTime($row['av_date_released']))->format('Y-m-d')
                : null,
        ];
    }
}

usort($allRequests, function ($a, $b) {
    return strcmp($b['date'], $a['date']);
});

$totalPrograms = count($programNameMap) + 1; // 8 project programs + 1 AICS
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
                    <p class="text-[13px] text-slate-500 mt-0.5">
                        Consolidated fund-request report for all 9 MSWDO programs.
                    </p>
                </div>

                <button
                    onclick="exportCSV()"
                    class="btn-action text-[12px] font-semibold text-white bg-green-600 rounded-lg px-3 py-1.5 hover:bg-green-700"
                >
                    <i class="fas fa-file-csv mr-1"></i>
                    Export CSV
                </button>
            </div>

            <!-- Summary Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 animate-fade-up-1">

                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">
                        Total Requests
                    </p>
                    <p class="text-2xl font-bold text-green-600" id="totalRequests">
                        0
                    </p>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">
                        Total Requested
                    </p>
                    <p class="text-2xl font-bold text-green-600" id="totalRequested">
                        ₱0
                    </p>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">
                        Total Released
                    </p>
                    <p class="text-2xl font-bold text-blue-600" id="totalReleased">
                        ₱0
                    </p>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">
                        Programs Covered
                    </p>
                    <p class="text-2xl font-bold text-green-600" id="totalPrograms">
                        <?= $totalPrograms ?>
                    </p>
                </div>

            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-end gap-3 animate-fade-up-2 bg-white rounded-2xl border border-slate-200 p-4">

                <div>
                    <label class="text-[10px] uppercase tracking-wider text-slate-400 block">
                        Program
                    </label>

                    <select
                        id="filterProgram"
                        class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                        onchange="applyFilters()"
                    >
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
                    <label class="text-[10px] uppercase tracking-wider text-slate-400 block">
                        Status
                    </label>

                    <select
                        id="filterStatus"
                        class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                        onchange="applyFilters()"
                    >
                        <option value="all">All Status</option>
                        <option value="Approved">Approved</option>
                        <option value="Released">Released</option>
                    </select>
                </div>

                <div>
                    <label class="text-[10px] uppercase tracking-wider text-slate-400 block">
                        From
                    </label>

                    <input
                        type="date"
                        id="filterFrom"
                        class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                        onchange="applyFilters()"
                    />
                </div>

                <div>
                    <label class="text-[10px] uppercase tracking-wider text-slate-400 block">
                        To
                    </label>

                    <input
                        type="date"
                        id="filterTo"
                        class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                        onchange="applyFilters()"
                    />
                </div>

                <button
                    type="button"
                    onclick="clearFilters()"
                    class="text-[12px] font-medium text-slate-500 border border-slate-200 rounded-lg px-3 py-1.5 hover:bg-slate-50 transition-colors"
                >
                    <i class="fas fa-rotate-left mr-1"></i>
                    Clear
                </button>

                <div class="flex-1"></div>

                <span class="text-[11px] text-slate-400" id="rowCount">
                    Showing 0 requests
                </span>

            </div>

            <!-- Fund Requests Table -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-up-3">

                <div class="overflow-x-auto">

                    <table class="w-full text-[12px]" id="reportTable">

                        <thead>

                            <tr class="bg-slate-50 border-b border-slate-100">

                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Program
                                </th>

                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Title / Beneficiary
                                </th>

                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Duration
                                </th>

                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Venue
                                </th>

                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Participants
                                </th>

                                <th
                                    class="sortable text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                                    data-sort="amount"
                                    onclick="sortTable('amount')"
                                >
                                    Budget
                                    <span class="sort-icon">
                                        <i class="fas fa-sort"></i>
                                    </span>
                                </th>

                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Source
                                </th>

                                <th
                                    class="sortable text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                                    data-sort="date"
                                    onclick="sortTable('date')"
                                >
                                    Date Submitted
                                    <span class="sort-icon">
                                        <i class="fas fa-sort"></i>
                                    </span>
                                </th>

                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Status
                                </th>

                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Date Released
                                </th>

                            </tr>

                        </thead>

                        <tbody
                            class="divide-y divide-slate-100"
                            id="tableBody"
                        ></tbody>

                    </table>

                </div>

                <!-- Pagination -->
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-t border-slate-100">

                    <span
                        class="text-[11px] text-slate-400"
                        id="paginationInfo"
                    >
                        Showing 0 of 0
                    </span>

                    <div class="flex items-center gap-1">

                        <button
                            id="prevPage"
                            type="button"
                            onclick="changePage(-1)"
                            class="text-[11px] text-slate-500 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            Previous
                        </button>

                        <span
                            id="pageNumbers"
                            class="flex items-center gap-1"
                        ></span>

                        <button
                            id="nextPage"
                            type="button"
                            onclick="changePage(1)"
                            class="text-[11px] text-slate-500 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            Next
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

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-6 right-6 bg-green-700 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-green-300"></i>
        <span id="toastMsg">Action completed!</span>
    </div>

    <script>
        // ── Consolidated server-side data ──
        const allData = <?= json_encode(
            $allRequests,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
        ) ?>;

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

        let currentSort = {
            key: 'date',
            dir: 'desc'
        };

        let filteredData = [...allData];
        let currentPage = 1;

        const rowsPerPage = 10;

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getBadgeClass(program) {
            return badgeClasses[program] || 'bg-slate-100 text-slate-700';
        }

        function getStatusClass(status) {
            if (status === 'Released') {
                return 'bg-blue-100 text-blue-700';
            }

            if (status === 'Approved') {
                return 'bg-emerald-100 text-emerald-700';
            }

            return 'bg-slate-100 text-slate-600';
        }

        function formatCurrency(amount) {
            return '₱' + Number(amount || 0).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatDate(date) {
            if (!date) return '—';

            const d = new Date(date + 'T00:00:00');

            if (Number.isNaN(d.getTime())) {
                return date;
            }

            return d.toLocaleDateString('en-PH', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        function renderTable(data) {
            const tbody = document.getElementById('tableBody');

            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="px-5 py-10 text-center text-slate-400">
                            <i class="fas fa-folder-open text-2xl mb-2 block"></i>
                            No fund requests match the selected filters.
                        </td>
                    </tr>
                `;

                updateSummary(data);
                updatePagination(0);

                return;
            }

            const totalPages = Math.ceil(data.length / rowsPerPage);

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            const startIndex = (currentPage - 1) * rowsPerPage;
            const pageData = data.slice(
                startIndex,
                startIndex + rowsPerPage
            );

            pageData.forEach(row => {
                const tr = document.createElement('tr');

                tr.className = 'table-row';

                tr.innerHTML = `
                    <td class="px-5 py-3">
                        <span class="${getBadgeClass(row.program)} px-2 py-0.5 rounded text-[10px] font-semibold">
                            ${escapeHtml(row.program)}
                        </span>
                    </td>

                    <td class="px-5 py-3">
                        <div class="font-medium text-green-700">
                            ${escapeHtml(row.title)}
                        </div>
                        <div class="text-[9px] text-slate-400 mt-0.5">
                            ${escapeHtml(row.type)} • ${escapeHtml(row.id)}
                        </div>
                    </td>

                    <td class="px-5 py-3 text-slate-600">
                        ${escapeHtml(row.duration)}
                    </td>

                    <td class="px-5 py-3 text-slate-600">
                        ${escapeHtml(row.venue)}
                    </td>

                    <td class="px-5 py-3 text-slate-600">
                        ${Number.parseInt(row.participants, 10) || 0}
                    </td>

                    <td class="px-5 py-3 font-semibold text-slate-700">
                        ${formatCurrency(row.budget)}
                    </td>

                    <td class="px-5 py-3">
                        <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px] font-semibold">
                            ${escapeHtml(row.source)}
                        </span>
                    </td>

                    <td class="px-5 py-3 text-slate-400">
                        ${formatDate(row.date)}
                    </td>

                    <td class="px-5 py-3">
                        <span class="${getStatusClass(row.status)} px-2 py-0.5 rounded-full text-[10px] font-semibold">
                            ${escapeHtml(row.status)}
                        </span>
                    </td>

                    <td class="px-5 py-3 text-slate-400">
                        ${formatDate(row.dateReleased)}
                    </td>
                `;

                tbody.appendChild(tr);
            });

            updateSummary(data);

            const endIndex = Math.min(
                startIndex + rowsPerPage,
                data.length
            );

            document.getElementById('paginationInfo').textContent =
                `Showing ${startIndex + 1}–${endIndex} of ${data.length}`;

            updatePagination(totalPages);
        }

        function updateSummary(data) {
            const totalRequested = data.reduce(
                (sum, row) => sum + Number(row.budget || 0),
                0
            );

            const totalReleased = data.reduce(
                (sum, row) =>
                    sum + (
                        row.status === 'Released'
                            ? Number(row.budget || 0)
                            : 0
                    ),
                0
            );

            const programs = new Set(
                data.map(row => row.program)
            );

            document.getElementById('totalRequests').textContent =
                data.length;

            document.getElementById('totalRequested').textContent =
                formatCurrency(totalRequested);

            document.getElementById('totalReleased').textContent =
                formatCurrency(totalReleased);

            document.getElementById('totalPrograms').textContent =
                programs.size;

            document.getElementById('rowCount').textContent =
                `Showing ${data.length} request${data.length === 1 ? '' : 's'}`;
        }

        function applyFilters(resetPage = true) {
            const programFilter =
                document.getElementById('filterProgram').value;

            const statusFilter =
                document.getElementById('filterStatus').value;

            const fromDate =
                document.getElementById('filterFrom').value;

            const toDate =
                document.getElementById('filterTo').value;

            if (resetPage) {
                currentPage = 1;
            }

            filteredData = allData.filter(row => {

                if (
                    programFilter !== 'all' &&
                    row.program !== programFilter
                ) {
                    return false;
                }

                if (
                    statusFilter !== 'all' &&
                    row.status !== statusFilter
                ) {
                    return false;
                }

                if (
                    fromDate &&
                    row.date < fromDate
                ) {
                    return false;
                }

                if (
                    toDate &&
                    row.date > toDate
                ) {
                    return false;
                }

                return true;
            });

            sortData(false);
        }

        function sortData(resetPage = true) {
            const key = currentSort.key;
            const dir = currentSort.dir;

            if (resetPage) {
                currentPage = 1;
            }

            filteredData.sort((a, b) => {

                let valA = a[key];
                let valB = b[key];

                if (key === 'amount') {
                    valA = Number(valA || 0);
                    valB = Number(valB || 0);
                } else if (key === 'date') {
                    valA = new Date(valA || '1900-01-01');
                    valB = new Date(valB || '1900-01-01');
                } else {
                    valA = String(valA ?? '').toLowerCase();
                    valB = String(valB ?? '').toLowerCase();
                }

                if (valA < valB) {
                    return dir === 'asc' ? -1 : 1;
                }

                if (valA > valB) {
                    return dir === 'asc' ? 1 : -1;
                }

                return 0;
            });

            renderTable(filteredData);
            updateSortIcons();
        }

        function sortTable(key) {
            if (currentSort.key === key) {
                currentSort.dir =
                    currentSort.dir === 'asc'
                        ? 'desc'
                        : 'asc';
            } else {
                currentSort.key = key;
                currentSort.dir = 'asc';
            }

            sortData(true);
        }

        function updateSortIcons() {
            document
                .querySelectorAll('th.sortable')
                .forEach(th => {

                    th.classList.remove(
                        'asc',
                        'desc'
                    );

                    const icon =
                        th.querySelector('.sort-icon i');

                    if (
                        th.dataset.sort ===
                        currentSort.key
                    ) {
                        th.classList.add(
                            currentSort.dir
                        );

                        icon.className =
                            currentSort.dir === 'asc'
                                ? 'fas fa-sort-up'
                                : 'fas fa-sort-down';
                    } else {
                        icon.className =
                            'fas fa-sort';
                    }
                });
        }

        function updatePagination(totalPages) {
            const prev =
                document.getElementById('prevPage');

            const next =
                document.getElementById('nextPage');

            const pageNumbers =
                document.getElementById('pageNumbers');

            prev.disabled =
                currentPage <= 1;

            next.disabled =
                currentPage >= totalPages ||
                totalPages === 0;

            pageNumbers.innerHTML = '';

            if (totalPages <= 1) {
                return;
            }

            const maxButtons = 5;

            let startPage =
                Math.max(
                    1,
                    currentPage -
                    Math.floor(maxButtons / 2)
                );

            let endPage =
                Math.min(
                    totalPages,
                    startPage + maxButtons - 1
                );

            if (
                endPage - startPage + 1 <
                maxButtons
            ) {
                startPage =
                    Math.max(
                        1,
                        endPage - maxButtons + 1
                    );
            }

            for (
                let page = startPage;
                page <= endPage;
                page++
            ) {
                const button =
                    document.createElement('button');

                button.type = 'button';

                button.textContent = page;

                button.className =
                    page === currentPage
                        ? 'text-[11px] font-medium text-white bg-green-600 rounded-lg px-3 py-1'
                        : 'text-[11px] text-slate-600 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors';

                button.onclick = () => {
                    currentPage = page;
                    renderTable(filteredData);
                };

                pageNumbers.appendChild(button);
            }
        }

        function changePage(direction) {
            const totalPages =
                Math.ceil(
                    filteredData.length /
                    rowsPerPage
                );

            const nextPage =
                currentPage + direction;

            if (
                nextPage < 1 ||
                nextPage > totalPages
            ) {
                return;
            }

            currentPage = nextPage;

            renderTable(filteredData);
        }

        function clearFilters() {
            document.getElementById('filterProgram').value = 'all';
            document.getElementById('filterStatus').value = 'all';
            document.getElementById('filterFrom').value = '';
            document.getElementById('filterTo').value = '';

            applyFilters(true);
        }

        // ── CSV Export ──
        // Exports ALL currently filtered records, not just the current page.
        function exportCSV() {
            const data = filteredData;

            if (data.length === 0) {
                showToast('No data to export.', 'error');
                return;
            }

            const programFilter =
                document.getElementById('filterProgram');

            const statusFilter =
                document.getElementById('filterStatus');

            const fromDate =
                document.getElementById('filterFrom').value;

            const toDate =
                document.getElementById('filterTo').value;

            const programLabel =
                programFilter.options[
                    programFilter.selectedIndex
                ].text;

            const statusLabel =
                statusFilter.options[
                    statusFilter.selectedIndex
                ].text;

            const csvEscape = value =>
                `"${String(value ?? '')
                    .replace(/"/g, '""')}"`;

            let csv = '';

            csv +=
                'Municipal Social Welfare and Development Office\n';

            csv +=
                'San Enrique, Negros Occidental\n';

            csv +=
                'Program Fund Request Report\n\n';

            csv +=
                'Program Filter: ' +
                csvEscape(programLabel) +
                '\n';

            csv +=
                'Status Filter: ' +
                csvEscape(statusLabel) +
                '\n';

            csv +=
                'Date From: ' +
                csvEscape(fromDate || 'All') +
                '\n';

            csv +=
                'Date To: ' +
                csvEscape(toDate || 'All') +
                '\n\n';

            csv +=
                'Program,Request Type,Request ID,Title / Beneficiary,Duration,Venue,Participants,Budget,Source of Fund,Date Submitted,Status,Date Released\n';

            data.forEach(row => {

                csv += [
                    row.program,
                    row.type,
                    row.id,
                    row.title,
                    row.duration,
                    row.venue,
                    row.participants,
                    Number(row.budget || 0).toFixed(2),
                    row.source,
                    row.date,
                    row.status,
                    row.dateReleased || ''
                ]
                    .map(csvEscape)
                    .join(',') + '\n';
            });

            csv += '\n';

            csv +=
                'Total Requests,' +
                data.length +
                '\n';

            csv +=
                'Total Requested,' +
                data.reduce(
                    (sum, row) =>
                        sum + Number(row.budget || 0),
                    0
                ).toFixed(2) +
                '\n';

            csv +=
                'Total Released,' +
                data.reduce(
                    (sum, row) =>
                        sum + (
                            row.status === 'Released'
                                ? Number(row.budget || 0)
                                : 0
                        ),
                    0
                ).toFixed(2) +
                '\n';

            csv +=
                '\nGenerated on: ' +
                new Date().toLocaleString(
                    'en-PH',
                    {
                        timeZone: 'Asia/Manila'
                    }
                ) +
                '\n';

            const blob =
                new Blob(
                    [csv],
                    {
                        type:
                            'text/csv;charset=utf-8;'
                    }
                );

            const url =
                URL.createObjectURL(blob);

            const a =
                document.createElement('a');

            a.href = url;

            a.download =
                'Program_Fund_Request_Report_' +
                new Date()
                    .toISOString()
                    .slice(0, 10) +
                '.csv';

            a.click();

            URL.revokeObjectURL(url);

            showToast(
                'CSV exported successfully!'
            );
        }

        function showToast(
            msg,
            type = 'success'
        ) {
            const t =
                document.getElementById('toast');

            document.getElementById(
                'toastMsg'
            ).textContent = msg;

            t.querySelector('i').className =
                type === 'error'
                    ? 'fas fa-exclamation-circle text-red-300'
                    : 'fas fa-check-circle text-green-300';

            t.classList.remove(
                'opacity-0',
                'translate-y-4',
                'pointer-events-none'
            );

            t.classList.add(
                'opacity-100',
                'translate-y-0'
            );

            setTimeout(() => {

                t.classList.add(
                    'opacity-0',
                    'translate-y-4'
                );

                t.classList.remove(
                    'opacity-100',
                    'translate-y-0'
                );

            }, 3000);
        }

        // ── Initialise ──
        applyFilters(true);
    </script>

</body>

</html>