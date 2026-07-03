<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pending Approvals – MSWDO San Enrique</title>
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

        .badge-pending {
            background: #FEF3C7;
            color: #D97706;
        }

        .badge-approved {
            background: #D1FAE5;
            color: #059669;
        }

        .badge-denied {
            background: #FEE2E2;
            color: #DC2626;
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

<body class="bg-slate2 min-h-screen flex">

    <!-- ══════════════════════════ SIDEBAR ══════════════════════════ -->
    <?php require 'sidebar.php'; ?>
    <!-- ══════════════════════════ MAIN ══════════════════════════════ -->
    <div class="ml-64 flex-1 flex flex-col min-h-screen">

        <!-- Top Bar -->
        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <span class="text-green-600 font-semibold">Pending Approvals</span>
            </div>
            <div class="flex items-center gap-2">
            </div>
        </header>

        <main class="flex-1 p-6 space-y-5 overflow-y-auto">

            <!-- Page Title with Quick Actions -->
            <div class="flex flex-wrap items-center justify-between gap-3 animate-fade-up">
                <div>
                    <h1 class="text-xl font-serif text-green-600">AICS Applications for Approval</h1>
                    <p class="text-[13px] text-slate-500 mt-0.5">Review and process pending AICS availments after case
                        study.</p>
                </div>
            </div>

            <!-- Budget Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 animate-fade-up-1">
                <!-- AICS FBML Budget -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[13px] font-semibold text-green-600"><i
                                class="fas fa-pills mr-1.5 text-green-400"></i>AICS FBML</h3>
                        <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">Budget</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total</p>
                            <p class="text-xl font-bold text-green-600">₱1,800,000</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Pending</p>
                            <p class="text-xl font-bold text-amber-600">₱210,000</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Approved</p>
                            <p class="text-xl font-bold text-emerald-600">₱1,260,000</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Available</p>
                            <p class="text-xl font-bold text-blue-600">₱330,000</p>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <div class="flex justify-between text-[10px] text-slate-400">
                            <span>Used: 70%</span>
                            <span>Remaining: 30%</span>
                        </div>
                        <div class="bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                            <div class="h-1.5 rounded-full bg-green-500" style="width:70%"></div>
                        </div>
                    </div>
                </div>

                <!-- AICS Educational Budget -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[13px] font-semibold text-green-600"><i
                                class="fas fa-graduation-cap mr-1.5 text-green-400"></i>AICS Educational</h3>
                        <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">Budget</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total</p>
                            <p class="text-xl font-bold text-green-600">₱200,000</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Pending</p>
                            <p class="text-xl font-bold text-amber-600">₱45,000</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Approved</p>
                            <p class="text-xl font-bold text-emerald-600">₱120,000</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Available</p>
                            <p class="text-xl font-bold text-blue-600">₱35,000</p>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <div class="flex justify-between text-[10px] text-slate-400">
                            <span>Used: 60%</span>
                            <span>Remaining: 40%</span>
                        </div>
                        <div class="bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                            <div class="h-1.5 rounded-full bg-green-500" style="width:60%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter & Search - Filter by Availment Type -->
            <div class="flex flex-wrap items-center justify-between gap-3 animate-fade-up-2">
                <div class="flex flex-wrap items-center gap-3">
                    <select id="filterType"
                        class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                        onchange="filterTable()">
                        <option value="all">All AICS</option>
                        <option value="Educational">Educational</option>
                        <option value="Financial">Financial</option>
                        <option value="Burial">Burial</option>
                        <option value="Medical">Medical</option>
                        <option value="Livelihood">Livelihood</option>
                    </select>
                    <select
                        class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none">
                        <option>All Status</option>
                        <option>Pending</option>
                        <option>Approved</option>
                        <option>Denied</option>
                    </select>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" placeholder="Search beneficiary..."
                            class="text-[12px] pl-8 pr-3 py-1.5 border border-slate-200 rounded-lg bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none w-48" />
                    </div>
                </div>
                <span class="text-[11px] text-slate-400" id="rowCount">Showing 6 pending applications</span>
            </div>

            <!-- Pending Applications Table -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-up-3">
                <div class="overflow-x-auto">
                    <table class="w-full text-[12px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Beneficiary</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Budget Source</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Type</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Amount</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Date Applied</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Status</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="pendingTableBody">
                            <!-- AICS FBML rows -->
                            <tr class="table-row" data-type="Medical">
                                <td class="px-5 py-3 font-medium text-green-700">Maria Santos</td>
                                <td class="px-5 py-3"><span
                                        class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px] font-semibold">AICS
                                        FBML</span></td>
                                <td class="px-5 py-3 text-slate-600">Medical</td>
                                <td class="px-5 py-3 font-semibold text-slate-700">₱3,500</td>
                                <td class="px-5 py-3 text-slate-400">Apr 14, 2026</td>
                                <td class="px-5 py-3"><span
                                        class="badge-pending px-2.5 py-0.5 rounded-full text-[10px] font-semibold">Pending</span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <button onclick="handleApprove('Maria Santos', 3500, 'fbml')"
                                            class="text-[11px] font-medium text-emerald-600 bg-emerald-50 border border-emerald-200 rounded-lg px-2.5 py-1 hover:bg-emerald-100 transition-colors">
                                            <i class="fas fa-check mr-1"></i> Approve
                                        </button>
                                        <button onclick="handleDeny('Maria Santos', 3500, 'fbml')"
                                            class="text-[11px] font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg px-2.5 py-1 hover:bg-red-100 transition-colors">
                                            <i class="fas fa-times mr-1"></i> Deny
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="table-row" data-type="Burial">
                                <td class="px-5 py-3 font-medium text-green-700">Elena Dela Cruz</td>
                                <td class="px-5 py-3"><span
                                        class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px] font-semibold">AICS
                                        FBML</span></td>
                                <td class="px-5 py-3 text-slate-600">Burial</td>
                                <td class="px-5 py-3 font-semibold text-slate-700">₱5,000</td>
                                <td class="px-5 py-3 text-slate-400">Apr 12, 2026</td>
                                <td class="px-5 py-3"><span
                                        class="badge-pending px-2.5 py-0.5 rounded-full text-[10px] font-semibold">Pending</span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <button onclick="handleApprove('Elena Dela Cruz', 5000, 'fbml')"
                                            class="text-[11px] font-medium text-emerald-600 bg-emerald-50 border border-emerald-200 rounded-lg px-2.5 py-1 hover:bg-emerald-100 transition-colors">
                                            <i class="fas fa-check mr-1"></i> Approve
                                        </button>
                                        <button onclick="handleDeny('Elena Dela Cruz', 5000, 'fbml')"
                                            class="text-[11px] font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg px-2.5 py-1 hover:bg-red-100 transition-colors">
                                            <i class="fas fa-times mr-1"></i> Deny
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="table-row" data-type="Livelihood">
                                <td class="px-5 py-3 font-medium text-green-700">Rodrigo Lim</td>
                                <td class="px-5 py-3"><span
                                        class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px] font-semibold">AICS
                                        FBML</span></td>
                                <td class="px-5 py-3 text-slate-600">Livelihood</td>
                                <td class="px-5 py-3 font-semibold text-slate-700">₱8,000</td>
                                <td class="px-5 py-3 text-slate-400">Apr 10, 2026</td>
                                <td class="px-5 py-3"><span
                                        class="badge-pending px-2.5 py-0.5 rounded-full text-[10px] font-semibold">Pending</span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <button onclick="handleApprove('Rodrigo Lim', 8000, 'fbml')"
                                            class="text-[11px] font-medium text-emerald-600 bg-emerald-50 border border-emerald-200 rounded-lg px-2.5 py-1 hover:bg-emerald-100 transition-colors">
                                            <i class="fas fa-check mr-1"></i> Approve
                                        </button>
                                        <button onclick="handleDeny('Rodrigo Lim', 8000, 'fbml')"
                                            class="text-[11px] font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg px-2.5 py-1 hover:bg-red-100 transition-colors">
                                            <i class="fas fa-times mr-1"></i> Deny
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- AICS Educational rows -->
                            <tr class="table-row" data-type="Educational">
                                <td class="px-5 py-3 font-medium text-green-700">Carlo Reyes</td>
                                <td class="px-5 py-3"><span
                                        class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded text-[10px] font-semibold">AICS
                                        Educational</span></td>
                                <td class="px-5 py-3 text-slate-600">Educational</td>
                                <td class="px-5 py-3 font-semibold text-slate-700">₱5,000</td>
                                <td class="px-5 py-3 text-slate-400">Apr 11, 2026</td>
                                <td class="px-5 py-3"><span
                                        class="badge-pending px-2.5 py-0.5 rounded-full text-[10px] font-semibold">Pending</span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <button onclick="handleApprove('Carlo Reyes', 5000, 'edu')"
                                            class="text-[11px] font-medium text-emerald-600 bg-emerald-50 border border-emerald-200 rounded-lg px-2.5 py-1 hover:bg-emerald-100 transition-colors">
                                            <i class="fas fa-check mr-1"></i> Approve
                                        </button>
                                        <button onclick="handleDeny('Carlo Reyes', 5000, 'edu')"
                                            class="text-[11px] font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg px-2.5 py-1 hover:bg-red-100 transition-colors">
                                            <i class="fas fa-times mr-1"></i> Deny
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="table-row" data-type="Educational">
                                <td class="px-5 py-3 font-medium text-green-700">Ana Delos Santos</td>
                                <td class="px-5 py-3"><span
                                        class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded text-[10px] font-semibold">AICS
                                        Educational</span></td>
                                <td class="px-5 py-3 text-slate-600">Educational</td>
                                <td class="px-5 py-3 font-semibold text-slate-700">₱2,500</td>
                                <td class="px-5 py-3 text-slate-400">Apr 9, 2026</td>
                                <td class="px-5 py-3"><span
                                        class="badge-pending px-2.5 py-0.5 rounded-full text-[10px] font-semibold">Pending</span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <button onclick="handleApprove('Ana Delos Santos', 2500, 'edu')"
                                            class="text-[11px] font-medium text-emerald-600 bg-emerald-50 border border-emerald-200 rounded-lg px-2.5 py-1 hover:bg-emerald-100 transition-colors">
                                            <i class="fas fa-check mr-1"></i> Approve
                                        </button>
                                        <button onclick="handleDeny('Ana Delos Santos', 2500, 'edu')"
                                            class="text-[11px] font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg px-2.5 py-1 hover:bg-red-100 transition-colors">
                                            <i class="fas fa-times mr-1"></i> Deny
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="table-row" data-type="Educational">
                                <td class="px-5 py-3 font-medium text-green-700">Josefa Reyes</td>
                                <td class="px-5 py-3"><span
                                        class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded text-[10px] font-semibold">AICS
                                        Educational</span></td>
                                <td class="px-5 py-3 text-slate-600">Educational</td>
                                <td class="px-5 py-3 font-semibold text-slate-700">₱1,200</td>
                                <td class="px-5 py-3 text-slate-400">Apr 8, 2026</td>
                                <td class="px-5 py-3"><span
                                        class="badge-pending px-2.5 py-0.5 rounded-full text-[10px] font-semibold">Pending</span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <button onclick="handleApprove('Josefa Reyes', 1200, 'edu')"
                                            class="text-[11px] font-medium text-emerald-600 bg-emerald-50 border border-emerald-200 rounded-lg px-2.5 py-1 hover:bg-emerald-100 transition-colors">
                                            <i class="fas fa-check mr-1"></i> Approve
                                        </button>
                                        <button onclick="handleDeny('Josefa Reyes', 1200, 'edu')"
                                            class="text-[11px] font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg px-2.5 py-1 hover:bg-red-100 transition-colors">
                                            <i class="fas fa-times mr-1"></i> Deny
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="flex items-center justify-between px-5 py-3 border-t border-slate-100">
                    <span class="text-[11px] text-slate-400">Showing <span id="visibleCount">6</span> of <span
                            id="totalCount">6</span> pending applications</span>
                    <div class="flex items-center gap-1">
                        <button
                            class="text-[11px] text-slate-400 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors">Previous</button>
                        <button class="text-[11px] font-medium text-white bg-green-600 rounded-lg px-3 py-1">1</button>
                        <button
                            class="text-[11px] text-slate-600 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors">Next</button>
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
        function filterTable() {
            const filterValue = document.getElementById('filterType').value.toLowerCase();
            const rows = document.querySelectorAll('#pendingTableBody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                const type = row.getAttribute('data-type');
                if (filterValue === 'all' || type.toLowerCase() === filterValue) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('visibleCount').textContent = visibleCount;
            document.getElementById('totalCount').textContent = rows.length;

            const countSpan = document.querySelector('.text-slate-400:not(.flex-1)');
            if (countSpan) {
                countSpan.textContent = `Showing ${visibleCount} pending applications`;
            }
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

        function handleApprove(name, amount, budgetType) {
            const budgetName = budgetType === 'fbml' ? 'AICS FBML' : 'AICS Educational';
            if (amount > 0) {
                if (confirm(`Approve ${name}'s ${budgetName} availment for ₱${amount.toLocaleString()}? This will deduct from the ${budgetName} budget.`)) {
                    showToast(`${name}'s ${budgetName} availment approved! ₱${amount.toLocaleString()} deducted.`);
                }
            } else {
                if (confirm(`Approve ${name}'s ${budgetName} availment?`)) {
                    showToast(`${name}'s ${budgetName} availment approved!`);
                }
            }
        }

        function handleDeny(name, amount, budgetType) {
            const budgetName = budgetType === 'fbml' ? 'AICS FBML' : 'AICS Educational';
            if (amount > 0) {
                if (confirm(`Deny ${name}'s ${budgetName} availment for ₱${amount.toLocaleString()}? The reserved budget will be released.`)) {
                    showToast(`${name}'s ${budgetName} availment denied. Budget released.`);
                }
            } else {
                if (confirm(`Deny ${name}'s ${budgetName} availment?`)) {
                    showToast(`${name}'s ${budgetName} availment denied.`);
                }
            }
        }
    </script>

</body>

</html>