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
    <title>Senior Activity Fund Request – MSWDO San Enrique</title>
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
                    keyframes: { fadeUp: { '0%': { opacity: '0', transform: 'translateY(10px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } } },
                    animation: { 'fade-up': 'fadeUp 0.35s ease both', 'fade-up-1': 'fadeUp 0.35s 0.05s ease both', 'fade-up-2': 'fadeUp 0.35s 0.10s ease both', 'fade-up-3': 'fadeUp 0.35s 0.15s ease both', 'fade-up-4': 'fadeUp 0.35s 0.20s ease both' }
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

        .upload-zone {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 130px;
            border: 2px dashed #CBD5E1;
            border-radius: 0.875rem;
            padding: 1.25rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            background: #F8FAFC;
            width: 100%;
            box-sizing: border-box;
        }

        .upload-zone:hover {
            border-color: #3A5F93;
            background: #EBF4FB;
        }

        .upload-zone.has-file {
            border-color: #0B2545;
            background: #E8EDF5;
            border-style: solid;
        }

        .upload-zone input[type=file] {
            display: none;
        }

        .upload-zone .upload-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            gap: 0.25rem;
        }

        .budget-bar-fill {
            transition: width 1s cubic-bezier(.4, 0, .2, 1);
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

    <?php require 'sidebar.php'; ?>

    <div class="ml-64 flex-1 flex flex-col min-h-screen">
        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <a href="#" class="text-slate-400 hover:text-navy-600">Programs</a>
                <span class="text-slate-300">/</span>
                <span class="text-navy-600 font-semibold">Senior Activity Fund Request</span>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="saveDraft()"
                    class="text-[12px] font-medium text-navy-600 border border-navy-200 bg-navy-50 rounded-lg px-3 py-1.5 hover:bg-navy-100">Save
                    Draft</button>
            </div>
        </header>

        <main class="p-6 overflow-y-auto">
            <div class="max-w-3xl mx-auto space-y-5">
                <div class="animate-fade-up">
                    <h1 class="text-xl font-serif text-navy-600">Senior Citizen Activity Fund Request</h1>
                    <p class="text-[13px] text-slate-500 mt-1">Request funds for senior citizen events and programs.</p>
                </div>

                <!-- Transaction Details -->
                <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-file-invoice"></i></div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Transaction Details</h2>
                        </div>
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-3 gap-4">
                            <div><label class="field-label req">Requested Amount (₱)</label>
                                <div class="relative"><span
                                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[13px]">₱</span><input
                                        type="number" min="0" class="field pl-7" id="amountField" placeholder="0.00"
                                        oninput="checkAmount(this)"></div>
                            </div>
                            <div><label class="field-label req">Application Date</label><input type="date" class="field"
                                    id="dateApplied"></div>
                            <div><label class="field-label req">Event Date</label><input type="date" class="field"
                                    id="eventDate"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="field-label req">Name of Event / Activity</label><input type="text"
                                    class="field" id="eventName" placeholder="e.g. Senior Citizens' Day"></div>
                            <div><label class="field-label">Purpose / Description</label><textarea class="field"
                                    rows="2" placeholder="Brief description"></textarea></div>
                        </div>

                        <!-- Limit Check Panel -->
                        <div class="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden" id="limitPanel">
                            <div
                                class="px-4 py-2.5 bg-slate-100/80 border-b border-slate-200 flex items-center justify-between">
                                <p class="text-[11px] font-semibold text-navy-600">Automatic Budget Check — Senior
                                    Activity Fund</p>
                                <button onclick="runLimitCheck()"
                                    class="text-[11px] text-blue-600 font-medium hover:underline">Recheck</button>
                            </div>
                            <div id="limitRows" class="divide-y divide-slate-100">
                                <div class="limit-row flex items-center justify-between px-4 py-2.5">
                                    <div class="flex items-center gap-2"><i
                                            class="fas fa-chart-line text-slate-500 text-sm"></i><span
                                            class="text-[12px] text-slate-600">Budget sufficient</span></div>
                                    <span class="text-[12px] font-semibold text-navy-600">₱84,000 available</span>
                                </div>
                                <div class="limit-row flex items-center justify-between px-4 py-2.5" id="amountCheck">
                                    <div class="flex items-center gap-2"><i
                                            class="fas fa-dollar-sign text-slate-500 text-sm"></i><span
                                            class="text-[12px] text-slate-600">Amount within budget</span></div>
                                    <span class="text-[12px] font-semibold text-slate-400">— Enter amount above</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Required Documents -->
                <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-paperclip"></i></div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Required Documents</h2>
                        </div>
                    </div>
                    <div class="p-6 grid grid-cols-2 gap-4">
                        <div>
                            <div class="field-label">Activity Proposal</div><label class="upload-zone"
                                id="uz-proposal"><input type="file" onchange="fileSelected(this,'uz-proposal')">
                                <div class="upload-content"><i class="fas fa-file-alt text-2xl mb-1"></i>
                                    <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                </div>
                            </label>
                        </div>
                        <div>
                            <div class="field-label">Valid ID</div><label class="upload-zone" id="uz-id"><input
                                    type="file" onchange="fileSelected(this,'uz-id')">
                                <div class="upload-content"><i class="fas fa-id-card text-2xl mb-1"></i>
                                    <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                </div>
                            </label>
                        </div>
                        <div>
                            <div class="field-label">Certificate of Indigency</div><label class="upload-zone"
                                id="uz-indigency"><input type="file" onchange="fileSelected(this,'uz-indigency')">
                                <div class="upload-content"><i class="fas fa-file-alt text-2xl mb-1"></i>
                                    <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                </div>
                            </label>
                        </div>
                        <div>
                            <div class="field-label">Certificate of Residency</div><label class="upload-zone"
                                id="uz-residency"><input type="file" onchange="fileSelected(this,'uz-residency')">
                                <div class="upload-content"><i class="fas fa-home text-2xl mb-1"></i>
                                    <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button onclick="saveComplete()"
                        class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500">Submit
                        Fund Request</button>
                </div>
            </div>
        </main>
        <footer class="border-t border-slate-200 bg-white px-6 py-3 text-[11px] text-slate-400"><span>MSWDO San Enrique
                Information System</span></footer>
    </div>

    <div id="toast"
        class="fixed bottom-6 right-6 bg-navy-600 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-navy-400"></i><span id="toastMsg">Saved!</span>
    </div>

    <script>
        const totalBudget = 180000, spent = 96000, remaining = totalBudget - spent, pctUsed = 53;
        requestAnimationFrame(() => setTimeout(() => document.querySelector('.budget-bar-fill').style.width = pctUsed + '%', 300));

        function checkAmount(input) {
            const val = parseFloat(input.value);
            const el = document.getElementById('amountCheck').querySelector('span');
            if (!val || isNaN(val)) { el.innerHTML = '— Enter amount above'; el.className = 'text-[12px] font-semibold text-slate-400'; }
            else if (val > remaining) { el.innerHTML = '<i class="fas fa-times-circle text-red-500 mr-1"></i> Exceeds remaining budget'; el.className = 'text-[12px] font-semibold text-red-500'; }
            else { el.innerHTML = `<i class="fas fa-check-circle text-navy-600 mr-1"></i> ₱${val.toLocaleString()} — within budget`; el.className = 'text-[12px] font-semibold text-navy-600'; }
        }
        function runLimitCheck() { checkAmount(document.getElementById('amountField')); showToast('Budget check refreshed ✓'); }
        function fileSelected(input, zoneId) {
            if (!input.files || !input.files[0]) return;
            const zone = document.getElementById(zoneId), name = input.files[0].name;
            zone.classList.add('has-file');
            zone.querySelector('.upload-content').innerHTML = `<i class="fas fa-check-circle text-navy-600 text-2xl mb-1"></i><p class="text-[12px] font-semibold text-navy-700">${name}</p><p class="text-[10px] text-navy-500">File ready</p>`;
        }
        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            document.getElementById('toastMsg').textContent = msg;
            t.querySelector('i').className = type === 'error' ? 'fas fa-exclamation-circle text-red-400' : 'fas fa-check-circle text-navy-400';
            t.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none'); t.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => { t.classList.add('opacity-0', 'translate-y-4'); t.classList.remove('opacity-100', 'translate-y-0'); }, 3000);
        }
        function saveDraft() { showToast('Draft saved!'); }
        function saveComplete() {
            const amount = document.getElementById('amountField').value.trim(), dateApplied = document.getElementById('dateApplied').value, eventDate = document.getElementById('eventDate').value, eventName = document.getElementById('eventName').value.trim(), amountVal = parseFloat(amount);
            if (!amount || amountVal <= 0) { showToast('Enter a valid amount.', 'error'); return; }
            if (amountVal > remaining) { showToast(`Requested amount exceeds the remaining budget (₱${remaining.toLocaleString()}).`, 'error'); return; }
            if (!dateApplied) { showToast('Select Application Date.', 'error'); return; }
            if (!eventDate) { showToast('Select Event Date.', 'error'); return; }
            if (!eventName) { showToast('Enter the name of the event/activity.', 'error'); return; }
            showToast('Senior activity fund request submitted ✓');
        }
    </script>
</body>

</html>