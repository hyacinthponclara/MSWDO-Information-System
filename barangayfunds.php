<?php
require 'auth.php';
requireRole(['Admin', 'Social Worker', 'Staff']);
require 'db_connect.php';

$barangay_id = (int)($_GET['barangay_id'] ?? 0);

if ($barangay_id <= 0) {
    header("Location: barangaylist.php");
    exit;
}

$barangayDB = $pdo->prepare("SELECT barangay_id, barangay_name FROM BARANGAY WHERE barangay_id = ?");
$barangayDB->execute([$barangay_id]);
$barangay = $barangayDB->fetch(PDO::FETCH_ASSOC);

if (!$barangay) {
    header("Location: barangaylist.php");
    exit;
}

$barangay_name = htmlspecialchars($barangay['barangay_name']);

$programBudget = $pdo->prepare("
    SELECT
        p.program_id,
        p.program_name,
        p.prog_annual_budget,
        COALESCE(SUM(a.av_amount), 0) AS total_spent
    FROM PROGRAM p
    LEFT JOIN AVAILMENT a
        ON a.program_id = p.program_id
        AND a.client_id IN (
            SELECT client_id FROM CLIENT WHERE brgy_id = ?
        )
    GROUP BY p.program_id, p.program_name, p.prog_annual_budget
    ORDER BY p.program_name ASC
");
$programBudget->execute([$barangay_id]);
$programs = $programBudget->fetchAll(PDO::FETCH_ASSOC);

$prog_spent = [];
foreach ($programs as $prog) {
    $prog_spent[$prog['program_name']] = [
        'spent'  => (float)$prog['total_spent'],
        'budget' => (float)$prog['prog_annual_budget'],
    ];
}

$clientCount = $pdo->prepare("SELECT COUNT(*) FROM CLIENT WHERE brgy_id = ?");
$clientCount->execute([$barangay_id]);
$client_count = (int)$clientCount->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Program Funds Selection – MSWDO San Enrique</title>
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
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'DM Sans', sans-serif; }
        .sidebar-item { transition: all .15s; }
        .sidebar-item:hover { background: rgba(255,255,255,.07); color: rgba(255,255,255,.95); }
        .sidebar-item.active { background: rgba(29,111,164,.28); border-left-color: #C49A2A; color: #fff; }
        .prog-card { transition: all .2s ease; cursor: pointer; }
        .prog-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .prog-card .card-icon { transition: transform .2s ease; }
        .prog-card:hover .card-icon { transform: scale(1.1); }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 2px; }
    </style>
</head>

<body class="bg-slate2 min-h-screen flex">

    <?php require 'sidebar.php'; ?>

    <div class="ml-64 flex-1 flex flex-col min-h-screen">
        <header class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <a href="barangaylist.php" class="text-slate-400 hover:text-navy-600">Barangay List</a>
                <span class="text-slate-300">/</span>
                <a href="barangayfunds.php?barangay_id=<?= $barangay_id ?>" class="text-slate-400 hover:text-navy-600">
                    <?= $barangay_name ?>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-navy-600 font-semibold">Select Program</span>
            </div>
        </header>

        <main class="flex-1 p-6 overflow-y-auto">
            <div class="max-w-5xl mx-auto space-y-6">
                <div class="animate-fade-up">
                    <h1 class="text-xl font-serif text-navy-600">Select a Program</h1>
                    <p class="text-[13px] text-slate-500 mt-1">
                        Choose the program to begin a new fund request for
                        <span class="font-semibold text-navy-600"><?= $barangay_name ?></span>
                        &mdash; <?= number_format($client_count) ?> registered client<?= $client_count !== 1 ? 's' : '' ?>
                    </p>
                </div>

                <div class="animate-fade-up-1 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">

                    <!-- 4Ps -->
                    <a href="funds_4ps.php?barangay_id=<?= $barangay_id ?>"
                        class="prog-card bg-white border-2 border-slate-200 hover:border-navy-600 rounded-2xl p-5 text-center">
                        <div class="card-icon w-14 h-14 mx-auto mb-3 rounded-xl bg-navy-50 flex items-center justify-center text-2xl">
                            <i class="fas fa-home text-navy-600"></i>
                        </div>
                        <p class="text-[13px] font-semibold text-navy-600">4Ps</p>
                        <p class="text-[10px] text-slate-400 mt-1">Monitoring</p>
                    </a>

                    <!-- Day Care -->
                    <a href="funds_daycare.php?barangay_id=<?= $barangay_id ?>"
                        class="prog-card bg-white border-2 border-slate-200 hover:border-navy-600 rounded-2xl p-5 text-center">
                        <div class="card-icon w-14 h-14 mx-auto mb-3 rounded-xl bg-navy-50 flex items-center justify-center text-2xl">
                            <i class="fas fa-school text-navy-600"></i>
                        </div>
                        <p class="text-[13px] font-semibold text-navy-600">Day Care</p>
                        <p class="text-[10px] text-slate-400 mt-1">Assessment</p>
                    </a>

                    <!-- Senior Citizen -->
                    <a href="funds_senior.php?barangay_id=<?= $barangay_id ?>"
                        class="prog-card bg-white border-2 border-slate-200 hover:border-navy-600 rounded-2xl p-5 text-center">
                        <div class="card-icon w-14 h-14 mx-auto mb-3 rounded-xl bg-navy-50 flex items-center justify-center text-2xl">
                            <i class="fas fa-user-friends text-navy-600"></i>
                        </div>
                        <p class="text-[13px] font-semibold text-navy-600">Senior Citizen</p>
                        <p class="text-[10px] text-slate-400 mt-1">Pension & ID</p>
                    </a>

                    <!-- PWD -->
                    <a href="funds_pwd.php?barangay_id=<?= $barangay_id ?>"
                        class="prog-card bg-white border-2 border-slate-200 hover:border-navy-600 rounded-2xl p-5 text-center">
                        <div class="card-icon w-14 h-14 mx-auto mb-3 rounded-xl bg-navy-50 flex items-center justify-center text-2xl">
                            <i class="fas fa-wheelchair text-navy-600"></i>
                        </div>
                        <p class="text-[13px] font-semibold text-navy-600">PWD</p>
                        <p class="text-[10px] text-slate-400 mt-1">ID & Assistance</p>
                    </a>

                    <!-- Solo Parent -->
                    <a href="funds_soloparents.php?barangay_id=<?= $barangay_id ?>"
                        class="prog-card bg-white border-2 border-slate-200 hover:border-navy-600 rounded-2xl p-5 text-center">
                        <div class="card-icon w-14 h-14 mx-auto mb-3 rounded-xl bg-navy-50 flex items-center justify-center text-2xl">
                            <i class="fas fa-user text-navy-600"></i>
                        </div>
                        <p class="text-[13px] font-semibold text-navy-600">Solo Parent</p>
                        <p class="text-[10px] text-slate-400 mt-1">ID & Assistance</p>
                    </a>
    
                </div>
            </div>
        </main>

        <footer class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

</body>
</html>