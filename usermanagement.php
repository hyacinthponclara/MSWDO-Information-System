<?php
require 'auth.php';
requireRole(['Social Worker', 'Admin']);
require 'db_connect.php';
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

        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(26, 92, 58, .2);
            border-radius: 2px;
        }

        /* Modal backdrop */
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        /* Role badge colors */
        .badge-admin {
            background: #FEE2E2;
            color: #DC2626;
        }

        .badge-staff {
            background: #D1FAE5;
            color: #059669;
        }
    </style>
</head>

<body class="bg-slate2 min-h-screen flex">

    <!-- ══════════════════════════ SIDEBAR ══════════════════════════ -->
    <?php require 'sidebar.php'; ?>
    <!-- ══════════════════════════ MAIN ══════════════════════════════ -->
    <div class="ml-64 flex-1 flex flex-col min-h-screen">

        <!-- Top Bar -->
        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <span class="text-green-600 font-semibold">User Management</span>
            </div>
        </header>

        <main class="flex-1 p-6 space-y-5 overflow-y-auto">

            <!-- Page Title & Add Button -->
            <div class="flex flex-wrap items-center justify-between gap-3 animate-fade-up">
                <div>
                    <h1 class="text-xl font-serif text-green-600">User Management</h1>
                    <p class="text-[13px] text-slate-500 mt-0.5">Manage system users, roles, and access permissions.</p>
                </div>
                <button onclick="openAddModal()"
                    class="btn-action text-[12px] font-semibold text-white bg-green-600 rounded-lg px-4 py-2 hover:bg-green-700 transition-all flex items-center gap-2">
                    <i class="fas fa-user-plus"></i> Add User
                </button>
            </div>

            <!-- Stats Cards  -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 animate-fade-up-1">
                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total Users</p>
                    <p class="text-2xl font-bold text-green-600" id="totalUsers">0</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">Admins</p>
                    <p class="text-2xl font-bold text-red-500" id="totalAdmins">0</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">Staff</p>
                    <p class="text-2xl font-bold text-emerald-500" id="totalStaff">0</p>
                </div>
            </div>

            <!-- Search & Filter -->
            <div
                class="flex flex-wrap items-center gap-3 animate-fade-up-2 bg-white rounded-2xl border border-slate-200 p-4">
                <div class="flex flex-wrap items-center gap-3 flex-1">
                    <div class="relative flex-1 min-w-[200px]">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="searchInput" placeholder="Search by name, username, or email..."
                            class="text-[12px] pl-8 pr-3 py-1.5 border border-slate-200 rounded-lg bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none w-full"
                            oninput="applyFilters()" />
                    </div>
                    <div>
                        <select id="roleFilter"
                            class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                            onchange="applyFilters()">
                            <option value="all">All Roles</option>
                            <option value="Admin">Admin</option>
                            <option value="Staff">Staff</option>
                        </select>
                    </div>
                    <div>
                        <select id="statusFilter"
                            class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                            onchange="applyFilters()">
                            <option value="all">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-slate-400" id="rowCount">Showing 0 users</span>
                </div>
            </div>

            <!-- User Table -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-up-3">
                <div class="overflow-x-auto">
                    <table class="w-full text-[12px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Name</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Username</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Role</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Email</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Contact</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Status</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Last Login</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="tableBody">
                            <!-- Rows injected by JS -->
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between px-5 py-3 border-t border-slate-100">
                    <span class="text-[11px] text-slate-400" id="paginationInfo">Showing 1–10 of 10</span>
                    <div class="flex items-center gap-1">
                        <button
                            class="text-[11px] text-slate-400 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors">Previous</button>
                        <button class="text-[11px] font-medium text-white bg-green-600 rounded-lg px-3 py-1">1</button>
                        <button
                            class="text-[11px] text-slate-600 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors">Next</button>
                    </div>
                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer
            class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <!-- ══════════════════════════ ADD/EDIT USER MODAL ══════════════════════════ -->
    <div id="userModal" class="fixed inset-0 z-50 flex items-center justify-center modal-backdrop hidden">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto animate-modal-in">
            <div
                class="sticky top-0 bg-white z-10 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-[16px] font-semibold text-green-600" id="modalTitle">Add New User</h2>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <form id="userForm" onsubmit="saveUser(event)">
                    <input type="hidden" id="editUserId" value="" />

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="field-label req">First Name</label>
                            <input type="text" id="firstName" class="field" placeholder="e.g. Juan" required />
                        </div>
                        <div>
                            <label class="field-label req">Last Name</label>
                            <input type="text" id="lastName" class="field" placeholder="e.g. Dela Cruz" required />
                        </div>
                    </div>

                    <div>
                        <label class="field-label">Middle Name</label>
                        <input type="text" id="middleName" class="field" placeholder="e.g. Santos" />
                    </div>

                    <div>
                        <label class="field-label req">Username</label>
                        <input type="text" id="username" class="field" placeholder="e.g. jdelacruz" required />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="field-label req" id="passwordLabel">Password</label>
                            <input type="password" id="password" class="field" placeholder="Min 8 characters" />
                        </div>
                        <div>
                            <label class="field-label req" id="confirmPasswordLabel">Confirm Password</label>
                            <input type="password" id="confirmPassword" class="field" placeholder="Re-enter password" />
                        </div>
                    </div>

                    <div>
                        <label class="field-label req">Role</label>
                        <select id="role" class="field" required>
                            <option value="">Select Role</option>
                            <option value="Admin">Admin</option>
                            <option value="Staff">Staff</option>
                        </select>
                    </div>

                    <div>
                        <label class="field-label">Email Address</label>
                        <input type="email" id="email" class="field" placeholder="e.g. juan@example.com" />
                    </div>

                    <div>
                        <label class="field-label">Contact Number</label>
                        <input type="text" id="contact" class="field" placeholder="e.g. 09123456789" />
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" id="isActive" class="w-4 h-4 accent-green-600" checked />
                        <label for="isActive" class="text-[12px] text-slate-600">Account Active</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" onclick="closeModal()"
                            class="text-[13px] font-medium text-slate-600 border border-slate-200 rounded-xl px-5 py-2 hover:border-green-400 hover:text-green-600 transition-all">
                            Cancel
                        </button>
                        <button type="submit"
                            class="text-[13px] font-semibold text-white bg-green-600 rounded-xl px-6 py-2 hover:bg-green-500 transition-all">
                            <i class="fas fa-save mr-1.5"></i> <span id="saveBtnText">Save User</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast"
        class="fixed bottom-6 right-6 bg-green-700 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-green-300"></i>
        <span id="toastMsg">Action completed!</span>
    </div>

    <script>
        // ── Sample Users ──
        let users = [{
            id: 1,
            firstName: 'Rosa',
            lastName: 'Villanueva',
            middleName: 'T.',
            username: 'rvillanueva',
            role: 'Admin',
            email: 'rosa.villanueva@mswdo.gov.ph',
            contact: '09123456789',
            isActive: true,
            lastLogin: '2026-04-15 08:30 AM'
        }, {
            id: 2,
            firstName: 'Ana',
            lastName: 'Reyes',
            middleName: 'M.',
            username: 'areyes',
            role: 'Staff',
            email: 'ana.reyes@mswdo.gov.ph',
            contact: '09123456788',
            isActive: true,
            lastLogin: '2026-04-15 09:15 AM'
        }, {
            id: 3,
            firstName: 'Ben',
            lastName: 'Torres',
            middleName: 'G.',
            username: 'btorres',
            role: 'Staff',
            email: 'ben.torres@mswdo.gov.ph',
            contact: '09123456787',
            isActive: false,
            lastLogin: '2026-04-10 04:20 PM'
        }, {
            id: 4,
            firstName: 'Juan',
            lastName: 'Dela Cruz',
            middleName: 'R.',
            username: 'jdelacruz',
            role: 'Admin',
            email: 'juan.delacruz@mswdo.gov.ph',
            contact: '09123456785',
            isActive: true,
            lastLogin: '2026-04-13 11:45 AM'
        },];

        let nextId = 5;
        let editUserId = null;
        let filteredData = [...users];

        // ── Role badge mapping ──
        function getRoleBadge(role) {
            const classes = {
                'Admin': 'badge-admin',
                'Staff': 'badge-staff'
            };
            return classes[role] || 'bg-slate-100 text-slate-700';
        }

        function getStatusBadge(isActive) {
            return isActive ?
                'bg-emerald-100 text-emerald-700' :
                'bg-red-100 text-red-700';
        }

        function getStatusText(isActive) {
            return isActive ? 'Active' : 'Inactive';
        }

        // ── Render table ──
        function renderTable(data) {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML =
                    `<tr><td colspan="8" class="text-center py-8 text-slate-400 text-[13px]">No users found. Click "Add User" to create one.</td></tr>`;
                updateStats(data);
                return;
            }

            data.forEach(user => {
                const tr = document.createElement('tr');
                tr.className = 'table-row';
                const fullName = [user.firstName, user.middleName, user.lastName].filter(Boolean).join(' ');
                // Determine toggle button text and icon
                const toggleText = user.isActive ? 'Pause' : 'Continue';
                const toggleIcon = user.isActive ? 'fa-pause' : 'fa-play';
                const toggleColor = user.isActive ? 'text-amber-600 bg-amber-50 border-amber-200 hover:bg-amber-100' :
                    'text-emerald-600 bg-emerald-50 border-emerald-200 hover:bg-emerald-100';
                tr.innerHTML = `
                    <td class="px-5 py-3 font-medium text-green-700">${fullName}</td>
                    <td class="px-5 py-3 text-slate-600">${user.username}</td>
                    <td class="px-5 py-3"><span class="${getRoleBadge(user.role)} px-2 py-0.5 rounded text-[10px] font-semibold">${user.role}</span></td>
                    <td class="px-5 py-3 text-slate-600">${user.email || '-'}</td>
                    <td class="px-5 py-3 text-slate-600">${user.contact || '-'}</td>
                    <td class="px-5 py-3"><span class="${getStatusBadge(user.isActive)} px-2.5 py-0.5 rounded-full text-[10px] font-semibold">${getStatusText(user.isActive)}</span></td>
                    <td class="px-5 py-3 text-slate-400">${user.lastLogin || 'Never'}</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-1.5">
                            <button onclick="openEditModal(${user.id})" class="text-[11px] font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg px-2.5 py-1 hover:bg-blue-100 transition-colors flex items-center gap-1">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="toggleUserStatus(${user.id})" class="text-[11px] font-medium ${toggleColor} border rounded-lg px-2.5 py-1 transition-colors flex items-center gap-1">
                                <i class="fas ${toggleIcon}"></i> ${toggleText}
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            updateStats(data);
            document.getElementById('rowCount').textContent = `Showing ${data.length} users`;
            document.getElementById('paginationInfo').textContent = `Showing 1–${data.length} of ${data.length}`;
        }

        // ── Update statistics ──
        function updateStats(data) {
            const total = data.length;
            const admins = data.filter(u => u.role === 'Admin').length;
            const staff = data.filter(u => u.role === 'Staff').length;

            document.getElementById('totalUsers').textContent = total;
            document.getElementById('totalAdmins').textContent = admins;
            document.getElementById('totalStaff').textContent = staff;
        }

        // ── Apply filters ──
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;

            filteredData = users.filter(user => {
                const fullName = [user.firstName, user.middleName, user.lastName].filter(Boolean).join(' ').toLowerCase();
                const searchMatch = fullName.includes(searchTerm) ||
                    user.username.toLowerCase().includes(searchTerm) ||
                    (user.email && user.email.toLowerCase().includes(searchTerm));

                const roleMatch = roleFilter === 'all' || user.role === roleFilter;
                const statusMatch = statusFilter === 'all' ||
                    (statusFilter === 'Active' && user.isActive) ||
                    (statusFilter === 'Inactive' && !user.isActive);

                return searchMatch && roleMatch && statusMatch;
            });

            renderTable(filteredData);
        }

        // ── Modal functions ──
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New User';
            document.getElementById('saveBtnText').textContent = 'Save User';
            document.getElementById('editUserId').value = '';
            document.getElementById('userForm').reset();
            document.getElementById('isActive').checked = true;
            document.getElementById('password').required = true;
            document.getElementById('confirmPassword').required = true;
            document.getElementById('passwordLabel').textContent = 'Password *';
            document.getElementById('confirmPasswordLabel').textContent = 'Confirm Password *';
            document.getElementById('userModal').classList.remove('hidden');
            document.getElementById('userModal').style.display = 'flex';
        }

        function openEditModal(userId) {
            const user = users.find(u => u.id === userId);
            if (!user) return;

            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('saveBtnText').textContent = 'Update User';
            document.getElementById('editUserId').value = user.id;
            document.getElementById('firstName').value = user.firstName;
            document.getElementById('lastName').value = user.lastName;
            document.getElementById('middleName').value = user.middleName || '';
            document.getElementById('username').value = user.username;
            document.getElementById('role').value = user.role;
            document.getElementById('email').value = user.email || '';
            document.getElementById('contact').value = user.contact || '';
            document.getElementById('isActive').checked = user.isActive;
            document.getElementById('password').value = '';
            document.getElementById('confirmPassword').value = '';
            document.getElementById('password').required = false;
            document.getElementById('confirmPassword').required = false;
            document.getElementById('passwordLabel').textContent = 'Password (leave blank to keep current)';
            document.getElementById('confirmPasswordLabel').textContent = 'Confirm Password';

            document.getElementById('userModal').classList.remove('hidden');
            document.getElementById('userModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('userModal').classList.add('hidden');
            document.getElementById('userModal').style.display = 'none';
            document.getElementById('userForm').reset();
        }

        // ── Save user with validation ──
        function saveUser(event) {
            event.preventDefault();

            const id = document.getElementById('editUserId').value;
            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const middleName = document.getElementById('middleName').value.trim();
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const role = document.getElementById('role').value;
            const email = document.getElementById('email').value.trim();
            const contact = document.getElementById('contact').value.trim();
            const isActive = document.getElementById('isActive').checked;

            // ── Validation ──
            if (!firstName) { showToast('First Name is required.', 'error'); return; }
            if (!lastName) { showToast('Last Name is required.', 'error'); return; }
            if (!username) { showToast('Username is required.', 'error'); return; }
            if (!role) { showToast('Role is required.', 'error'); return; }

            // Check username uniqueness
            const existingUser = users.find(u => u.username === username && u.id != id);
            if (existingUser) {
                showToast('Username already exists. Please choose another.', 'error');
                return;
            }

            // Password validation for new user
            if (!id) {
                // New user: password is required
                if (!password || password.length < 8) {
                    showToast('Password must be at least 8 characters.', 'error');
                    return;
                }
                if (password !== confirmPassword) {
                    showToast('Passwords do not match.', 'error');
                    return;
                }
            } else {
                // Editing: if password is provided, validate it
                if (password) {
                    if (password.length < 8) {
                        showToast('Password must be at least 8 characters.', 'error');
                        return;
                    }
                    if (password !== confirmPassword) {
                        showToast('Passwords do not match.', 'error');
                        return;
                    }
                }
            }

            if (id) {
                // Edit existing user
                const userIndex = users.findIndex(u => u.id == id);
                if (userIndex !== -1) {
                    users[userIndex].firstName = firstName;
                    users[userIndex].lastName = lastName;
                    users[userIndex].middleName = middleName;
                    users[userIndex].username = username;
                    users[userIndex].role = role;
                    users[userIndex].email = email;
                    users[userIndex].contact = contact;
                    users[userIndex].isActive = isActive;
                    if (password) {
                        // In a real app, hash the password here
                        // users[userIndex].password = password;
                    }
                    showToast(`User ${firstName} ${lastName} updated successfully!`);
                }
            } else {
                // Add new user
                const newUser = {
                    id: nextId++,
                    firstName,
                    lastName,
                    middleName,
                    username,
                    role,
                    email,
                    contact,
                    isActive,
                    lastLogin: 'Never',
                };
                users.push(newUser);
                showToast(`User ${firstName} ${lastName} added successfully!`);
            }

            closeModal();
            applyFilters();
        }

        // ── Toggle user status ──
        function toggleUserStatus(userId) {
            const user = users.find(u => u.id === userId);
            if (!user) return;

            const action = user.isActive ? 'disable' : 'enable';
            const confirmMsg = user.isActive ?
                `Are you sure you want to pause (disable) ${user.firstName} ${user.lastName}?` :
                `Are you sure you want to continue (enable) ${user.firstName} ${user.lastName}?`;

            if (confirm(confirmMsg)) {
                user.isActive = !user.isActive;
                showToast(`${user.firstName} ${user.lastName} ${user.isActive ? 'enabled' : 'disabled'} successfully!`);
                applyFilters();
            }
        }

        // ── Toast ──
        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            document.getElementById('toastMsg').textContent = msg;
            t.querySelector('i').className = type === 'error' ? 'fas fa-exclamation-circle text-red-300' :
                'fas fa-check-circle text-green-300';
            t.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
            t.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => {
                t.classList.add('opacity-0', 'translate-y-4');
                t.classList.remove('opacity-100', 'translate-y-0');
            }, 3000);
        }

        // ── Initialise ──
        applyFilters();

        // Close modal on backdrop click
        document.getElementById('userModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>

</body>

</html>