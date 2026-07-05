<?php
require 'auth.php';
requireRole(['Staff']);
require 'db_connect.php';

// ── STAT CARDS ─────────────────────────────────────────────
$clientsTodayDB = $pdo->query("
    SELECT COUNT(DISTINCT client_id)
    FROM AVAILMENT
    WHERE av_date_applied = CURDATE()
");
$clients_today = (int)$clientsTodayDB->fetchColumn();

// Week = Monday-Sunday of the current week (mode 1 = ISO week, starts Monday)
$availmentsWeekDB = $pdo->query("
    SELECT COUNT(*)
    FROM AVAILMENT
    WHERE YEARWEEK(av_date_applied, 1) = YEARWEEK(CURDATE(), 1)
");
$availments_this_week = (int)$availmentsWeekDB->fetchColumn();

// ── AICS QUICK ACCESS TILE (availments this week) ──────────
$aicsWeekDB = $pdo->prepare("
    SELECT COUNT(*)
    FROM AVAILMENT a
    JOIN PROGRAM p ON p.program_id = a.program_id
    WHERE p.program_name = :pname
      AND YEARWEEK(a.av_date_applied, 1) = YEARWEEK(CURDATE(), 1)
");
$aicsWeekDB->execute(['pname' => 'AICS']);
$aics_this_week = (int)$aicsWeekDB->fetchColumn();

// ── AICS BUDGET CARD ─────────────────────────────────────
$aicsBudgetDB = $pdo->prepare("
    SELECT
        p.prog_annual_budget AS total,
        COALESCE(SUM(a.av_amount), 0) AS spent
    FROM PROGRAM p
    LEFT JOIN AVAILMENT a
        ON a.program_id = p.program_id
       AND a.av_status IN ('Approved', 'Released')
    WHERE p.program_name = :pname
    GROUP BY p.program_id, p.prog_annual_budget
");
$aicsBudgetDB->execute(['pname' => 'AICS']);
$aicsBudget = $aicsBudgetDB->fetch(PDO::FETCH_ASSOC);

$aics_annual    = $aicsBudget ? (float)$aicsBudget['total'] : 0;
$aics_used      = $aicsBudget ? (float)$aicsBudget['spent'] : 0;
$aics_remaining = $aics_annual - $aics_used;
$aics_pct_used  = $aics_annual > 0 ? round(($aics_used / $aics_annual) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Staff Dashboard – MSWDO San Enrique</title>
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
                            400: '#C49A2A',
                        },
                        slate2: '#F4F7FC',
                    },
                    keyframes: {
                        fadeUp: { '0%': { opacity: '0', transform: 'translateY(12px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        pulse2: { '0%,100%': { opacity: '1' }, '50%': { opacity: '.45' } },
                        ping2: { '0%': { transform: 'scale(1)', opacity: '.8' }, '100%': { transform: 'scale(2)', opacity: '0' } },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.4s ease both',
                        'fade-up-1': 'fadeUp 0.4s ease 0.05s both',
                        'fade-up-2': 'fadeUp 0.4s ease 0.10s both',
                        'fade-up-3': 'fadeUp 0.4s ease 0.15s both',
                        'fade-up-4': 'fadeUp 0.4s ease 0.20s both',
                        'fade-up-5': 'fadeUp 0.4s ease 0.25s both',
                        'fade-up-6': 'fadeUp 0.4s ease 0.30s both',
                        'pulse2': 'pulse2 2s ease-in-out infinite',
                        'ping2': 'ping2 1.5s ease-out infinite',
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
            background: rgba(26, 92, 58, .22);
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

        .prog-tile {
            transition: all .18s ease;
        }
        .prog-tile:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 92, 58, .09);
            border-color: #1A5C3A;
        }

        ::-webkit-scrollbar {
            width: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(26, 92, 58, .2);
            border-radius: 2px;
        }

        .prog-bar-fill {
            transition: width 1s cubic-bezier(.4, 0, .2, 1);
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
                <span
                    class="bg-green-50 text-green-700 text-[11px] font-semibold px-3 py-0.5 rounded-full border border-green-200">Staff</span>
                <span class="text-slate-400 text-xs hidden sm:block" id="currentDate"></span>
            </div>
        </header>

        <main class="flex-1 p-6 space-y-5 overflow-y-auto">

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div
                    class="stat-card animate-fade-up-1 bg-white rounded-2xl border border-slate-200 p-4 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-green-500 rounded-t-2xl"></div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2">Clients Served
                        Today</p>
                    <p class="text-3xl font-semibold text-green-600 leading-none"><?= number_format($clients_today) ?></p>
                    <div class="absolute right-3 top-3 text-2xl opacity-30"><i class="fas fa-users"></i></div>
                </div>

                <div
                    class="stat-card animate-fade-up-2 bg-white rounded-2xl border border-slate-200 p-4 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-blue-500 rounded-t-2xl"></div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2">Availments This
                        Week</p>
                    <p class="text-3xl font-semibold text-green-600 leading-none"><?= number_format($availments_this_week) ?></p>
                    <div class="absolute right-3 top-3 text-2xl opacity-30"><i class="fas fa-clipboard-list"></i></div>
                </div>
            </div>

            <!-- Program Quick Access -->
            <div class="animate-fade-up bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-[13px] font-semibold text-green-600">Program Quick Access</h2>
                    <span class="text-[11px] text-slate-400">Click to start an availment</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" id="progGrid">
                    <div class="prog-tile bg-white border rounded-xl p-3.5 cursor-pointer flex items-center gap-3 border-green-200 hover:border-green-400"
                        onclick="window.location.href='aics.php'">
                        <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fas fa-capsules text-green-400"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[12px] font-semibold text-green-700 truncate">AICS</p>
                            <p class="text-[10px] text-slate-400 mt-0.5"><?= number_format($aics_this_week) ?> this week</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AICS Budget Card -->
            <div class="animate-fade-up bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-[13px] font-semibold text-green-600">AICS Budget</h2>
                    <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">View Only</span>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-[12px] text-slate-500">Annual Budget</span>
                        <span class="text-[13px] font-bold text-green-600">₱<?= number_format($aics_annual, 2) ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[12px] text-slate-500">Used</span>
                        <span class="text-[13px] font-semibold text-amber-500">₱<?= number_format($aics_used, 2) ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[12px] text-slate-500">Remaining</span>
                        <span class="text-[13px] font-bold text-green-600">₱<?= number_format($aics_remaining, 2) ?></span>
                    </div>
                    <div class="mt-2">
                        <div class="flex justify-between text-[10px] text-slate-400 mb-1">
                            <span><?= $aics_pct_used ?>% used</span>
                            <span><?= 100 - $aics_pct_used ?>% remaining</span>
                        </div>
                        <div class="bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="prog-bar-fill h-2 rounded-full bg-green-500" style="width:0%" data-target="<?= $aics_pct_used ?>%"></div>
                        </div>
                    </div>
                    <div class="text-[10px] text-slate-400 pt-3 border-t border-slate-100">
                        Last updated: <?= date('F j, Y \a\t g:i A') ?>
                    </div>
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
        // Animate progress bar
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