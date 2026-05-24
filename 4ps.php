<?php
require 'auth.php';
requireRole(['Admin', 'Social Worker', 'Staff']);
require 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>4Ps Monitoring – MSWDO San Enrique</title>
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
            transition: all .15s;
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

        .screen-panel {
            display: block;
            animation: fadeUp 0.3s ease both;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .field {
            display: block;
            width: 100%;
            border-radius: .75rem;
            border: 1.5px solid #E2E8F0;
            background: #F8FAFC;
            padding: .625rem .875rem;
            font-size: 13px;
            color: #1e293b;
            outline: none;
            font-family: 'DM Sans', sans-serif;
            transition: all .2s;
        }

        .field:focus {
            border-color: #3A5F93;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(58, 95, 147, .1);
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
            color: #64748B;
            margin-bottom: 6px;
        }

        .req::after {
            content: '*';
            color: #EF4444;
            margin-left: 2px;
        }

        .status-opt {
            transition: all .18s;
            cursor: pointer;
        }

        .status-opt:hover {
            transform: translateY(-1px);
        }

        .status-opt.sel-active {
            border-color: #335481;
            background: #ECFDF5;
        }

        .status-opt.sel-suspended {
            border-color: #335481;
            background: #FFFBEB;
        }

        .status-opt.sel-graduated {
            border-color: #335481;
            background: #ECFDF5;
        }

        .safety-check {
            transition: all .15s;
            cursor: pointer;
        }

        .safety-check:has(input:checked) {
            border-color: #335481;
            background: #ECFDF5;
        }

        .copy-badge {
            display: inline-flex;
            padding: 1px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            background: #FEF3C7;
            color: #92400E;
            margin-left: 6px;
        }

        .limit-row {
            transition: background .1s;
        }

        .limit-row:hover {
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

    <!-- ═══════════════════ SIDEBAR ═══════════════════ -->
    <?php require 'sidebar.php'; ?>


    <!-- Main -->
    <div class="ml-56 flex-1 flex flex-col min-h-screen">
        <!-- Header -->
        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <a href="#" class="text-slate-400 hover:text-navy-600">Clients</a>
                <span class="text-slate-300">/</span>
                <a href="#" class="text-slate-400 hover:text-navy-600">Program Selection</a>
                <span class="text-slate-300">/</span>
                <span class="text-navy-600 font-semibold">4Ps Monitoring</span>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="saveDraft()"
                    class="text-[12px] font-medium text-navy-600 border border-navy-200 bg-navy-50 rounded-lg px-3 py-1.5 hover:bg-navy-100 transition-all">Save
                    Draft</button>
            </div>
        </header>

        <main class="p-6 overflow-y-auto">
            <div class="max-w-3xl mx-auto space-y-5">
                <!-- Title -->
                <div class="animate-fade-up">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-slate-300">·</span>
                        <span class="text-[12px] text-slate-400">Pantawid Pamilyang Pilipino Program</span>
                    </div>
                    <h1 class="text-xl font-serif text-navy-600">4Ps Household Monitoring Form</h1>
                    <p class="text-[13px] text-slate-500 mt-1">Records household enrollment status and compliance
                        monitoring notes. No budget deduction.</p>
                </div>

                <!-- Info banner -->
                <div
                    class="animate-fade-up-1 bg-navy-50 border border-navy-100 rounded-xl px-4 py-3 flex items-start gap-3">
                    <i class="fas fa-info-circle text-navy-400 text-lg mt-0.5"></i>
                    <p class="text-[12px] text-navy-800"><strong class="font-semibold">No availment limit</strong> for
                        4Ps — this form is for status tracking and compliance monitoring only. No funds are disbursed
                        through this record.</p>
                </div>

                <!-- Transaction Details -->
                <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Transaction Details</h2>
                            <p class="text-[11px] text-slate-400">Household identifier and enrollment dates</p>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <div><label class="field-label req">Household ID</label><input type="text" class="field"
                                    placeholder="HH-2024-00142"></div>
                            <div><label class="field-label req">Date Enrolled</label><input type="date" class="field">
                            </div>
                            <div><label class="field-label">Date Graduated</label><input type="date" class="field">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Limit Check -->
                <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between">
                        <p class="text-[12px] font-semibold text-navy-600">4Ps Eligibility & Status Check — Maria Santos
                        </p>
                        <span
                            class="text-[11px] text-navy-600 font-semibold bg-navy-50 border border-navy-200 px-2.5 py-0.5 rounded-full"><i
                                class="fas fa-check-circle mr-1"></i> Active</span>
                    </div>
                    <div class="grid grid-cols-3 divide-x divide-slate-100">
                        <div class="px-5 py-3">
                            <p class="text-[10px] text-slate-400">Previous 4Ps Record</p>
                            <p class="text-[13px] font-semibold text-slate-500">Exists — Update only</p>
                        </div>
                        <div class="px-5 py-3">
                            <p class="text-[10px] text-slate-400">Budget</p>
                            <p class="text-[13px] font-semibold text-slate-500">N/A (no funds)</p>
                        </div>
                        <div class="px-5 py-3">
                            <p class="text-[10px] text-slate-400">Household Status</p>
                            <p class="text-[13px] font-semibold text-slate-500">Active</p>
                        </div>
                    </div>
                </div>

                <!-- Section: Household Status -->
                <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-flag"></i>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Household Status</h2>
                            <p class="text-[11px] text-slate-400">Select the current enrollment status of this household
                            </p>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-3 gap-3" id="statusSelector">
                            <div onclick="setStatus(this,'active','sel-active')"
                                class="status-opt sel-active border-2 border-navy-500 bg-navy-50 rounded-2xl p-4 text-center">
                                <i class="fas fa-check-circle text-navy-500 text-2xl mb-2"></i>
                                <p class="so-label text-[13px] font-semibold text-navy-700">Active</p>
                                <p class="text-[11px] text-slate-500 mt-1">Currently enrolled and compliant</p>
                            </div>
                            <div onclick="setStatus(this,'suspended','sel-suspended')"
                                class="status-opt border-2 border-slate-200 rounded-2xl p-4 text-center">
                                <i class="fas fa-pause-circle text-navy-500 text-2xl mb-2"></i>
                                <p class="so-label text-[13px] font-semibold text-navy-700">Suspended</p>
                                <p class="text-[11px] text-slate-500 mt-1">Temporarily suspended due to non-compliance
                                </p>
                            </div>
                            <div onclick="setStatus(this,'graduated','sel-graduated')"
                                class="status-opt border-2 border-slate-200 rounded-2xl p-4 text-center">
                                <i class="fas fa-graduation-cap text-navy-500 text-2xl mb-2"></i>
                                <p class="so-label text-[13px] font-semibold text-navy-700">Graduated</p>
                                <p class="text-[11px] text-slate-500 mt-1">Successfully completed the program</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Conditionalities -->
                <div class="animate-fade-up-4 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Conditionalities Compliance</h2>
                            <p class="text-[11px] text-slate-400">Check all conditionalities that have been met for this
                                period</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-3 mb-5">
                            <label
                                class="safety-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer">
                                <input type="checkbox" class="w-4 h-4 accent-navy-500 flex-shrink-0">
                                <div><span class="text-[12px] font-medium text-slate-700 block"><i
                                            class="fas fa-book-open mr-1"></i> School attendance met</span><span
                                        class="text-[10px] text-slate-400">Children 6–14 attended ≥85% of school
                                        days</span></div>
                            </label>
                            <label
                                class="safety-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer">
                                <input type="checkbox" class="w-4 h-4 accent-navy-500 flex-shrink-0">
                                <div><span class="text-[12px] font-medium text-slate-700 block"><i
                                            class="fas fa-hospital-user mr-1"></i> Health facility visits
                                        done</span><span class="text-[10px] text-slate-400">Children 0–5 completed
                                        required check-ups</span></div>
                            </label>
                            <label
                                class="safety-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer">
                                <input type="checkbox" class="w-4 h-4 accent-navy-500 flex-shrink-0">
                                <div><span class="text-[12px] font-medium text-slate-700 block"><i
                                            class="fas fa-syringe mr-1"></i> Vaccinations up to date</span><span
                                        class="text-[10px] text-slate-400">All required immunizations completed</span>
                                </div>
                            </label>
                            <label
                                class="safety-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer">
                                <input type="checkbox" class="w-4 h-4 accent-navy-500 flex-shrink-0">
                                <div><span class="text-[12px] font-medium text-slate-700 block"><i
                                            class="fas fa-baby mr-1"></i> Prenatal care attended</span><span
                                        class="text-[10px] text-slate-400">For pregnant household members</span></div>
                            </label>
                            <label
                                class="safety-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer">
                                <input type="checkbox" class="w-4 h-4 accent-navy-500 flex-shrink-0">
                                <div><span class="text-[12px] font-medium text-slate-700 block"><i
                                            class="fas fa-users mr-1"></i> FDS attended</span><span
                                        class="text-[10px] text-slate-400">Parent/guardian attended Family Development
                                        Session</span></div>
                            </label>
                            <label
                                class="safety-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer">
                                <input type="checkbox" class="w-4 h-4 accent-navy-500 flex-shrink-0">
                                <div><span class="text-[12px] font-medium text-slate-700 block"><i
                                            class="fas fa-child mr-1"></i> Day Care enrollment (0–5)</span><span
                                        class="text-[10px] text-slate-400">Children 3–5 enrolled in Day Care</span>
                                </div>
                            </label>
                        </div>
                        <div>
                            <label class="field-label">Monitoring Period</label>
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="text-[10px] text-slate-400 mb-1 block">From</label><input type="date"
                                        class="field"></div>
                                <div><label class="text-[10px] text-slate-400 mb-1 block">To</label><input type="date"
                                        class="field"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monitoring Notes -->
                <div class="animate-fade-up-4 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-pen"></i>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Monitoring Notes</h2>
                            <p class="text-[11px] text-slate-400">Staff observations, issues, and follow-up actions</p>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div><label class="field-label">Monitoring Notes</label><textarea class="field resize-none"
                                rows="4" placeholder="Record observations..."></textarea></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="field-label">Monitored By</label><input type="text" class="field"
                                    value=""></div>
                            <div><label class="field-label">Date of Visit</label><input type="date" class="field"></div>
                        </div>
                        <div><label class="field-label">Follow-up Action Required</label><textarea
                                class="field resize-none" rows="2"
                                placeholder="Describe any follow-up actions..."></textarea></div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button onclick="saveComplete()"
                        class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500 transition-all">Submit
                        Monitoring Record </button>
                </div>
            </div>
        </main>
        <footer class="border-t border-slate-200 bg-white px-6 py-3 text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <!-- Toast -->
    <div id="toast"
        class="fixed bottom-6 right-6 bg-navy-600 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-navy-400"></i><span id="toastMsg">Saved!</span>
    </div>

    <script>
        function setStatus(el, type, cls) {
            document.querySelectorAll('#statusSelector .status-opt').forEach(e => {
                e.className = 'status-opt border-2 border-slate-200 rounded-2xl p-4 text-center cursor-pointer';
                e.querySelector('.so-label').className = 'so-label text-[13px] font-semibold text-slate-600';
            });
            el.classList.add(cls);
            if (type === 'active') el.querySelector('.so-label').classList.add('text-navy-700');
            if (type === 'suspended') el.querySelector('.so-label').classList.add('text-navy-700');
            if (type === 'graduated') el.querySelector('.so-label').classList.add('text-navy-700');
        }
        function showToast(msg) {
            document.getElementById('toastMsg').textContent = msg;
            const t = document.getElementById('toast');
            t.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
            t.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => {
                t.classList.add('opacity-0', 'translate-y-4');
                t.classList.remove('opacity-100', 'translate-y-0');
            }, 3000);
        }
        function saveDraft() { showToast('Draft saved successfully!'); }
        function saveComplete() { showToast('Record submitted & saved'); }
    </script>
</body>

</html>