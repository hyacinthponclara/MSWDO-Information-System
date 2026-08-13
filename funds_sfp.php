<?php
require 'auth.php';
requireRole(['Admin', 'Staff']); 
require 'db_connect.php';
require 'budget_helpers.php';

// -- BUDGET SUMMARY CARD (same formula as every other budget page) --
$fundBudget = getProgramBudget($pdo, ['SFP']);

// -- FUND REQUESTS TABLE (live from PROJECT_PROPOSAL) --
$fundRequestsPhp = getFundRequests($pdo, 'SFP');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SFP Fund Requests – MSWDO San Enrique</title>
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
        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <span class="text-green-600 font-semibold">SFP Fund Requests</span>
            </div>
        </header>

        <main class="flex-1 p-6 space-y-5 overflow-y-auto">

            <!-- Page Title -->
            <div class="relative z-50 flex flex-wrap items-center justify-between gap-3 animate-fade-up">
                <div>
                    <h1 class="text-xl font-serif text-green-600">SFP Fund Requests</h1>
                    <p class="text-[13px] text-slate-500 mt-0.5">View, filter, and export all sfp fund requests.</p>
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

            <!-- Budget Summary Card -->
            <div class="grid grid-cols-1 md:grid-cols-1 gap-4 animate-fade-up-1">
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[13px] font-semibold text-green-600"><i
                                class="fas fa-home mr-1.5 text-green-400"></i>SFP Budget</h3>
                        <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">DSWD</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total</p>
                            <p class="text-xl font-bold text-green-600">₱<?= number_format($fundBudget['total'], 0) ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Released</p>
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
            <div
                class="flex flex-wrap items-center gap-3 animate-fade-up-2 bg-white rounded-2xl border border-slate-200 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div>
                        <label class="text-[10px] uppercase tracking-wider text-slate-400 block">Search</label>
                        <div class="relative">
                            <i
                                class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[11px]"></i>
                            <input type="search" id="filterSearch" placeholder="Search fund requests..."
                                autocomplete="off"
                                class="w-56 text-[12px] border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                                oninput="applyFilters()" />
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] uppercase tracking-wider text-slate-400 block">From</label>
                        <input type="date" id="filterFrom"
                            class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                            onchange="applyFilters()" />
                    </div>
                    <div>
                        <label class="text-[10px] uppercase tracking-wider text-slate-400 block">To</label>
                        <input type="date" id="filterTo"
                            class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                            onchange="applyFilters()" />
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
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Fund Request Title</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Duration</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Venue</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Participants</th>
                                <th class="sortable text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                                    data-sort="amount" onclick="sortTable('amount')">
                                    Budget <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                </th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Source of Fund</th>
                                <th class="sortable text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                                    data-sort="date" onclick="sortTable('date')">
                                    Date Submitted <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                </th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Status</th>
                                <th class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Date Released</th>
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
                    <span class="text-[11px] text-slate-400" id="paginationInfo">Showing 0 of 0</span>
                    <div class="flex items-center gap-1" id="paginationControls">
                        <button
                            id="prevPage"
                            type="button"
                            onclick="changePage(-1)"
                            class="text-[11px] text-slate-500 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            Previous
                        </button>

                        <span id="pageNumbers" class="flex items-center gap-1"></span>

                        <button
                            id="nextPage"
                            type="button"
                            onclick="changePage(1)"
                            class="text-[11px] text-slate-500 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            Next
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

    <!-- Toast Notification -->
    <div id="toast"
        class="fixed bottom-6 right-6 bg-green-700 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-green-300"></i>
        <span id="toastMsg">Action completed!</span>
    </div>

    <script>
        // ── Database Data (sfp fund requests) ──
        const fundRequests = <?= json_encode(
            $fundRequestsPhp,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
        ) ?>;

        let currentSort = { key: 'date', dir: 'asc' };
        let filteredData = [...fundRequests];
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

        function statusClass(status) {
            if (status === 'Released') {
                return 'bg-emerald-100 text-emerald-700';
            }

            if (status === 'Approved') {
                return 'bg-amber-100 text-amber-700';
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

            const total = data.length;
            const totalPages = Math.max(1, Math.ceil(total / rowsPerPage));

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            const startIndex = total === 0 ? 0 : (currentPage - 1) * rowsPerPage;
            const endIndex = Math.min(startIndex + rowsPerPage, total);
            const pageData = data.slice(startIndex, endIndex);

            tbody.innerHTML = '';

            if (pageData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="px-5 py-10 text-center text-slate-400">
                            No sfp fund requests found for the selected date range.
                        </td>
                    </tr>
                `;
            } else {
                pageData.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.className = 'table-row';

                    const title = escapeHtml(row.title);
                    const duration = escapeHtml(row.duration);
                    const venue = escapeHtml(row.venue);
                    const participants = escapeHtml(row.participants);
                    const fundSource = escapeHtml(row.fundSource);
                    const date = escapeHtml(row.date);
                    const dateReleased = escapeHtml(row.dateReleased || '—');
                    const status = escapeHtml(statusLabel(row.status));
                    const budget = Number(row.budget || 0);

                    tr.innerHTML = `
                        <td class="px-5 py-3 font-medium text-green-700">${title}</td>
                        <td class="px-5 py-3 text-slate-600">${duration}</td>
                        <td class="px-5 py-3 text-slate-600">${venue}</td>
                        <td class="px-5 py-3 text-slate-600">${participants}</td>
                        <td class="px-5 py-3 font-semibold text-slate-700">₱${budget.toLocaleString('en-PH', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })}</td>
                        <td class="px-5 py-3">
                            <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px] font-semibold">
                                ${fundSource}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-400">${date}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 rounded-full text-[10px] font-semibold ${statusClass(row.status)}">
                                ${status}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-400">${dateReleased}</td>
                        <td class="px-5 py-3">
                            <a
                                href="project_proposal_view.php?id=${encodeURIComponent(row.id)}"
                                class="text-[12px] font-medium text-green-600 bg-green-50 border border-green-200 rounded-lg px-3 py-1.5 hover:bg-green-100 transition-colors inline-flex items-center gap-1.5">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    `;

                    tbody.appendChild(tr);
                });
            }

            const from = total === 0 ? 0 : startIndex + 1;
            const to = endIndex;

            document.getElementById('rowCount').textContent =
                `Showing ${total} fund request${total === 1 ? '' : 's'}`;

            document.getElementById('paginationInfo').textContent =
                total === 0
                    ? 'Showing 0 of 0'
                    : `Showing ${from}–${to} of ${total}`;

            renderPagination(totalPages);
        }

        function renderPagination(totalPages) {
            const pageNumbers = document.getElementById('pageNumbers');
            const prevButton = document.getElementById('prevPage');
            const nextButton = document.getElementById('nextPage');

            pageNumbers.innerHTML = '';

            prevButton.disabled = currentPage <= 1;
            nextButton.disabled = currentPage >= totalPages;

            if (totalPages <= 1) {
                return;
            }

            const maxButtons = 5;

            let startPage = Math.max(
                1,
                currentPage - Math.floor(maxButtons / 2)
            );

            let endPage = Math.min(
                totalPages,
                startPage + maxButtons - 1
            );

            if (endPage - startPage + 1 < maxButtons) {
                startPage = Math.max(
                    1,
                    endPage - maxButtons + 1
                );
            }

            for (let page = startPage; page <= endPage; page++) {
                const button = document.createElement('button');

                button.type = 'button';
                button.textContent = page;
                button.className = page === currentPage
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
            const totalPages = Math.max(
                1,
                Math.ceil(filteredData.length / rowsPerPage)
            );

            const nextPage = currentPage + direction;

            if (nextPage < 1 || nextPage > totalPages) {
                return;
            }

            currentPage = nextPage;
            renderTable(filteredData);
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

            const fromDate =
                document.getElementById('filterFrom').value;

            const toDate =
                document.getElementById('filterTo').value;

            if (fromDate && toDate && fromDate > toDate) {
                filteredData = [];
            } else {
                filteredData = fundRequests.filter(row => {

                    const rowDate =
                        normalizeDateForFilter(row.date);

                    if (fromDate && (!rowDate || rowDate < fromDate)) {
                        return false;
                    }

                    if (toDate && (!rowDate || rowDate > toDate)) {
                        return false;
                    }

                    if (searchTerm) {

                        const searchableText = [
                            row.program,
                            row.type,
                            row.id,
                            row.title,
                            row.duration,
                            row.venue,
                            row.participants,
                            row.budget,
                            row.fundSource,
                            row.source,
                            row.date,
                            row.status,
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
            }

            if (resetPage) {
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

                if (key === 'amount' || key === 'budget') {
                    valA = parseFloat(valA) || 0;
                    valB = parseFloat(valB) || 0;
                } else if (key === 'date') {
                    valA = new Date(valA);
                    valB = new Date(valB);
                } else {
                    valA = String(valA ?? '').toLowerCase();
                    valB = String(valB ?? '').toLowerCase();
                }

                if (valA < valB) return dir === 'asc' ? -1 : 1;
                if (valA > valB) return dir === 'asc' ? 1 : -1;
                return 0;
            });

            renderTable(filteredData);

            document.querySelectorAll('th.sortable').forEach(th => {
                th.classList.remove('asc', 'desc');

                const icon = th.querySelector('.sort-icon i');

                if (th.dataset.sort === key) {
                    th.classList.add(dir);

                    if (icon) {
                        icon.className =
                            dir === 'asc'
                                ? 'fas fa-sort-up'
                                : 'fas fa-sort-down';
                    }
                } else if (icon) {
                    icon.className = 'fas fa-sort';
                }
            });
        }

        function sortTable(key) {
            if (currentSort.key === key) {
                currentSort.dir =
                    currentSort.dir === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.key = key;
                currentSort.dir = 'asc';
            }

            currentPage = 1;
            sortData();
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

        // Format exported dates consistently for PDF/Word/Excel.
        // The table can contain either YYYY-MM-DD values or browser-parseable dates.
        function formatDate(value) {
            if (!value) return '';

            const raw = String(value).trim();

            if (!raw) return '';

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

        function getExportData() {
            // Export the complete filtered dataset, not just the current page.
            return filteredData.map(row => ({
                'Request ID': row.id,
                'Title / Beneficiary': row.title,
                'Duration': row.duration,
                'Venue': row.venue,
                'Participants': row.participants,
                'Budget': row.budget,
                'Source of Fund': row.fundSource ?? row.source ?? '',
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
                    { header: 'REQUEST ID', key: 'Request ID', width: 16 },
                    { header: 'TITLE / BENEFICIARY', key: 'Title / Beneficiary', width: 43 },
                    { header: 'DURATION', key: 'Duration', width: 14 },
                    { header: 'VENUE', key: 'Venue', width: 36 },
                    { header: 'PARTICIPANTS', key: 'Participants', width: 17 },
                    { header: 'BUDGET', key: 'Budget', width: 22 },
                    { header: 'SOURCE OF FUND', key: 'Source of Fund', width: 26 },
                    { header: 'DATE SUBMITTED', key: 'Date Submitted', width: 19 },
                    { header: 'STATUS', key: 'Status', width: 17 },
                    { header: 'DATE RELEASED', key: 'Date Released', width: 19 }
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
                    1100,
                    3250,
                    1100,
                    2250,
                    1150,
                    1550,
                    1750,
                    1350,
                    1150,
                    1350
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

                const xmlDeclaration = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>';

                const documentXml =
                    `${xmlDeclaration}
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
                    `${xmlDeclaration}

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
                    `${xmlDeclaration}

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
                e.preventDefault();
                e.stopPropagation();

                const dropdown =
                    document.getElementById('exportDropdownContainer');

                dropdown.classList.toggle('active');
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