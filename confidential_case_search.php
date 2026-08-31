<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';

// Latest incident dates first at the database level.
$stmt = $pdo->query("
    SELECT wc.protection_id, wc.wc_case_number, wc.wc_case_type, wc.wc_incident_date,
           wc.wc_status, wc.wc_assigned_worker, wc.wc_narrative,
           c.client_id, c.cl_firstname, c.cl_lastname,
           c.cl_is_soloparent, c.cl_is_indigent,
           b.barangay_name
    FROM woman_and_children wc
    LEFT JOIN client c ON c.client_id = wc.client_id
    LEFT JOIN barangay b ON b.barangay_id = c.brgy_id
    ORDER BY wc.wc_incident_date DESC, wc.protection_id DESC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$allCasesPhp = array_map(function ($r) {
    $sectors = [];
    if (!empty($r['cl_is_soloparent'])) $sectors[] = 'solo';
    if (!empty($r['cl_is_indigent']))   $sectors[] = 'indigent';

    return [
        'case_id'         => $r['wc_case_number'] ?? ('WC-' . $r['protection_id']),
        'client'          => trim(($r['cl_firstname'] ?? '') . ' ' . ($r['cl_lastname'] ?? '')) ?: 'Unknown Client',
        'client_id'       => (int)($r['client_id'] ?? 0),
        'case_type'       => $r['wc_case_type'],
        'barangay'        => $r['barangay_name'] ?? '—',
        'incident_date'   => $r['wc_incident_date'],
        'status'          => $r['wc_status'],
        'assigned_worker' => $r['wc_assigned_worker'] ?? '—',
        'narrative'       => $r['wc_narrative'],
        'critical'        => $r['wc_status'] === 'Active',
        'sectors'         => $sectors,
    ];
}, $rows);

$flashMsg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Confidential Cases – MSWDO San Enrique</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['DM Sans', 'sans-serif'],
                        serif: ['DM Serif Display', 'serif']
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
                        gold: { DEFAULT: '#C49A2A', 400: '#C49A2A' },
                        slate2: '#F4F7FC'
                    },
                    keyframes: {
                        fadeUp: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        }
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.35s ease both',
                        'fade-up-1': 'fadeUp 0.35s 0.05s ease both',
                        'fade-up-2': 'fadeUp 0.35s 0.10s ease both',
                        'fade-up-3': 'fadeUp 0.35s 0.15s ease both',
                        'fade-up-4': 'fadeUp 0.35s 0.20s ease both',
                        'fade-in': 'fadeIn 0.2s ease both'
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'DM Sans', sans-serif; }

        .sidebar-item { transition: all .15s; }
        .sidebar-item:hover {
            background: rgba(255,255,255,.07);
            color: rgba(255,255,255,.95);
        }
        .sidebar-item.active {
            background: rgba(26,92,58,.25);
            border-left-color: #C49A2A;
            color: #fff;
        }

        #mainSearch:focus {
            box-shadow: 0 0 0 4px rgba(26,92,58,.15);
        }

        .case-row {
            transition: all .15s ease;
            cursor: pointer;
        }
        .case-row:hover { background: #EEF6F0; }

        .filter-chip {
            transition: all .15s ease;
            cursor: pointer;
            user-select: none;
        }
        .filter-chip:hover {
            border-color: #1A5C3A;
            color: #1A5C3A;
        }
        .filter-chip.active {
            background: #1A5C3A;
            color: #fff;
            border-color: #1A5C3A;
        }

        .sort-btn { transition: color .12s; }
        .sort-btn:hover { color: #1A5C3A; }
        .sort-btn.asc::after { content: ' ↑'; font-size: 10px; }
        .sort-btn.desc::after { content: ' ↓'; font-size: 10px; }

        .av-0 { background: #1A5C3A; }
        .av-1 { background: #2F6B4F; }
        .av-2 { background: #4A7A5A; }
        .av-3 { background: #6DB88C; }
        .av-4 { background: #103722; }
        .av-5 { background: #154A2E; }

        .badge-vawc { background: #FEE2E2; color: #DC2626; }
        .badge-cicl { background: #FEF3C7; color: #D97706; }
        .badge-childabuse { background: #F3E8FF; color: #6D28D9; }
        .badge-car { background: #DBEAFE; color: #1D4ED8; }

        .status-active { background: #FEE2E2; color: #DC2626; }
        .status-monitoring { background: #FEF3C7; color: #D97706; }
        .status-resolved { background: #D1FAE5; color: #059669; }
        .status-closed { background: #E2E8F0; color: #475569; }
        .status-referred { background: #DBEAFE; color: #1D4ED8; }

        .empty-state { animation: fadeUp .4s ease both; }
        #quickCard { transition: all .2s ease; }

        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb {
            background: rgba(26,92,58,.2);
            border-radius: 2px;
        }
    </style>
</head>

<body class="bg-slate2 min-h-screen flex">

    <?php require 'sidebar.php'; ?>

    <div class="ml-64 flex-1 flex flex-col min-h-screen">

        <header class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20 animate-fade-up">
            <div class="flex items-center gap-2 text-[13px]">
                <span class="text-green-600 font-semibold">Confidential Cases</span>
            </div>
            <a href="confidential.php"
               class="text-[12px] font-semibold text-white bg-green-600 rounded-lg px-4 py-1.5 hover:bg-green-500 transition-all flex items-center gap-1.5">
                <i class="fas fa-plus-circle"></i> New Case
            </a>
        </header>

        <main class="flex-1 p-6 flex flex-col gap-5">

            <div class="animate-fade-up">
                <h1 class="text-xl font-serif text-green-600">Confidential Case Registry</h1>
                <p class="text-[13px] text-slate-500 mt-0.5">
                    Search, filter, and manage all confidential cases (VAWC, CICL, Child Abuse, CAR).
                </p>
            </div>

            <div class="animate-fade-up-1 grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white rounded-2xl border border-slate-200 px-4 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center">
                        <i class="fas fa-folder-open text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-[20px] font-bold text-green-600 leading-none" id="totalCases">0</p>
                        <p class="text-[10px] text-slate-400 mt-0.5 uppercase tracking-wide">Total Cases</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 px-4 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                    </div>
                    <div>
                        <p class="text-[20px] font-bold text-red-500 leading-none" id="pendingCases">0</p>
                        <p class="text-[10px] text-slate-400 mt-0.5 uppercase tracking-wide">Active / Monitoring</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 px-4 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center">
                        <i class="fas fa-clock text-amber-500"></i>
                    </div>
                    <div>
                        <p class="text-[20px] font-bold text-amber-500 leading-none" id="criticalCases">0</p>
                        <p class="text-[10px] text-slate-400 mt-0.5 uppercase tracking-wide">Critical / Urgent</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 px-4 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                        <i class="fas fa-check-circle text-emerald-500"></i>
                    </div>
                    <div>
                        <p class="text-[20px] font-bold text-emerald-500 leading-none" id="resolvedCases">0</p>
                        <p class="text-[10px] text-slate-400 mt-0.5 uppercase tracking-wide">Resolved / Closed</p>
                    </div>
                </div>
            </div>

            <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 p-4">
                <div class="relative mb-4">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                        <i class="fas fa-search"></i>
                    </span>
                    <input id="mainSearch" type="text"
                           placeholder="Search by client name, case ID, barangay..."
                           class="w-full pl-11 pr-4 py-3 rounded-xl border-2 border-slate-200 bg-slate-50 text-[13px] text-slate-800 outline-none transition-all focus:border-green-400 focus:bg-white"
                           oninput="doSearch(this.value)">
                    <button id="clearSearch" onclick="clearSearch()"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-500 text-lg hidden">✕</button>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mr-1">Filter:</span>

                    <button onclick="toggleChip(this,'VAWC')" class="filter-chip flex items-center gap-1.5 border border-slate-200 rounded-full px-3 py-1 text-[11px] font-medium text-slate-600">
                        <i class="fas fa-shield-alt"></i> VAWC
                    </button>
                    <button onclick="toggleChip(this,'CICL')" class="filter-chip flex items-center gap-1.5 border border-slate-200 rounded-full px-3 py-1 text-[11px] font-medium text-slate-600">
                        <i class="fas fa-gavel"></i> CICL
                    </button>
                    <button onclick="toggleChip(this,'Child Abuse')" class="filter-chip flex items-center gap-1.5 border border-slate-200 rounded-full px-3 py-1 text-[11px] font-medium text-slate-600">
                        <i class="fas fa-child"></i> Child Abuse
                    </button>
                    <button onclick="toggleChip(this,'CAR')" class="filter-chip flex items-center gap-1.5 border border-slate-200 rounded-full px-3 py-1 text-[11px] font-medium text-slate-600">
                        <i class="fas fa-user-shield"></i> CAR
                    </button>

                    <div class="w-px h-5 bg-slate-200 mx-1"></div>

                    <select id="statusFilter" onchange="applyFilters()"
                        class="text-[11px] border border-slate-200 rounded-full px-3 py-1 bg-white text-slate-600 outline-none focus:border-green-400">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Monitoring">Monitoring</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Closed">Closed</option>
                        <option value="Referred">Referred</option>
                    </select>

                    <button id="clearFiltersBtn" onclick="clearAllFilters()"
                        class="hidden ml-auto text-[11px] font-medium text-red-500 hover:text-red-700">
                        ✕ Clear all filters
                    </button>

                    <span id="resultCount" class="ml-auto text-[11px] text-slate-400 font-medium">
                        Showing <strong class="text-slate-600" id="countNum">0</strong> cases
                    </span>
                </div>
            </div>

            <div class="animate-fade-up-3 flex gap-4 items-start">
                <div class="flex-1 bg-white rounded-2xl border border-slate-200 overflow-hidden min-w-0">
                    <div id="tableView" class="overflow-x-auto">
                        <table class="w-full text-[12px]">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50/40">
                                    <th class="px-4 py-3 text-left">
                                        <button class="sort-btn text-[10px] font-bold uppercase tracking-wider text-slate-400"
                                            onclick="sortBy('caseId',this)">Case ID</button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button class="sort-btn text-[10px] font-bold uppercase tracking-wider text-slate-400"
                                            onclick="sortBy('client',this)">Client</button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Case Type</span>
                                    </th>
                                    <th class="px-4 py-3 text-left hidden md:table-cell">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Barangay</span>
                                    </th>
                                    <th class="px-4 py-3 text-left hidden lg:table-cell">
                                        <button id="incidentDateSortBtn"
                                            class="sort-btn text-[10px] font-bold uppercase tracking-wider text-slate-400"
                                            onclick="sortBy('incidentDate',this)">Incident Date</button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Status</span>
                                    </th>
                                    <th class="px-4 py-3 text-left hidden sm:table-cell">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Assigned</span>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Action</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="caseTableBody"></tbody>
                        </table>

                        <div id="emptyState" class="hidden empty-state flex flex-col items-center justify-center py-16 text-center">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl mb-4">
                                <i class="fas fa-lock text-slate-300"></i>
                            </div>
                            <p class="text-[14px] font-semibold text-slate-600">No confidential cases found</p>
                            <p class="text-[12px] text-slate-400 mt-1">Try adjusting your search or filters</p>
                            <button onclick="clearAllFilters()"
                                class="mt-4 text-[12px] font-medium text-green-600 border border-green-200 rounded-lg px-4 py-2 hover:bg-green-50 transition-all">
                                Clear filters
                            </button>
                        </div>
                    </div>
                </div>

                <div id="quickCard" class="w-64 flex-shrink-0 hidden">
                    <div class="bg-white rounded-2xl border border-green-200 overflow-hidden sticky top-20">
                        <div class="h-1 bg-green-600"></div>
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-green-600">Case Details</p>
                                <button onclick="closeQuick()" class="text-slate-300 hover:text-slate-500 text-lg">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <div class="flex items-center gap-3 mb-3">
                                <div id="qcAvatar"></div>
                                <div>
                                    <p id="qcName" class="text-[13px] font-semibold text-green-700"></p>
                                    <p id="qcCaseId" class="text-[10px] font-mono text-slate-400"></p>
                                </div>
                            </div>

                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between text-[11px]"><span class="text-slate-400">Case Type</span><span id="qcType" class="font-medium"></span></div>
                                <div class="flex justify-between text-[11px]"><span class="text-slate-400">Status</span><span id="qcStatus"></span></div>
                                <div class="flex justify-between text-[11px]"><span class="text-slate-400">Barangay</span><span id="qcBrgy" class="font-medium text-slate-600"></span></div>
                                <div class="flex justify-between text-[11px]"><span class="text-slate-400">Incident Date</span><span id="qcIncident" class="font-medium text-slate-600"></span></div>
                                <div class="flex justify-between text-[11px]"><span class="text-slate-400">Assigned To</span><span id="qcWorker" class="font-medium text-slate-600"></span></div>
                            </div>

                            <p class="text-[11px] text-slate-500 leading-relaxed mb-4" id="qcNarrative"></p>

                            <a id="qcViewBtn" href="#"
                                class="block text-center w-full py-2.5 bg-green-600 text-white text-[12px] font-semibold rounded-xl hover:bg-green-500 transition-all">
                                View Full Case →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <div id="toast"
        class="fixed bottom-6 right-6 bg-green-700 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-green-300"></i>
        <span id="toastMsg">Done!</span>
    </div>

    <script>
        const ALL_CASES = <?= json_encode($allCasesPhp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

        const CASE_TYPE_META = {
            'VAWC': { label: 'VAWC', cls: 'badge-vawc' },
            'CICL': { label: 'CICL', cls: 'badge-cicl' },
            'Child Abuse': { label: 'Child Abuse', cls: 'badge-childabuse' },
            'CAR': { label: 'CAR', cls: 'badge-car' }
        };

        const STATUS_META = {
            'Active': { cls: 'status-active' },
            'Monitoring': { cls: 'status-monitoring' },
            'Resolved': { cls: 'status-resolved' },
            'Closed': { cls: 'status-closed' },
            'Referred': { cls: 'status-referred' }
        };

        const AV_COLORS = ['av-0', 'av-1', 'av-2', 'av-3', 'av-4', 'av-5'];

        // Always start with the latest incident date first.
        let filtered = [...ALL_CASES].sort((a, b) => {
            const dateA = a.incident_date ? new Date(a.incident_date).getTime() : 0;
            const dateB = b.incident_date ? new Date(b.incident_date).getTime() : 0;
            return dateB - dateA;
        });

        let activeChips = new Set();
        let searchQuery = '';
        let sortDir = { incidentDate: 'desc' };

        function getInitials(name) {
            const parts = String(name || '').split(/[\s,]+/).filter(Boolean);
            return (parts[0]?.[0] || '') + (parts[parts.length - 1]?.[0] || '');
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function sortCasesByCurrentDateOrder(list) {
            return list.sort((a, b) => {
                const dateA = a.incident_date ? new Date(a.incident_date).getTime() : 0;
                const dateB = b.incident_date ? new Date(b.incident_date).getTime() : 0;
                return dateB - dateA;
            });
        }

        function renderTable() {
            const tbody = document.getElementById('caseTableBody');
            const empty = document.getElementById('emptyState');

            document.getElementById('countNum').textContent = filtered.length.toLocaleString();

            const total = ALL_CASES.length;
            const active = ALL_CASES.filter(c => c.status === 'Active' || c.status === 'Monitoring').length;
            const critical = ALL_CASES.filter(c => c.critical).length;
            const resolved = ALL_CASES.filter(c => c.status === 'Resolved' || c.status === 'Closed').length;

            document.getElementById('totalCases').textContent = total;
            document.getElementById('pendingCases').textContent = active;
            document.getElementById('criticalCases').textContent = critical;
            document.getElementById('resolvedCases').textContent = resolved;

            if (filtered.length === 0) {
                tbody.innerHTML = '';
                empty.classList.remove('hidden');
                return;
            }

            empty.classList.add('hidden');

            tbody.innerHTML = filtered.map((c, i) => {
                const av = AV_COLORS[i % AV_COLORS.length];
                const typeMeta = CASE_TYPE_META[c.case_type] || {
                    label: c.case_type || '—',
                    cls: 'bg-slate-100 text-slate-700'
                };
                const statusMeta = STATUS_META[c.status] || {
                    cls: 'bg-slate-100 text-slate-700'
                };

                const highlight = q => {
                    const safeClient = escapeHtml(c.client);
                    if (!q) return safeClient;
                    const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    const re = new RegExp(`(${escaped})`, 'gi');
                    return safeClient.replace(re, '<mark class="bg-yellow-200 rounded">$1</mark>');
                };

                return `
                    <tr class="case-row border-b border-slate-50" onclick="openQuick(${i})">
                        <td class="px-4 py-3.5">
                            <span class="font-mono text-[11px] font-semibold text-green-700 bg-green-50 px-2 py-0.5 rounded">
                                ${escapeHtml(c.case_id)}
                            </span>
                        </td>

                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl ${av} flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                    ${escapeHtml(getInitials(c.client))}
                                </div>
                                <div>
                                    <p class="font-semibold text-green-700 text-[13px]">${highlight(searchQuery)}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">${escapeHtml(c.barangay)}</p>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-3.5">
                            <span class="${typeMeta.cls} px-2 py-0.5 rounded text-[10px] font-semibold">
                                ${escapeHtml(typeMeta.label)}
                            </span>
                        </td>

                        <td class="px-4 py-3.5 hidden md:table-cell text-[12px] text-slate-600">
                            ${escapeHtml(c.barangay)}
                        </td>

                        <td class="px-4 py-3.5 hidden lg:table-cell text-[11px] text-slate-400">
                            ${escapeHtml(c.incident_date || '—')}
                        </td>

                        <td class="px-4 py-3.5">
                            <span class="${statusMeta.cls} px-2.5 py-0.5 rounded-full text-[10px] font-semibold">
                                ${escapeHtml(c.status || '—')}
                            </span>
                            ${c.critical ? '<span class="ml-1 text-red-500 text-[10px]"><i class="fas fa-exclamation-circle"></i></span>' : ''}
                        </td>

                        <td class="px-4 py-3.5 hidden sm:table-cell text-[11px] text-slate-600">
                            ${escapeHtml(c.assigned_worker)}
                        </td>

                        <td class="px-4 py-3.5">
                            <a href="confidential_case_view.php?id=${encodeURIComponent(c.case_id)}"
                                onclick="event.stopPropagation()"
                                class="text-[11px] font-semibold text-green-600 border border-green-200 bg-green-50 rounded-lg px-2.5 py-1.5 hover:bg-green-100 transition-all whitespace-nowrap">
                                View Case
                            </a>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function doSearch(q) {
            searchQuery = q.trim().toLowerCase();
            document.getElementById('clearSearch').classList.toggle('hidden', !q);
            document.getElementById('clearFiltersBtn').classList.toggle(
                'hidden',
                activeChips.size === 0 && !searchQuery && !document.getElementById('statusFilter').value
            );
            applyFilters();
        }

        function clearSearch() {
            document.getElementById('mainSearch').value = '';
            document.getElementById('clearSearch').classList.add('hidden');
            searchQuery = '';
            applyFilters();
        }

        function toggleChip(btn, type) {
            if (activeChips.has(type)) {
                activeChips.delete(type);
                btn.classList.remove('active');
            } else {
                activeChips.add(type);
                btn.classList.add('active');
            }

            document.getElementById('clearFiltersBtn').classList.toggle(
                'hidden',
                activeChips.size === 0 && !searchQuery && !document.getElementById('statusFilter').value
            );

            applyFilters();
        }

        function applyFilters() {
            const statusFilter = document.getElementById('statusFilter').value;

            filtered = ALL_CASES.filter(c => {
                const client = String(c.client || '').toLowerCase();
                const caseId = String(c.case_id || '').toLowerCase();
                const barangay = String(c.barangay || '').toLowerCase();

                const matchSearch =
                    !searchQuery ||
                    client.includes(searchQuery) ||
                    caseId.includes(searchQuery) ||
                    barangay.includes(searchQuery);

                const matchTypes =
                    activeChips.size === 0 ||
                    activeChips.has(c.case_type);

                const matchStatus =
                    !statusFilter ||
                    c.status === statusFilter;

                return matchSearch && matchTypes && matchStatus;
            });

            // Preserve latest-to-oldest order after searching/filtering,
            // unless the user deliberately selected another sort.
            if (sortDir.incidentDate === 'desc') {
                sortCasesByCurrentDateOrder(filtered);
            }

            document.getElementById('clearFiltersBtn').classList.toggle(
                'hidden',
                activeChips.size === 0 && !searchQuery && !statusFilter
            );

            renderTable();
        }

        function clearAllFilters() {
            activeChips.clear();
            document.querySelectorAll('.filter-chip').forEach(b => b.classList.remove('active'));
            document.getElementById('statusFilter').value = '';
            document.getElementById('mainSearch').value = '';
            document.getElementById('clearSearch').classList.add('hidden');
            searchQuery = '';
            document.getElementById('clearFiltersBtn').classList.add('hidden');

            filtered = [...ALL_CASES];
            sortDir = { incidentDate: 'desc' };
            sortCasesByCurrentDateOrder(filtered);

            document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('asc', 'desc'));
            document.getElementById('incidentDateSortBtn').classList.add('desc');

            renderTable();
        }

        function openQuick(idx) {
            const c = filtered[idx];
            if (!c) return;

            const card = document.getElementById('quickCard');
            const av = AV_COLORS[idx % AV_COLORS.length];
            const typeMeta = CASE_TYPE_META[c.case_type] || {
                label: c.case_type || '—',
                cls: 'bg-slate-100 text-slate-700'
            };
            const statusMeta = STATUS_META[c.status] || {
                cls: 'bg-slate-100 text-slate-700'
            };

            document.getElementById('qcAvatar').className =
                `w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0 ${av}`;

            document.getElementById('qcAvatar').textContent = getInitials(c.client);
            document.getElementById('qcName').textContent = c.client;
            document.getElementById('qcCaseId').textContent = c.case_id;
            document.getElementById('qcType').innerHTML =
                `<span class="${typeMeta.cls} px-2 py-0.5 rounded text-[10px] font-semibold">${escapeHtml(typeMeta.label)}</span>`;
            document.getElementById('qcStatus').innerHTML =
                `<span class="${statusMeta.cls} px-2 py-0.5 rounded-full text-[10px] font-semibold">${escapeHtml(c.status || '—')}</span>`;

            document.getElementById('qcBrgy').textContent = c.barangay;
            document.getElementById('qcIncident').textContent = c.incident_date || '—';
            document.getElementById('qcWorker').textContent = c.assigned_worker || '—';
            document.getElementById('qcNarrative').textContent = c.narrative || 'No narrative available.';
            document.getElementById('qcViewBtn').href =
                `confidential_case_view.php?id=${encodeURIComponent(c.case_id)}`;

            card.classList.remove('hidden');
        }

        function closeQuick() {
            document.getElementById('quickCard').classList.add('hidden');
        }

        function sortBy(key, btn) {
            document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('asc', 'desc'));

            const map = {
                caseId: 'case_id',
                client: 'client',
                incidentDate: 'incident_date'
            };

            // For the Incident Date button, the first click should be latest -> oldest.
            if (key === 'incidentDate') {
                sortDir[key] = sortDir[key] === 'desc' ? 'asc' : 'desc';
            } else {
                sortDir[key] = sortDir[key] === 'asc' ? 'desc' : 'asc';
            }

            btn.classList.add(sortDir[key]);

            filtered.sort((a, b) => {
                const va = a[map[key]];
                const vb = b[map[key]];

                if (key === 'incidentDate') {
                    const dateA = va ? new Date(va).getTime() : 0;
                    const dateB = vb ? new Date(vb).getTime() : 0;

                    return sortDir[key] === 'desc'
                        ? dateB - dateA
                        : dateA - dateB;
                }

                if (typeof va === 'number' && typeof vb === 'number') {
                    return sortDir[key] === 'asc'
                        ? va - vb
                        : vb - va;
                }

                return String(va ?? '').localeCompare(String(vb ?? ''), undefined, {
                    numeric: true,
                    sensitivity: 'base'
                }) * (sortDir[key] === 'asc' ? 1 : -1);
            });

            renderTable();
        }

        function showToast(msg) {
            document.getElementById('toastMsg').textContent = msg;
            const t = document.getElementById('toast');

            t.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
            t.classList.add('opacity-100', 'translate-y-0');

            setTimeout(() => {
                t.classList.add('opacity-0', 'translate-y-4');
                t.classList.remove('opacity-100', 'translate-y-0');
            }, 2800);
        }

        <?php if ($flashMsg): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showToast(<?= json_encode($flashMsg) ?>);
        });
        <?php endif; ?>

        // Initial display: latest incident date first.
        document.getElementById('incidentDateSortBtn').classList.add('desc');
        renderTable();
    </script>
</body>
</html>
