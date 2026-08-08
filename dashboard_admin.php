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
  <!-- Sidebar (desktop) -->
  <?php require 'sidebar.php'; ?>
  <!-- Mobile header -->
  <div class="md:hidden bg-forest-600 text-white p-3 flex items-center justify-between">
    <span class="font-serif text-xl">MSWDO</span>
    <button class="text-white"><i class="fas fa-bars text-xl"></i></button>
  </div>
  <!-- ═══════════ MAIN ═══════════ -->
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
          <p class="text-2xl md:text-3xl font-semibold text-forest-600 leading-none">1,284</p>
          <div class="absolute right-2 top-2 text-xl md:text-2xl opacity-20 text-forest-500"><i
              class="fas fa-users"></i></div>
        </div>
        <div
          class="stat-card animate-fade-up-2 bg-white rounded-2xl border border-slate-200 p-3 md:p-4 relative overflow-hidden">
          <div class="absolute top-0 left-0 right-0 h-0.5 bg-emerald-500 rounded-t-2xl"></div>
          <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-1">Avail/Month</p>
          <p class="text-2xl md:text-3xl font-semibold text-forest-600 leading-none">347</p>
          <div class="absolute right-2 top-2 text-xl md:text-2xl opacity-20 text-forest-500"><i
              class="fas fa-clipboard-list"></i></div>
        </div>
        <div
          class="stat-card animate-fade-up-4 bg-white rounded-2xl border border-slate-200 p-3 md:p-4 relative overflow-hidden">
          <div class="absolute top-0 left-0 right-0 h-0.5 bg-forest-600 rounded-t-2xl"></div>
          <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-1">Budget Left</p>
          <p class="text-2xl md:text-3xl font-semibold text-forest-600 leading-none">₱2.4M</p>
        </div>
        <div
          class="stat-card animate-fade-up-5 bg-white rounded-2xl border border-red-100 p-3 md:p-4 relative overflow-hidden cursor-pointer">
          <div class="absolute top-0 left-0 right-0 h-0.5 bg-red-500 rounded-t-2xl"></div>
          <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-1">Budget Alerts</p>
          <p class="text-2xl md:text-3xl font-semibold text-red-500 leading-none">2</p>
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
              <p class="text-[11px] text-slate-400">FY 2026 · all 9 programs</p>
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
              <h2 class="text-[13px] font-semibold text-forest-600">Pending Approvals</h2>
              <a href="pending_approvals.php" class="text-[11px] text-forest-500 font-medium">View all →</a>
            </div>
            <div class="space-y-2.5">
              <div class="flex gap-2 p-2.5 bg-red-50 border border-red-100 rounded-xl">
                <i class="fas fa-circle text-red-500 text-[7px] mt-1.5"></i>
                <div>
                  <p class="text-[12px] font-semibold text-red-700">AICS FBML Critical</p>
                  <p class="text-[10px] text-red-500">₱28,400 left</p>
                </div>
              </div>
              <div class="flex gap-2 p-2.5 bg-red-50 border border-red-100 rounded-xl">
                <i class="fas fa-circle text-red-500 text-[7px] mt-1.5"></i>
                <div>
                  <p class="text-[12px] font-semibold text-red-700">SLP Critical</p>
                  <p class="text-[10px] text-red-500">₱12,000 left</p>
                </div>
              </div>
              <div class="flex gap-2 p-2.5 bg-amber-50 border border-amber-100 rounded-xl">
                <i class="fas fa-circle text-amber-500 text-[7px] mt-1.5"></i>
                <div>
                  <p class="text-[12px] font-semibold text-amber-700">5 Pending Approvals</p>
                  <p class="text-[10px] text-amber-600">Awaiting Mayor</p>
                </div>
              </div>
              <div class="flex gap-2 p-2.5 bg-forest-50 border border-forest-100 rounded-xl">
                <i class="fas fa-circle text-forest-500 text-[7px] mt-1.5"></i>
                <div>
                  <p class="text-[12px] font-semibold text-forest-700">Q1 Report Due</p>
                  <p class="text-[10px] text-forest-500">Apr 30</p>
                </div>
              </div>
            </div>
          </div>
          <div class="animate-fade-up bg-white rounded-2xl border border-slate-200 p-4 md:p-5">
            <h2 class="text-[13px] font-semibold text-forest-600 mb-1">Monthly Spending</h2>
            <p class="text-[11px] text-slate-400 mb-3">Jan – Apr 2026</p>
            <div class="flex items-end gap-1 h-20">
              <div class="flex flex-col items-center gap-1 flex-1">
                <div class="w-full rounded-t bg-forest-200" style="height:52%"></div><span
                  class="text-[9px] text-slate-400">Jan</span>
              </div>
              <div class="flex flex-col items-center gap-1 flex-1">
                <div class="w-full rounded-t bg-forest-300" style="height:38%"></div><span
                  class="text-[9px] text-slate-400">Feb</span>
              </div>
              <div class="flex flex-col items-center gap-1 flex-1">
                <div class="w-full rounded-t bg-forest-400" style="height:65%"></div><span
                  class="text-[9px] text-slate-400">Mar</span>
              </div>
              <div class="flex flex-col items-center gap-1 flex-1">
                <div class="w-full rounded-t bg-forest-600" style="height:78%"></div><span
                  class="text-[9px] text-slate-400 font-semibold">Apr</span>
              </div>
            </div>
            <p class="text-[10px] text-slate-400 mt-2">Projected: <strong class="text-forest-600">₱2.1M</strong></p>
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
              <p class="text-[11px] text-slate-400">All 10 programs · highest first</p>
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
    const programs = [
      {
        name: 'AICS FBML', cycle: 'Quarterly', icon: 'fa-hand-holding-heart', pct: 88, spent: 211600,
        remaining: 28400, beneficiaries: 128
      },
      {
        name: 'AICS Educational', cycle: 'Quarterly', icon: 'fa-graduation-cap', pct: 65, spent: 130000,
        remaining: 70000, beneficiaries: 96
      },
      {
        name: '4Ps', cycle: 'Annually', icon: 'fa-home', pct: 42, spent: 252000, remaining: 348000,
        beneficiaries: 47
      },
      {
        name: 'SLP', cycle: 'Annually', icon: 'fa-seedling', pct: 92, spent: 138000, remaining: 12000,
        beneficiaries: 22
      },
      {
        name: 'SFP', cycle: 'Quarterly', icon: 'fa-utensils', pct: 35, spent: 70000, remaining: 130000,
        beneficiaries: 65
      },
      {
        name: 'Day Care', cycle: 'Annually', icon: 'fa-child', pct: 55, spent: 110000, remaining: 90000,
        beneficiaries: 40
      },
      {
        name: 'Senior Citizen', cycle: 'Annually', icon: 'fa-user-friends', pct: 71, spent: 213000,
        remaining: 87000, beneficiaries: 74
      },
      {
        name: 'PWD', cycle: 'Half-year', icon: 'fa-wheelchair', pct: 28, spent: 56000,
        remaining: 144000, beneficiaries: 58
      },
      {
        name: 'Solo Parents', cycle: 'Quarterly', icon: 'fa-user-shield', pct: 68, spent: 102000,
        remaining: 48000, beneficiaries: 34
      },
      {
        name: 'Women and Children', cycle: 'Annually', icon: 'fa-people-roof', pct: 47, spent: 94000,
        remaining: 106000, beneficiaries: 51
      },
    ];
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
        insight = 'Low util, broad reach';
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