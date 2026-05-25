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
    <title>SLP Availment – MSWDO San Enrique</title>
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

        .upload-zone .upload-icon {
            font-size: 1.75rem;
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .upload-zone .upload-title {
            font-size: 12px;
            font-weight: 500;
            color: #475569;
            line-height: 1.3;
        }

        .upload-zone .upload-hint {
            font-size: 11px;
            color: #94A3B8;
            line-height: 1.3;
        }

        .upload-zone.has-file .upload-icon {
            font-size: 1.5rem;
        }

        .upload-zone.has-file .upload-title {
            color: #0B2545;
            font-weight: 600;
            font-size: 12px;
            word-break: break-all;
            padding: 0 4px;
        }

        .upload-zone.has-file .upload-hint {
            color: #3A5F93;
            font-size: 10px;
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

    <?php require 'sidebar.php'; ?>

    <div class="ml-64 flex-1 flex flex-col min-h-screen">
        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <a href="#" class="text-slate-400 hover:text-navy-600">Clients</a>
                <span class="text-slate-300">/</span>
                <a href="#" class="text-slate-400 hover:text-navy-600">Program Availments</a>
                <span class="text-slate-300">/</span>
                <span class="text-navy-600 font-semibold">SLP Availment</span>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="saveDraft()"
                    class="text-[12px] font-medium text-navy-600 border border-navy-200 bg-navy-50 rounded-lg px-3 py-1.5 hover:bg-navy-100 transition-all">Save
                    Draft</button>
            </div>
        </header>

        <main class="p-6 overflow-y-auto">
            <div class="max-w-3xl mx-auto space-y-5">
                <div class="animate-fade-up">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-slate-300">·</span>
                        <span class="text-[12px] text-slate-400">Sustainable Livelihood Program</span>
                    </div>
                    <h1 class="text-xl font-serif text-navy-600">SLP Availment Form</h1>
                    <p class="text-[13px] text-slate-500 mt-1">Livelihood assistance to help families build sustainable
                        income-generating activities.</p>
                </div>

                <!-- Warning -->
                <div
                    class="animate-fade-up-1 bg-navy-50 border border-navy-200 rounded-xl px-4 py-3 flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-navy-500 text-lg mt-0.5"></i>
                    <div class="text-[12px] text-navy-800">
                        <strong class="font-semibold block mb-0.5">No New Project · Additional Funding Only</strong>
                        A client who has already availed SLP <strong>cannot apply for a new livelihood project</strong>.
                        They may only request additional funds for the same project, and only if the previous
                        project was successful (profitable / income‑generating). Otherwise, the system will block
                        further assistance.
                    </div>
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
                            <p class="text-[11px] text-slate-400">Amount, dates, and limit verification</p>
                        </div>
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="field-label req">Start-up Assistance (₱)</label>
                                <div class="relative">
                                    <span
                                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-medium text-[13px]">₱</span>
                                    <input type="number" min="0" class="field pl-7" id="amountField" placeholder="0.00"
                                        oninput="checkAmount(this)">
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1.5">No fixed min/max</p>
                            </div>
                            <div><label class="field-label req">Date Applied</label><input type="date" class="field"
                                    id="dateApplied"></div>
                            <div><label class="field-label">Date Released</label><input type="date" class="field"
                                    id="dateReleased"></div>
                        </div>

                        <!-- Limit Check -->
                        <div class="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden" id="limitPanel">
                            <div
                                class="px-4 py-2.5 bg-slate-100/80 border-b border-slate-200 flex items-center justify-between">
                                <p class="text-[11px] font-semibold text-navy-600">SLP Eligibility Check — Maria Santos
                                </p>
                            </div>
                            <div id="limitRows" class="divide-y divide-slate-100">
                                <div class="limit-row flex items-center justify-between px-4 py-2.5">
                                    <div class="flex items-center gap-2"><i
                                            class="fas fa-history text-slate-500 text-sm"></i><span
                                            class="text-[12px] text-slate-600">Previous SLP availed</span></div>
                                    <span class="text-[12px] font-semibold text-navy-600 flex items-center gap-1"><i
                                            class="fas fa-check-circle"></i> None on record — eligible</span>
                                </div>
                                <div class="limit-row flex items-center justify-between px-4 py-2.5">
                                    <div class="flex items-center gap-2"><i
                                            class="fas fa-chart-line text-slate-500 text-sm"></i><span
                                            class="text-[12px] text-slate-600">Budget sufficient</span></div>
                                    <span class="text-[12px] font-semibold text-red-500">₱12,000 remaining</span>
                                </div>
                                <div class="limit-row flex items-center justify-between px-4 py-2.5" id="amountCheck">
                                    <div class="flex items-center gap-2"><i
                                            class="fas fa-dollar-sign text-slate-500 text-sm"></i><span
                                            class="text-[12px] text-slate-600">Amount within range</span></div>
                                    <span class="text-[12px] font-semibold text-slate-400">— Enter amount above</span>
                                </div>
                            </div>
                        </div>

                        <!-- Business Info -->
                        <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                <div
                                    class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                                    <i class="fas fa-store"></i>
                                </div>
                                <div>
                                    <h2 class="text-[14px] font-semibold text-navy-600">Business / Livelihood
                                        Information</h2>
                                </div>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="field-label req">Livelihood Type</label>
                                        <select class="field" id="livelihoodType" onchange="handleLivelihoodChange()">
                                            <option value="">Select</option>
                                            <option value="Sari-sari Store">Sari-sari Store</option>
                                            <option value="Rice Retailing">Rice Retailing</option>
                                            <option value="Frozen Goods">Frozen Goods</option>
                                            <option value="Food Vending">Food Vending</option>
                                            <option value="Farming">Farming</option>
                                            <option value="Livestock">Livestock</option>
                                            <option value="Handicrafts">Handicrafts</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        <input type="text" id="otherLivelihood" class="field mt-2 hidden"
                                            placeholder="Specify livelihood type" />
                                    </div>
                                    <div><label class="field-label req">Business Name</label><input type="text"
                                            class="field" placeholder="Proposed business name"></div>
                                    <div><label class="field-label">Business Location</label><input type="text"
                                            class="field" placeholder="Address or description"></div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div><label class="field-label">Target Start Date</label><input type="date"
                                            class="field">
                                    </div>
                                </div>
                                <div
                                    class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                                    <div>
                                        <p class="text-[13px] font-semibold text-slate-700">Training Completed</p>
                                        <p class="text-[11px] text-slate-400">Has the client completed any livelihood
                                            training?
                                        </p>
                                    </div>
                                    <label class="relative cursor-pointer flex items-center gap-3">
                                        <input type="checkbox" id="trainingToggle" class="sr-only"
                                            onchange="toggleTraining()">
                                        <div class="w-11 h-6 bg-slate-200 rounded-full relative transition-colors"
                                            id="trainingTrack">
                                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                                id="trainingThumb"></div>
                                        </div>
                                        <span id="trainingLabel"
                                            class="text-[12px] font-medium text-slate-500">No</span>
                                    </label>
                                </div>
                                <div id="trainingDetails" class="hidden grid grid-cols-2 gap-4">
                                    <div><label class="field-label">Training Program</label><input type="text"
                                            class="field" placeholder="e.g. TESDA NC II – Food Processing"></div>
                                    <div><label class="field-label">Date Completed</label><input type="date"
                                            class="field">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Documents -->
                        <div class="animate-fade-up-4 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                <div
                                    class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                                    <i class="fas fa-paperclip"></i>
                                </div>
                                <div>
                                    <h2 class="text-[14px] font-semibold text-navy-600">Required Documents</h2>
                                </div>
                            </div>
                            <div class="p-6 grid grid-cols-2 gap-4">
                                <div>
                                    <div class="field-label flex items-center">Letter of Intent <span
                                            class="copy-badge">1 orig
                                            + 2 copies</span></div><label class="upload-zone" id="uz-slp-intent"><input
                                            type="file" onchange="fileSelected(this,'uz-slp-intent')">
                                        <div class="upload-content"><i class="fas fa-pen-alt text-2xl mb-1"></i>
                                            <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            <p class="text-[11px] text-slate-400">PDF, JPG, PNG</p>
                                        </div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center">Business Proposal <span
                                            class="copy-badge">1 orig
                                            + 2 copies</span></div><label class="upload-zone"
                                        id="uz-slp-proposal"><input type="file"
                                            onchange="fileSelected(this,'uz-slp-proposal')">
                                        <div class="upload-content"><i class="fas fa-chart-pie text-2xl mb-1"></i>
                                            <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            <p class="text-[11px] text-slate-400">PDF, JPG, PNG</p>
                                        </div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center">Valid ID <span class="copy-badge">1 orig
                                            + 2
                                            copies</span></div><label class="upload-zone" id="uz-slp-id"><input
                                            type="file" onchange="fileSelected(this,'uz-slp-id')">
                                        <div class="upload-content"><i class="fas fa-id-card text-2xl mb-1"></i>
                                            <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                        </div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center">Certificate of Indigency <span
                                            class="copy-badge">1 orig + 2 copies</span></div><label class="upload-zone"
                                        id="uz-slp-indigency"><input type="file"
                                            onchange="fileSelected(this,'uz-slp-indigency')">
                                        <div class="upload-content"><i class="fas fa-file-alt text-2xl mb-1"></i>
                                            <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                        </div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center">Certificate of Residency <span
                                            class="copy-badge">1 orig + 2 copies</span></div><label class="upload-zone"
                                        id="uz-slp-residency"><input type="file"
                                            onchange="fileSelected(this,'uz-slp-residency')">
                                        <div class="upload-content"><i class="fas fa-home text-2xl mb-1"></i>
                                            <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                        </div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center">Training Certificate <span
                                            class="ml-2 text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full">If
                                            applicable</span></div><label class="upload-zone"
                                        id="uz-slp-training"><input type="file"
                                            onchange="fileSelected(this,'uz-slp-training')">
                                        <div class="upload-content"><i class="fas fa-certificate text-2xl mb-1"></i>
                                            <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button onclick="saveComplete()"
                                class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500">Submit
                                SLP Availment </button>
                        </div>
                    </div>
        </main>
        <footer class="border-t border-slate-200 bg-white px-6 py-3 text-[11px] text-slate-400"><span>MSWDO San Enrique
                Information System</span></footer>
    </div>

    <!-- Toast -->
    <div id="toast"
        class="fixed bottom-6 right-6 bg-navy-600 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-navy-400"></i><span id="toastMsg">Saved!</span>
    </div>

    <script>
        let trainingOn = false;
        function toggleTraining() {
            trainingOn = !trainingOn;
            document.getElementById('trainingTrack').classList.toggle('bg-navy-600', trainingOn);
            document.getElementById('trainingTrack').classList.toggle('bg-slate-200', !trainingOn);
            document.getElementById('trainingThumb').style.transform = trainingOn ? 'translateX(20px)' : '';
            document.getElementById('trainingLabel').textContent = trainingOn ? 'Yes' : 'No';
            document.getElementById('trainingLabel').className = trainingOn ? 'text-[12px] font-medium text-navy-600' : 'text-[12px] font-medium text-slate-500';
            document.getElementById('trainingDetails').classList.toggle('hidden', !trainingOn);
            document.getElementById('trainingDetails').classList.toggle('grid', trainingOn);
        }
        function checkAmount(input) {
            const val = parseFloat(input.value);
            const el = document.getElementById('amountCheck').querySelector('span');
            if (!val || isNaN(val)) {
                el.innerHTML = '— Enter amount above';
                el.className = 'text-[12px] font-semibold text-slate-400';
            } else if (val > 12000) {
                el.innerHTML = '<i class="fas fa-times-circle text-red-500 mr-1"></i> Exceeds available budget';
                el.className = 'text-[12px] font-semibold text-red-500';
            } else {
                el.innerHTML = `<i class="fas fa-check-circle text-navy-600 mr-1"></i> ₱${val.toLocaleString()} — within budget`;
                el.className = 'text-[12px] font-semibold text-navy-600';
            }
        }
        function handleLivelihoodChange() {
            const select = document.getElementById('livelihoodType');
            const otherInput = document.getElementById('otherLivelihood');
            if (select.value === 'Other') {
                otherInput.classList.remove('hidden');
                otherInput.focus();
            } else {
                otherInput.classList.add('hidden');
                otherInput.value = ''; // clear when hidden
            }
        }
        function fileSelected(input, zoneId) {
            if (!input.files || !input.files[0]) return;
            const zone = document.getElementById(zoneId);
            const name = input.files[0].name;
            zone.classList.add('has-file');
            zone.querySelector('.upload-content').innerHTML = `<i class="fas fa-check-circle text-navy-600 text-2xl mb-1"></i><p class="text-[12px] font-semibold text-navy-700">${name}</p><p class="text-[10px] text-navy-500">File ready</p>`;
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
        // Ensure Date Released cannot be before Date Applied
        document.getElementById('dateApplied').addEventListener('change', function () {
            const appliedVal = this.value;
            const releasedInput = document.getElementById('dateReleased');

            // Set the minimum allowed date for release to the application date
            releasedInput.min = appliedVal;

            // If a release date was already selected and it's now before the new application date, clear it
            if (releasedInput.value && releasedInput.value < appliedVal) {
                releasedInput.value = '';
                showToast('Date Released was cleared because it cannot be before Date Applied.');
            }
        });

        // Optional: also trigger on page load if a date is pre-filled
        window.addEventListener('load', function () {
            const applied = document.getElementById('dateApplied').value;
            if (applied) {
                document.getElementById('dateReleased').min = applied;
            }
        });
        function saveDraft() { showToast('Draft saved!'); }
        function saveComplete() {
            const errors = [];

            const amountInput = document.getElementById('amountField');
            const amountVal = parseFloat(amountInput.value);
            if (!amountInput.value.trim() || isNaN(amountVal) || amountVal <= 0) {
                errors.push({ field: amountInput, msg: 'Please enter a valid start‑up assistance amount.' });
            } else if (amountVal > 12000) {
                errors.push({ field: amountInput, msg: 'Amount exceeds the available budget (₱12,000).' });
            }

            const dateApplied = document.getElementById('dateApplied');
            if (!dateApplied.value) {
                errors.push({ field: dateApplied, msg: 'Please select Date Applied.' });
            }

            // Livelihood Type
            const typeSelect = document.getElementById('livelihoodType');
            if (!typeSelect.value) {
                errors.push({ field: typeSelect, msg: 'Please select a Livelihood Type.' });
            } else if (typeSelect.value === 'Other') {
                const otherInput = document.getElementById('otherLivelihood');
                if (!otherInput.value.trim()) {
                    errors.push({ field: otherInput, msg: 'Please specify the livelihood type.' });
                }
            }

            const bizNameInput = document.querySelector('input[placeholder="Proposed business name"]');
            if (!bizNameInput.value.trim()) {
                errors.push({ field: bizNameInput, msg: 'Please enter a Business Name.' });
            }

            if (errors.length > 0) {
                const first = errors[0];
                showToast(first.msg, 'error');
                first.field.focus();
                first.field.style.borderColor = '#EF4444';
                setTimeout(() => { first.field.style.borderColor = ''; }, 2000);
                return;
            }

            showToast('SLP record submitted ');
        }
    </script>
</body>

</html>