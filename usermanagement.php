<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';

// ── Flash message (set by usermanagement_action.php after a redirect) ──
$flashMsg  = $_GET['msg'] ?? '';
$flashType = $_GET['type'] ?? 'success';

$stmt = $pdo->query("
    SELECT user_id, username, user_firstname, user_middlename, user_lastname,
           user_role, user_email, user_contactnum, user_isactive, user_last_login
    FROM mswdo_user
    ORDER BY user_lastname ASC, user_firstname ASC
");

$usersData = array_map(function ($u) {
    return [
        'id'          => (int) $u['user_id'],
        'firstName'   => $u['user_firstname'],
        'lastName'    => $u['user_lastname'],
        'middleName'  => $u['user_middlename'],
        'username'    => $u['username'],
        'role'        => $u['user_role'],
        'email'       => $u['user_email'],
        'contact'     => $u['user_contactnum'],
        'isActive'    => (bool) $u['user_isactive'],
        'lastLogin'   => $u['user_last_login']
            ? date('Y-m-d h:i A', strtotime($u['user_last_login']))
            : 'Never',
    ];
}, $stmt->fetchAll(PDO::FETCH_ASSOC));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Management – MSWDO San Enrique</title>
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
                        modalIn: { '0%': { opacity: '0', transform: 'scale(0.95) translateY(10px)' }, '100%': { opacity: '1', transform: 'scale(1) translateY(0)' } },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.4s ease both',
                        'fade-up-1': 'fadeUp 0.4s ease 0.05s both',
                        'fade-up-2': 'fadeUp 0.4s ease 0.1s both',
                        'fade-up-3': 'fadeUp 0.4s ease 0.15s both',
                        'modal-in': 'modalIn 0.3s ease both',
                    }
                }
            }
        }
    </script>
    <style>
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

        .btn-action {
            transition: all .15s ease;
        }

        .btn-action:hover {
            transform: translateY(-1px);
        }

        .table-row {
            transition: background .12s;
        }

        .table-row:hover {
            background: #EEF6F0;
        }

        .field {
            display: block;
            width: 100%;
            border-radius: .75rem;
            border: 1.5px solid #D4E8DC;
            background: #FAFCFB;
            padding: .625rem .875rem;
            font-size: 13px;
            color: #1e293b;
            outline: none;
            font-family: 'DM Sans', sans-serif;
            transition: all .2s;
        }

        .field:focus {
            border-color: #1A5C3A;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(26, 92, 58, .12);
        }

        .field::placeholder {
            color: #94A3B8;
        }

        .field.input-error {
            border-color: #EF4444;
            background: #FEF2F2;
        }

        .field.input-success {
            border-color: #10B981;
        }

        select.field {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236B7280' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
            appearance: none;
        }

        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #4A7A5A;
            margin-bottom: 6px;
        }

        .req::after {
            content: '*';
            color: #EF4444;
            margin-left: 2px;
        }

        .validation-message {
            display: none;
            font-size: 10px;
            color: #DC2626;
            margin-top: 4px;
            line-height: 1.4;
        }

        .validation-message.show {
            display: block;
        }

        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(26, 92, 58, .2);
            border-radius: 2px;
        }

        .user-table-wrap {
            width: 100%;
            overflow: hidden;
        }

        .user-table {
            width: 100%;
            table-layout: fixed;
        }

        .user-table th,
        .user-table td {
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
        }

        .user-table .col-name {
            width: 22%;
        }

        .user-table .col-username {
            width: 13%;
        }

        .user-table .col-role {
            width: 10%;
        }

        .user-table .col-position {
            width: 20%;
        }

        .user-table .col-employment {
            width: 13%;
        }

        .user-table .col-status {
            width: 9%;
        }

        .user-table .col-actions {
            width: 13%;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 4px;
            white-space: nowrap;
        }

        .action-button {
            width: 30px;
            height: 30px;
            min-width: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .readonly-field {
            background: #F1F5F9 !important;
            color: #64748B !important;
            cursor: not-allowed;
            border-color: #E2E8F0 !important;
        }

        .readonly-field:focus {
            border-color: #E2E8F0 !important;
            box-shadow: none !important;
            background: #F1F5F9 !important;
        }

        @media (max-width: 1100px) {
            .user-table .col-username {
                width: 11%;
            }

            .user-table .col-position {
                width: 18%;
            }

            .user-table .col-employment {
                width: 12%;
            }

            .user-table .col-actions {
                width: 14%;
            }

            .user-table th,
            .user-table td {
                padding-left: 10px !important;
                padding-right: 10px !important;
            }
        }

        @media (max-width: 768px) {
            .user-table-wrap {
                overflow: visible;
            }

            .user-table,
            .user-table thead,
            .user-table tbody,
            .user-table tr,
            .user-table th,
            .user-table td {
                display: block;
            }

            .user-table thead {
                display: none;
            }

            .user-table tbody {
                width: 100%;
            }

            .user-table tr {
                background: #fff;
                border-bottom: 1px solid #E2E8F0;
                padding: 12px 14px;
            }

            .user-table tr:last-child {
                border-bottom: 0;
            }

            .user-table td {
                width: 100% !important;
                max-width: none;
                border: 0;
                padding: 5px 0 !important;
                white-space: normal !important;
                overflow: visible;
                text-overflow: clip;
            }

            .user-table td[data-label]::before {
                content: attr(data-label);
                display: block;
                font-size: 9px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .05em;
                color: #94A3B8;
                margin-bottom: 2px;
            }

            .user-table td[data-label="Name"]::before {
                display: none;
            }

            .user-table td[data-label="Name"] {
                padding-bottom: 7px !important;
            }

            .user-table td[data-label="Role"],
            .user-table td[data-label="Employment"],
            .user-table td[data-label="Status"] {
                display: inline-flex;
                width: auto !important;
                margin-right: 6px;
                vertical-align: middle;
            }

            .user-table td[data-label="Role"]::before,
            .user-table td[data-label="Employment"]::before,
            .user-table td[data-label="Status"]::before {
                display: none;
            }

            .user-table td[data-label="Actions"] {
                padding-top: 9px !important;
                margin-top: 6px;
                border-top: 1px solid #F1F5F9;
            }

            .action-buttons {
                justify-content: flex-start;
                gap: 6px;
            }

            .action-button {
                width: 34px;
                height: 34px;
                min-width: 34px;
            }
        }

        @media (max-width: 480px) {
            .user-table tr {
                padding: 11px 12px;
            }

            .user-table .name-email {
                min-width: 0;
            }

            .user-table .name-email p {
                max-width: 100%;
            }
        }

        .view-tab.active { border-bottom-color: #16a34a; color: #16a34a; }
        .history-item { position: relative; padding: 12px 14px 12px 18px; border: 1px solid #E2E8F0; border-radius: 12px; background: #fff; }
        .history-item::before { content: ""; position: absolute; left: 7px; top: 18px; width: 5px; height: 5px; border-radius: 999px; background: #16a34a; }
        .history-action { font-size: 11px; font-weight: 700; color: #334155; }
        .history-details { font-size: 10px; color: #64748B; line-height: 1.5; margin-top: 3px; }
        .history-meta { font-size: 9px; color: #94A3B8; margin-top: 7px; }
        .history-empty { padding: 42px 20px; text-align: center; border: 1px dashed #CBD5E1; border-radius: 12px; color: #94A3B8; }

        .modal-backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .badge-admin {
            background: #FEE2E2;
            color: #DC2626;
        }

        .badge-staff {
            background: #D1FAE5;
            color: #059669;
        }

        .badge-active {
            background: #D1FAE5;
            color: #059669;
        }

        .badge-inactive {
            background: #F1F5F9;
            color: #64748B;
        }

        .badge-permanent {
            background: #DBEAFE;
            color: #2563EB;
        }

        .badge-contractual {
            background: #FEF3C7;
            color: #D97706;
        }

        .badge-casual {
            background: #EDE9FE;
            color: #7C3AED;
        }

        .badge-job-order {
            background: #FCE7F3;
            color: #DB2777;
        }

        .badge-temporary {
            background: #E0F2FE;
            color: #0284C7;
        }

        .badge-probationary {
            background: #F3F4F6;
            color: #4B5563;
        }

        .detail-row {
            border-bottom: 1px solid #F1F5F9;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .section-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .empty-state {
            padding: 48px 20px;
            text-align: center;
        }

        @media (max-width: 640px) {
            .modal-content {
                max-height: calc(100vh - 24px);
            }
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

            <div class="flex items-center gap-2 text-[13px]">
                <span class="text-green-600 font-semibold">
                    User Management
                </span>
            </div>

        </header>


        <main class="flex-1 p-4 md:p-6 space-y-5">

            <!-- PAGE TITLE -->

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 animate-fade-up">

                <div>

                    <h1 class="text-xl md:text-2xl font-serif text-green-600">
                        User Management
                    </h1>

                    <p class="text-[12px] md:text-[13px] text-slate-500 mt-0.5">
                        Manage system users, roles, employment information, and account access.
                    </p>

                </div>


                <button type="button" onclick="openAddModal()"
                    class="btn-action text-[12px] font-semibold text-white bg-green-600 rounded-lg px-4 py-2.5 hover:bg-green-700 transition-all flex items-center justify-center gap-2">

                    <i class="fas fa-user-plus"></i>

                    Add User

                </button>

            </div>

            <!-- STATS -->

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 animate-fade-up-1">

                <div class="stat-card bg-white rounded-2xl border border-slate-200 p-4">

                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">
                        Total Users
                    </p>

                    <p class="text-2xl font-bold text-green-600 mt-1" id="totalUsers">
                        0
                    </p>

                </div>


                <div class="stat-card bg-white rounded-2xl border border-slate-200 p-4">

                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">
                        Administrators
                    </p>

                    <p class="text-2xl font-bold text-red-500 mt-1" id="totalAdmins">
                        0
                    </p>

                </div>


                <div class="stat-card bg-white rounded-2xl border border-slate-200 p-4">

                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">
                        Staff
                    </p>

                    <p class="text-2xl font-bold text-emerald-500 mt-1" id="totalStaff">
                        0
                    </p>

                </div>


                <div class="stat-card bg-white rounded-2xl border border-slate-200 p-4">

                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">
                        Inactive
                    </p>

                    <p class="text-2xl font-bold text-slate-500 mt-1" id="totalInactive">
                        0
                    </p>

                </div>

            </div>


            <!-- SEARCH & FILTER -->

            <div class="bg-white rounded-2xl border border-slate-200 p-4 animate-fade-up-2">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">

                    <!-- Search -->

                    <div class="relative lg:col-span-2">

                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">
                        </i>

                        <input type="text" id="searchInput" placeholder="Search name, username, position, or email..."
                            class="text-[12px] pl-8 pr-3 py-2 border border-slate-200 rounded-lg bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none w-full"
                            oninput="applyFilters()">

                    </div>


                    <!-- Role -->

                    <select id="roleFilter"
                        class="text-[12px] border border-slate-200 rounded-lg px-3 py-2 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                        onchange="applyFilters()">

                        <option value="all">
                            All Roles
                        </option>

                        <option value="Admin">
                            Admin
                        </option>

                        <option value="Staff">
                            Staff
                        </option>

                    </select>


                    <!-- Employment -->

                    <select id="employmentFilter"
                        class="text-[12px] border border-slate-200 rounded-lg px-3 py-2 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                        onchange="applyFilters()">

                        <option value="all">
                            All Employment
                        </option>

                        <option value="Permanent">
                            Permanent
                        </option>

                        <option value="Casual">
                            Casual
                        </option>

                        <option value="Contractual">
                            Contractual
                        </option>

                        <option value="Job Order">
                            Job Order
                        </option>

                        <option value="Temporary">
                            Temporary
                        </option>

                        <option value="Probationary">
                            Probationary
                        </option>

                    </select>


                    <!-- Status -->

                    <select id="statusFilter"
                        class="text-[12px] border border-slate-200 rounded-lg px-3 py-2 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                        onchange="applyFilters()">

                        <option value="all">
                            All Status
                        </option>

                        <option value="Active">
                            Active
                        </option>

                        <option value="Inactive">
                            Inactive
                        </option>

                    </select>

                </div>


                <div class="flex items-center justify-between mt-3">

                    <span class="text-[11px] text-slate-400" id="rowCount">
                        Showing 0 users
                    </span>

                </div>

            </div>


            <!-- USER TABLE -->

            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-up-3">

                <div class="user-table-wrap">

                    <table class="user-table text-[12px]">

                        <thead>

                            <tr class="bg-slate-50 border-b border-slate-100">

                                <th
                                    class="col-name text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Name
                                </th>

                                <th
                                    class="col-username text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Username
                                </th>

                                <th
                                    class="col-role text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Role
                                </th>

                                <th
                                    class="col-position text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Position
                                </th>

                                <th
                                    class="col-employment text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Employment
                                </th>

                                <th
                                    class="col-status text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Status
                                </th>

                                <th
                                    class="col-actions text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Actions
                                </th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100" id="tableBody"></tbody>

                    </table>

                </div>


                <!-- PAGINATION -->

                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-5 py-3 border-t border-slate-100">

                    <span class="text-[11px] text-slate-400" id="paginationInfo">
                        Showing 0 users
                    </span>

                    <div class="flex items-center gap-1">

                        <button type="button" id="prevBtn" onclick="previousPage()"
                            class="text-[11px] text-slate-400 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors">
                            Previous
                        </button>

                        <span id="pageNumber"
                            class="text-[11px] font-medium text-white bg-green-600 rounded-lg px-3 py-1">
                            1
                        </span>

                        <button type="button" id="nextBtn" onclick="nextPage()"
                            class="text-[11px] text-slate-600 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors">
                            Next
                        </button>

                    </div>

                </div>

            </div>

        </main>


        <!-- FOOTER -->

        <footer
            class="border-t border-slate-200 bg-white px-4 md:px-6 py-3 flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-400 gap-1">

            <span>
                MSWDO San Enrique Information System
            </span>

        </footer>


        <!-- ADD / EDIT USER MODAL -->

        <div id="userModal" class="hidden fixed inset-0 z-50 modal-backdrop px-4 py-6 overflow-y-auto">

            <div class="min-h-full flex items-center justify-center">

                <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">

                    <!-- Header -->

                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">

                        <div>

                            <h2 id="modalTitle" class="text-[14px] font-semibold text-green-600">
                                Add New User
                            </h2>

                            <p id="modalSubtitle" class="text-[10px] text-slate-400 mt-0.5">
                                Create a new system account.
                            </p>

                        </div>


                        <button type="button" onclick="closeModal()"
                            class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">

                            <i class="fas fa-xmark text-sm"></i>

                        </button>

                    </div>


                    <!-- FORM -->

                    <form id="userForm" onsubmit="saveUser(event)" novalidate>

                        <div class="p-5 space-y-6 overflow-y-auto max-h-[70vh]">

                            <!-- PERSONAL -->

                            <section>

                                <div class="flex items-center gap-2 mb-4">

                                    <div class="section-icon bg-green-50 text-green-600">
                                        <i class="fas fa-id-card text-sm"></i>
                                    </div>

                                    <div>

                                        <h3 class="text-[13px] font-semibold text-green-600">
                                            Personal Information
                                        </h3>

                                        <p class="text-[10px] text-slate-400">
                                            Employee's registered name
                                        </p>

                                    </div>

                                </div>


                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                    <div>

                                        <label class="field-label req">
                                            First Name
                                        </label>

                                        <input type="text" id="firstName" class="field" placeholder="e.g. Juan"
                                            required>

                                        <p id="firstNameError" class="validation-message"></p>

                                    </div>


                                    <div>

                                        <label class="field-label req">
                                            Last Name
                                        </label>

                                        <input type="text" id="lastName" class="field" placeholder="e.g. Dela Cruz"
                                            required>

                                        <p id="lastNameError" class="validation-message"></p>

                                    </div>

                                </div>


                                <div class="mt-4">

                                    <label class="field-label">
                                        Middle Name
                                    </label>

                                    <input type="text" id="middleName" class="field" placeholder="e.g. Santos">

                                </div>

                            </section>


                            <!-- ACCOUNT -->

                            <section class="pt-5 border-t border-slate-100">

                                <div class="flex items-center gap-2 mb-4">

                                    <div class="section-icon bg-blue-50 text-blue-600">
                                        <i class="fas fa-user-lock text-sm"></i>
                                    </div>

                                    <div>

                                        <h3 class="text-[13px] font-semibold text-green-600">
                                            Account Information
                                        </h3>

                                        <p class="text-[10px] text-slate-400">
                                            Login credentials and system access
                                        </p>

                                    </div>

                                </div>


                                <!-- ACCOUNT ID -->

                                <div class="mb-4">

                                    <label class="field-label">
                                        Account ID
                                    </label>

                                    <input type="text" id="accountId" class="field bg-slate-100 text-slate-500"
                                        placeholder="Automatically generated" readonly>

                                </div>


                                <!-- USERNAME -->

                                <div class="mb-4">

                                    <label class="field-label req">
                                        Username
                                    </label>

                                    <input type="text" id="username" class="field" placeholder="e.g. jdelacruz"
                                        required>

                                    <p id="usernameError" class="validation-message"></p>

                                </div>


                                <!-- PASSWORD -->

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                    <div>

                                        <label class="field-label" id="passwordLabel">

                                            Password

                                        </label>

                                        <div class="relative">

                                            <input type="password" id="password" class="field pr-10"
                                                placeholder="Min 8 characters">

                                            <button type="button" onclick="toggleUserPassword('password', this)"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-green-600">

                                                <i class="fas fa-eye text-[11px]"></i>

                                            </button>

                                        </div>

                                        <p id="passwordError" class="validation-message">
                                        </p>

                                    </div>


                                    <div>

                                        <label class="field-label" id="confirmPasswordLabel">

                                            Confirm Password

                                        </label>

                                        <div class="relative">

                                            <input type="password" id="confirmPassword" class="field pr-10"
                                                placeholder="Re-enter password">

                                            <button type="button" onclick="toggleUserPassword('confirmPassword', this)"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-green-600">

                                                <i class="fas fa-eye text-[11px]"></i>

                                            </button>

                                        </div>

                                        <p id="confirmPasswordError" class="validation-message">
                                        </p>

                                    </div>

                                </div>


                                <p class="text-[10px] text-slate-400 mt-2">
                                    Password must contain at least 8 characters.
                                </p>


                                <!-- ROLE -->

                                <div class="mt-4">

                                    <label class="field-label req">
                                        System Role
                                    </label>

                                    <select id="role" class="field" required>

                                        <option value="">
                                            Select Role
                                        </option>

                                        <option value="Admin">
                                            Admin
                                        </option>

                                        <option value="Staff">
                                            Staff
                                        </option>

                                    </select>

                                    <p id="roleError" class="validation-message"></p>

                                    <p class="text-[10px] text-slate-400 mt-1">
                                        Role controls the user's permissions in the system.
                                    </p>

                                </div>

                            </section>


                            <!-- CONTACT -->

                            <section class="pt-5 border-t border-slate-100">

                                <div class="flex items-center gap-2 mb-4">

                                    <div class="section-icon bg-purple-50 text-purple-600">
                                        <i class="fas fa-address-book text-sm"></i>
                                    </div>

                                    <div>

                                        <h3 class="text-[13px] font-semibold text-green-600">
                                            Contact Information
                                        </h3>

                                        <p class="text-[10px] text-slate-400">
                                            Employee's registered contact details
                                        </p>

                                    </div>

                                </div>


                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                    <div>

                                        <label class="field-label req">
                                            Email Address
                                        </label>

                                        <input type="email" id="email" class="field" placeholder="e.g. juan@example.com"
                                            required>

                                        <p id="emailError" class="validation-message"></p>

                                    </div>


                                    <div>

                                        <label class="field-label req">
                                            Contact Number
                                        </label>

                                        <input type="tel" id="contact" class="field" placeholder="e.g. 09123456789"
                                            maxlength="13" required>

                                        <p id="contactError" class="validation-message"></p>

                                    </div>

                                </div>

                            </section>


                            <!-- EMPLOYMENT -->

                            <section class="pt-5 border-t border-slate-100">

                                <div class="flex items-center gap-2 mb-4">

                                    <div class="section-icon bg-lime-50 text-lime-600">
                                        <i class="fas fa-briefcase text-sm"></i>
                                    </div>

                                    <div>

                                        <h3 class="text-[13px] font-semibold text-green-600">
                                            Employment & Office Information
                                        </h3>

                                        <p class="text-[10px] text-slate-400">
                                            Employee's position and office assignment
                                        </p>

                                    </div>

                                </div>


                                <!-- POSITION -->

                                <div class="mb-4">

                                    <label class="field-label req">
                                        Position
                                    </label>

                                    <input type="text" id="position" class="field"
                                        placeholder="e.g. Social Welfare Assistant" required>

                                    <p id="positionError" class="validation-message"></p>

                                </div>


                                <!-- EMPLOYMENT STATUS -->

                                <div class="mb-4">

                                    <label class="field-label req">
                                        Employment Status
                                    </label>

                                    <select id="employmentStatus" class="field" required>

                                        <option value="">
                                            Select Employment Status
                                        </option>

                                        <option value="Permanent">
                                            Permanent
                                        </option>

                                        <option value="Casual">
                                            Casual
                                        </option>

                                        <option value="Contractual">
                                            Contractual
                                        </option>

                                        <option value="Job Order">
                                            Job Order
                                        </option>

                                        <option value="Temporary">
                                            Temporary
                                        </option>

                                        <option value="Probationary">
                                            Probationary
                                        </option>

                                    </select>

                                    <p id="employmentStatusError" class="validation-message"></p>

                                </div>


                                <!-- OFFICE -->

                                <div class="mb-4">

                                    <label class="field-label">
                                        Office
                                    </label>

                                    <input type="text" id="office" class="field readonly-field" value="MSWDO" readonly
                                        aria-readonly="true">

                                    <p id="officeError" class="validation-message"></p>

                                </div>


                                <!-- LOCATION -->

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                    <div>

                                        <label class="field-label">
                                            Municipality
                                        </label>

                                        <input type="text" id="municipality" class="field readonly-field"
                                            value="San Enrique" readonly aria-readonly="true">

                                        <p id="municipalityError" class="validation-message"></p>

                                    </div>


                                    <div>

                                        <label class="field-label">
                                            Province
                                        </label>

                                        <input type="text" id="province" class="field readonly-field"
                                            value="Negros Occidental" readonly aria-readonly="true">

                                        <p id="provinceError" class="validation-message"></p>

                                    </div>

                                </div>

                            </section>


                            <!-- STATUS -->

                            <section class="pt-5 border-t border-slate-100">

                                <div class="flex items-center gap-2 mb-4">

                                    <div class="section-icon bg-emerald-50 text-emerald-600">
                                        <i class="fas fa-shield-halved text-sm"></i>
                                    </div>

                                    <div>

                                        <h3 class="text-[13px] font-semibold text-green-600">
                                            Account Status
                                        </h3>

                                        <p class="text-[10px] text-slate-400">
                                            Control whether this account can access the system
                                        </p>

                                    </div>

                                </div>


                                <div
                                    class="flex items-center justify-between gap-4 p-3 rounded-xl bg-slate-50 border border-slate-100">

                                    <div>

                                        <p class="text-[12px] font-semibold text-slate-700">
                                            Account Active
                                        </p>

                                        <p class="text-[10px] text-slate-400 mt-0.5">
                                            Inactive accounts cannot log in to the system.
                                        </p>

                                    </div>


                                    <label class="relative inline-flex items-center cursor-pointer">

                                        <input type="checkbox" id="isActive" class="sr-only peer" checked>

                                        <div
                                            class="w-10 h-5 bg-slate-300 rounded-full peer peer-checked:bg-green-600 transition-colors">
                                        </div>

                                        <div
                                            class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform peer-checked:translate-x-5">
                                        </div>

                                    </label>

                                </div>

                            </section>

                        </div>


                        <!-- FOOTER -->

                        <div
                            class="px-5 py-4 bg-slate-50 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">

                            <button type="button" onclick="closeModal()"
                                class="text-[13px] font-medium text-slate-600 border border-slate-200 bg-white rounded-xl px-5 py-2 hover:border-green-400 hover:text-green-600 transition-all">

                                Cancel

                            </button>


                            <button type="submit" id="saveUserButton"
                                class="text-[13px] font-semibold text-white bg-green-600 rounded-xl px-6 py-2 hover:bg-green-500 transition-all">

                                <i class="fas fa-save mr-1.5"></i>

                                <span id="saveBtnText">
                                    Save User
                                </span>

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>


        <!-- VIEW USER MODAL -->

        <div id="viewModal" class="hidden fixed inset-0 z-50 modal-backdrop px-4 py-6 overflow-y-auto">

            <div class="min-h-full flex items-center justify-center">

                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">

                    <!-- HEADER -->

                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">

                        <div>

                            <h2 class="text-[14px] font-semibold text-green-600">
                                UserName
                            </h2>

                            <p class="text-[10px] text-slate-400 mt-0.5">
                                View User Details and User History Log
                            </p>

                        </div>


                        <button type="button" onclick="closeViewModal()"
                            class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center">

                            <i class="fas fa-xmark text-sm"></i>

                        </button>

                    </div>


                    <div class="px-5 pt-3 border-b border-slate-100 bg-white">
                        <div class="flex items-center gap-1" role="tablist" aria-label="User view tabs">
                            <button type="button" id="userDetailsTab" onclick="switchViewTab('details')" class="view-tab active px-3 py-2.5 text-[11px] font-semibold border-b-2 border-green-600 text-green-600 transition">
                                <i class="fas fa-id-card mr-1.5"></i> User Details
                            </button>
                            <button type="button" id="userHistoryTab" onclick="switchViewTab('history')" class="view-tab px-3 py-2.5 text-[11px] font-semibold border-b-2 border-transparent text-slate-400 hover:text-green-600 transition">
                                <i class="fas fa-clock-rotate-left mr-1.5"></i> User History Log
                            </button>
                        </div>
                    </div>

                    <div id="viewDetailsPanel" class="p-5 max-h-[75vh] overflow-y-auto">

                        <!-- PROFILE -->

                        <div class="bg-green-50 border border-green-100 rounded-2xl p-4 mb-5">

                            <div class="flex flex-col sm:flex-row sm:items-center gap-4">

                                <div
                                    class="w-14 h-14 rounded-xl bg-green-600 text-white flex items-center justify-center flex-shrink-0">

                                    <i class="fas fa-user text-xl"></i>

                                </div>


                                <div class="flex-1 min-w-0">

                                    <div class="flex flex-wrap items-center gap-2">

                                        <h3 id="viewFullName" class="text-[16px] font-semibold text-green-700">
                                            —
                                        </h3>

                                        <span id="viewStatus"
                                            class="text-[10px] font-semibold px-2.5 py-1 rounded-full">
                                            —
                                        </span>

                                    </div>


                                    <p id="viewPosition" class="text-[11px] text-slate-500 mt-1">
                                        —
                                    </p>

                                    <p id="viewOffice" class="text-[10px] text-slate-400 mt-0.5">
                                        —
                                    </p>

                                </div>


                                <div class="sm:text-right">

                                    <p class="text-[9px] uppercase tracking-wider text-slate-400 font-semibold">
                                        System Role
                                    </p>

                                    <span id="viewRole"
                                        class="inline-block text-[10px] font-semibold px-2.5 py-1 rounded-full mt-1">
                                        —
                                    </span>

                                </div>

                            </div>

                        </div>


                        <!-- PERSONAL -->

                        <div class="border border-slate-200 rounded-xl overflow-hidden mb-4">

                            <div class="px-4 py-3 bg-slate-50 border-b border-slate-100">

                                <h3 class="text-[11px] font-semibold text-green-600">
                                    <i class="fas fa-id-card mr-1.5"></i>
                                    Personal Information
                                </h3>

                            </div>


                            <div class="px-4">

                                <div class="detail-row py-3 flex items-center justify-between gap-4">

                                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        Account ID
                                    </span>

                                    <span id="viewAccountId" class="text-[12px] font-medium text-slate-700 text-right">
                                        —
                                    </span>

                                </div>


                                <div class="detail-row py-3 flex items-center justify-between gap-4">

                                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        First Name
                                    </span>

                                    <span id="viewFirstName" class="text-[12px] text-slate-700 text-right">
                                        —
                                    </span>

                                </div>


                                <div class="detail-row py-3 flex items-center justify-between gap-4">

                                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        Middle Name
                                    </span>

                                    <span id="viewMiddleName" class="text-[12px] text-slate-700 text-right">
                                        —
                                    </span>

                                </div>


                                <div class="detail-row py-3 flex items-center justify-between gap-4">

                                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        Last Name
                                    </span>

                                    <span id="viewLastName" class="text-[12px] text-slate-700 text-right">
                                        —
                                    </span>

                                </div>

                            </div>

                        </div>


                        <!-- CONTACT -->

                        <div class="border border-slate-200 rounded-xl overflow-hidden mb-4">

                            <div class="px-4 py-3 bg-slate-50 border-b border-slate-100">

                                <h3 class="text-[11px] font-semibold text-green-600">
                                    <i class="fas fa-address-book mr-1.5"></i>
                                    Contact Information
                                </h3>

                            </div>


                            <div class="px-4">

                                <div class="detail-row py-3 flex items-center justify-between gap-4">

                                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        Email Address
                                    </span>

                                    <span id="viewEmail" class="text-[12px] text-slate-700 text-right break-all">
                                        —
                                    </span>

                                </div>


                                <div class="detail-row py-3 flex items-center justify-between gap-4">

                                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        Contact Number
                                    </span>

                                    <span id="viewContact" class="text-[12px] text-slate-700 text-right">
                                        —
                                    </span>

                                </div>

                            </div>

                        </div>


                        <!-- EMPLOYMENT -->

                        <div class="border border-slate-200 rounded-xl overflow-hidden mb-4">

                            <div class="px-4 py-3 bg-slate-50 border-b border-slate-100">

                                <h3 class="text-[11px] font-semibold text-green-600">
                                    <i class="fas fa-briefcase mr-1.5"></i>
                                    Employment & Office Information
                                </h3>

                            </div>


                            <div class="px-4">

                                <div class="detail-row py-3 flex items-center justify-between gap-4">

                                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        Position
                                    </span>

                                    <span id="viewPositionDetail"
                                        class="text-[12px] font-medium text-green-700 text-right">
                                        —
                                    </span>

                                </div>


                                <div class="detail-row py-3 flex items-center justify-between gap-4">

                                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        Employment Status
                                    </span>

                                    <span id="viewEmploymentStatus"
                                        class="text-[10px] font-semibold px-2.5 py-1 rounded-full">
                                        —
                                    </span>

                                </div>


                                <div class="detail-row py-3 flex items-center justify-between gap-4">

                                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        Office
                                    </span>

                                    <span id="viewOfficeDetail" class="text-[12px] text-slate-700 text-right">
                                        —
                                    </span>

                                </div>


                                <div class="detail-row py-3 flex items-center justify-between gap-4">

                                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        Municipality
                                    </span>

                                    <span id="viewMunicipality" class="text-[12px] text-slate-700 text-right">
                                        —
                                    </span>

                                </div>


                                <div class="detail-row py-3 flex items-center justify-between gap-4">

                                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        Province
                                    </span>

                                    <span id="viewProvince" class="text-[12px] text-slate-700 text-right">
                                        —
                                    </span>

                                </div>

                            </div>

                        </div>


                        <!-- ACCOUNT -->

                        <div class="border border-slate-200 rounded-xl overflow-hidden">

                            <div class="px-4 py-3 bg-slate-50 border-b border-slate-100">

                                <h3 class="text-[11px] font-semibold text-green-600">
                                    <i class="fas fa-user-lock mr-1.5"></i>
                                    Account Activity
                                </h3>

                            </div>


                            <div class="px-4">

                                <div class="detail-row py-3 flex items-center justify-between gap-4">

                                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        Username
                                    </span>

                                    <span id="viewUsername" class="text-[12px] text-slate-700 text-right">
                                        —
                                    </span>

                                </div>


                                <div class="detail-row py-3 flex items-center justify-between gap-4">

                                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        Date Joined
                                    </span>

                                    <span id="viewDateJoined" class="text-[12px] text-slate-700 text-right">
                                        —
                                    </span>

                                </div>


                                <div class="detail-row py-3 flex items-center justify-between gap-4">

                                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        Last Login
                                    </span>

                                    <span id="viewLastLogin" class="text-[12px] text-slate-700 text-right">
                                        —
                                    </span>

                                </div>


                                <div class="py-3 flex items-center justify-between gap-4">

                                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        Password
                                    </span>

                                    <span class="text-[11px] text-slate-500">
                                        <i class="fas fa-lock mr-1"></i>
                                        Protected
                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div id="viewHistoryPanel" class="hidden p-5 max-h-[75vh] overflow-y-auto">
                        <div class="mb-4">
                            <h3 class="text-[13px] font-semibold text-green-600"><i class="fas fa-clock-rotate-left mr-1.5"></i>User History Log</h3>
                            <p id="historyUserSubtitle" class="text-[10px] text-slate-400 mt-0.5">Account activity and changes for this user</p>
                        </div>
                        <div id="userHistoryList" class="space-y-3"></div>
                    </div>

                    <div class="px-5 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">

                        <button type="button" onclick="closeViewModal()"
                            class="px-5 py-2 rounded-xl border border-slate-200 bg-white text-[12px] font-semibold text-slate-600 hover:border-green-400 hover:text-green-600 transition">

                            Close

                        </button>

                    </div>

                </div>

            </div>

        </div>


        <!-- CONFIRMATION MODAL -->

        <div id="confirmModal" class="hidden fixed inset-0 z-[60] modal-backdrop px-4">

            <div class="min-h-full flex items-center justify-center">

                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">

                    <div class="p-5">

                        <div
                            class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-3">

                            <i class="fas fa-triangle-exclamation"></i>

                        </div>

                        <h3 id="confirmTitle" class="text-[14px] font-semibold text-slate-800">
                            Confirm Action
                        </h3>

                        <p id="confirmMessage" class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            Are you sure?
                        </p>

                    </div>


                    <div class="px-5 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">

                        <button type="button" onclick="closeConfirmModal()"
                            class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-[11px] font-semibold text-slate-600">
                            Cancel
                        </button>

                        <button type="button" id="confirmActionButton"
                            class="px-4 py-2 rounded-lg bg-green-600 text-white text-[11px] font-semibold">
                            Confirm
                        </button>

                    </div>

                </div>

            </div>

        </div>


        <script>

            /* 
               USER DATA
    
            /*
             * Replace this demo array with your PHP-generated usersData.
             *
             * IMPORTANT:
             * The password is intentionally NOT stored here.
             */

            let usersData = [
                {
                    id: "MSWDO-STAFF-001",

                    firstName: "Juan",
                    middleName: "Santos",
                    lastName: "Dela Cruz",

                    username: "staff",

                    role: "Staff",

                    email: "staff@gmail.com",
                    contact: "09123456789",

                    position: "Social Welfare Assistant",
                    employmentStatus: "Permanent",

                    office: "MSWDO",
                    municipality: "San Enrique",
                    province: "Negros Occidental",

                    dateJoined: "January 15, 2026",
                    lastLogin: "August 9, 2026 · 8:42 AM",

                    isActive: true,

                    history: [
                        { date: "August 9, 2026 · 8:42 AM", action: "Successful login", details: "User signed in to the MSWDO system.", performedBy: "Juan Dela Cruz" },
                        { date: "January 15, 2026 · 9:00 AM", action: "Account created", details: "User account was created and activated.", performedBy: "Admin" }
                    ]
                }
            ];


            let currentEditId = null;

            let currentPage = 1;

            const rowsPerPage = 10;

            let filteredUsers = [...usersData];


            /* INITIALIZATION */

            document.addEventListener("DOMContentLoaded", function () {

                updateStats();

                applyFilters();

            });


            /* STATISTICS */

            function updateStats() {

                const total =
                    usersData.length;

                const admins =
                    usersData.filter(
                        user => user.role === "Admin"
                    ).length;

                const staff =
                    usersData.filter(
                        user => user.role === "Staff"
                    ).length;

                const inactive =
                    usersData.filter(
                        user => !user.isActive
                    ).length;


                document.getElementById("totalUsers").textContent =
                    total;

                document.getElementById("totalAdmins").textContent =
                    admins;

                document.getElementById("totalStaff").textContent =
                    staff;

                document.getElementById("totalInactive").textContent =
                    inactive;

            }


            /* FILTERS */

            function applyFilters() {

                const search =
                    document.getElementById("searchInput")
                        .value
                        .trim()
                        .toLowerCase();

                const role =
                    document.getElementById("roleFilter").value;

                const employment =
                    document.getElementById("employmentFilter").value;

                const status =
                    document.getElementById("statusFilter").value;


                filteredUsers =
                    usersData.filter(user => {

                        const fullName =
                            `${user.firstName} ${user.middleName || ""} ${user.lastName}`
                                .toLowerCase();

                        const searchableText =
                            [
                                user.id,
                                fullName,
                                user.username,
                                user.email,
                                user.contact,
                                user.position,
                                user.employmentStatus,
                                user.office,
                                user.municipality,
                                user.province
                            ]
                                .join(" ")
                                .toLowerCase();


                        const matchesSearch =
                            !search ||
                            searchableText.includes(search);


                        const matchesRole =
                            role === "all" ||
                            user.role === role;


                        const matchesEmployment =
                            employment === "all" ||
                            user.employmentStatus === employment;


                        const matchesStatus =
                            status === "all" ||
                            (
                                status === "Active"
                                    ? user.isActive
                                    : !user.isActive
                            );


                        return (
                            matchesSearch &&
                            matchesRole &&
                            matchesEmployment &&
                            matchesStatus
                        );

                    });


                currentPage = 1;

                renderTable();

            }


            /* TABLE */

            function renderTable() {

                const tableBody = document.getElementById("tableBody");

                tableBody.innerHTML = "";

                if (filteredUsers.length === 0) {

                    tableBody.innerHTML = `
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-users-slash"></i>
                                </div>
                                <p class="text-[13px] font-semibold text-slate-600">
                                    No users found
                                </p>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    Try changing your search or filters.
                                </p>
                            </div>
                        </td>
                    </tr>
                `;

                    updatePagination();
                    return;
                }

                const start = (currentPage - 1) * rowsPerPage;
                const end = start + rowsPerPage;
                const pageUsers = filteredUsers.slice(start, end);

                pageUsers.forEach(user => {

                    const fullName = [
                        user.firstName,
                        user.middleName,
                        user.lastName
                    ].filter(Boolean).join(" ");

                    const roleClass =
                        user.role === "Admin"
                            ? "badge-admin"
                            : "badge-staff";

                    const statusClass =
                        user.isActive
                            ? "badge-active"
                            : "badge-inactive";

                    const employmentClass =
                        getEmploymentBadgeClass(user.employmentStatus);

                    const row = document.createElement("tr");

                    row.className = "table-row";

                    row.innerHTML = `
                    <td class="col-name px-5 py-3" data-label="Name">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-green-50 text-green-600 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-user text-[11px]"></i>
                            </div>

                            <div class="name-email min-w-0">
                                <p class="font-semibold text-slate-700 truncate">
                                    ${escapeHtml(fullName)}
                                </p>
                                <p class="text-[10px] text-slate-400 truncate">
                                    ${escapeHtml(user.email || "No email")}
                                </p>
                            </div>
                        </div>
                    </td>

                    <td class="col-username px-5 py-3" data-label="Username">
                        <span class="text-slate-700">
                            ${escapeHtml(user.username)}
                        </span>
                    </td>

                    <td class="col-role px-5 py-3" data-label="Role">
                        <span class="text-[10px] font-semibold px-2.5 py-1 rounded-full ${roleClass}">
                            ${escapeHtml(user.role)}
                        </span>
                    </td>

                    <td class="col-position px-5 py-3" data-label="Position">
                        <span class="text-slate-700" title="${escapeHtml(user.position || "")}">
                            ${escapeHtml(user.position || "—")}
                        </span>
                    </td>

                    <td class="col-employment px-5 py-3" data-label="Employment">
                        <span class="text-[10px] font-semibold px-2.5 py-1 rounded-full ${employmentClass}">
                            ${escapeHtml(user.employmentStatus || "—")}
                        </span>
                    </td>

                    <td class="col-status px-5 py-3" data-label="Status">
                        <span class="text-[10px] font-semibold px-2.5 py-1 rounded-full ${statusClass}">
                            ${user.isActive ? "Active" : "Inactive"}
                        </span>
                    </td>

                    <td class="col-actions px-5 py-3" data-label="Actions">
                        <div class="action-buttons">

                            <button
                                type="button"
                                onclick="viewUser('${escapeAttribute(user.id)}')"
                                class="btn-action action-button rounded-lg text-slate-400 hover:text-green-600 hover:bg-green-50 border border-slate-100"
                                title="View Details"
                                aria-label="View Details">
                                <i class="fas fa-eye text-[11px]"></i>
                            </button>

                            <button
                                type="button"
                                onclick="editUser('${escapeAttribute(user.id)}')"
                                class="btn-action action-button rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 border border-slate-100"
                                title="Edit User"
                                aria-label="Edit User">
                                <i class="fas fa-pen text-[11px]"></i>
                            </button>

                            <button
                                type="button"
                                onclick="confirmToggleStatus('${escapeAttribute(user.id)}')"
                                class="btn-action action-button rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 border border-slate-100"
                                title="${user.isActive ? "Deactivate" : "Activate"} User"
                                aria-label="${user.isActive ? "Deactivate" : "Activate"} User">
                                <i class="fas ${user.isActive ? "fa-user-slash" : "fa-user-check"} text-[11px]"></i>
                            </button>

                        </div>
                    </td>
                `;

                    tableBody.appendChild(row);

                });

                updatePagination();

            }

            /* PAGINATION */

            function updatePagination() {

                const total =
                    filteredUsers.length;


                const totalPages =
                    Math.max(
                        1,
                        Math.ceil(total / rowsPerPage)
                    );


                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }


                const start =
                    total === 0
                        ? 0
                        : ((currentPage - 1) * rowsPerPage) + 1;


                const end =
                    Math.min(
                        currentPage * rowsPerPage,
                        total
                    );


                document.getElementById("paginationInfo")
                    .textContent =
                    total === 0
                        ? "Showing 0 users"
                        : `Showing ${start}–${end} of ${total}`;


                document.getElementById("rowCount")
                    .textContent =
                    `Showing ${total} user${total === 1 ? "" : "s"}`;


                document.getElementById("pageNumber")
                    .textContent =
                    currentPage;


                const prev =
                    document.getElementById("prevBtn");

                const next =
                    document.getElementById("nextBtn");


                prev.disabled =
                    currentPage <= 1;


                next.disabled =
                    currentPage >= totalPages;


                prev.classList.toggle(
                    "opacity-50",
                    prev.disabled
                );


                next.classList.toggle(
                    "opacity-50",
                    next.disabled
                );

            }


            function previousPage() {

                if (currentPage > 1) {

                    currentPage--;

                    renderTable();

                }

            }


            function nextPage() {

                const totalPages =
                    Math.ceil(
                        filteredUsers.length / rowsPerPage
                    );


                if (currentPage < totalPages) {

                    currentPage++;

                    renderTable();

                }

            }


            /* ADD USER */

            function openAddModal() {

                currentEditId = null;


                document.getElementById("userForm").reset();


                document.getElementById("modalTitle")
                    .textContent =
                    "Add New User";


                document.getElementById("modalSubtitle")
                    .textContent =
                    "Create a new system account.";


                document.getElementById("saveBtnText")
                    .textContent =
                    "Save User";


                document.getElementById("accountId")
                    .value =
                    generateAccountId();


                document.getElementById("office")
                    .value =
                    "MSWDO";


                document.getElementById("municipality")
                    .value =
                    "San Enrique";


                document.getElementById("province")
                    .value =
                    "Negros Occidental";


                document.getElementById("isActive")
                    .checked =
                    true;


                setPasswordRequired(true);


                clearValidation();


                document.getElementById("userModal")
                    .classList.remove("hidden");


                document.body.classList.add("overflow-hidden");


                setTimeout(() => {

                    document.getElementById("firstName").focus();

                }, 100);

            }


            /* EDIT USER */

            function editUser(id) {

                const user =
                    usersData.find(
                        item => item.id === id
                    );


                if (!user) {
                    return;
                }


                currentEditId =
                    id;


                document.getElementById("modalTitle")
                    .textContent =
                    "Edit User";


                document.getElementById("modalSubtitle")
                    .textContent =
                    "Update this user's account and employment information.";


                document.getElementById("saveBtnText")
                    .textContent =
                    "Save Changes";


                document.getElementById("accountId")
                    .value =
                    user.id;


                document.getElementById("firstName")
                    .value =
                    user.firstName;


                document.getElementById("middleName")
                    .value =
                    user.middleName || "";


                document.getElementById("lastName")
                    .value =
                    user.lastName;


                document.getElementById("username")
                    .value =
                    user.username;


                document.getElementById("password")
                    .value =
                    "";


                document.getElementById("confirmPassword")
                    .value =
                    "";


                document.getElementById("role")
                    .value =
                    user.role;


                document.getElementById("email")
                    .value =
                    user.email || "";


                document.getElementById("contact")
                    .value =
                    user.contact || "";


                document.getElementById("position")
                    .value =
                    user.position || "";


                document.getElementById("employmentStatus")
                    .value =
                    user.employmentStatus || "";


                document.getElementById("office")
                    .value =
                    user.office || "";


                document.getElementById("municipality")
                    .value =
                    user.municipality || "";


                document.getElementById("province")
                    .value =
                    user.province || "";


                document.getElementById("isActive")
                    .checked =
                    !!user.isActive;


                setPasswordRequired(false);


                clearValidation();


                document.getElementById("userModal")
                    .classList.remove("hidden");


                document.body.classList.add("overflow-hidden");

            }


            /* PASSWORD REQUIREMENT */

            function setPasswordRequired(required) {

                const password =
                    document.getElementById("password");

                const confirm =
                    document.getElementById("confirmPassword");


                const label =
                    document.getElementById("passwordLabel");

                const confirmLabel =
                    document.getElementById("confirmPasswordLabel");


                if (required) {

                    label.classList.add("req");

                    confirmLabel.classList.add("req");

                    password.required = true;

                    confirm.required = true;

                    password.placeholder =
                        "Min 8 characters";

                    confirm.placeholder =
                        "Re-enter password";

                } else {

                    label.classList.remove("req");

                    confirmLabel.classList.remove("req");

                    password.required = false;

                    confirm.required = false;

                    password.placeholder =
                        "Leave blank to keep current password";

                    confirm.placeholder =
                        "Re-enter new password";

                }

            }


            /* SAVE USER + VALIDATION */

            function saveUser(event) {

                event.preventDefault();


                clearValidation();


                const firstName =
                    getValue("firstName");

                const middleName =
                    getValue("middleName");

                const lastName =
                    getValue("lastName");

                const username =
                    getValue("username");

                const password =
                    document.getElementById("password").value;

                const confirmPassword =
                    document.getElementById("confirmPassword").value;

                const role =
                    getValue("role");

                const email =
                    getValue("email");

                const contact =
                    getValue("contact");

                const position =
                    getValue("position");

                const employmentStatus =
                    getValue("employmentStatus");

                const office = "MSWDO";
                const municipality = "San Enrique";
                const province = "Negros Occidental";

                const isActive =
                    document.getElementById("isActive").checked;


                let isValid = true;


                /* REQUIRED FIELDS */

                if (!firstName) {

                    showValidation(
                        "firstName",
                        "Please enter the first name."
                    );

                    isValid = false;

                } else if (!isValidName(firstName)) {

                    showValidation(
                        "firstName",
                        "First name may only contain letters, spaces, hyphens, or apostrophes."
                    );

                    isValid = false;

                }


                if (!lastName) {

                    showValidation(
                        "lastName",
                        "Please enter the last name."
                    );

                    isValid = false;

                } else if (!isValidName(lastName)) {

                    showValidation(
                        "lastName",
                        "Last name may only contain letters, spaces, hyphens, or apostrophes."
                    );

                    isValid = false;

                }


                if (!username) {

                    showValidation(
                        "username",
                        "Please enter a username."
                    );

                    isValid = false;

                } else if (!/^[A-Za-z0-9._-]{4,30}$/.test(username)) {

                    showValidation(
                        "username",
                        "Username must be 4–30 characters and may contain letters, numbers, dot, underscore, or hyphen."
                    );

                    isValid = false;

                } else {

                    const duplicate =
                        usersData.some(user =>

                            user.username.toLowerCase() ===
                            username.toLowerCase() &&

                            user.id !== currentEditId

                        );


                    if (duplicate) {

                        showValidation(
                            "username",
                            "This username is already being used."
                        );

                        isValid = false;

                    }

                }


                const isAdding =
                    currentEditId === null;


                if (isAdding && !password) {

                    showValidation(
                        "password",
                        "Password is required when creating a new user."
                    );

                    isValid = false;

                }


                if (password) {

                    if (password.length < 8) {

                        showValidation(
                            "password",
                            "Password must contain at least 8 characters."
                        );

                        isValid = false;

                    }


                    if (password !== confirmPassword) {

                        showValidation(
                            "confirmPassword",
                            "Passwords do not match."
                        );

                        isValid = false;

                    }

                }


                if (isAdding && !confirmPassword) {

                    showValidation(
                        "confirmPassword",
                        "Please confirm the password."
                    );

                    isValid = false;

                }


                if (!role) {

                    showValidation(
                        "role",
                        "Please select a system role."
                    );

                    isValid = false;

                }


                if (!email) {

                    showValidation(
                        "email",
                        "Please enter an email address."
                    );

                    isValid = false;

                } else if (!isValidEmail(email)) {

                    showValidation(
                        "email",
                        "Please enter a valid email address."
                    );

                    isValid = false;

                }


                if (!contact) {

                    showValidation(
                        "contact",
                        "Please enter a contact number."
                    );

                    isValid = false;

                } else if (!isValidPhilippineContact(contact)) {

                    showValidation(
                        "contact",
                        "Enter a valid Philippine mobile number, e.g. 09123456789."
                    );

                    isValid = false;

                }



                if (!position) {

                    showValidation(
                        "position",
                        "Please enter the employee's position."
                    );

                    isValid = false;

                }


                if (!employmentStatus) {

                    showValidation(
                        "employmentStatus",
                        "Please select the employment status."
                    );

                    isValid = false;

                }


                if (!office) {

                    showValidation(
                        "office",
                        "Please enter the office."
                    );

                    isValid = false;

                }


                if (!municipality) {

                    showValidation(
                        "municipality",
                        "Please enter the municipality."
                    );

                    isValid = false;

                }


                if (!province) {

                    showValidation(
                        "province",
                        "Please enter the province."
                    );

                    isValid = false;

                }



                if (!isValid) {

                    showToast(
                        "Please complete all required fields.",
                        "error"
                    );

                    const firstError =
                        document.querySelector(
                            ".field.input-error"
                        );


                    if (firstError) {

                        firstError.scrollIntoView({
                            behavior: "smooth",
                            block: "center"
                        });

                        setTimeout(() => {
                            firstError.focus();
                        }, 300);

                    }

                    return;

                }


                if (currentEditId === null) {

                    const newUser = {

                        id:
                            document.getElementById("accountId").value,

                        firstName,
                        middleName,
                        lastName,

                        username,

                        role,

                        email,
                        contact,

                        position,
                        employmentStatus,

                        office,
                        municipality,
                        province,

                        dateJoined:
                            formatCurrentDate(),

                        lastLogin:
                            "Never",

                        isActive,

                        history: [
                            { date: formatCurrentDate(), action: "Account created", details: "User account was created and activated.", performedBy: "Admin" }
                        ]

                    };


                    usersData.push(newUser);


                    /*
                     * IMPORTANT:
                     * Send the password to your PHP backend here.
                     * Never store the plain password in usersData.
                     */


                    showToast(
                        "User created successfully."
                    );

                }


                else {

                    const user =
                        usersData.find(
                            item => item.id === currentEditId
                        );


                    if (!user) {

                        showToast(
                            "Unable to find the selected user.",
                            "error"
                        );

                        return;

                    }


                    user.firstName =
                        firstName;

                    user.middleName =
                        middleName;

                    user.lastName =
                        lastName;

                    user.username =
                        username;

                    user.role =
                        role;

                    user.email =
                        email;

                    user.contact =
                        contact;

                    user.position =
                        position;

                    user.employmentStatus =
                        employmentStatus;

                    user.office =
                        office;

                    user.municipality =
                        municipality;

                    user.province =
                        province;

                    user.isActive =
                        isActive;


                    /*
                     * If password is not blank,
                     * update it through the backend.
                     */


                    if (!Array.isArray(user.history)) { user.history = []; }
                    user.history.unshift({
                        date: formatCurrentDate(),
                        action: "User information updated",
                        details: "Personal, account, employment, or access information was updated.",
                        performedBy: "Admin"
                    });

                    showToast(
                        "User information updated successfully."
                    );

                }


                closeModal();

                updateStats();

                applyFilters();

            }


            /* VIEW USER */

            function viewUser(id) {

                const user =
                    usersData.find(
                        item => item.id === id
                    );


                if (!user) {
                    return;
                }


                const fullName =
                    [
                        user.firstName,
                        user.middleName,
                        user.lastName
                    ]
                        .filter(Boolean)
                        .join(" ");


                document.getElementById("viewFullName")
                    .textContent =
                    fullName;


                document.getElementById("viewPosition")
                    .textContent =
                    user.position;


                document.getElementById("viewOffice")
                    .textContent =
                    `${user.office} · ${user.municipality}, ${user.province}`;


                document.getElementById("viewAccountId")
                    .textContent =
                    user.id;


                document.getElementById("viewFirstName")
                    .textContent =
                    user.firstName;


                document.getElementById("viewMiddleName")
                    .textContent =
                    user.middleName || "—";


                document.getElementById("viewLastName")
                    .textContent =
                    user.lastName;


                document.getElementById("viewEmail")
                    .textContent =
                    user.email || "—";


                document.getElementById("viewContact")
                    .textContent =
                    user.contact || "—";


                document.getElementById("viewPositionDetail")
                    .textContent =
                    user.position;


                const employmentElement =
                    document.getElementById(
                        "viewEmploymentStatus"
                    );


                employmentElement.textContent =
                    user.employmentStatus;


                employmentElement.className =
                    `text-[10px] font-semibold px-2.5 py-1 rounded-full ${getEmploymentBadgeClass(
                        user.employmentStatus
                    )
                    }`;


                document.getElementById("viewOfficeDetail")
                    .textContent =
                    user.office;


                document.getElementById("viewMunicipality")
                    .textContent =
                    user.municipality;


                document.getElementById("viewProvince")
                    .textContent =
                    user.province;


                document.getElementById("viewUsername")
                    .textContent =
                    user.username;


                document.getElementById("viewDateJoined")
                    .textContent =
                    user.dateJoined || "—";


                document.getElementById("viewLastLogin")
                    .textContent =
                    user.lastLogin || "Never";


                const roleElement =
                    document.getElementById("viewRole");


                roleElement.textContent =
                    user.role;


                roleElement.className =
                    `inline-block text-[10px] font-semibold px-2.5 py-1 rounded-full ${user.role === "Admin"
                        ? "badge-admin"
                        : "badge-staff"
                    }`;


                const statusElement =
                    document.getElementById("viewStatus");


                statusElement.textContent =
                    user.isActive
                        ? "Active"
                        : "Inactive";


                statusElement.className =
                    `text-[10px] font-semibold px-2.5 py-1 rounded-full ${user.isActive
                        ? "badge-active"
                        : "badge-inactive"
                    }`;


                renderUserHistory(user);
                switchViewTab("details");

                document.getElementById("viewModal")
                    .classList.remove("hidden");


                document.body.classList.add("overflow-hidden");

            }


            /* TOGGLE STATUS */

            function confirmToggleStatus(id) {

                const user =
                    usersData.find(
                        item => item.id === id
                    );


                if (!user) {
                    return;
                }


                const action =
                    user.isActive
                        ? "deactivate"
                        : "activate";


                document.getElementById("confirmTitle")
                    .textContent =
                    `${capitalize(action)} User`;


                document.getElementById("confirmMessage")
                    .textContent =
                    user.isActive
                        ? `Are you sure you want to deactivate ${user.firstName} ${user.lastName}? They will no longer be able to log in.`
                        : `Are you sure you want to activate ${user.firstName} ${user.lastName}? They will be able to access the system again.`;


                document.getElementById("confirmActionButton")
                    .onclick =
                    function () {

                        user.isActive =
                            !user.isActive;

                        if (!Array.isArray(user.history)) { user.history = []; }
                        user.history.unshift({
                            date: formatCurrentDate(),
                            action: user.isActive ? "Account activated" : "Account deactivated",
                            details: user.isActive ? "User account access was restored." : "User account access was disabled.",
                            performedBy: "Admin"
                        });

                        closeConfirmModal();

                        updateStats();

                        applyFilters();


                        showToast(
                            user.isActive
                                ? "User account activated."
                                : "User account deactivated."
                        );

                    };


                document.getElementById("confirmModal")
                    .classList.remove("hidden");


                document.body.classList.add("overflow-hidden");

            }


            /* USER VIEW TABS */

            function switchViewTab(tab) {
                const detailsPanel = document.getElementById("viewDetailsPanel");
                const historyPanel = document.getElementById("viewHistoryPanel");
                const detailsTab = document.getElementById("userDetailsTab");
                const historyTab = document.getElementById("userHistoryTab");
                const isDetails = tab === "details";

                detailsPanel.classList.toggle("hidden", !isDetails);
                historyPanel.classList.toggle("hidden", isDetails);
                detailsTab.classList.toggle("active", isDetails);
                historyTab.classList.toggle("active", !isDetails);
                detailsTab.setAttribute("aria-selected", isDetails ? "true" : "false");
                historyTab.setAttribute("aria-selected", isDetails ? "false" : "true");
            }

            function renderUserHistory(user) {
                const container = document.getElementById("userHistoryList");
                const subtitle = document.getElementById("historyUserSubtitle");
                const fullName = [user.firstName, user.middleName, user.lastName].filter(Boolean).join(" ");
                subtitle.textContent = `${fullName} · ${user.username} · Account history`;
                const history = Array.isArray(user.history) ? user.history : [];

                if (history.length === 0) {
                    container.innerHTML = `<div class="history-empty"><i class="fas fa-clock-rotate-left text-2xl mb-3"></i><p class="text-[11px] font-semibold text-slate-500">No history recorded</p><p class="text-[10px] mt-1">User activity will appear here as actions are performed.</p></div>`;
                    return;
                }

                container.innerHTML = history.map(entry => `
                    <div class="history-item">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="history-action">${escapeHtml(entry.action || "Activity")}</p>
                                <p class="history-details">${escapeHtml(entry.details || "No additional details.")}</p>
                            </div>
                            <span class="text-[9px] text-slate-400 whitespace-nowrap">${escapeHtml(entry.date || "—")}</span>
                        </div>
                        <p class="history-meta"><i class="fas fa-user-shield mr-1"></i>Performed by: ${escapeHtml(entry.performedBy || "Admin")}</p>
                    </div>`).join("");
            }

            function escapeHtml(value) {
                return String(value ?? "")
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/\"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            /* MODAL CONTROLS */

            function closeModal() {

                document.getElementById("userModal")
                    .classList.add("hidden");

                document.body.classList.remove("overflow-hidden");

                clearValidation();

                document.getElementById("userForm").reset();

                currentEditId = null;

            }


            function closeViewModal() {

                document.getElementById("viewModal")
                    .classList.add("hidden");

                document.body.classList.remove("overflow-hidden");

            }


            function closeConfirmModal() {

                document.getElementById("confirmModal")
                    .classList.add("hidden");

                document.body.classList.remove("overflow-hidden");

            }


            /* VALIDATION */

            function getValue(id) {

                return document
                    .getElementById(id)
                    .value
                    .trim();

            }


            function showValidation(id, message) {

                const field =
                    document.getElementById(id);

                const error =
                    document.getElementById(
                        `${id}Error`
                    );


                if (field) {

                    field.classList.add(
                        "input-error"
                    );

                    field.classList.remove(
                        "input-success"
                    );

                }


                if (error) {

                    error.textContent =
                        message;

                    error.classList.add(
                        "show"
                    );

                }

            }


            function clearValidation() {

                document
                    .querySelectorAll(".field")
                    .forEach(field => {

                        field.classList.remove(
                            "input-error",
                            "input-success"
                        );

                    });


                document
                    .querySelectorAll(".validation-message")
                    .forEach(error => {

                        error.textContent =
                            "";

                        error.classList.remove(
                            "show"
                        );

                    });

            }


            function isValidName(name) {

                return /^[A-Za-zÀ-ÖØ-öø-ÿ\s.'-]+$/.test(
                    name
                );

            }


            function isValidEmail(email) {

                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
                    email
                );

            }


            function isValidPhilippineContact(contact) {

                const normalized =
                    contact.replace(
                        /[\s-]/g,
                        ""
                    );


                return /^(09\d{9}|\+639\d{9})$/.test(
                    normalized
                );

            }


            /* PASSWORD TOGGLE */

            function toggleUserPassword(
                inputId,
                button
            ) {

                const input =
                    document.getElementById(
                        inputId
                    );


                const icon =
                    button.querySelector("i");


                if (input.type === "password") {

                    input.type =
                        "text";

                    icon.classList.remove(
                        "fa-eye"
                    );

                    icon.classList.add(
                        "fa-eye-slash"
                    );

                } else {

                    input.type =
                        "password";

                    icon.classList.remove(
                        "fa-eye-slash"
                    );

                    icon.classList.add(
                        "fa-eye"
                    );

                }

            }


            /* ACCOUNT ID */

            function generateAccountId() {

                const staffNumbers =
                    usersData
                        .map(user => {

                            const match =
                                user.id.match(
                                    /(\d+)$/
                                );

                            return match
                                ? parseInt(match[1], 10)
                                : 0;

                        });


                const nextNumber =
                    Math.max(
                        0,
                        ...staffNumbers
                    ) + 1;


                return `MSWDO-STAFF-${String(nextNumber).padStart(3, "0")}`;

            }


            /* EMPLOYMENT BADGES */

            function getEmploymentBadgeClass(
                status
            ) {

                switch (status) {

                    case "Permanent":
                        return "badge-permanent";

                    case "Contractual":
                        return "badge-contractual";

                    case "Casual":
                        return "badge-casual";

                    case "Job Order":
                        return "badge-job-order";

                    case "Temporary":
                        return "badge-temporary";

                    case "Probationary":
                        return "badge-probationary";

                    default:
                        return "badge-inactive";

                }

            }


            /* DATE */

            function formatCurrentDate() {

                return new Date().toLocaleDateString(
                    "en-PH",
                    {
                        year: "numeric",
                        month: "long",
                        day: "numeric"
                    }
                );

            }


            /* ESCAPE HTML */

            function escapeHtml(value) {

                return String(value ?? "")
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");

            }


            function escapeAttribute(value) {

                return String(value ?? "")
                    .replace(/\\/g, "\\\\")
                    .replace(/'/g, "\\'");

            }


            /* CAPITALIZE */

            function capitalize(value) {

                return value.charAt(0).toUpperCase() +
                    value.slice(1);

            }


            /* TOAST */

            function showToast(
                message,
                type = "success"
            ) {

                const existing =
                    document.getElementById(
                        "userManagementToast"
                    );


                if (existing) {
                    existing.remove();
                }


                const toast =
                    document.createElement("div");


                toast.id =
                    "userManagementToast";


                const icon =
                    type === "error"
                        ? "fa-circle-exclamation"
                        : "fa-check";


                const bg =
                    type === "error"
                        ? "bg-red-600"
                        : "bg-green-700";


                toast.className =
                    `fixed bottom-5 right-5 z-[100] ${bg} text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-2 text-[11px] font-medium max-w-[calc(100vw-40px)]`;


                toast.innerHTML = `

        <div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0">

            <i class="fas ${icon} text-[9px]"></i>

        </div>

        <span>
            ${escapeHtml(message)}
        </span>

    `;


                document.body.appendChild(
                    toast
                );


                setTimeout(() => {

                    toast.style.opacity =
                        "0";

                    toast.style.transform =
                        "translateY(8px)";

                    toast.style.transition =
                        "all .25s ease";


                    setTimeout(() => {

                        toast.remove();

                    }, 250);

                }, 2500);

            }


            /* KEYBOARD / BACKDROP CONTROLS */

            document
                .getElementById("userModal")
                .addEventListener(
                    "click",
                    function (event) {

                        if (
                            event.target === this
                        ) {

                            closeModal();

                        }

                    }
                );


            document
                .getElementById("viewModal")
                .addEventListener(
                    "click",
                    function (event) {

                        if (
                            event.target === this
                        ) {

                            closeViewModal();

                        }

                    }
                );


            document
                .getElementById("confirmModal")
                .addEventListener(
                    "click",
                    function (event) {

                        if (
                            event.target === this
                        ) {

                            closeConfirmModal();

                        }

                    }
                );


            document.addEventListener(
                "keydown",
                function (event) {

                    if (
                        event.key !== "Escape"
                    ) {
                        return;
                    }


                    closeModal();

                    closeViewModal();

                    closeConfirmModal();

                }
            );


            /* LIVE FIELD VALIDATION */

            document.addEventListener(
                "input",
                function (event) {

                    if (
                        event.target.classList.contains(
                            "field"
                        )
                    ) {

                        event.target.classList.remove(
                            "input-error"
                        );


                        const error =
                            document.getElementById(
                                `${event.target.id}Error`
                            );


                        if (error) {

                            error.classList.remove(
                                "show"
                            );

                        }

                    }

                }
            );

        </script>

    </div>
</body>

</html>