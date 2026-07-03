<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MSWDO Head Dashboard – San Enrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                            300: '#E4BC3F',
                            400: '#C49A2A',
                            500: '#9E7A1F',
                        },
                        slate2: '#F4F7FC',
                    },
                    keyframes: {
                        fadeUp: { '0%': { opacity: '0', transform: 'translateY(12px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        pulse2: { '0%,100%': { opacity: '1' }, '50%': { opacity: '.45' } },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.4s ease both',
                        'fade-up-1': 'fadeUp 0.4s ease 0.05s both',
                        'fade-up-2': 'fadeUp 0.4s ease 0.1s  both',
                        'fade-up-3': 'fadeUp 0.4s ease 0.15s both',
                        'fade-up-4': 'fadeUp 0.4s ease 0.2s  both',
                        'fade-up-5': 'fadeUp 0.4s ease 0.25s both',
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

        /* Sidebar */
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

        /* Cards */
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

        .case-row {
            transition: background .12s;
        }

        .case-row:hover {
            background: #EEF6F0;
            cursor: pointer;
        }

        .prog-bar-fill {
            transition: width 1s cubic-bezier(.4, 0, .2, 1);
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

        <!-- Top bar -->
        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20 animate-fade-up">
            <div class="flex items-center gap-3">
                <h1 class="text-[15px] font-semibold text-green-600">Dashboard</h1>
                <span class="bg-green-100 text-green-700 text-[11px] font-semibold px-3 py-0.5 rounded-full">MSWDO
                    Head</span>
                <span class="text-slate-400 text-xs hidden sm:block" id="currentDate"></span>
            </div>
        </header>

        <main class="flex-1 p-6 space-y-5 overflow-y-auto">

            <!-- Stat Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
                <div
                    class="stat-card animate-fade-up-1 bg-white rounded-2xl border border-slate-200 p-4 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-green-500 rounded-t-2xl"></div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2">Total Active
                        Cases</p>
                    <p class="text-3xl font-semibold text-green-600 leading-none">412</p>
                    <div class="absolute right-3 top-3 text-2xl opacity-30"><i class="fas fa-file-alt"></i></div>
                </div>

                <div
                    class="stat-card animate-fade-up-3 bg-white rounded-2xl border border-slate-200 p-4 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-emerald-500 rounded-t-2xl"></div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2">Clients This
                        Month</p>
                    <p class="text-3xl font-semibold text-green-600 leading-none">89</p>
                    <div class="absolute right-3 top-3 text-2xl opacity-30"><i class="fas fa-users"></i></div>
                </div>

                <div
                    class="stat-card animate-fade-up-4 bg-white rounded-2xl border border-slate-200 p-4 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-amber-400 rounded-t-2xl"></div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2">Pending
                        Approvals</p>
                    <p class="text-3xl font-semibold text-green-600 leading-none">7</p>
                    <div class="absolute right-3 top-3 text-2xl opacity-30"><i class="fas fa-clock"></i></div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="animate-fade-up grid grid-cols-2 sm:grid-cols-4 gap-4">
                <button
                    class="btn-action bg-white border border-slate-200 hover:border-green-400 hover:bg-green-50 rounded-xl px-4 py-3 flex items-center gap-3 text-left group">
                    <div
                        class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center text-base group-hover:bg-green-100 transition-colors flex-shrink-0">
                        <i class="fas fa-user-plus text-green-600"></i>
                    </div>
                    <span class="text-[12px] font-medium text-slate-700 group-hover:text-green-600">Register
                        Client</span>
                </button>
                <button
                    class="btn-action bg-white border border-slate-200 hover:border-green-400 hover:bg-green-50 rounded-xl px-4 py-3 flex items-center gap-3 text-left group">
                    <div
                        class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center text-base group-hover:bg-emerald-100 transition-colors flex-shrink-0">
                        <i class="fas fa-file-alt text-green-600"></i>
                    </div>
                    <span class="text-[12px] font-medium text-slate-700 group-hover:text-green-600">New Availment</span>
                </button>
                <a href="pending_approvals.html"
                    <button
                        class="btn-action bg-white border border-slate-200 hover:border-green-400 hover:bg-green-50 rounded-xl px-4 py-3 flex items-center gap-3 text-left group">
                        <div
                            class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center text-base group-hover:bg-emerald-100 transition-colors flex-shrink-0">
                            <i class="fas fa-clock text-green-600"></i>
                        </div>
                        <span class="text-[12px] font-medium text-slate-700 group-hover:text-green-600">Pending
                            Approvals</span>
                    </button></a>
                <button
                    class="btn-action bg-white border border-slate-200 hover:border-green-400 hover:bg-green-50 rounded-xl px-4 py-3 flex items-center gap-3 text-left group">
                    <div
                        class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center text-base group-hover:bg-emerald-100 transition-colors flex-shrink-0">
                        <i class="fas fa-lock text-green-700"></i>
                    </div>
                    <span class="text-[12px] font-medium text-green-700 group-hover:text-green-900">New Confidential
                        Case</span>
                </button>
            </div>

            <!-- Budget Summary -->
            <div class="animate-fade-up bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <h2 class="text-[13px] font-semibold text-green-600">Budget Summary — All Programs</h2>
                        <span
                            class="bg-green-100 text-green-700 text-[10px] font-semibold px-2 py-0.5 rounded-full">View
                            Only</span>
                    </div>
                    <a href="#" class="text-[11px] text-green-500 font-medium hover:text-green-700">View full budget
                        →</a>
                </div>
                <div class="p-5">
                    <div class="space-y-3" id="budgetFull"></div>
                    <p class="text-[10px] text-slate-400 mt-3 pt-3 border-t border-slate-100">Contact Admin to
                        reallocate funds.</p>
                </div>
            </div>

            <!-- Recent Regular Program Activity Table -->
            <div class="animate-fade-up bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h2 class="text-[13px] font-semibold text-green-600">Recent Regular Program Activity</h2>
                    <a href="#" class="text-[11px] text-green-500 font-medium hover:text-green-700">View all availments
                        →</a>
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
                            <tr class="case-row">
                                <td class="px-5 py-3 text-slate-500">Apr 14</td>
                                <td class="px-5 py-3 font-medium text-green-600">Maria Santos</td>
                                <td class="px-5 py-3"><span
                                        class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px] font-semibold">AICS</span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">Medical</td>
                                <td class="px-5 py-3 font-semibold text-slate-700">₱3,500</td>
                                <td class="px-5 py-3"><span
                                        class="bg-blue-50 text-blue-600 px-2.5 py-0.5 rounded-full text-[10px] font-semibold">Approved</span>
                                </td>
                            </tr>
                            <tr class="case-row">
                                <td class="px-5 py-3 text-slate-500">Apr 14</td>
                                <td class="px-5 py-3 font-medium text-green-600">Pedro Cruz</td>
                                <td class="px-5 py-3"><span
                                        class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded text-[10px] font-semibold">PWD</span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">Financial</td>
                                <td class="px-5 py-3 font-semibold text-slate-700">₱2,000</td>
                                <td class="px-5 py-3"><span
                                        class="bg-emerald-50 text-emerald-600 px-2.5 py-0.5 rounded-full text-[10px] font-semibold">Released</span>
                                </td>
                            </tr>
                            <tr class="case-row">
                                <td class="px-5 py-3 text-slate-500">Apr 13</td>
                                <td class="px-5 py-3 font-medium text-green-600">Luz Bautista</td>
                                <td class="px-5 py-3"><span
                                        class="bg-teal-100 text-teal-700 px-2 py-0.5 rounded text-[10px] font-semibold">Solo
                                        Parent</span></td>
                                <td class="px-5 py-3 text-slate-600">SPID Issuance</td>
                                <td class="px-5 py-3 text-slate-400">—</td>
                                <td class="px-5 py-3"><span
                                        class="bg-blue-50 text-blue-600 px-2.5 py-0.5 rounded-full text-[10px] font-semibold">Approved</span>
                                </td>
                            </tr>
                            <tr class="case-row">
                                <td class="px-5 py-3 text-slate-500">Apr 12</td>
                                <td class="px-5 py-3 font-medium text-green-600">Elena Dela Cruz</td>
                                <td class="px-5 py-3"><span
                                        class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px] font-semibold">AICS</span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">Burial</td>
                                <td class="px-5 py-3 font-semibold text-slate-700">₱5,000</td>
                                <td class="px-5 py-3"><span
                                        class="bg-amber-50 text-amber-600 px-2.5 py-0.5 rounded-full text-[10px] font-semibold">Pending</span>
                                </td>
                            </tr>
                            <tr class="case-row">
                                <td class="px-5 py-3 text-slate-500">Apr 12</td>
                                <td class="px-5 py-3 font-medium text-green-600">Rodrigo Lim</td>
                                <td class="px-5 py-3"><span
                                        class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded text-[10px] font-semibold">Senior</span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">Pension Top-up</td>
                                <td class="px-5 py-3 font-semibold text-slate-700">₱500</td>
                                <td class="px-5 py-3"><span
                                        class="bg-emerald-50 text-emerald-600 px-2.5 py-0.5 rounded-full text-[10px] font-semibold">Released</span>
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
        const dateSpan = document.getElementById('currentDate');
        if (dateSpan) {
            const today = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            dateSpan.textContent = today.toLocaleDateString('en-US', options);
        }
    </script>

    <script>
        // Full budget data
        const budgetFullData = [
            { name: 'AICS', annual: 2800000, remaining: 1540000, used: 1260000 },
            { name: '4Ps', annual: 30000, remaining: 28500, used: 1500 },
            { name: 'SLP', annual: 450000, remaining: 320000, used: 130000 },
            { name: 'SFP', annual: 800000, remaining: 620000, used: 180000 },
            { name: 'Day Care', annual: 350000, remaining: 280000, used: 70000 },
            { name: 'Senior Citizen', annual: 1500000, remaining: 1050000, used: 450000 },
            { name: 'PWD', annual: 200000, remaining: 144000, used: 56000 },
            { name: 'Solo Parent', annual: 300000, remaining: 228000, used: 72000 },
            { name: 'Women & Child', annual: 100000, remaining: 72000, used: 28000 },
        ];

        const container = document.getElementById('budgetFull');
        budgetFullData.forEach(b => {
            const pct = Math.round((b.used / b.annual) * 100);
            const barColor = pct > 80 ? 'bg-red-400' : pct > 60 ? 'bg-amber-400' : 'bg-emerald-400';
            const textColor = pct > 80 ? 'text-red-500' : pct > 60 ? 'text-amber-500' : 'text-emerald-600';
            container.innerHTML += `
                <div class="flex items-center gap-3 text-[12px]">
                    <span class="w-24 text-slate-600 flex-shrink-0 truncate font-medium">${b.name}</span>
                    <div class="flex-1">
                        <div class="flex justify-between text-[10px] text-slate-400 mb-0.5">
                            <span>₱${b.used.toLocaleString()} used</span>
                            <span>₱${b.remaining.toLocaleString()} remaining</span>
                        </div>
                        <div class="bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="prog-bar-fill h-2 rounded-full ${barColor}" style="width:0%" data-target="${pct}%"></div>
                        </div>
                    </div>
                    <span class="w-20 text-right font-semibold ${textColor} flex-shrink-0">${pct}%</span>
                </div>
            `;
        });

        // Animate bars
        requestAnimationFrame(() => {
            setTimeout(() => {
                document.querySelectorAll('.prog-bar-fill').forEach(el => {
                    el.style.width = el.dataset.target;
                });
            }, 300);
        });
    </script>
</body>

</html>