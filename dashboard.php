<?php
require 'auth.php';
requireRole(['Admin', 'Social Worker', 'Staff']);
require 'db_connect.php';

$avTodayDB = $pdo->query("
    SELECT COUNT(DISTINCT client_id)
    FROM AVAILMENT
    WHERE av_date_applied = CURDATE()
");
$clients_today = (int)$avTodayDB->fetchColumn();

$avDB = $pdo->query("SELECT COUNT(*) FROM AVAILMENT");
$total_availments = (int)$avDB->fetchColumn();

$avThisMonthDB = $pdo->query("
    SELECT COUNT(DISTINCT client_id)
    FROM AVAILMENT
    WHERE MONTH(av_date_applied) = MONTH(CURDATE())
      AND YEAR(av_date_applied)  = YEAR(CURDATE())
");
$clients_this_month = (int)$avThisMonthDB->fetchColumn();

//  BUDGET SUMMARY 
$budgetDB = $pdo->query("
    SELECT
        p.program_name,
        p.prog_annual_budget AS total,
        COALESCE(SUM(a.av_amount), 0) AS spent
    FROM PROGRAM p
    LEFT JOIN AVAILMENT a
        ON a.program_id = p.program_id
       AND a.av_status IN ('Approved', 'Released')
    GROUP BY p.program_id, p.program_name, p.prog_annual_budget
    ORDER BY p.program_name ASC
");
$programs = $budgetDB->fetchAll(PDO::FETCH_ASSOC);
?>

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
                            <p class="text-2xl font-bold text-navy-600"><?= number_format($clients_today) ?></p>
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
                            <p class="text-2xl font-bold text-navy-600"><?= number_format($total_availments) ?></p>
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
                            <p class="text-2xl font-bold text-navy-600"><?= number_format($clients_this_month) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budget Summary -->
            <div class="animate-fade-up-4 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h2 class="text-[13px] font-semibold text-navy-600">Program Budget Summary</h2>
                    <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">View Only</span>
                </div>
                <div class="divide-y divide-slate-100" id="budgetRows">
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

        // Budget data — now driven by the database via PHP
        const budgets = <?= json_encode(array_map(fn($p) => [
            'name'  => $p['program_name'],
            'total' => (float)$p['total'],
            'spent' => (float)$p['spent'],
        ], $programs)) ?>;

        // Render budget rows
        const budgetRows = document.getElementById('budgetRows');

        // Header row
        budgetRows.innerHTML = `
            <div class="flex items-center px-5 py-2 gap-4 bg-slate-50">
                <span class="text-[10px] uppercase tracking-widest text-slate-400 font-semibold w-36 flex-shrink-0">Program</span>
                <span class="text-[10px] uppercase tracking-widest text-slate-400 font-semibold flex-1 text-right">Total Budget</span>
                <span class="text-[10px] uppercase tracking-widest text-slate-400 font-semibold flex-1 text-right">Spent</span>
                <span class="text-[10px] uppercase tracking-widest text-slate-400 font-semibold flex-1 text-right">Remaining</span>
            </div>
        `;

        budgets.forEach(b => {
            const remaining = b.total - b.spent;
            const isLow = remaining < b.total * 0.2;
            const remainingColor = isLow ? 'text-red-500' : 'text-emerald-600';

            budgetRows.innerHTML += `
                <div class="budget-row flex items-center px-5 py-3.5 gap-4">
                    <span class="text-[12px] font-semibold text-navy-600 w-36 flex-shrink-0">${b.name}</span>
                    <span class="text-[12px] text-slate-500 flex-1 text-right">₱${b.total.toLocaleString()}</span>
                    <span class="text-[12px] text-slate-500 flex-1 text-right">₱${b.spent.toLocaleString()}</span>
                    <span class="text-[12px] font-semibold ${remainingColor} flex-1 text-right">₱${remaining.toLocaleString()}</span>
                </div>
            `;
        });
    </script>
</body>

</html>