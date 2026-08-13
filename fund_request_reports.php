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
        .export-dropdown {
    position: relative;
    display: inline-block;
    z-index: 9999;
}

.export-dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background: #fff;
    min-width: 220px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-radius: 0.5rem;
    border: 1px solid #D4E8DC;
    z-index: 1000;
    overflow: visible;
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

                <div class="export-dropdown" id="exportDropdownContainer">

    <button
        type="button"
        class="btn-action text-[12px] font-semibold text-white bg-green-600 rounded-lg px-3 py-1.5 hover:bg-green-700"
        id="exportDropdownBtn"
    >
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
        const PREPARED_BY_NAME = 'MA. TERESA C. PONCLARA, RSW';
const PREPARED_BY_TITLE = 'MSWDO';

function getDateOnly() {
    return new Date().toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function getTimeOnly() {
    return new Date().toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

function getFooterTimestamp() {
    return `Generated on ${getDateOnly()} at ${getTimeOnly()}`;
}

function getExportData() {

    return filteredData.map(row => ({
        'Program': row.program,
        'Request Type': row.type,
        'Request ID': row.id,
        'Title / Beneficiary': row.title,
        'Duration': row.duration,
        'Venue': row.venue,
        'Participants': row.participants,
        'Budget': row.budget,
        'Source of Fund': row.source,
        'Date Submitted': row.date,
        'Status': row.status,
        'Date Released': row.dateReleased || ''
    }));

}

async function exportToXlsx() {

    try {

        if (!window.ExcelJS) {
            throw new Error('ExcelJS library not loaded.');
        }

        const wb = new ExcelJS.Workbook();

        wb.creator = 'MSWDO San Enrique Information System';
        wb.modified = new Date();

        const cols = [
            { header: 'PROGRAM', key: 'Program', width: 18 },
            { header: 'REQUEST TYPE', key: 'Request Type', width: 20 },
            { header: 'REQUEST ID', key: 'Request ID', width: 12 },
            { header: 'TITLE / BENEFICIARY', key: 'Title / Beneficiary', width: 40 },
            { header: 'DURATION', key: 'Duration', width: 11 },
            { header: 'VENUE', key: 'Venue', width: 32 },
            { header: 'PARTICIPANTS', key: 'Participants', width: 13 },
            { header: 'BUDGET', key: 'Budget', width: 18 },
            { header: 'SOURCE OF FUND', key: 'Source of Fund', width: 22 },
            { header: 'DATE SUBMITTED', key: 'Date Submitted', width: 15 },
            { header: 'STATUS', key: 'Status', width: 13 },
            { header: 'DATE RELEASED', key: 'Date Released', width: 15 }
        ];

        const BLACK = 'FF000000';

        const BORDER = {
            style: 'thin',
            color: { argb: BLACK }
        };

        const THIN_BORDERS = {
            top: BORDER,
            left: BORDER,
            bottom: BORDER,
            right: BORDER
        };

        const ws = wb.addWorksheet('Program Report');

        ws.pageSetup = {
            paperSize: ws.PAPERSIZE_LEGAL,
            orientation: 'landscape',
            fitToPage: true,
            fitToWidth: 1,
            fitToHeight: 0,
            horizontalCentered: true,
            margins: {
                left: 0.2,
                right: 0.2,
                top: 0.3,
                bottom: 0.3,
                header: 0.1,
                footer: 0.1
            }
        };

        const font = {
            name: 'Arial',
            size: 11,
            color: { argb: BLACK }
        };

        const boldFont = {
            name: 'Arial',
            size: 11,
            bold: true,
            color: { argb: BLACK }
        };

        const titleFont = {
            name: 'Arial',
            size: 13,
            bold: true,
            color: { argb: BLACK }
        };

        const mergeTitle = (row, text, useBold = false, size = 11) => {

            ws.mergeCells(row, 1, row, cols.length);

            const cell = ws.getCell(row, 1);

            cell.value = text;

            cell.font = useBold
                ? { ...boldFont, size }
                : { ...font, size };

            cell.alignment = {
                horizontal: 'center',
                vertical: 'middle'
            };
        };

        mergeTitle(1, 'Republic of the Philippines');
        mergeTitle(2, 'Province of Negros Occidental', true);
        mergeTitle(3, 'Municipality of San Enrique', true);
        mergeTitle(4, 'Municipal Social Welfare and Development Office', true);
        mergeTitle(5, 'PROGRAM FUND REQUEST REPORT', true, 13);
        mergeTitle(6, `Calendar Year ${new Date().getFullYear()}`);

        const headerRow = 8;

        ws.getRow(headerRow).height = 28;

        cols.forEach((c, i) => {

            const cell = ws.getCell(headerRow, i + 1);

            cell.value = c.header;

            cell.font = {
                name: 'Arial',
                size: 10,
                bold: true,
                color: { argb: BLACK }
            };

            cell.alignment = {
                horizontal: i === 0 ? 'left' : 'center',
                vertical: 'middle',
                wrapText: true
            };

            cell.border = THIN_BORDERS;

            cell.fill = {
                type: 'pattern',
                pattern: 'solid',
                fgColor: {
                    argb: 'FFEFEFEF'
                }
            };

            ws.getColumn(i + 1).width = c.width;
        });

        const rows = getExportData();

        let r = headerRow + 1;

        let totalParticipants = 0;
        let totalBudget = 0;

        rows.forEach(rowData => {

            const row = ws.getRow(r++);

            row.height = 20;

            cols.forEach((c, i) => {

                const cell = row.getCell(i + 1);

                cell.value = rowData[c.key] ?? '';

                cell.font = {
                    name: 'Arial',
                    size: 11,
                    bold: i === 0,
                    color: { argb: BLACK }
                };

                cell.alignment = {
                    horizontal: i === 0 ? 'left' : 'center',
                    vertical: 'middle',
                    wrapText: true
                };

                cell.border = THIN_BORDERS;

                if (c.key === 'Budget') {
                    cell.numFmt = '₱#,##0.00';
                }
            });

            totalParticipants += Number(rowData.Participants || 0);
            totalBudget += Number(rowData.Budget || 0);
        });

        const totalRow = ws.getRow(r++);

        totalRow.height = 20;

        cols.forEach((c, i) => {

            const cell = totalRow.getCell(i + 1);

            cell.value =
                i === 0
                    ? 'TOTAL'
                    : c.key === 'Participants'
                        ? totalParticipants
                        : c.key === 'Budget'
                            ? totalBudget
                            : '';

            cell.font = {
                name: 'Arial',
                size: 11,
                bold: true,
                color: { argb: BLACK }
            };

            cell.alignment = {
                horizontal: i === 0 ? 'left' : 'center',
                vertical: 'middle'
            };

            cell.border = THIN_BORDERS;

            cell.fill = {
                type: 'pattern',
                pattern: 'solid',
                fgColor: {
                    argb: 'FFEFEFEF'
                }
            };

            if (c.key === 'Budget') {
                cell.numFmt = '₱#,##0.00';
            }
        });

        const sigRow = r + 2;

        ws.getCell(sigRow, 1).value = 'Prepared by:';

        ws.getCell(sigRow, 1).font = font;

        ws.mergeCells(sigRow + 3, 1, sigRow + 3, 3);

        const nameCell = ws.getCell(sigRow + 3, 1);

        nameCell.value = PREPARED_BY_NAME;

        nameCell.font = {
            ...boldFont
        };

        nameCell.border = {
            top: BORDER
        };

        ws.mergeCells(sigRow + 4, 1, sigRow + 4, 3);

        ws.getCell(sigRow + 4, 1).value =
            PREPARED_BY_TITLE;

        ws.getCell(sigRow + 4, 1).font = font;

        ws.mergeCells(
            sigRow + 3,
            cols.length - 2,
            sigRow + 3,
            cols.length
        );

        const footer =
            ws.getCell(sigRow + 3, cols.length - 2);

        footer.value = getFooterTimestamp();

        footer.font = {
            name: 'Arial',
            size: 9,
            italic: true,
            color: { argb: 'FF666666' }
        };

        footer.alignment = {
            horizontal: 'right',
            vertical: 'middle'
        };

        const buffer = await wb.xlsx.writeBuffer();

        const blob = new Blob(
            [buffer],
            {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            }
        );

        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');

        a.href = url;

        a.download =
            `MSWDO_San_Enrique_Program_Report_${new Date().toISOString().slice(0, 10)}.xlsx`;

        document.body.appendChild(a);

        a.click();

        a.remove();

        setTimeout(() => {
            URL.revokeObjectURL(url);
        }, 1000);

    } catch (err) {

        console.error('Excel export failed:', err);

        alert(
            'Excel export failed: ' +
            (err.message || err)
        );

    }

}

//PDF Export
async function exportToPdf() {

    try {

        if (!window.jspdf) {
            throw new Error('jsPDF library not loaded.');
        }

        const { jsPDF } = window.jspdf;

        const doc =
            new jsPDF('l', 'pt', 'legal');

        const pageWidth =
            doc.internal.pageSize.getWidth();

        const pageHeight =
            doc.internal.pageSize.getHeight();

        const margin = 30;

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(11);

        doc.text(
            'Republic of the Philippines',
            pageWidth / 2,
            36,
            { align: 'center' }
        );

        doc.setFont('helvetica', 'bold');

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

        doc.setFontSize(13);

        doc.text(
            'PROGRAM FUND REQUEST REPORT',
            pageWidth / 2,
            120,
            { align: 'center' }
        );

        doc.setFontSize(11);
        doc.setFont('helvetica', 'normal');

        doc.text(
            `Calendar Year ${new Date().getFullYear()}`,
            pageWidth / 2,
            138,
            { align: 'center' }
        );

        doc.line(
            margin,
            148,
            pageWidth - margin,
            148
        );

        const rows = getExportData();

        const cols = [
            { header: 'Program', dataKey: 'Program' },
            { header: 'Request Type', dataKey: 'Request Type' },
            { header: 'Request ID', dataKey: 'Request ID' },
            { header: 'Title / Beneficiary', dataKey: 'Title / Beneficiary' },
            { header: 'Duration', dataKey: 'Duration' },
            { header: 'Venue', dataKey: 'Venue' },
            { header: 'Participants', dataKey: 'Participants' },
            { header: 'Budget', dataKey: 'Budget' },
            { header: 'Source of Fund', dataKey: 'Source of Fund' },
            { header: 'Date Submitted', dataKey: 'Date Submitted' },
            { header: 'Status', dataKey: 'Status' },
            { header: 'Date Released', dataKey: 'Date Released' }
        ];

        const data = rows.map(r => ({

            ...r,

            'Budget':
                `₱${Number(r.Budget || 0).toLocaleString(
                    'en-PH',
                    {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }
                )}`,

            'Date Submitted':
                formatDate(r['Date Submitted']),

            'Date Released':
                formatDate(r['Date Released'])

        }));

        let totalParticipants = 0;
        let totalBudget = 0;

        rows.forEach(r => {

            totalParticipants +=
                Number(r.Participants || 0);

            totalBudget +=
                Number(r.Budget || 0);

        });

        data.push({

            'Program': 'TOTAL',
            'Request Type': '',
            'Request ID': '',
            'Title / Beneficiary': '',
            'Duration': '',
            'Venue': '',
            'Participants': totalParticipants,

            'Budget':
                `₱${totalBudget.toLocaleString(
                    'en-PH',
                    {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }
                )}`,

            'Source of Fund': '',
            'Date Submitted': '',
            'Status': '',
            'Date Released': ''

        });

        const ROWS_PER_PAGE = 15;
const DATA_ROWS_PER_PAGE = ROWS_PER_PAGE - 1;

// Split the data into groups of 14 records.
// The header is added automatically to every page.
for (
    let start = 0;
    start < data.length;
    start += DATA_ROWS_PER_PAGE
) {

    const pageRows = data.slice(
        start,
        start + DATA_ROWS_PER_PAGE
    );

    // Add a new page for every page after the first
    if (start > 0) {
        doc.addPage();
    }

    // Header
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(11);

    doc.text(
        'Republic of the Philippines',
        pageWidth / 2,
        36,
        { align: 'center' }
    );

    doc.setFont('helvetica', 'bold');

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

    doc.setFontSize(13);

    doc.text(
        'PROGRAM FUND REQUEST REPORT',
        pageWidth / 2,
        120,
        { align: 'center' }
    );

    doc.setFontSize(11);
    doc.setFont('helvetica', 'normal');

    doc.text(
        `Calendar Year ${new Date().getFullYear()}`,
        pageWidth / 2,
        138,
        { align: 'center' }
    );

    doc.line(
        margin,
        148,
        pageWidth - margin,
        148
    );

    // TABLE
    doc.autoTable({

        startY: 160,

        head: [
            cols.map(c => c.header)
        ],

        body:
            pageRows.map(r =>
                cols.map(c =>
                    r[c.dataKey] ?? ''
                )
            ),

        theme: 'grid',

        headStyles: {
            fillColor: [240, 240, 240],
            textColor: [0, 0, 0],
            fontSize: 9,
            fontStyle: 'bold',
            valign: 'middle'
        },

        bodyStyles: {
            fontSize: 9,
            cellPadding: 4,
            valign: 'middle'
        },

        columnStyles: {

            0: { cellWidth: 72, halign: 'left' },
            1: { cellWidth: 85, halign: 'center' },
            2: { cellWidth: 52, halign: 'center' },
            3: { cellWidth: 165, halign: 'left' },
            4: { cellWidth: 48, halign: 'center' },
            5: { cellWidth: 105, halign: 'left' },
            6: { cellWidth: 50, halign: 'center' },
            7: { cellWidth: 78, halign: 'right' },
            8: { cellWidth: 90, halign: 'center' },
            9: { cellWidth: 75, halign: 'center' },
            10: { cellWidth: 58, halign: 'center' },
            11: { cellWidth: 70, halign: 'center' }

        },

        margin: {
            left: margin,
            right: margin
        },

        pageBreak: 'avoid'

    });

}

        const finalY =
            doc.lastAutoTable.finalY + 30;

        doc.setFontSize(11);

        doc.setFont('helvetica', 'normal');

        doc.text(
            'Prepared by:',
            margin,
            finalY
        );

        doc.setFont('helvetica', 'bold');

        doc.text(
            PREPARED_BY_NAME,
            margin,
            finalY + 36
        );

        doc.line(
            margin,
            finalY + 26,
            margin + 200,
            finalY + 26
        );

        doc.setFont('helvetica', 'normal');

        doc.text(
            PREPARED_BY_TITLE,
            margin,
            finalY + 48
        );

        doc.setFontSize(9);

        doc.setFont('helvetica', 'italic');

        doc.text(
            getFooterTimestamp(),
            pageWidth - margin,
            pageHeight - 24,
            { align: 'right' }
        );

        const blob = doc.output('blob');

        const url =
            URL.createObjectURL(blob);

        const a =
            document.createElement('a');

        a.href = url;

        a.download =
            `MSWDO_San_Enrique_Program_Report_${new Date().toISOString().slice(0, 10)}.pdf`;

        document.body.appendChild(a);

        a.click();

        a.remove();

        setTimeout(() => {
            URL.revokeObjectURL(url);
        }, 1000);

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

//Word Export
async function exportToDocx() {

    try {

        if (!window.JSZip) {
            throw new Error('JSZip library not loaded.');
        }

        const zip = new JSZip();

        const rows = getExportData();

        const esc = value =>
            String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&apos;');

        const cols = [
            'Program',
            'Request Type',
            'Request ID',
            'Title / Beneficiary',
            'Duration',
            'Venue',
            'Participants',
            'Budget',
            'Source of Fund',
            'Date Submitted',
            'Status',
            'Date Released'
        ];

        const widths = [
            1200,
            1300,
            850,
            3000,
            850,
            2000,
            900,
            1300,
            1500,
            1100,
            900,
            1100
        ];

        let totalParticipants = 0;
        let totalBudget = 0;

        rows.forEach(r => {

            totalParticipants +=
                Number(r.Participants || 0);

            totalBudget +=
                Number(r.Budget || 0);

        });

        const cell =
            (
                text,
                width,
                boldText = false,
                align = 'center'
            ) =>
                `<w:tc>
                    <w:tcPr>
                        <w:tcW w:w="${width}" w:type="dxa"/>
                        <w:tcBorders>
                            <w:top w:val="single" w:sz="4" w:color="000000"/>
                            <w:left w:val="single" w:sz="4" w:color="000000"/>
                            <w:bottom w:val="single" w:sz="4" w:color="000000"/>
                            <w:right w:val="single" w:sz="4" w:color="000000"/>
                        </w:tcBorders>
                    </w:tcPr>
                    <w:p>
                        <w:pPr>
                            <w:jc w:val="${align}"/>
                        </w:pPr>
                        <w:r>
                            <w:rPr>
                                ${boldText ? '<w:b/>' : ''}
                                <w:rFonts w:ascii="Arial" w:hAnsi="Arial"/>
                                <w:sz w:val="22"/>
                            </w:rPr>
                            <w:t>${esc(text)}</w:t>
                        </w:r>
                    </w:p>
                </w:tc>`;

        let table =
            `<w:tbl>
                <w:tblPr>
                    <w:tblW w:w="16000" w:type="dxa"/>
                    <w:tblLayout w:type="fixed"/>
                </w:tblPr>
                <w:tblGrid>
                    ${widths.map(
                        w => `<w:gridCol w:w="${w}"/>`
                    ).join('')}
                </w:tblGrid>`;

        table +=
            `<w:tr>
                ${cols.map(
                    (h, i) =>
                        cell(
                            h,
                            widths[i],
                            true,
                            i === 0 ? 'left' : 'center'
                        )
                ).join('')}
            </w:tr>`;

        rows.forEach(r => {

            const vals = [

                r.Program,
                r['Request Type'],
                r['Request ID'],
                r['Title / Beneficiary'],
                r.Duration,
                r.Venue,
                r.Participants,

                `₱${Number(
                    r.Budget || 0
                ).toLocaleString(
                    'en-PH',
                    {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }
                )}`,

                r['Source of Fund'],
                r['Date Submitted'],
                r.Status,
                r['Date Released'] || ''

            ];

            table +=
                `<w:tr>
                    ${vals.map(
                        (v, i) =>
                            cell(
                                v,
                                widths[i],
                                i === 0,
                                i === 0 ? 'left' : 'center'
                            )
                    ).join('')}
                </w:tr>`;

        });

        const totalVals = [

            'TOTAL',
            '',
            '',
            '',
            '',
            '',
            totalParticipants,

            `₱${totalBudget.toLocaleString(
                'en-PH',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            )}`,

            '',
            '',
            '',
            ''

        ];

        table +=
            `<w:tr>
                ${totalVals.map(
                    (v, i) =>
                        cell(
                            v,
                            widths[i],
                            true,
                            i === 0 ? 'left' : 'center'
                        )
                ).join('')}
            </w:tr>
            </w:tbl>`;

        const content = `

            <w:p>
                <w:pPr>
                    <w:jc w:val="center"/>
                </w:pPr>
                <w:r>
                    <w:rPr>
                        <w:rFonts w:ascii="Arial" w:hAnsi="Arial"/>
                        <w:sz w:val="22"/>
                    </w:rPr>
                    <w:t>Republic of the Philippines</w:t>
                </w:r>
            </w:p>

            <w:p>
                <w:pPr>
                    <w:jc w:val="center"/>
                </w:pPr>
                <w:r>
                    <w:rPr>
                        <w:b/>
                        <w:rFonts w:ascii="Arial" w:hAnsi="Arial"/>
                        <w:sz w:val="22"/>
                    </w:rPr>
                    <w:t>Province of Negros Occidental</w:t>
                </w:r>
            </w:p>

            <w:p>
                <w:pPr>
                    <w:jc w:val="center"/>
                </w:pPr>
                <w:r>
                    <w:rPr>
                        <w:b/>
                        <w:rFonts w:ascii="Arial" w:hAnsi="Arial"/>
                        <w:sz w:val="22"/>
                    </w:rPr>
                    <w:t>Municipality of San Enrique</w:t>
                </w:r>
            </w:p>

            <w:p>
                <w:pPr>
                    <w:jc w:val="center"/>
                </w:pPr>
                <w:r>
                    <w:rPr>
                        <w:b/>
                        <w:rFonts w:ascii="Arial" w:hAnsi="Arial"/>
                        <w:sz w:val="22"/>
                    </w:rPr>
                    <w:t>Municipal Social Welfare and Development Office</w:t>
                </w:r>
            </w:p>

            <w:p>
                <w:pPr>
                    <w:jc w:val="center"/>
                </w:pPr>
                <w:r>
                    <w:rPr>
                        <w:b/>
                        <w:rFonts w:ascii="Arial" w:hAnsi="Arial"/>
                        <w:sz w:val="26"/>
                    </w:rPr>
                    <w:t>PROGRAM FUND REQUEST REPORT</w:t>
                </w:r>
            </w:p>

            <w:p>
                <w:pPr>
                    <w:jc w:val="center"/>
                </w:pPr>
                <w:r>
                    <w:rPr>
                        <w:rFonts w:ascii="Arial" w:hAnsi="Arial"/>
                        <w:sz w:val="20"/>
                    </w:rPr>
                    <w:t>Calendar Year ${new Date().getFullYear()}</w:t>
                </w:r>
            </w:p>

            ${table}

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
                    <w:t>${esc(PREPARED_BY_NAME)}</w:t>
                </w:r>
            </w:p>

            <w:p>
                <w:r>
                    <w:t>${esc(PREPARED_BY_TITLE)}</w:t>
                </w:r>
            </w:p>

            <w:p>
                <w:pPr>
                    <w:jc w:val="right"/>
                </w:pPr>
                <w:r>
                    <w:rPr>
                        <w:i/>
                    </w:rPr>
                    <w:t>${esc(getFooterTimestamp())}</w:t>
                </w:r>
            </w:p>
        `;

        const documentXml =
            `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <w:document
                xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">

                <w:body>

                    ${content}

                    <w:sectPr>
                        <w:pgSz
                            w:w="16840"
                            w:h="12240"
                            w:orient="landscape"/>

                        <w:pgMar
                            w:top="540"
                            w:right="420"
                            w:bottom="540"
                            w:left="420"
                            w:header="240"
                            w:footer="240"
                            w:gutter="0"/>
                    </w:sectPr>

                </w:body>

            </w:document>`;

        zip.file(
            'word/document.xml',
            documentXml
        );

        const rels =
            `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>

            <Relationships
                xmlns="http://schemas.openxmlformats.org/package/2006/relationships">

                <Relationship
                    Id="rId1"
                    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"
                    Target="word/document.xml"/>

            </Relationships>`;

        zip.file(
            '_rels/.rels',
            rels
        );

        const contentTypes =
            `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>

            <Types
                xmlns="http://schemas.openxmlformats.org/package/2006/content-types">

                <Default
                    Extension="xml"
                    ContentType="application/xml"/>

                <Default
                    Extension="rels"
                    ContentType="application/vnd.openxmlformats-package.relationships+xml"/>

                <Override
                    PartName="/word/document.xml"
                    ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>

            </Types>`;

        zip.file(
            '[Content_Types].xml',
            contentTypes
        );

        const out =
            await zip.generateAsync({
                type: 'blob',
                mimeType:
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            });

        const url =
            URL.createObjectURL(out);

        const a =
            document.createElement('a');

        a.href = url;

        a.download =
            `MSWDO_San_Enrique_Program_Report_${new Date().toISOString().slice(0, 10)}.docx`;

        document.body.appendChild(a);

        a.click();

        a.remove();

        setTimeout(() => {
            URL.revokeObjectURL(url);
        }, 1000);

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

//Export Button Event Listener
document.getElementById('exportXlsx')
    .addEventListener('click', async (e) => {

        e.preventDefault();

        document
            .getElementById('exportDropdownContainer')
            .classList.remove('active');

        await exportToXlsx();

    });


document.getElementById('exportPdf')
    .addEventListener('click', async (e) => {

        e.preventDefault();

        document
            .getElementById('exportDropdownContainer')
            .classList.remove('active');

        await exportToPdf();

    });


document.getElementById('exportDocx')
    .addEventListener('click', async (e) => {

        e.preventDefault();

        document
            .getElementById('exportDropdownContainer')
            .classList.remove('active');

        await exportToDocx();

    });


document.getElementById('exportDropdownBtn')
    .addEventListener('click', (e) => {

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