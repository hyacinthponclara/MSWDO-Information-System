<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
$role = $_SESSION['user_role'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

<style type="text/tailwindcss">
    @theme {
        /* Custom MSWDO Dark Green */
        --color-mswdo-green: #0B3D2E;
        --color-mswdo-green-dark: #073326;
        --color-mswdo-green-light: #145A43;

        /* Green palette */
        --color-green-50: #f0fdf4;
        --color-green-100: #dcfce7;
        --color-green-200: #bbf7d0;
        --color-green-300: #86efac;
        --color-green-400: #4ade80;
        --color-green-500: #22c55e;
        --color-green-600: #16a34a;
        --color-green-700: #15803d;
        --color-green-800: #166534;
        --color-green-900: #14532d;

        /* Gold */
        --color-gold: #C49A2A;
        --color-gold-50: #FBF5E6;
        --color-gold-100: #F5E4B3;
        --color-gold-200: #EDD07A;
        --color-gold-300: #E4BC3F;
        --color-gold-400: #C49A2A;
        --color-gold-500: #9E7A1F;
        --color-gold-600: #795C16;

        /* Other colors */
        --color-slate2: #F4F7FC;

        /* Fonts */
        --font-sans: 'DM Sans', sans-serif;
        --font-serif: 'DM Serif Display', serif;
    }
</style>

<style>
    #sidebar summary {
        list-style: none;
        cursor: pointer;
    }

    #sidebar summary::-webkit-details-marker {
        display: none;
    }
</style>

<aside id="sidebar" class="fixed top-0 left-0 w-64 h-screen flex flex-col overflow-y-auto z-50 bg-mswdo-green">

    <!-- Logo -->
    <div class="px-5 pt-5 pb-4 border-b border-white/10">
        <div
            class="w-9 h-9 rounded-full bg-gold-400 flex items-center justify-center text-green-600 text-lg font-serif font-bold mb-3">
            M</div>
        <p class="font-serif text-white text-sm leading-snug">MSWDO San Enrique</p>
        <p class="text-white/40 text-[10px] mt-0.5 tracking-wide">Information System</p>
    </div>


    <!-- access by head social worker/admin -->
    <?php if ($role === 'Admin'): ?>
        <a href="dashboard_admin.php"
            class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/70 text-sm <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie text-sm"></i> Dashboard
        </a>
        <a href="clientslist.php"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent <?= $current_page == 'clientslist.php' ? 'active' : '' ?>">
            <i class="fas fa-users text-sm"></i> Clients
        </a>
        <a href="requestfund.php"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent <?= $current_page == 'barangaylist.php' ? 'active' : '' ?>">
            <i class="fas fa-list text-sm"></i> Fund Request
        </a>
        <!-- <a href="casestudy.php"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent <?= $current_page == 'casestudy.php' ? 'active' : '' ?>">
            <i class="fas fa-book text-sm"></i> Case Study
        </a>
 -->
        <p class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-widest text-white/30 font-medium">Confidential</p>
        <a href="confidential.php"
            class="sidebar-item flex items-center justify-between px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <span class="flex items-center gap-2.5"><i class="fas fa-lock text-sm"></i> Confidential Case</span>
        </a>
        <a href="confidential_case_search.php"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-search text-sm"></i> Case List
        </a>


        <!-- Programs -->
        <details class="group">
            <summary
                class="flex items-center justify-between px-3 pt-3 pb-1 text-[10px] uppercase tracking-widest text-white/30 font-medium cursor-pointer select-none">

                <span>Programs</span>

                <i class="fas fa-chevron-down text-[8px] transition-transform duration-200 group-open:rotate-180"></i>
            </summary>

            <div class="mt-1">
                <a href="funds_aics.php"
                    class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
                    <i class="fas fa-capsules text-sm"></i>
                    AICS
                </a>

                <a href="funds_4ps.php"
                    class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
                    <i class="fas fa-home text-sm"></i>
                    4Ps
                </a>

                <a href="funds_slp.php"
                    class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
                    <i class="fas fa-briefcase text-sm"></i>
                    SLP
                </a>

                <a href="funds_sfp.php"
                    class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
                    <i class="fas fa-utensils text-sm"></i>
                    SFP
                </a>

                <a href="funds_daycare.php"
                    class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
                    <i class="fas fa-school text-sm"></i>
                    Day Care
                </a>

                <a href="funds_senior.php"
                    class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
                    <i class="fas fa-user-friends text-sm"></i>
                    Senior Citizen
                </a>

                <a href="funds_pwd.php"
                    class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
                    <i class="fas fa-wheelchair text-sm"></i>
                    PWD
                </a>

                <a href="funds_soloparents.php"
                    class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
                    <i class="fas fa-user text-sm"></i>
                    Solo Parent
                </a>

                <a href="funds_wac.php"
                    class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
                    <i class="fas fa-lock text-sm"></i>
                    Women &amp; Child
                </a>
            </div>
        </details>

        <p class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-widest text-white/30 font-medium">Reports</p>
        <a href="fund_request_reports.php"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-file-alt text-sm"></i> Reports</a>
        <a href="geographic_analysis.php"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-map-marked-alt text-sm"></i> Geographic</a>
        <a href="budgetmanagement.php"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-money-bill text-sm"></i> Budget Management</a>
        <a href="fiscalyear.php"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-money-bill text-sm"></i> Fiscal Year</a>
        </nav>

        <p class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-widest text-white/30 font-medium">Administration</p>
        <a href="usermanagement.php"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-user-gear text-sm"></i> User Management
        </a>
        <a href="accountdetails.php"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-file-alt text-sm"></i> Account Details
        </a>
        <a href="activitylogs.php"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-file-alt text-sm"></i> Activity Logs
        </a>
        </nav>

        <!-- User footer -->
        <div class="px-4 py-3 border-t border-white/10 flex items-center gap-3">
            <div
                class="w-8 h-8 rounded-full bg-violet-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                <?= strtoupper(substr($_SESSION['user_firstname'] ?? 'S', 0, 1) . substr($_SESSION['user_lastname'] ?? 'W', 0, 1)) ?>
            </div>
            <div class="min-w-0">
                <p class="text-white text-xs font-medium truncate">
                    <?= htmlspecialchars(($_SESSION['user_firstname'] ?? '') . ' ' . ($_SESSION['user_lastname'] ?? '')) ?>
                </p>
                <p class="text-violet-300/60 text-[10px]"><?= htmlspecialchars($_SESSION['user_role'] ?? 'Social Worker') ?>
                </p>
            </div>
            <a href="logout.php"
                class="ml-auto text-white/50 hover:text-white text-[12px] font-medium transition-colors px-2 py-1 rounded hover:bg-white/10">Logout</a>
        </div>

    <?php endif; ?>

    <!-- access by staff -->
    <?php if ($role === 'Staff'): ?>
        <a href="dashboard_staff.php"
            class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/70 text-sm <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie text-sm"></i> Dashboard
        </a>
        <a href="clientslist.php"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent <?= $current_page == 'clientslist.php' ? 'active' : '' ?>">
            <i class="fas fa-users text-sm"></i> Clients
        </a>
        <a href="requestfund.php"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent <?= $current_page == 'barangaylist.php' ? 'active' : '' ?>">
            <i class="fas fa-list text-sm"></i> Fund Request
        </a>

        <details class="group" open>
            <summary
                class="flex items-center justify-between px-3 pt-3 pb-1 text-[10px] uppercase tracking-widest text-white/30 font-medium">
                <span>Program</span>
                <i class="fas fa-chevron-down text-[8px] transition-transform duration-200 group-open:rotate-180"></i>
            </summary>
            <a href="funds_aics.php"
                class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                    class="fas fa-capsules text-sm"></i> AICS</a>
            <a href="funds_4ps.php"
                class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                    class="fas fa-home text-sm"></i> 4Ps</a>
            <a href="funds_slp.php"
                class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                    class="fas fa-briefcase text-sm"></i> SLP</a>
            <a href="funds_sfp.php"
                class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                    class="fas fa-utensils text-sm"></i> SFP</a>
            <a href="funds_daycare.php"
                class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                    class="fas fa-school text-sm"></i> Day Care</a>
            <a href="funds_senior.php"
                class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                    class="fas fa-user-friends text-sm"></i> Senior Citizen</a>
            <a href="funds_pwd.php"
                class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                    class="fas fa-wheelchair text-sm"></i> PWD</a>
            <a href="funds_soloparents.php"
                class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                    class="fas fa-user text-sm"></i> Solo Parent</a>
            </a>
            <a href="funds_wac.php"
                class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
                <i class="fas fa-lock text-sm"></i> Women &amp; Child
            </a>

        </details>

        <p class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-widest text-white/30 font-medium">Reports</p>
        <a href="fund_request_reports.php"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-file-alt text-sm"></i> Reports</a>
        <a href="accountdetails.php"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-file-alt text-sm"></i> Account Details
        </a>
        </nav>

        <!-- User footer -->
        <div class="px-4 py-3 border-t border-white/10 flex items-center gap-3">
            <div
                class="w-8 h-8 rounded-full bg-teal-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                <?= strtoupper(substr($_SESSION['user_firstname'] ?? 'S', 0, 1) . substr($_SESSION['user_lastname'] ?? 'T', 0, 1)) ?>
            </div>
            <div class="min-w-0">
                <p class="text-white text-xs font-medium truncate">
                    <?= htmlspecialchars(($_SESSION['user_firstname'] ?? '') . ' ' . ($_SESSION['user_lastname'] ?? '')) ?>
                </p>
                <p class="text-white/40 text-[10px]"><?= htmlspecialchars($_SESSION['user_role'] ?? 'Staff') ?></p>
            </div>
            <a href="logout.php"
                class="ml-auto text-white/50 hover:text-white text-[12px] font-medium transition-colors px-2 py-1 rounded hover:bg-white/10">Logout</a>
        </div>
    <?php endif; ?>

    </nav>

</aside>