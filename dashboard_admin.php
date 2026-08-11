<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';
require 'budget_helpers.php';

$totalClients = $pdo->query("SELECT COUNT(*) FROM client")->fetchColumn();

$availmentPerMonth = $pdo->query("SELECT COUNT(*) FROM availment 
  WHERE MONTH(av_date_applied) = MONTH(CURDATE()) AND YEAR(av_date_applied) = YEAR(CURDATE())")->fetchColumn();

$allProgramBudget = getAllProgramBudgets($pdo);

$totalBudget = 0;
$totalSpent = 0;
foreach ($allProgramBudget as $program) {
  $totalBudget += $program['total'];
  $totalSpent += $program['spent'];
}

$budgetAlert = 0;
foreach ($allProgramBudget as $program) {
  $total = (float) $program['total'];
  $remaining = (float) $program['remaining'];
  if ($total > 0 && $remaining < ($total * 0.10)) {
    $budgetAlert++;
  }
}

// Icons used by the dashboard
$programIcons = [
  'AICS FBML' => 'fa-hand-holding-heart',
  'AICS Educational' => 'fa-graduation-cap',
  '4Ps' => 'fa-home',
  'SLP' => 'fa-seedling',
  'SFP' => 'fa-utensils',
  'Day Care' => 'fa-child',
  'Senior Citizen' => 'fa-user-friends',
  'PWD' => 'fa-wheelchair',
  'Solo Parents' => 'fa-user-shield',
  'Women and Children' => 'fa-people-roof',
];


// Prepare data specifically for the dashboard 
$dashboardPrograms = [];

foreach ($allProgramBudget as $program) {

  $beneficiaryStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT client_id)
        FROM AVAILMENT
        WHERE program_id = ?
          AND av_status IN ('Approved', 'Released')
    ");

  $beneficiaryStmt->execute([
    $program['program_id']
  ]);

  $beneficiaries = (int) $beneficiaryStmt->fetchColumn();

  $dashboardPrograms[] = [
    'name' => $program['program_name'],
    'cycle' => $program['prog_period'],
    'icon' => $programIcons[$program['program_name']] ?? 'fa-folder',
    'pct' => (float) $program['pct_used'],
    'spent' => (float) $program['spent'],
    'remaining' => (float) $program['remaining'],
    'beneficiaries' => $beneficiaries
  ];
}

// Approved applications waiting for release 
$approved = $pdo->query("
    SELECT
        a.availment_id,
        a.av_amount,
        a.av_date_applied,
        a.av_date_approved,

        c.cl_firstname,
        c.cl_lastname,

        p.program_name

    FROM AVAILMENT a

    INNER JOIN CLIENT c
        ON c.client_id = a.client_id

    INNER JOIN PROGRAM p
        ON p.program_id = a.program_id

    WHERE a.av_status = 'Approved'

    ORDER BY
        COALESCE(a.av_date_approved, a.av_date_applied) DESC

    LIMIT 5
");

$approvedApplications = $approved->fetchAll(PDO::FETCH_ASSOC);

// Monthly Spending - shows the current 4 month period (Jan-Apr...)
$currentMonth = (int) date('n');

// to know which 4-month period we're in
if ($currentMonth <= 4) {
  $startMonth = 1;
  $endMonth = 4;
} elseif ($currentMonth <= 8) {
  $startMonth = 5;
  $endMonth = 8;
} else {
  $startMonth = 9;
  $endMonth = 12;
}

// to get the names of the months
$startDate = new DateTime(date('Y') . '-' . str_pad($startMonth, 2, '0', STR_PAD_LEFT) . '-01');
$endDate = new DateTime(date('Y') . '-' . str_pad($endMonth, 2, '0', STR_PAD_LEFT) . '-01');

$startDateFormat = $startDate->format('M');
$endDateFormat = $endDate->format('M');

// get the current year and 4 month period released spendings
$monthlySpendingStmt = $pdo->prepare("
    SELECT
        MONTH(av_date_released) AS month_number,
        COALESCE(SUM(av_amount), 0) AS total_spent

    FROM AVAILMENT

    WHERE av_status = 'Released'
      AND av_date_released IS NOT NULL
      AND YEAR(av_date_released) = YEAR(CURDATE())
      AND MONTH(av_date_released) BETWEEN ? AND ?

    GROUP BY MONTH(av_date_released)

    ORDER BY MONTH(av_date_released)
");

$monthlySpendingStmt->execute([
  $startMonth,
  $endMonth
]);

$monthlySpendingRows = $monthlySpendingStmt->fetchAll(PDO::FETCH_ASSOC);

$monthlySpending = []; //so they get 0 as default
for (
  $month = $startMonth;
  $month <= $endMonth;
  $month++
) {
  $monthlySpending[$month] = 0;
}

foreach ($monthlySpendingRows as $row) {

  $month = (int) $row['month_number'];

  $monthlySpending[$month] =
    (float) $row['total_spent'];
}

// the highest spending month will be 100%
$highestMonthlySpending = max($monthlySpending);

// This will be used in the frontend to set the height of each bar in the monthly spending chart.
$monthlySpendingBars = [];
foreach ($monthlySpending as $month => $amount) {
  if ($highestMonthlySpending > 0) {
    $percentage =
      ($amount / $highestMonthlySpending) * 100;
  } else {
    $percentage = 0;
  }
  $monthlySpendingBars[$month] = [
    'amount' => $amount,
    'percentage' => round($percentage)
  ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes" />
  <title>MSWDO Admin Dashboard – San Enrique</title>
  <script src="https://cdn.tailwindcss.com">
  </script>
  <link
    href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['DM Sans', 'sans-serif'],
            serif: ['DM Serif Display', 'serif'],
          },
          colors: {
            forest: {
              DEFAULT: '#0F3D2E',
              50: '#EAF4EE',
              100: '#CFE8DA',
              200: '#A3D2B6',
              300: '#78BC93',
              400: '#3F9A68',
              500: '#1F7A4D',
              600: '#0F3D2E',
              700: '#0C3125',
              800: '#082019',
              900: '#04120E',
            },
            lime: {
              DEFAULT: '#8A9A3A',
              50: '#F6F8EC',
              100: '#E9EECB',
              200: '#D3DE9C',
              300: '#BCC96D',
              400: '#A5B44A',
              500: '#8A9A3A',
              600: '#6D7A2D',
            },
            slate2: '#F2F8F4',
          },
          keyframes: {
            fadeUp: {
              '0%': { opacity: '0', transform: 'translateY(12px)' }, '100%': {
                opacity: '1',
                transform: 'translateY(0)'
              }
            },
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
      background-color: #F2F8F4;
    }

    .stat-card {
      transition: transform .2s ease, box-shadow .2s ease;
    }

    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(15, 61, 46, .12);
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
      background: #F6FAF7;
    }

    .util-row {
      transition: background .12s;
      border-radius: .75rem;
    }

    .util-row:hover {
      background: #F6FAF7;
    }

    .rank-badge {
      font-variant-numeric: tabular-nums;
    }

    .scroll-thin::-webkit-scrollbar {
      width: 6px;
    }

    .scroll-thin::-webkit-scrollbar-track {
      background: transparent;
    }

    .scroll-thin::-webkit-scrollbar-thumb {
      background: #CFE8DA;
      border-radius: 999px;
    }

    @media (max-width: 640px) {
      .mobile-stack {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        width: 100%;
      }
    }

    .sidebar-card {
      display: flex;
      flex-direction: column;
      flex: 1;
    }

    .sidebar-card .card-body {
      flex: 1;
    }

    .activity-card {
      display: flex;
      flex-direction: column;
      min-height: 100%;
    }

    .activity-card .activity-scroll {
      flex: 1;
      overflow-y: auto;
    }

    .row-equal-height {
      align-items: stretch;
    }

    .row-equal-height>div {
      display: flex;
      flex-direction: column;
    }

    .row-equal-height>div>div {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .row-equal-height .util-rank-card {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .row-equal-height .util-rank-card .rank-body {
      flex: 1;
    }

    .row-equal-height .activity-wrapper {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .row-equal-height .activity-wrapper .activity-inner {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .row-equal-height .activity-wrapper .activity-scroll-wrap {
      flex: 1;
      overflow-y: auto;
    }
  </style>
</head>

<body class="bg-slate2 min-h-screen flex flex-col md:flex-row">
  <?php require 'sidebar.php'; ?>
  <!-- Mobile header -->
  <div class="md:hidden bg-forest-600 text-white p-3 flex items-center justify-between">
    <span class="font-serif text-xl">MSWDO</span>
    <button class="text-white"><i class="fas fa-bars text-xl"></i></button>
  </div>
  <!--  MAIN  -->
  <div class="md:ml-64 flex-1 flex flex-col min-h-screen w-full">
    <!-- Top Bar -->
    <header
      class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-4 md:px-6 sticky top-0 z-20 animate-fade-up flex-wrap gap-y-2">
      <div class="flex items-center gap-2 md:gap-3 flex-wrap">
        <h1 class="text-[15px] font-semibold text-forest-600">Dashboard</h1>
        <span
          class="bg-green-100 text-green-700 text-[11px] font-semibold px-3 py-0.5 rounded-full">Administrator</span>
        <span class="text-slate-400 text-xs hidden sm:inline-block" id="currentDate"></span>
      </div>
    </header>
    <!-- Content -->
    <main class="flex-1 p-3 md:p-6 space-y-4 md:space-y-5 overflow-y-auto">
      <!-- ── STAT CARDS ── -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4">
        <div
          class="stat-card animate-fade-up-1 bg-white rounded-2xl border border-slate-200 p-3 md:p-4 relative overflow-hidden">
          <div class="absolute top-0 left-0 right-0 h-0.5 bg-forest-400 rounded-t-2xl"></div>
          <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-1">Total Clients</p>
          <p class="text-2xl md:text-3xl font-semibold text-forest-600 leading-none"><?php echo $totalClients; ?></p>
          <div class="absolute right-2 top-2 text-xl md:text-2xl opacity-20 text-forest-500"><i
              class="fas fa-users"></i></div>
        </div>
        <div
          class="stat-card animate-fade-up-2 bg-white rounded-2xl border border-slate-200 p-3 md:p-4 relative overflow-hidden">
          <div class="absolute top-0 left-0 right-0 h-0.5 bg-emerald-500 rounded-t-2xl"></div>
          <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-1">Avail/Month</p>
          <p class="text-2xl md:text-3xl font-semibold text-forest-600 leading-none"><?php echo $availmentPerMonth; ?>
          </p>
          <div class="absolute right-2 top-2 text-xl md:text-2xl opacity-20 text-forest-500"><i
              class="fas fa-clipboard-list"></i></div>
        </div>
        <div
          class="stat-card animate-fade-up-4 bg-white rounded-2xl border border-slate-200 p-3 md:p-4 relative overflow-hidden">
          <div class="absolute top-0 left-0 right-0 h-0.5 bg-forest-600 rounded-t-2xl"></div>
          <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-1">Budget Left</p>
          <p class="text-2xl md:text-3xl font-semibold text-forest-600 leading-none">
            ₱<?php echo number_format($totalBudget - $totalSpent, 2); ?></p>
        </div>
        <div
          class="stat-card animate-fade-up-5 bg-white rounded-2xl border border-red-100 p-3 md:p-4 relative overflow-hidden cursor-pointer">
          <div class="absolute top-0 left-0 right-0 h-0.5 bg-red-500 rounded-t-2xl"></div>
          <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-1">Budget Alerts</p>
          <p class="text-2xl md:text-3xl font-semibold text-red-500 leading-none"><?php echo $budgetAlert; ?></p>
        </div>
      </div>
      <!-- ── QUICK ACTIONS ── -->
      <div class="animate-fade-up grid grid-cols-2 sm:grid-cols-4 gap-2 md:gap-3">
        <a href="clientregistrationform.php" class="block w-full">
          <button
            class="btn-action bg-white border border-slate-200 hover:border-forest-400 rounded-xl px-3 py-3 flex items-center gap-2 text-left group w-full transition-all duration-200 hover:shadow-md">
            <div
              class="w-8 h-8 rounded-lg bg-forest-50 flex items-center justify-center text-forest-600 group-hover:bg-forest-100 flex-shrink-0">
              <i class="fas fa-user-plus text-sm"></i>
            </div>
            <span class="text-[11px] md:text-[12px] font-medium text-slate-700">Register Client</span>
          </button>
        </a>

        <a href="aics.php" class="block w-full">
          <button
            class="btn-action bg-white border border-slate-200 hover:border-forest-400 rounded-xl px-3 py-3 flex items-center gap-2 text-left group w-full transition-all duration-200 hover:shadow-md">
            <div
              class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 flex-shrink-0">
              <i class="fas fa-clipboard-list text-sm"></i>
            </div>
            <span class="text-[11px] md:text-[12px] font-medium text-slate-700">New AICS Availment</span>
          </button>
        </a>

        <a href="fund_request_reports.php" class="block w-full">
          <button
            class="btn-action bg-white border border-slate-200 hover:border-forest-400 rounded-xl px-3 py-3 flex items-center gap-2 text-left group w-full transition-all duration-200 hover:shadow-md">
            <div class="w-8 h-8 rounded-lg bg-lime-50 flex items-center justify-center text-lime-600 flex-shrink-0">
              <i class="fas fa-file-alt text-sm"></i>
            </div>
            <span class="text-[11px] md:text-[12px] font-medium text-slate-700">Generate Report</span>
          </button>
        </a>

        <a href="budgetmanagement.php" class="block w-full">
          <button
            class="btn-action bg-white border border-slate-200 hover:border-forest-400 rounded-xl px-3 py-3 flex items-center gap-2 text-left group w-full transition-all duration-200 hover:shadow-md">
            <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center text-red-500 flex-shrink-0">
              <i class="fas fa-coins text-sm"></i>
            </div>
            <span class="text-[11px] md:text-[12px] font-medium text-slate-700">Budget Status</span>
          </button>
        </a>
      </div>

      <!-- ── ROW 1: Budget Utilization (3/4) + Pending Approvals & Monthly Spending (1/4) ── -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 md:gap-5 items-stretch">
        <!-- Budget Utilization -->
        <div class="animate-fade-up lg:col-span-3 bg-white rounded-2xl border border-slate-200 p-5 md:p-6">
          <div class="flex items-center justify-between mb-2">
            <div>
              <h2 class="text-[13px] font-semibold text-forest-600">Budget Summary</h2>
              <p class="text-[11px] text-slate-400">FY <?= date('Y') ?> · all <?= count($dashboardPrograms) ?> MSWDO programs
              </p>
            </div>
            <a href="budgetmanagement.php" class="text-[11px] text-forest-500 font-medium">Manage Budget →</a>
          </div>
          <div class="mt-2 space-y-2" id="budgetBars"></div>
          <div class="flex flex-wrap items-center gap-3 mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500">
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>OK</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400"></span>Low</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500"></span>Critical</span>
          </div>
        </div>
        <!-- Sidebar: Pending Approvals + Monthly Spending, 1/4 width  -->
        <div class="flex flex-col gap-4 md:gap-5">
          <div class="animate-fade-up bg-white rounded-2xl border border-slate-200 p-4 md:p-5 flex-1">
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-[13px] font-semibold text-forest-600">Approved / For Release</h2>
              <a href="pending_approvals.php" class="text-[11px] text-forest-500 font-medium">View all →</a>
            </div>
            <p class="text-[11px] text-slate-400">
              Applications approved and waiting for release
            </p>
            <div class="space-y-2.5">
              <?php if (empty($approvedApplications)): ?>

                <div class="py-8 text-center text-slate-400">
                  <i class="fa-solid fa-circle-check text-2xl mb-2"></i>

                  <p class="text-sm">
                    No applications are waiting for release.
                  </p>
                </div>

              <?php else: ?>

                <div class="divide-y divide-slate-100">

                  <?php foreach ($approvedApplications as $application): ?>

                    <div class="flex items-center justify-between py-3">

                      <div class="flex items-center gap-3">

                        <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center">
                          <i class="fa-solid fa-clock text-amber-500"></i>
                        </div>

                        <div>

                          <p class="text-sm font-medium text-slate-700">
                            <?= htmlspecialchars($application['program_name']) ?>
                          </p>

                          <p class="text-[11px] text-slate-400">
                            <?= htmlspecialchars(
                              $application['cl_firstname'] . ' ' .
                              $application['cl_lastname']
                            ) ?>
                          </p>

                        </div>

                      </div>

                      <div class="text-right">

                        <p class="text-sm font-semibold text-slate-700">
                          ₱
                          <?= number_format(
                            (float) $application['av_amount'],
                            2
                          ) ?>
                        </p>

                        <p class="text-[10px] text-amber-500 font-medium">
                          Approved
                        </p>

                      </div>

                    </div>

                  <?php endforeach; ?>

                </div>

              <?php endif; ?>
            </div>
          </div>
          <div class="animate-fade-up bg-white rounded-2xl border border-slate-200 p-4 md:p-5">
            <h2 class="text-[13px] font-semibold text-forest-600 mb-1">Monthly Spending</h2>
            <p class="text-[11px] text-slate-400">
              <?= $startDateFormat ?> - <?= $endDateFormat ?> <?= date('Y') ?>
            </p>
            <div class="flex items-end justify-between gap-3 h-32">
              <?php foreach ($monthlySpendingBars as $month => $data): ?>
                <?php
                $monthDate = new DateTime(
                  date('Y') . '-' .
                  str_pad($month, 2, '0', STR_PAD_LEFT) . '-01'
                );
                $monthLabel = $monthDate->format('M');
                ?>
                <div class="flex-1 flex flex-col items-center gap-2">
                  <div class="w-full h-24 flex items-end">
                    <div class="w-full rounded-t bg-green-500" style="height: <?= $data['percentage'] ?>%;"
                      title="<?= $monthLabel ?>: ₱<?= number_format($data['amount'], 2) ?>"></div>
                  </div>
                  <span class="text-[10px] text-slate-400">
                    <?= $monthLabel ?>
                  </span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- ── ROW 2: Program Utilization (3/4) + Recent Activity (1/4) -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 md:gap-5 items-stretch">
        <!-- Program Utilization -->
        <div
          class="animate-fade-up lg:col-span-3 bg-white rounded-2xl border border-slate-200 p-5 md:p-6 flex flex-col">
          <div class="flex items-center justify-between mb-2">
            <div>
              <h2 class="text-[13px] font-semibold text-forest-600">Program Utilization</h2>
              <p class="text-[11px] text-slate-400">All <?= count($dashboardPrograms) ?> programs · highest first</p>
            </div>
            <span class="text-[11px] font-semibold text-red-500 bg-red-50 px-2.5 py-1 rounded-full"
              id="highUtilCount"></span>
          </div>
          <div class="mt-2 space-y-2 flex-1" id="highUtilRows"></div>
        </div>
        <!-- Recent Activity -->
        <div class="animate-fade-up bg-white rounded-2xl border border-slate-200 flex flex-col overflow-hidden">
          <div class="flex justify-between items-center px-4 py-3 border-b border-slate-200">
            <h2 class="text-[13px] font-semibold text-forest-600">Recent Activity</h2>
            <a href="#" class="text-[11px] text-forest-500 font-medium">All →</a>
          </div>
          <div class="divide-y divide-slate-100 flex-1 overflow-y-auto scroll-thin">
            <div class="activity-row flex items-start gap-2 px-4 py-3">
              <div
                class="w-6 h-6 rounded-full bg-forest-100 text-forest-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-user-plus"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[12px] leading-snug text-slate-700">Maria Santos registered</p>
                <p class="text-[10px] text-slate-400">2 mins ago</p>
              </div>
            </div>
            <div class="activity-row flex items-start gap-2 px-4 py-3">
              <div
                class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-hand-holding-heart"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[12px] leading-snug text-slate-700">AICS FBML approved · ₱3,500</p>
                <p class="text-[10px] text-slate-400">15 mins ago</p>
              </div>
            </div>
            <div class="activity-row flex items-start gap-2 px-4 py-3">
              <div
                class="w-6 h-6 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-wheelchair"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[12px] leading-snug text-slate-700">PWD ID issued · Rodrigo Lim</p>
                <p class="text-[10px] text-slate-400">1 hour ago</p>
              </div>
            </div>
            <div class="activity-row flex items-start gap-2 px-4 py-3">
              <div
                class="w-6 h-6 rounded-full bg-lime-100 text-lime-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-user-shield"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[12px] leading-snug text-slate-700">Solo Parent · Luz Bautista</p>
                <p class="text-[10px] text-slate-400">2 hrs ago</p>
              </div>
            </div>
            <div class="activity-row flex items-start gap-2 px-4 py-3">
              <div
                class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-coins"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[12px] leading-snug text-slate-700">Budget adjusted for SLP · ₱15,000</p>
                <p class="text-[10px] text-slate-400">3 hrs ago</p>
              </div>
            </div>
            <div class="activity-row flex items-start gap-2 px-4 py-3">
              <div
                class="w-6 h-6 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-file-signature"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[12px] leading-snug text-slate-700">New program proposal submitted</p>
                <p class="text-[10px] text-slate-400">5 hrs ago</p>
              </div>
            </div>
            <div class="activity-row flex items-start gap-2 px-4 py-3">
              <div
                class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-user-edit"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[12px] leading-snug text-slate-700">Beneficiary records updated · 12 clients</p>
                <p class="text-[10px] text-slate-400">6 hrs ago</p>
              </div>
            </div>
            <div class="activity-row flex items-start gap-2 px-4 py-3">
              <div
                class="w-6 h-6 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-paper-plane"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[12px] leading-snug text-slate-700">Approval request sent to Mayor</p>
                <p class="text-[10px] text-slate-400">8 hrs ago</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
    <footer
      class="border-t border-slate-200 bg-white px-4 py-3 flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-400 gap-1">
      <span>MSWDO San Enrique Information System</span>
    </footer>
  </div>
  <script>
    const dateSpan = document.getElementById('currentDate');
    if (dateSpan) {
      const today = new Date();
      dateSpan.textContent = today.toLocaleDateString('en-US', {
        weekday: 'long', year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    }
  </script>
  <script>
    // ── All 10 MSWDO programs ──
    const programs = <?= json_encode(
      $dashboardPrograms,
      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) ?>;
    const peso = n => '₱' + n.toLocaleString('en-PH');
    const barColor = p => p >= 80 ? 'bg-red-500' : p >= 60 ? 'bg-amber-400' : 'bg-emerald-500';
    const pctColor = p => p >= 80 ? 'text-red-500' : p >= 60 ? 'text-amber-500' : 'text-emerald-600';
    const cycleStyle = c => c === 'Quarterly' ? 'text-blue-600 bg-blue-50' : c === 'Half-year' ?
      'text-teal-600 bg-teal-50' :
      'text-purple-600 bg-purple-50';



    // ── Budget Utilization ──
    const barsContainer = document.getElementById('budgetBars');
    programs.forEach((p) => {
      barsContainer.innerHTML += `
                                <div class="util-row px-3 py-2">
                                    <div class="flex items-center justify-between mb-1 flex-wrap gap-x-2 gap-y-0.5">
                                        <span class="text-[12px] font-medium text-slate-700 flex items-center gap-1.5">
                                            <i class="fas ${p.icon} text-forest-400 text-[11px]"></i>${p.name}
                                            <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full ${cycleStyle(p.cycle)}">${p.cycle}</span>
                                        </span>
                                        <span class="flex items-center gap-1.5 text-[11px]">
                                            <span class="text-forest-600 font-semibold"><i class="fas fa-user-group text-[10px] mr-1 opacity-60"></i>${p.beneficiaries}</span>
                                            <span class="${pctColor(p.pct)} font-bold w-8 text-right">${p.pct}%</span>
                                        </span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                        <div class="prog-bar-fill h-2 rounded-full ${barColor(p.pct)}" style="width:0%" data-target="${p.pct}%"></div>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-1">${peso(p.remaining)} left of ${peso(p.spent + p.remaining)}</p>
                                </div>`;
    });

    // ── Program Utilization — 
    const ranked = [...programs].sort((a, b) => b.pct - a.pct);
    document.getElementById('highUtilCount').textContent = `${ranked.filter(p => p.pct >= 60).length} at ≥60%`;
    const rowsContainer = document.getElementById('highUtilRows');
    ranked.forEach((p, i) => {
      let insight, insightClass;
      if (p.pct >= 60 && p.beneficiaries < 30) {
        insight = 'High cost, low reach';
        insightClass = 'text-red-600 bg-red-50';
      } else if (p.pct >= 60 && p.beneficiaries >= 60) {
        insight = 'High utilization, broad reach';
        insightClass = 'text-emerald-600 bg-emerald-50';
      } else if (p.pct >= 60) {
        insight = 'Watch cost';
        insightClass = 'text-amber-600 bg-amber-50';
      } else if (p.beneficiaries >= 60) {
        insight = 'Low utilization, broad reach';
        insightClass = 'text-forest-600 bg-forest-50';
      } else {
        insight = 'Tracking';
        insightClass = 'text-slate-500 bg-slate-100';
      }
      rowsContainer.innerHTML += `
        <div class="util-row flex items-center gap-2 px-3 py-2 flex-wrap sm:flex-nowrap">
            <span class="rank-badge w-5 text-[11px] font-bold text-slate-300">${i + 1}</span>
            <div class="flex-1 min-w-0">
                <div class="flex justify-between items-center mb-1 flex-wrap gap-x-2 gap-y-0.5">
                    <span class="text-[12px] font-semibold text-slate-700 flex items-center gap-1.5">
                        <i class="fas ${p.icon} text-forest-400 text-[11px]"></i>${p.name}
                        <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full ${cycleStyle(p.cycle)}">${p.cycle}</span>
                    </span>
                    <span class="text-forest-600 font-semibold text-[11px] flex items-center gap-1">
                        <i class="fas fa-user-group text-[10px] opacity-60"></i>
                        ${p.beneficiaries}
                    </span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden mb-1">
                    <div class="prog-bar-fill h-2 rounded-full ${barColor(p.pct)}" style="width:0%" data-target="${p.pct}%"></div>
                </div>
                <span class="text-[9px] px-1.5 py-0.5 rounded-full ${insightClass}">${insight}</span>
            </div>
        </div>`;
    });

    // animate bars after render
    requestAnimationFrame(() => {
      setTimeout(() => {
        document.querySelectorAll('.prog-bar-fill').forEach(el => el.style.width = el
          .dataset.target);
      }, 200);
    });
  </script>
</body>

</html>