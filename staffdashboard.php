

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard – MSWDO San Enrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['DM Sans', 'sans-serif'], serif: ['DM Serif Display', 'serif'] },
                    colors: {
                        navy: { DEFAULT: '#0B2545', 50: '#E8EDF5', 100: '#C5D1E6', 400: '#3A5F93', 500: '#163566', 600: '#0B2545', 700: '#091D38' },
                        gold: { DEFAULT: '#C49A2A', 400: '#C49A2A' },
                        slate2: '#F4F7FC',
                    },
                    keyframes: {
                        fadeUp: { '0%': { opacity: '0', transform: 'translateY(10px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.35s ease both',
                        'fade-up-1': 'fadeUp 0.35s 0.05s ease both',
                        'fade-up-2': 'fadeUp 0.35s 0.10s ease both',
                        'fade-up-3': 'fadeUp 0.35s 0.15s ease both',
                        'fade-up-4': 'fadeUp 0.35s 0.20s ease both',
                        'fade-up-5': 'fadeUp 0.35s 0.25s ease both',
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
            background: rgba(29, 111, 164, .28);
            border-left-color: #C49A2A;
            color: #fff;
        }

        .stat-card {
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(11, 37, 69, .1);
        }

        .prog-bar-fill {
            transition: width 1s cubic-bezier(.4, 0, .2, 1);
        }

        .table-row {
            transition: background .12s;
        }

        .table-row:hover {
            background: #F8FAFC;
            cursor: pointer;
        }

        .budget-row {
            transition: background .1s;
        }

        .budget-row:hover {
            background: #F8FAFC;
        }

        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, .15);
            border-radius: 2px;
        }
    </style>
</head>

<body class="bg-slate2 min-h-screen flex">

    <?php require 'sidebar.php'; ?>

    <div class="ml-64 flex-1 flex flex-col min-h-screen">

        <!-- Top bar -->
        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20 animate-fade-up">
            <div class="flex items-center gap-3">
                <h1 class="text-[15px] font-semibold text-navy-600">Dashboard</h1>
                <span class="text-slate-400 text-xs hidden sm:block" id="currentDate"></span>
            </div>
            <div class="flex items-center gap-2">
                <a href="clientregistrationform.php"
                    class="text-[12px] font-medium text-white bg-navy-600 rounded-lg px-3 py-1.5 hover:bg-navy-500 transition-all">
                    <i class="fas fa-user-plus mr-1"></i> Register Client
                </a>
            </div>
        </header>

        <main class="flex-1 p-6 space-y-5 overflow-y-auto">

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="stat-card animate-fade-up-1 bg-white rounded-2xl border border-slate-200 p-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-navy-50 flex items-center justify-center text-navy-600 text-lg flex-shrink-0">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase tracking-widest text-slate-400 font-semibold">Clients
                                Availed Today</p>
                            <p class="text-2xl font-bold text-navy-600">24</p>
                        </div>
                    </div>
                </div>

                <div class="stat-card animate-fade-up-2 bg-white rounded-2xl border border-slate-200 p-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-navy-50 flex items-center justify-center text-navy-600 text-lg flex-shrink-0">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase tracking-widest text-slate-400 font-semibold">Total
                                Availments</p>
                            <p class="text-2xl font-bold text-navy-600">1,247</p>
                        </div>
                    </div>
                </div>

                <div class="stat-card animate-fade-up-3 bg-white rounded-2xl border border-slate-200 p-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-navy-50 flex items-center justify-center text-navy-600 text-lg flex-shrink-0">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase tracking-widest text-slate-400 font-semibold">Clients This
                                Month</p>
                            <p class="text-2xl font-bold text-navy-600">89</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budget Summary  -->
            <div class="animate-fade-up-4 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h2 class="text-[13px] font-semibold text-navy-600">Program Budget Summary</h2>
                    <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">View Only</span>
                </div>
                <div class="divide-y divide-slate-100" id="budgetRows">
                </div>
                <div class="px-5 py-3 bg-slate-50 border-t border-slate-100">
                    <p class="text-[10px] text-slate-400">Contact Admin to reallocate funds.</p>
                </div>
            </div>

            <!-- Recent Availments -->
            <div class="animate-fade-up-5 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h2 class="text-[13px] font-semibold text-navy-600">Recent Availments</h2>
                    <a href="#" class="text-[11px] text-navy-500 font-medium hover:text-navy-700">View all →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-[12px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Date</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Client</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Program</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Type</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Amount</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr class="table-row">
                                <td class="px-5 py-3 text-slate-500">Apr 14</td>
                                <td class="px-5 py-3 font-medium text-navy-600">Maria Santos</td>
                                <td class="px-5 py-3"><span
                                        class="bg-navy-50 text-navy-600 px-2 py-0.5 rounded text-[10px] font-semibold">AICS</span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">Medical</td>
                                <td class="px-5 py-3 font-semibold text-slate-700">₱3,500</td>
                                <td class="px-5 py-3"><span
                                        class="bg-navy-50 text-navy-600 px-2.5 py-0.5 rounded-full text-[10px] font-semibold">Approved</span>
                                </td>
                            </tr>
                            <tr class="table-row">
                                <td class="px-5 py-3 text-slate-500">Apr 14</td>
                                <td class="px-5 py-3 font-medium text-navy-600">Pedro Cruz</td>
                                <td class="px-5 py-3"><span
                                        class="bg-navy-50 text-navy-600 px-2 py-0.5 rounded text-[10px] font-semibold">PWD</span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">Financial</td>
                                <td class="px-5 py-3 font-semibold text-slate-700">₱2,000</td>
                                <td class="px-5 py-3"><span
                                        class="bg-navy-50 text-navy-600 px-2.5 py-0.5 rounded-full text-[10px] font-semibold">Released</span>
                                </td>
                            </tr>
                            <tr class="table-row">
                                <td class="px-5 py-3 text-slate-500">Apr 13</td>
                                <td class="px-5 py-3 font-medium text-navy-600">Luz Bautista</td>
                                <td class="px-5 py-3"><span
                                        class="bg-navy-50 text-navy-600 px-2 py-0.5 rounded text-[10px] font-semibold">Solo
                                        Parent</span></td>
                                <td class="px-5 py-3 text-slate-600">SPID Issuance</td>
                                <td class="px-5 py-3 text-slate-400">—</td>
                                <td class="px-5 py-3"><span
                                        class="bg-navy-50 text-navy-600 px-2.5 py-0.5 rounded-full text-[10px] font-semibold">Approved</span>
                                </td>
                            </tr>
                            <tr class="table-row">
                                <td class="px-5 py-3 text-slate-500">Apr 12</td>
                                <td class="px-5 py-3 font-medium text-navy-600">Elena Dela Cruz</td>
                                <td class="px-5 py-3"><span
                                        class="bg-navy-50 text-navy-600 px-2 py-0.5 rounded text-[10px] font-semibold">AICS</span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">Burial</td>
                                <td class="px-5 py-3 font-semibold text-slate-700">₱5,000</td>
                                <td class="px-5 py-3"><span
                                        class="bg-navy-50 text-navy-600 px-2.5 py-0.5 rounded-full text-[10px] font-semibold">Pending</span>
                                </td>
                            </tr>
                            <tr class="table-row">
                                <td class="px-5 py-3 text-slate-500">Apr 12</td>
                                <td class="px-5 py-3 font-medium text-navy-600">Rodrigo Lim</td>
                                <td class="px-5 py-3"><span
                                        class="bg-navy-50 text-navy-600 px-2 py-0.5 rounded text-[10px] font-semibold">Senior</span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">Pension Top-up</td>
                                <td class="px-5 py-3 font-semibold text-slate-700">₱500</td>
                                <td class="px-5 py-3"><span
                                        class="bg-navy-50 text-navy-600 px-2.5 py-0.5 rounded-full text-[10px] font-semibold">Released</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>

        <footer
            class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <script>
        // Current date
        const dateSpan = document.getElementById('currentDate');
        if (dateSpan) {
            const today = new Date();
            dateSpan.textContent = today.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }

        // Budget data for all 10 programs
        const budgets = [
            { name: 'AICS FBML', total: 240000, spent: 211600 },
            { name: 'AICS Educational', total: 120000, spent: 78000 },
            { name: 'SLP', total: 150000, spent: 138000 },
            { name: 'SFP', total: 200000, spent: 70000 },
            { name: 'Day Care', total: 300000, spent: 189000 },
            { name: 'Solo Parents', total: 160000, spent: 73200 },
            { name: 'Senior Citizen', total: 180000, spent: 96000 },
            { name: '4Ps', total: 250000, spent: 178500 },
            { name: 'PWD', total: 210000, spent: 134500 },
            { name: 'Women & Children', total: 100000, spent: 32000 },
        ];

        // Render budget rows
        const budgetRows = document.getElementById('budgetRows');
        budgets.forEach(b => {
            const remaining = b.total - b.spent;
            const pct = Math.round((b.spent / b.total) * 100);
            const barColor = remaining < b.total * 0.2 ? 'bg-red-400' : 'bg-navy-500';
            const textColor = remaining < b.total * 0.2 ? 'text-red-500' : 'text-navy-600';

            budgetRows.innerHTML += `
                <div class="budget-row flex items-center px-5 py-3.5 gap-4">
                    <span class="text-[12px] font-semibold text-navy-600 w-36 flex-shrink-0">${b.name}</span>
                    <div class="flex-1 flex items-center gap-3 min-w-0">
                        <div class="flex-1 min-w-0">
                            <div class="bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                <div class="prog-bar-fill h-1.5 rounded-full ${barColor}" style="width:0%" data-target="${pct}%"></div>
                            </div>
                        </div>
                        <span class="text-[11px] text-slate-400 w-10 text-right flex-shrink-0">${pct}%</span>
                    </div>
                    <span class="text-[12px] font-semibold ${textColor} w-28 text-right flex-shrink-0">₱${remaining.toLocaleString()}</span>
                </div>
            `;
        });

        // Animate bars
        requestAnimationFrame(() => {
            setTimeout(() => {
                document.querySelectorAll('.prog-bar-fill').forEach(el => el.style.width = el.dataset.target);
            }, 300);
        });
    </script>
</body>

</html>