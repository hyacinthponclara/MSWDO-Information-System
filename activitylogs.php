<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Activity Logs | MSWDO</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        forest: {
                            50: '#eef7f2',
                            100: '#d9eee2',
                            200: '#b8ddc8',
                            300: '#8fc6a9',
                            400: '#62ab87',
                            500: '#3b8d69',
                            600: '#287252',
                            700: '#205b43',
                            800: '#1b4837',
                            900: '#173b2f'
                        },
                        slate2: '#f4f8f6'
                    }
                }
            }
        };
    </script>

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body {
            font-family: Arial, sans-serif;
        }

        .font-serif {
            font-family: Georgia, "Times New Roman", serif;
        }

        .filter-field {
            height: 36px;
            padding: 0 12px;
            border: 1px solid #dbe3ea;
            border-radius: 9px;
            background: #fff;
            color: #334155;
            font-size: 11px;
            outline: none;
            transition: all 0.2s ease;
        }

        .filter-field:focus {
            border-color: #62ab87;
            box-shadow: 0 0 0 2px rgba(98, 171, 135, 0.12);
        }

        .filter-field::placeholder {
            color: #94a3b8;
        }

        select.filter-field {
            cursor: pointer;
        }

        input[type="date"].filter-field {
            color: #64748b;
        }

        .scroll-thin::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        .scroll-thin::-webkit-scrollbar-track {
            background: transparent;
        }

        .scroll-thin::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-up {
            animation: fadeUp 0.35s ease both;
        }

        .animate-fade-up-1 {
            animation: fadeUp 0.35s ease 0.05s both;
        }

        .animate-fade-up-2 {
            animation: fadeUp 0.35s ease 0.1s both;
        }

        .animate-fade-up-3 {
            animation: fadeUp 0.35s ease 0.15s both;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(0.97) translateY(5px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .animate-modal-in {
            animation: modalIn 0.2s ease both;
        }

        @media (max-width: 767px) {

            body.mobile-menu-open {
                overflow: hidden;
            }

            body>aside,
            body aside {
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                bottom: 0 !important;
                width: 270px !important;
                max-width: 85vw !important;
                z-index: 60 !important;
                transform: translateX(-105%);
                transition: transform 0.25s ease;
                box-shadow: 10px 0 30px rgba(15, 23, 42, 0.12);
            }

            body aside.mobile-sidebar-open {
                transform: translateX(0);
            }

            .mobile-sidebar-overlay {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.35);
                z-index: 50;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.25s ease;
            }

            .mobile-sidebar-overlay.active {
                opacity: 1;
                pointer-events: auto;
            }
        }

        @media (min-width: 768px) {
            .mobile-sidebar-overlay {
                display: none !important;
            }
        }

        .mobile-activity-card {
            transition: background 0.2s ease;
        }

        .mobile-activity-card:active {
            background: #f8fafc;
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


        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-4 md:px-6 sticky top-0 z-20">

            <div class="flex items-center gap-2">

                <h1 class="text-[15px] font-semibold text-forest-600">
                    Activity Logs
                </h1>

            </div>

            <div class="hidden sm:flex items-center gap-2 text-[11px] text-slate-400">

                <i class="far fa-calendar"></i>

                <span id="currentDate"></span>

            </div>

        </header>

        <main class="flex-1 p-4 md:p-6 space-y-5 overflow-y-auto">


            <div class="animate-fade-up">

                <h2 class="text-xl font-serif text-forest-600">
                    Activity Logs
                </h2>

                <p class="text-[12px] text-slate-500 mt-1">
                    Monitor recent activities and actions performed within the MSWDO Information System.
                </p>

            </div>

            <!-- SUMMARY CARDS -->

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 animate-fade-up-1">

                <!-- TOTAL ACTIVITIES -->

                <div class="bg-white rounded-2xl border border-slate-200 p-3 md:p-4 relative overflow-hidden">

                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-forest-400"></div>

                    <div class="flex items-center justify-between gap-2">

                        <div class="min-w-0">

                            <p class="text-[9px] md:text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                Total Activities
                            </p>

                            <p id="totalActivities" class="text-xl md:text-2xl font-semibold text-forest-600 mt-1">
                                0
                            </p>

                        </div>

                        <div
                            class="w-8 h-8 md:w-9 md:h-9 rounded-xl bg-forest-50 text-forest-600 flex items-center justify-center flex-shrink-0">

                            <i class="fas fa-list-check text-xs md:text-sm"></i>

                        </div>

                    </div>

                </div>

                <!-- TODAY -->

                <div class="bg-white rounded-2xl border border-slate-200 p-3 md:p-4 relative overflow-hidden">

                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-blue-400"></div>

                    <div class="flex items-center justify-between gap-2">

                        <div class="min-w-0">

                            <p class="text-[9px] md:text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                Today
                            </p>

                            <p id="todayActivities" class="text-xl md:text-2xl font-semibold text-blue-600 mt-1">
                                0
                            </p>

                        </div>

                        <div
                            class="w-8 h-8 md:w-9 md:h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">

                            <i class="fas fa-calendar-day text-xs md:text-sm"></i>

                        </div>

                    </div>

                </div>

                <!-- ADMIN ACTIONS -->

                <div class="bg-white rounded-2xl border border-slate-200 p-3 md:p-4 relative overflow-hidden">

                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-amber-400"></div>

                    <div class="flex items-center justify-between gap-2">

                        <div class="min-w-0">

                            <p class="text-[9px] md:text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                Admin Actions
                            </p>

                            <p id="adminActivities" class="text-xl md:text-2xl font-semibold text-amber-600 mt-1">
                                0
                            </p>

                        </div>

                        <div
                            class="w-8 h-8 md:w-9 md:h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">

                            <i class="fas fa-user-shield text-xs md:text-sm"></i>

                        </div>

                    </div>

                </div>

                <!-- STAFF ACTIONS -->

                <div class="bg-white rounded-2xl border border-slate-200 p-3 md:p-4 relative overflow-hidden">

                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-emerald-400"></div>

                    <div class="flex items-center justify-between gap-2">

                        <div class="min-w-0">

                            <p class="text-[9px] md:text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                Staff Actions
                            </p>

                            <p id="staffActivities" class="text-xl md:text-2xl font-semibold text-emerald-600 mt-1">
                                0
                            </p>

                        </div>

                        <div
                            class="w-8 h-8 md:w-9 md:h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">

                            <i class="fas fa-users text-xs md:text-sm"></i>

                        </div>

                    </div>

                </div>

            </div>

            <!-- =====================================================
           FILTERS
      ====================================================== -->

            <div class="bg-white rounded-2xl border border-slate-200 p-3 md:p-4 animate-fade-up-2">

                <!-- DESKTOP / TABLET FILTER ROW -->

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:items-center gap-2.5 md:gap-3">

                    <!-- SEARCH -->

                    <div class="relative flex-1 min-w-0 lg:min-w-[180px]">

                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">
                        </i>

                        <input id="searchInput" type="text" placeholder="Search activity, user, module..."
                            class="filter-field w-full pl-8" oninput="applyFilters()">

                    </div>

                    <!-- USER -->

                    <select id="userFilter" class="filter-field w-full lg:w-auto" onchange="applyFilters()">

                        <option value="all">
                            All Users
                        </option>

                        <option value="Admin">
                            Admin
                        </option>

                        <option value="Staff">
                            Staff
                        </option>

                    </select>

                    <!-- MODULE -->

                    <select id="moduleFilter" class="filter-field w-full lg:w-auto" onchange="applyFilters()">

                        <option value="all">
                            All Modules
                        </option>

                        <option value="User Management">
                            User Management
                        </option>

                        <option value="Client Records">
                            Client Records
                        </option>

                        <option value="AICS">
                            AICS
                        </option>

                        <option value="Budget">
                            Budget
                        </option>

                        <option value="Reports">
                            Reports
                        </option>

                        <option value="System">
                            System
                        </option>

                    </select>

                    <!-- ACTION -->

                    <select id="actionFilter" class="filter-field w-full lg:w-auto" onchange="applyFilters()">

                        <option value="all">
                            All Actions
                        </option>

                        <option value="Create">
                            Create
                        </option>

                        <option value="Update">
                            Update
                        </option>

                        <option value="Approve">
                            Approve
                        </option>

                        <option value="Login">
                            Login
                        </option>

                        <option value="Submit">
                            Submit
                        </option>

                    </select>

                    <!-- DATE RANGE -->

                    <div class="flex items-center gap-2 w-full lg:w-auto">

                        <input id="dateFrom" type="date" title="From date" class="filter-field w-full lg:w-[135px]"
                            onchange="applyFilters()">

                        <span class="text-[10px] text-slate-400 flex-shrink-0">
                            to
                        </span>

                        <input id="dateTo" type="date" title="To date" class="filter-field w-full lg:w-[135px]"
                            onchange="applyFilters()">

                    </div>

                    <!-- CLEAR -->

                    <button type="button" onclick="clearFilters()"
                        class="h-9 px-4 rounded-lg border border-slate-200 text-[11px] font-medium text-slate-600 hover:bg-slate-50 transition w-full lg:w-auto flex-shrink-0">

                        <i class="fas fa-rotate-left mr-1"></i>

                        Clear

                    </button>

                </div>

                <!-- FILTER RESULT -->

                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mt-3 pt-3 border-t border-slate-100">

                    <span id="resultCount" class="text-[11px] text-slate-400">
                        Showing 0 activities
                    </span>

                    <span class="text-[10px] text-slate-400">

                        <i class="fas fa-circle text-emerald-500 text-[6px] mr-1">
                        </i>

                        Activity monitoring

                    </span>

                </div>

            </div>

            <!--  ACTIVITY LOG TABLE -->

            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-up-3">

                <!-- TABLE HEADER -->

                <div class="px-4 md:px-5 py-3 border-b border-slate-200 flex items-center justify-between">

                    <div>

                        <h2 class="text-[13px] font-semibold text-forest-600">
                            Recent Activity
                        </h2>

                        <p class="text-[10px] text-slate-400 mt-0.5">
                            System actions recorded by users
                        </p>

                    </div>

                </div>

                <!-- DESKTOP TABLE -->

                <div class="hidden md:block overflow-x-auto">

                    <table class="w-full text-[12px]">

                        <thead>

                            <tr class="bg-slate-50 border-b border-slate-100">

                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Date & Time
                                </th>

                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    User
                                </th>

                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Activity
                                </th>

                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Module
                                </th>

                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Action
                                </th>

                                <th
                                    class="text-right px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Details
                                </th>

                            </tr>

                        </thead>

                        <tbody id="activityTable" class="divide-y divide-slate-100">
                        </tbody>

                    </table>

                </div>

                <!-- MOBILE ACTIVITY LIST -->

                <div id="mobileActivityList" class="md:hidden divide-y divide-slate-100">
                </div>

                <!-- EMPTY STATE -->

                <div id="emptyState" class="hidden text-center py-12 px-4">

                    <div
                        class="w-12 h-12 mx-auto rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3">

                        <i class="fas fa-magnifying-glass"></i>

                    </div>

                    <p class="text-[13px] font-medium text-slate-600">
                        No activities found
                    </p>

                    <p class="text-[11px] text-slate-400 mt-1">
                        Try changing your search or filters.
                    </p>

                </div>

                <div
                    class="px-4 md:px-5 py-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2">

                    <span id="paginationInfo" class="text-[10px] text-slate-400">
                        Showing 0
                    </span>

                    <div class="flex items-center gap-1">

                        <button id="prevBtn" onclick="changePage(-1)"
                            class="text-[10px] px-3 py-1.5 border border-slate-200 rounded-lg text-slate-400 hover:bg-slate-50 disabled:opacity-40"
                            disabled>
                            Previous
                        </button>

                        <span id="pageNumber"
                            class="text-[10px] font-medium text-white bg-forest-600 rounded-lg px-3 py-1.5">
                            1
                        </span>

                        <button id="nextBtn" onclick="changePage(1)"
                            class="text-[10px] px-3 py-1.5 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50">
                            Next
                        </button>

                    </div>

                </div>

            </div>

        </main>

        <footer
            class="border-t border-slate-200 bg-white px-4 md:px-6 py-3 flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-400 gap-1">

            <span>
                MSWDO San Enrique Information System
            </span>

        </footer>

    </div>

    <!--  ACTIVITY DETAILS MODAL -->

    <div id="detailsModal"
        class="hidden fixed inset-0 z-[100] bg-slate-900/40 backdrop-blur-[1px] items-center justify-center p-4"
        onclick="handleModalBackground(event)">

        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto animate-modal-in">

            <!-- MODAL HEADER -->

            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">

                <div>

                    <h2 class="text-[15px] font-semibold text-forest-600">
                        Activity Details
                    </h2>

                    <p class="text-[10px] text-slate-400 mt-0.5">
                        Activity record information
                    </p>

                </div>

                <button type="button" onclick="closeDetails()"
                    class="w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">

                    <i class="fas fa-times"></i>

                </button>

            </div>

            <!-- MODAL CONTENT -->

            <div class="p-5 space-y-4">

                <!-- ACTIVITY -->

                <div class="flex items-center gap-3">

                    <div id="modalIcon"
                        class="w-11 h-11 rounded-xl bg-forest-50 text-forest-600 flex items-center justify-center flex-shrink-0">

                        <i class="fas fa-list"></i>

                    </div>

                    <div class="min-w-0">

                        <p id="modalActivity" class="text-[13px] font-semibold text-slate-700">
                            Activity
                        </p>

                        <p id="modalTime" class="text-[10px] text-slate-400 mt-0.5">
                            Date and time
                        </p>

                    </div>

                </div>

                <!-- INFORMATION GRID -->

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                    <!-- USER -->

                    <div class="bg-slate-50 rounded-xl p-3">

                        <p class="text-[9px] uppercase tracking-wider text-slate-400 font-semibold">
                            Performed By
                        </p>

                        <p id="modalUser" class="text-[12px] font-medium text-slate-700 mt-1">
                            -
                        </p>

                    </div>

                    <!-- ROLE -->

                    <div class="bg-slate-50 rounded-xl p-3">

                        <p class="text-[9px] uppercase tracking-wider text-slate-400 font-semibold">
                            Role
                        </p>

                        <p id="modalRole" class="text-[12px] font-medium text-slate-700 mt-1">
                            -
                        </p>

                    </div>

                    <!-- MODULE -->

                    <div class="bg-slate-50 rounded-xl p-3">

                        <p class="text-[9px] uppercase tracking-wider text-slate-400 font-semibold">
                            Module
                        </p>

                        <p id="modalModule" class="text-[12px] font-medium text-slate-700 mt-1">
                            -
                        </p>

                    </div>

                    <!-- ACTION -->

                    <div class="bg-slate-50 rounded-xl p-3">

                        <p class="text-[9px] uppercase tracking-wider text-slate-400 font-semibold">
                            Action
                        </p>

                        <p id="modalAction" class="text-[12px] font-medium text-slate-700 mt-1">
                            -
                        </p>

                    </div>

                </div>

                <!-- DESCRIPTION -->

                <div>

                    <p class="text-[9px] uppercase tracking-wider text-slate-400 font-semibold mb-2">
                        Description
                    </p>

                    <div class="bg-slate-50 border border-slate-100 rounded-xl p-3">

                        <p id="modalDescription" class="text-[12px] text-slate-600 leading-relaxed">
                            -
                        </p>

                    </div>

                </div>

                <!-- REFERENCE -->

                <div>

                    <p class="text-[9px] uppercase tracking-wider text-slate-400 font-semibold mb-2">
                        Record / Reference
                    </p>

                    <div class="bg-slate-50 border border-slate-100 rounded-xl p-3">

                        <p id="modalReference" class="text-[12px] font-medium text-forest-600">
                            -
                        </p>

                    </div>

                </div>

            </div>

            <!-- MODAL FOOTER -->

            <div class="px-5 py-3 border-t border-slate-200 flex justify-end">

                <button type="button" onclick="closeDetails()"
                    class="text-[11px] font-medium text-slate-600 border border-slate-200 rounded-lg px-4 py-2 hover:bg-slate-50 transition">

                    Close

                </button>

            </div>

        </div>

    </div>

    <script>


        function updateCurrentDate() {

            const dateElement = document.getElementById('currentDate');

            if (!dateElement) return;

            const now = new Date();

            dateElement.textContent = now.toLocaleDateString('en-PH', {
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            });

        }

        updateCurrentDate();


        function getSidebar() {

            return document.querySelector('body > aside') ||
                document.querySelector('body aside');

        }


        function toggleMobileMenu() {

            const sidebar = getSidebar();
            const overlay = document.getElementById('mobileSidebarOverlay');

            if (!sidebar || !overlay) return;

            sidebar.classList.toggle('mobile-sidebar-open');
            overlay.classList.toggle('active');

            document.body.classList.toggle(
                'mobile-menu-open',
                sidebar.classList.contains('mobile-sidebar-open')
            );

        }


        function closeMobileMenu() {

            const sidebar = getSidebar();
            const overlay = document.getElementById('mobileSidebarOverlay');

            if (!sidebar || !overlay) return;

            sidebar.classList.remove('mobile-sidebar-open');
            overlay.classList.remove('active');

            document.body.classList.remove('mobile-menu-open');

        }



        document.addEventListener('click', function (event) {

            if (window.innerWidth >= 768) return;

            const sidebar = getSidebar();

            if (!sidebar) return;

            const link = event.target.closest('a');

            if (link && sidebar.contains(link)) {
                closeMobileMenu();
            }

        });



        window.addEventListener('resize', function () {

            if (window.innerWidth >= 768) {
                closeMobileMenu();
            }

        });


        /* SAMPLE ACTIVITY DATA */

        const activityData = [

            {
                id: 1,
                date: '2026-08-09T09:42:00',
                user: 'Admin',
                role: 'Administrator',
                activity: 'Maria Santos registered',
                module: 'Client Records',
                action: 'Create',
                description: 'A new beneficiary record was registered in the system.',
                reference: 'Client Record #CR-2026-0012',
                icon: 'fa-user-plus',
                iconBg: 'bg-forest-100',
                iconColor: 'text-forest-600'
            },

            {
                id: 2,
                date: '2026-08-09T09:15:00',
                user: 'Staff',
                role: 'Staff',
                activity: 'AICS assistance approved · ₱3,500',
                module: 'AICS',
                action: 'Approve',
                description: 'An AICS financial assistance request was approved.',
                reference: 'AICS-2026-0048',
                icon: 'fa-hand-holding-heart',
                iconBg: 'bg-emerald-100',
                iconColor: 'text-emerald-600'
            },

            {
                id: 3,
                date: '2026-08-09T08:35:00',
                user: 'Staff',
                role: 'Staff',
                activity: 'Client record updated · Rodrigo Lim',
                module: 'Client Records',
                action: 'Update',
                description: 'Beneficiary information was updated by the assigned user.',
                reference: 'Client Record #CR-2026-0010',
                icon: 'fa-user-edit',
                iconBg: 'bg-indigo-100',
                iconColor: 'text-indigo-600'
            },

            {
                id: 4,
                date: '2026-08-08T16:20:00',
                user: 'Admin',
                role: 'Administrator',
                activity: 'Budget adjusted for SLP · ₱15,000',
                module: 'Budget',
                action: 'Update',
                description: 'The available budget allocation for the program was adjusted.',
                reference: 'Budget FY 2026 · SLP',
                icon: 'fa-coins',
                iconBg: 'bg-blue-100',
                iconColor: 'text-blue-600'
            },

            {
                id: 5,
                date: '2026-08-08T14:10:00',
                user: 'Staff',
                role: 'Staff',
                activity: 'New program proposal submitted',
                module: 'Reports',
                action: 'Submit',
                description: 'A new program proposal was submitted for review.',
                reference: 'Proposal #PP-2026-003',
                icon: 'fa-file-signature',
                iconBg: 'bg-purple-100',
                iconColor: 'text-purple-600'
            },

            {
                id: 6,
                date: '2026-08-08T11:30:00',
                user: 'Admin',
                role: 'Administrator',
                activity: 'Beneficiary records updated · 12 clients',
                module: 'Client Records',
                action: 'Update',
                description: 'Multiple beneficiary records were updated.',
                reference: 'Batch Update #BU-2026-006',
                icon: 'fa-user-edit',
                iconBg: 'bg-indigo-100',
                iconColor: 'text-indigo-600'
            },

            {
                id: 7,
                date: '2026-08-07T15:45:00',
                user: 'Staff',
                role: 'Staff',
                activity: 'Approval request submitted',
                module: 'AICS',
                action: 'Submit',
                description: 'An assistance request was submitted for approval.',
                reference: 'AICS-2026-0044',
                icon: 'fa-paper-plane',
                iconBg: 'bg-rose-100',
                iconColor: 'text-rose-600'
            },

            {
                id: 8,
                date: '2026-08-07T13:20:00',
                user: 'Admin',
                role: 'Administrator',
                activity: 'User account created',
                module: 'User Management',
                action: 'Create',
                description: 'A new system user account was created.',
                reference: 'User Account #USR-2026-009',
                icon: 'fa-user-plus',
                iconBg: 'bg-forest-100',
                iconColor: 'text-forest-600'
            },

            {
                id: 9,
                date: '2026-08-06T10:15:00',
                user: 'Staff',
                role: 'Staff',
                activity: 'System login',
                module: 'System',
                action: 'Login',
                description: 'A user successfully logged into the MSWDO Information System.',
                reference: 'Session #SES-2026-112',
                icon: 'fa-right-to-bracket',
                iconBg: 'bg-sky-100',
                iconColor: 'text-sky-600'
            },

            {
                id: 10,
                date: '2026-08-05T14:30:00',
                user: 'Admin',
                role: 'Administrator',
                activity: 'AICS assistance approved · ₱5,000',
                module: 'AICS',
                action: 'Approve',
                description: 'An AICS assistance request was approved.',
                reference: 'AICS-2026-0038',
                icon: 'fa-hand-holding-heart',
                iconBg: 'bg-emerald-100',
                iconColor: 'text-emerald-600'
            },

            {
                id: 11,
                date: '2026-08-04T09:50:00',
                user: 'Staff',
                role: 'Staff',
                activity: 'Client record created',
                module: 'Client Records',
                action: 'Create',
                description: 'A new client record was added to the system.',
                reference: 'Client Record #CR-2026-0007',
                icon: 'fa-user-plus',
                iconBg: 'bg-forest-100',
                iconColor: 'text-forest-600'
            },

            {
                id: 12,
                date: '2026-08-03T15:05:00',
                user: 'Admin',
                role: 'Administrator',
                activity: 'User account updated',
                module: 'User Management',
                action: 'Update',
                description: 'User account information was updated.',
                reference: 'User Account #USR-2026-005',
                icon: 'fa-user-gear',
                iconBg: 'bg-amber-100',
                iconColor: 'text-amber-600'
            }

        ];


        /* FILTER VARIABLES */

        let filteredActivities = [...activityData];

        let currentPage = 1;

        const itemsPerPage = 6;


        /* FORMAT DATE */

        function formatDateTime(dateString) {

            const date = new Date(dateString);

            return date.toLocaleString('en-PH', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });

        }


        /*  DATE FILTER */

        function isWithinDateRange(activityDate) {

            const fromDate =
                document.getElementById('dateFrom').value;

            const toDate =
                document.getElementById('dateTo').value;

            if (!fromDate && !toDate) {
                return true;
            }

            const activity =
                new Date(activityDate);

            if (isNaN(activity.getTime())) {
                return true;
            }

            if (fromDate) {

                const from =
                    new Date(fromDate);

                from.setHours(
                    0,
                    0,
                    0,
                    0
                );

                if (activity < from) {
                    return false;
                }

            }

            if (toDate) {

                const to =
                    new Date(toDate);

                to.setHours(
                    23,
                    59,
                    59,
                    999
                );

                if (activity > to) {
                    return false;
                }

            }

            return true;

        }


        /*  APPLY FILTERS */

        function applyFilters() {

            const search =
                document
                    .getElementById('searchInput')
                    .value
                    .toLowerCase()
                    .trim();

            const user =
                document
                    .getElementById('userFilter')
                    .value;

            const module =
                document
                    .getElementById('moduleFilter')
                    .value;

            const action =
                document
                    .getElementById('actionFilter')
                    .value;


            filteredActivities =
                activityData.filter(activity => {

                    const searchableText = [
                        activity.activity,
                        activity.user,
                        activity.role,
                        activity.module,
                        activity.action,
                        activity.description,
                        activity.reference
                    ]
                        .join(' ')
                        .toLowerCase();


                    const matchesSearch =
                        !search ||
                        searchableText.includes(search);


                    const matchesUser =
                        user === 'all' ||
                        activity.user === user;


                    const matchesModule =
                        module === 'all' ||
                        activity.module === module;


                    const matchesAction =
                        action === 'all' ||
                        activity.action === action;


                    const matchesDate =
                        isWithinDateRange(activity.date);


                    return (
                        matchesSearch &&
                        matchesUser &&
                        matchesModule &&
                        matchesAction &&
                        matchesDate
                    );

                });


            currentPage = 1;

            renderActivities();

        }


        /* CLEAR FILTERS*/

        function clearFilters() {

            document
                .getElementById('searchInput')
                .value = '';

            document
                .getElementById('userFilter')
                .value = 'all';

            document
                .getElementById('moduleFilter')
                .value = 'all';

            document
                .getElementById('actionFilter')
                .value = 'all';

            document
                .getElementById('dateFrom')
                .value = '';

            document
                .getElementById('dateTo')
                .value = '';

            currentPage = 1;

            applyFilters();

        }


        /* RENDER ACTIVITIES */

        function renderActivities() {

            const table =
                document.getElementById('activityTable');

            const mobileList =
                document.getElementById('mobileActivityList');

            const emptyState =
                document.getElementById('emptyState');


            table.innerHTML = '';
            mobileList.innerHTML = '';


            if (filteredActivities.length === 0) {

                emptyState.classList.remove('hidden');

                updatePagination();

                return;

            }


            emptyState.classList.add('hidden');


            const start =
                (currentPage - 1) *
                itemsPerPage;


            const end =
                start +
                itemsPerPage;


            const pageItems =
                filteredActivities.slice(
                    start,
                    end
                );


            /* DESKTOP TABLE*/

            pageItems.forEach(activity => {

                const row =
                    document.createElement('tr');

                row.className =
                    'hover:bg-slate-50 transition';


                row.innerHTML = `

          <td class="px-5 py-3 whitespace-nowrap">

            <p class="text-[11px] text-slate-600">
              ${formatDateTime(activity.date)}
            </p>

          </td>


          <td class="px-5 py-3">

            <div class="flex items-center gap-2">

              <div
                class="w-7 h-7 rounded-full bg-forest-50 text-forest-600 flex items-center justify-center text-[9px]">

                <i class="fas fa-user"></i>

              </div>

              <div>

                <p class="text-[11px] font-medium text-slate-700">
                  ${activity.user}
                </p>

                <p class="text-[9px] text-slate-400">
                  ${activity.role}
                </p>

              </div>

            </div>

          </td>


          <td class="px-5 py-3">

            <p class="text-[11px] text-slate-700">
              ${activity.activity}
            </p>

          </td>


          <td class="px-5 py-3">

            <span
              class="inline-flex items-center px-2 py-1 rounded-md bg-slate-100 text-slate-600 text-[9px]">

              ${activity.module}

            </span>

          </td>


          <td class="px-5 py-3">

            <span
              class="inline-flex items-center px-2 py-1 rounded-md bg-forest-50 text-forest-600 text-[9px] font-medium">

              ${activity.action}

            </span>

          </td>


          <td class="px-5 py-3 text-right">

            <button
              type="button"
              onclick="showDetails(${activity.id})"
              class="text-[10px] text-forest-600 hover:text-forest-700 font-medium">

              View Details

            </button>

          </td>

        `;


                table.appendChild(row);

            });


            /*  MOBILE LIST */

            pageItems.forEach(activity => {

                const card =
                    document.createElement('div');

                card.className =
                    'mobile-activity-card px-4 py-4';


                card.innerHTML = `

          <div class="flex items-start gap-3">

            <div
              class="w-9 h-9 rounded-xl ${activity.iconBg} ${activity.iconColor} flex items-center justify-center text-xs flex-shrink-0">

              <i class="fas ${activity.icon}"></i>

            </div>


            <div class="flex-1 min-w-0">

              <div class="flex items-start justify-between gap-2">

                <p
                  class="text-[12px] font-medium text-slate-700 leading-snug">

                  ${activity.activity}

                </p>

                <button
                  type="button"
                  onclick="showDetails(${activity.id})"
                  class="text-[10px] text-forest-600 font-medium flex-shrink-0">

                  View

                </button>

              </div>


              <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">

                <span class="text-[10px] text-slate-400">
                  ${activity.user}
                </span>

                <span class="text-slate-300">
                  •
                </span>

                <span class="text-[10px] text-slate-400">
                  ${activity.module}
                </span>

              </div>


              <div class="flex flex-wrap items-center gap-2 mt-2">

                <span
                  class="inline-flex items-center px-2 py-1 rounded-md bg-forest-50 text-forest-600 text-[9px] font-medium">

                  ${activity.action}

                </span>

                <span class="text-[9px] text-slate-400">

                  ${formatDateTime(activity.date)}

                </span>

              </div>

            </div>

          </div>

        `;


                mobileList.appendChild(card);

            });


            updatePagination();

        }


        function updatePagination() {

            const total =
                filteredActivities.length;


            const totalPages =
                Math.max(
                    1,
                    Math.ceil(
                        total /
                        itemsPerPage
                    )
                );


            if (currentPage > totalPages) {
                currentPage = totalPages;
            }


            const start =
                total === 0
                    ? 0
                    : ((currentPage - 1) *
                        itemsPerPage) +
                    1;


            const end =
                Math.min(
                    currentPage *
                    itemsPerPage,
                    total
                );


            document
                .getElementById('resultCount')
                .textContent =
                `Showing ${total} activities`;


            document
                .getElementById('paginationInfo')
                .textContent =
                total === 0
                    ? 'Showing 0'
                    : `Showing ${start}-${end} of ${total}`;


            document
                .getElementById('pageNumber')
                .textContent =
                currentPage;


            document
                .getElementById('prevBtn')
                .disabled =
                currentPage <= 1;


            document
                .getElementById('nextBtn')
                .disabled =
                currentPage >= totalPages;


            document
                .getElementById('nextBtn')
                .classList.toggle(
                    'opacity-40',
                    currentPage >= totalPages
                );

        }


        function changePage(direction) {

            const totalPages =
                Math.max(
                    1,
                    Math.ceil(
                        filteredActivities.length /
                        itemsPerPage
                    )
                );


            const newPage =
                currentPage +
                direction;


            if (
                newPage < 1 ||
                newPage > totalPages
            ) {
                return;
            }


            currentPage = newPage;

            renderActivities();


            const activitySection =
                document.querySelector(
                    '.mobile-activity-card'
                );


            if (activitySection) {

                activitySection
                    .closest('.bg-white')
                    ?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });

            }

        }


        /*  SUMMARY COUNTS */

        function updateSummaryCards() {

            const today =
                new Date()
                    .toISOString()
                    .split('T')[0];


            const todayCount =
                activityData.filter(activity =>
                    activity.date.startsWith(today)
                ).length;


            const adminCount =
                activityData.filter(activity =>
                    activity.user === 'Admin'
                ).length;


            const staffCount =
                activityData.filter(activity =>
                    activity.user === 'Staff'
                ).length;


            document
                .getElementById('totalActivities')
                .textContent =
                activityData.length;


            document
                .getElementById('todayActivities')
                .textContent =
                todayCount;


            document
                .getElementById('adminActivities')
                .textContent =
                adminCount;


            document
                .getElementById('staffActivities')
                .textContent =
                staffCount;

        }


        /*  ACTIVITY DETAILS*/

        function showDetails(id) {

            const activity =
                activityData.find(
                    item => item.id === id
                );


            if (!activity) return;


            document
                .getElementById('modalActivity')
                .textContent =
                activity.activity;


            document
                .getElementById('modalTime')
                .textContent =
                formatDateTime(activity.date);


            document
                .getElementById('modalUser')
                .textContent =
                activity.user;


            document
                .getElementById('modalRole')
                .textContent =
                activity.role;


            document
                .getElementById('modalModule')
                .textContent =
                activity.module;


            document
                .getElementById('modalAction')
                .textContent =
                activity.action;


            document
                .getElementById('modalDescription')
                .textContent =
                activity.description;


            document
                .getElementById('modalReference')
                .textContent =
                activity.reference;


            const modalIcon =
                document.getElementById('modalIcon');


            modalIcon.className =
                `w-11 h-11 rounded-xl ${activity.iconBg} ${activity.iconColor} flex items-center justify-center flex-shrink-0`;


            modalIcon.innerHTML =
                `<i class="fas ${activity.icon}"></i>`;


            const modal =
                document.getElementById('detailsModal');


            modal.classList.remove('hidden');

            modal.classList.add('flex');

            document.body.classList.add('overflow-hidden');

        }


        function closeDetails() {

            const modal =
                document.getElementById('detailsModal');


            modal.classList.add('hidden');

            modal.classList.remove('flex');

            document.body.classList.remove('overflow-hidden');

        }


        function handleModalBackground(event) {

            if (
                event.target ===
                document.getElementById('detailsModal')
            ) {

                closeDetails();

            }

        }


        /*  ESC KEY*/

        document.addEventListener(
            'keydown',
            function (event) {

                if (event.key === 'Escape') {

                    closeDetails();

                    closeMobileMenu();

                }

            }
        );


        /*  INITIALIZE */

        updateSummaryCards();

        applyFilters();

    </script>

</body>

</html>