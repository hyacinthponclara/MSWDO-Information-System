<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MSWDO Admin Dashboard – San Enrique</title>
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
                        pulse2: { '0%,100%': { opacity: '1' }, '50%': { opacity: '.5' } },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.4s ease both',
                        'fade-up-1': 'fadeUp 0.4s ease 0.05s both',
                        'fade-up-2': 'fadeUp 0.4s ease 0.1s both',
                        'fade-up-3': 'fadeUp 0.4s ease 0.15s both',
                        'fade-up-4': 'fadeUp 0.4s ease 0.2s both',
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

        .prog-bar-fill {
            transition: width 1s cubic-bezier(.4, 0, .2, 1);
        }

        .btn-action {
            transition: all .15s ease;
        }
        .btn-action:hover {
            transform: translateY(-1px);
        }

        .activity-row {
            transition: background .12s;
        }
        .activity-row:hover {
            background: #EEF6F0;
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

    <!-- ═══════════════════════════════════════ SIDEBAR ═══════════ -->
    <?php require 'sidebar.php'; ?>
    <!-- ═══════════════════════════════════════ MAIN ═══════════════ -->
    <div class="ml-64 flex-1 flex flex-col min-h-screen">

        <!-- Top Bar -->
        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20 animate-fade-up">
            <div class="flex items-center gap-3">
                <h1 class="text-[15px] font-semibold text-green-600">Dashboard</h1>
                <span class="bg-green-100 text-green-700 text-[11px] font-semibold px-3 py-0.5 rounded-full">Administrator</span>
                <span class="text-slate-400 text-xs hidden sm:block" id="currentDate"></span>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 p-6 space-y-5 overflow-y-auto">


            <!-- ── STAT CARDS ) ── -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">

                <!-- Total Clients -->
                <div
                    class="stat-card animate-fade-up-1 bg-white rounded-2xl border border-slate-200 p-4 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-blue-500 rounded-t-2xl"></div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2">Total Clients</p>
                    <p class="text-3xl font-semibold text-green-600 leading-none">1,284</p>
                    <div class="absolute right-3 top-3 text-2xl opacity-30"><i class="fas fa-users"></i></div>
                </div>

                <!-- Availments This Month -->
                <div
                    class="stat-card animate-fade-up-2 bg-white rounded-2xl border border-slate-200 p-4 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-emerald-500 rounded-t-2xl"></div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2">Availments / Month</p>
                    <p class="text-3xl font-semibold text-green-600 leading-none">347</p>
                    <div class="absolute right-3 top-3 text-2xl opacity-30"><i class="fas fa-clipboard-list"></i></div>
                </div>

                <!-- Budget Alerts -->
                <div
                    class="stat-card animate-fade-up-3 bg-white rounded-2xl border border-red-100 p-4 relative overflow-hidden cursor-pointer">
                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-red-500 rounded-t-2xl"></div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2">Budget Alerts</p>
                    <p class="text-3xl font-semibold text-red-500 leading-none">2</p>
                    <div class="absolute right-3 top-3 text-2xl opacity-30"><i class="fas fa-triangle-exclamation"></i></div>
                </div>

            </div>

            <!-- ── QUICK ACTIONS ── -->
            <div class="animate-fade-up grid grid-cols-2 sm:grid-cols-4 gap-3">
                <button
                    class="btn-action bg-white border border-slate-200 hover:border-green-400 hover:bg-green-50 rounded-xl px-4 py-3 flex items-center gap-3 text-left group">
                    <div
                        class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center text-base group-hover:bg-green-100 transition-colors flex-shrink-0">
                        <i class="fas fa-user-plus text-green-600"></i>
                    </div>
                    <span class="text-[12px] font-medium text-slate-700 group-hover:text-green-600">Register Client</span>
                </button>
                <button
                    class="btn-action bg-white border border-slate-200 hover:border-green-400 hover:bg-green-50 rounded-xl px-4 py-3 flex items-center gap-3 text-left group">
                    <div
                        class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center text-base group-hover:bg-emerald-100 transition-colors flex-shrink-0">
                        <i class="fas fa-clipboard-list text-green-600"></i>
                    </div>
                    <span class="text-[12px] font-medium text-slate-700 group-hover:text-green-600">New Availment</span>
                </button>
                <button
                    class="btn-action bg-white border border-slate-200 hover:border-green-400 hover:bg-green-50 rounded-xl px-4 py-3 flex items-center gap-3 text-left group">
                    <div
                        class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center text-base group-hover:bg-amber-100 transition-colors flex-shrink-0">
                        <i class="fas fa-file-alt text-green-600"></i>
                    </div>
                    <span class="text-[12px] font-medium text-slate-700 group-hover:text-green-600">Generate Report</span>
                </button>
                <button
                    class="btn-action bg-white border border-slate-200 hover:border-green-400 hover:bg-green-50 rounded-xl px-4 py-3 flex items-center gap-3 text-left group">
                    <div
                        class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center text-base group-hover:bg-red-100 transition-colors flex-shrink-0">
                        <i class="fas fa-coins text-green-600"></i>
                    </div>
                    <span class="text-[12px] font-medium text-slate-700 group-hover:text-green-600">Budget Status</span>
                </button>
            </div>

            <!-- ── BUDGET SUMMARY ── -->
            <div class="animate-fade-up bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <h2 class="text-[13px] font-semibold text-green-600">Budget Summary — All Programs</h2>
                        <span class="bg-green-100 text-green-700 text-[10px] font-semibold px-2 py-0.5 rounded-full">Admin
                            View</span>
                    </div>
                    <a href="#" class="text-[11px] text-green-500 font-medium hover:text-green-700">Manage budgets →</a>
                </div>
                <div class="p-5">
                    <div class="space-y-3" id="budgetFull"></div>
                    <p class="text-[10px] text-slate-400 mt-3 pt-3 border-t border-slate-100">Admin can reallocate funds via
                        Budget Management.</p>
                </div>
            </div>

            <!-- ── RECENT ACTIVITY ── -->
            <div class="animate-fade-up bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h2 class="text-[13px] font-semibold text-green-600">Recent Activity</h2>
                    <a href="#" class="text-[11px] text-green-500 font-medium hover:text-green-700">View all logs →</a>
                </div>
                <div class="divide-y divide-slate-100">

                    <div class="activity-row flex items-start gap-3 px-5 py-3.5">
                        <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
                            <i class="fas fa-user-plus text-blue-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] text-slate-700">New client registered — <strong
                                    class="font-semibold text-green-600">Maria Santos</strong>, Brgy. Poblacion</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">2 mins ago · Staff: Ana Reyes</p>
                        </div>
                        <span
                            class="text-[10px] font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full flex-shrink-0">Create</span>
                    </div>

                    <div class="activity-row flex items-start gap-3 px-5 py-3.5">
                        <div class="w-7 h-7 rounded-full bg-emerald-100 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
                            <i class="fas fa-pills text-emerald-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] text-slate-700">AICS Medical availment approved — <strong
                                    class="font-semibold text-green-600">Pedro Cruz</strong> · ₱3,500</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">15 mins ago · Staff: Ana Reyes</p>
                        </div>
                        <span
                            class="text-[10px] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full flex-shrink-0">Approved</span>
                    </div>

                    <div class="activity-row flex items-start gap-3 px-5 py-3.5">
                        <div class="w-7 h-7 rounded-full bg-amber-100 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
                            <i class="fas fa-wheelchair text-amber-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] text-slate-700">PWD ID issued — <strong
                                    class="font-semibold text-green-600">Rodrigo Lim</strong>, Brgy. San Jose</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">1 hour ago · Staff: Ben Torres</p>
                        </div>
                        <span
                            class="text-[10px] font-medium text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full flex-shrink-0">Released</span>
                    </div>

                    <div class="activity-row flex items-start gap-3 px-5 py-3.5">
                        <div class="w-7 h-7 rounded-full bg-purple-100 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
                            <i class="fas fa-user-plus text-purple-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] text-slate-700">New Solo Parent — <strong class="font-semibold text-green-600">Luz
                                    Bautista</strong></p>
                            <p class="text-[10px] text-slate-400 mt-0.5">2 hours ago · Staff: Ben Torres</p>
                        </div>
                        <span
                            class="text-[10px] font-medium text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full flex-shrink-0">Updated</span>
                    </div>

                    <div class="activity-row flex items-start gap-3 px-5 py-3.5">
                        <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
                            <i class="fas fa-graduation-cap text-blue-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] text-slate-700">AICS Educational availment encoded — <strong
                                    class="font-semibold text-green-600">Carlo Reyes</strong> · ₱5,000</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">3 hours ago · Staff: Ana Reyes</p>
                        </div>
                        <span
                            class="text-[10px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full flex-shrink-0">Pending</span>
                    </div>

                    <div class="activity-row flex items-start gap-3 px-5 py-3.5">
                        <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
                            <i class="fas fa-gear text-slate-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] text-slate-700">Budget reallocated — <strong
                                    class="font-semibold text-green-600">AICS</strong> ₱200,000 → ₱240,000</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">Yesterday · Admin: Juan Dela Cruz</p>
                        </div>
                        <span
                            class="text-[10px] font-medium text-green-600 bg-green-50 px-2 py-0.5 rounded-full flex-shrink-0">Admin</span>
                    </div>
                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer
            class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
            <span>Developed by: Elupre, Macalino, Mana-ay, Ponclara</span>
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
        // ── Budget Summary — All Programs  ──
        const budgetFullData = [
            { name: 'AICS', annual: 2800000, remaining: 1540000, used: 1260000},
            { name: '4Ps', annual: 30000, remaining: 28500, used: 1500},
            { name: 'SLP', annual: 450000, remaining: 320000, used: 130000},
            { name: 'SFP', annual: 800000, remaining: 620000, used: 180000},
            { name: 'Day Care', annual: 350000, remaining: 280000, used: 70000},
            { name: 'Senior Citizen', annual: 1500000, remaining: 1050000, used: 450000},
            { name: 'PWD', annual: 200000, remaining: 144000, used: 56000},
            { name: 'Solo Parent', annual: 300000, remaining: 228000, used: 72000},
            { name: 'Women & Child', annual: 100000, remaining: 72000, used: 28000},
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