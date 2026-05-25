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
    <title>Senior Citizen – MSWDO San Enrique</title>
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

        .svc-tab {
            transition: all .18s;
            cursor: pointer;
        }

        .svc-tab:hover {
            border-color: #94A3B8;
        }

        .svc-tab.active {
            border-color: #0B2545 !important;
            background: #E8EDF5;
        }

        .svc-tab.active .st-title {
            color: #0B2545;
            font-weight: 700;
        }

        .svc-tab.active .st-icon {
            background: rgba(11, 37, 69, .1);
        }

        .verif-check {
            transition: all .15s;
            cursor: pointer;
        }

        .verif-check:has(input:checked) {
            border-color: #0B2545;
            background: #E8EDF5;
        }

        .verif-check:has(input:checked) span {
            color: #0B2545;
            font-weight: 500;
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
            padding: 1px 7px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            background: #FEF3C7;
            color: #92400E;
            margin-left: 5px;
        }

        .opt-badge {
            display: inline-flex;
            padding: 1px 7px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 500;
            background: #F1F5F9;
            color: #64748B;
            margin-left: 5px;
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
                <span class="text-navy-600 font-semibold">Senior Citizen Availment</span>
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
                    <h1 class="text-xl font-serif text-navy-600">Senior Citizen Availment Form</h1>
                    <p class="text-[13px] text-slate-500 mt-1">SCID issuance, social pension top‑up, and centenarian
                        benefit processing.</p>
                </div>

                <!-- Client banner -->
                <div class="animate-fade-up-1 bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-4">
                    <div
                        class="w-11 h-11 rounded-xl bg-navy-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        MS</div>
                    <div class="flex-1">
                        <p class="text-[14px] font-semibold text-navy-600">Maria R. Santos</p>
                        <p class="text-[11px] text-slate-400">CLT-2024-00142 · Poblacion · 62 yrs, Female</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-slate-400 uppercase tracking-wide">Age Verification</p>
                        <p class="text-[13px] font-bold text-navy-600 mt-0.5"><i class="fas fa-check-circle"></i> 62 yrs
                            — Eligible (60+)</p>
                    </div>
                </div>

                <!-- Service Type -->
                <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-list-ul"></i></div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Service Type</h2>
                            <p class="text-[11px] text-slate-400">Select the specific senior citizen service</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-3 gap-3 mb-5" id="seniorSvcSelector">
                            <div onclick="setSeniorSvc(this,'scid')"
                                class="svc-tab active border-2 border-navy-600 bg-navy-50 rounded-2xl p-4 text-center">
                                <div
                                    class="st-icon w-10 h-10 rounded-xl bg-navy-100 flex items-center justify-center mx-auto mb-2">
                                    <i class="fas fa-id-card text-navy-600 text-lg"></i></div>
                                <p class="st-title text-[12px] font-bold text-navy-700">SCID Issuance</p>
                                <p class="text-[10px] text-slate-400 mt-1">Senior Citizen ID — once only</p>
                            </div>
                            <div onclick="setSeniorSvc(this,'pension')"
                                class="svc-tab border-2 border-slate-200 rounded-2xl p-4 text-center">
                                <div
                                    class="st-icon w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center mx-auto mb-2">
                                    <i class="fas fa-coins text-navy-500 text-lg"></i></div>
                                <p class="st-title text-[12px] font-semibold text-slate-600">Pension Top‑up</p>
                                <p class="text-[10px] text-slate-400 mt-1">Monthly — if indigent</p>
                            </div>
                            <div onclick="setSeniorSvc(this,'centenarian')"
                                class="svc-tab border-2 border-slate-200 rounded-2xl p-4 text-center">
                                <div
                                    class="st-icon w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center mx-auto mb-2">
                                    <i class="fas fa-birthday-cake text-navy-500 text-lg"></i></div>
                                <p class="st-title text-[12px] font-semibold text-slate-600">Centenarian Benefit</p>
                                <p class="text-[10px] text-slate-400 mt-1">Age 100+ only</p>
                            </div>
                        </div>

                        <!-- SCID panel -->
                        <div id="svc-scid" class="space-y-4">
                            <div class="bg-navy-50 border border-navy-100 rounded-xl px-4 py-3 flex items-start gap-3">
                                <i class="fas fa-info-circle text-navy-500 text-lg mt-0.5"></i>
                                <p class="text-[12px] text-navy-700">SCID issued <strong>once only</strong>. For lost
                                    IDs use Affidavit of Loss. For expired IDs, upload the old card.</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="field-label req">SCID Number</label><input type="text" class="field"
                                        placeholder="SC-XXXX-XXXX-XXXX"></div>
                                <div><label class="field-label req">Date Issued</label><input type="date" class="field">
                                </div>
                            </div>
                            <div>
                                <label class="field-label">ID Type</label>
                                <div class="flex gap-3 mt-1">
                                    <label
                                        class="flex-1 flex items-center justify-center gap-2 border-2 border-slate-200 rounded-xl py-2.5 cursor-pointer text-[12px] font-medium has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50 has-[:checked]:text-navy-700"><input
                                            type="radio" name="scidType" class="hidden"> <i
                                            class="fas fa-plus-circle"></i> New</label>
                                    <label
                                        class="flex-1 flex items-center justify-center gap-2 border-2 border-slate-200 rounded-xl py-2.5 cursor-pointer text-[12px] font-medium has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50 has-[:checked]:text-navy-700"><input
                                            type="radio" name="scidType" class="hidden"> <i class="fas fa-sync-alt"></i>
                                        Renewal</label>
                                    <label
                                        class="flex-1 flex items-center justify-center gap-2 border-2 border-slate-200 rounded-xl py-2.5 cursor-pointer text-[12px] font-medium has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50 has-[:checked]:text-navy-700"><input
                                            type="radio" name="scidType" class="hidden"> <i class="fas fa-file-alt"></i>
                                        Replacement</label>
                                </div>
                            </div>
                            <div>
                                <label class="field-label">Required Documents</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <div class="field-label flex items-center text-[10px]">Birth Certificate (PSA)
                                            <span class="copy-badge">1 orig + 2 copies</span></div><label
                                            class="upload-zone" id="uz-sc-birth"><input type="file"
                                                onchange="fileSelected(this,'uz-sc-birth')">
                                            <div class="upload-content"><i class="fas fa-file-alt text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div>
                                        <div class="field-label flex items-center text-[10px]">Valid ID <span
                                                class="copy-badge">1 orig + 2 copies</span></div><label
                                            class="upload-zone" id="uz-sc-id"><input type="file"
                                                onchange="fileSelected(this,'uz-sc-id')">
                                            <div class="upload-content"><i class="fas fa-id-card text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div>
                                        <div class="field-label flex items-center text-[10px]">1×1 Photo <span
                                                class="copy-badge">2 pcs</span></div><label class="upload-zone"
                                            id="uz-sc-photo"><input type="file"
                                                onchange="fileSelected(this,'uz-sc-photo')">
                                            <div class="upload-content"><i class="fas fa-camera text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div>
                                        <div class="field-label flex items-center text-[10px]">Expired SCID <span
                                                class="opt-badge">If renewal</span></div><label class="upload-zone"
                                            id="uz-sc-old"><input type="file" onchange="fileSelected(this,'uz-sc-old')">
                                            <div class="upload-content"><i class="fas fa-history text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pension panel -->
                        <div id="svc-pension" class="hidden space-y-4">
                            <div class="bg-navy-50 border border-navy-100 rounded-xl px-4 py-3 flex items-start gap-3">
                                <i class="fas fa-exclamation-triangle text-navy-500 text-lg mt-0.5"></i>
                                <p class="text-[12px] text-navy-700">Requires <strong>all four eligibility
                                        criteria</strong> to be verified. Pension is released monthly.</p>
                            </div>
                            <div><label class="field-label req">Top‑up Amount (₱ / month)</label>
                                <div class="relative max-w-xs"><span
                                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[13px]">₱</span><input
                                        type="number" min="0" class="field pl-7" placeholder="e.g. 500"></div>
                            </div>
                            <div>
                                <label class="field-label req">Eligibility Verification — All 4 Required</label>
                                <div class="grid grid-cols-2 gap-3 mt-2">
                                    <label
                                        class="verif-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                            type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0">
                                        <div><span class="text-[12px] font-medium text-slate-700 block"><i
                                                    class="fas fa-clipboard-check mr-1"></i> Classified as
                                                Indigent</span></div>
                                    </label>
                                    <label
                                        class="verif-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                            type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0">
                                        <div><span class="text-[12px] font-medium text-slate-700 block"><i
                                                    class="fas fa-heartbeat mr-1"></i> Sick or Frail</span></div>
                                    </label>
                                    <label
                                        class="verif-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                            type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0">
                                        <div><span class="text-[12px] font-medium text-slate-700 block"><i
                                                    class="fas fa-bed mr-1"></i> Bedridden / Low‑mobility</span></div>
                                    </label>
                                    <label
                                        class="verif-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                            type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0">
                                        <div><span class="text-[12px] font-medium text-slate-700 block"><i
                                                    class="fas fa-times-circle mr-1"></i> No SSS / GSIS Pension</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div><label class="field-label">Remarks</label><textarea class="field" rows="2"
                                    placeholder="Additional notes..."></textarea></div>
                        </div>

                        <!-- Centenarian panel -->
                        <div id="svc-centenarian" class="hidden space-y-4">
                            <div class="bg-navy-50 border border-navy-100 rounded-xl px-4 py-3 flex items-start gap-3">
                                <i class="fas fa-birthday-cake text-navy-500 text-lg mt-0.5"></i>
                                <p class="text-[12px] text-navy-700">Available only to clients aged <strong>100 years
                                        and above</strong>.</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="field-label req">Benefit Amount (₱)</label>
                                    <div class="relative"><span
                                            class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[13px]">₱</span><input
                                            type="number" min="0" class="field pl-7" placeholder="e.g. 100000"></div>
                                </div>
                                <div><label class="field-label req">Date of Birth (Verified)</label><input type="date"
                                        class="field"></div>
                            </div>
                            <div>
                                <label class="field-label">Required Documents</label>
                                <div class="grid grid-cols-2 gap-3 mt-1">
                                    <div>
                                        <div class="field-label flex items-center text-[10px]">PSA Birth Certificate
                                            <span class="copy-badge">Required</span></div><label class="upload-zone"
                                            id="uz-cent-birth"><input type="file"
                                                onchange="fileSelected(this,'uz-cent-birth')">
                                            <div class="upload-content"><i class="fas fa-file-alt text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div>
                                        <div class="field-label flex items-center text-[10px]">PSA Marriage Contract
                                            <span class="opt-badge">If applicable</span></div><label class="upload-zone"
                                            id="uz-cent-marr"><input type="file"
                                                onchange="fileSelected(this,'uz-cent-marr')">
                                            <div class="upload-content"><i class="fas fa-ring text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div>
                                        <div class="field-label flex items-center text-[10px]">Baptismal Certificate
                                            <span class="copy-badge">Original</span></div><label class="upload-zone"
                                            id="uz-cent-bap"><input type="file"
                                                onchange="fileSelected(this,'uz-cent-bap')">
                                            <div class="upload-content"><i class="fas fa-church text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div>
                                        <div class="field-label flex items-center text-[10px]">Birth Cert. of First Born
                                            <span class="copy-badge">Required</span></div><label class="upload-zone"
                                            id="uz-cent-fb"><input type="file"
                                                onchange="fileSelected(this,'uz-cent-fb')">
                                            <div class="upload-content"><i class="fas fa-baby text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-span-2">
                                        <div class="field-label flex items-center text-[10px]">Death Cert. of First Born
                                            <span class="opt-badge">If deceased</span></div><label class="upload-zone"
                                            id="uz-cent-fd"><input type="file"
                                                onchange="fileSelected(this,'uz-cent-fd')">
                                            <div class="upload-content"><i
                                                    class="fas fa-file-medical-alt text-2xl mb-1"></i>
                                                <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button onclick="saveComplete()"
                        class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500">Submit
                        Senior Citizen Record</button>
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
        function setSeniorSvc(el, svc) {
            document.querySelectorAll('#seniorSvcSelector .svc-tab').forEach(t => {
                t.classList.remove('active', 'border-navy-600', 'bg-navy-50');
                t.querySelector('.st-title').className = 'st-title text-[12px] font-semibold text-slate-600';
            });
            el.classList.add('active', 'border-navy-600', 'bg-navy-50');
            el.querySelector('.st-title').className = 'st-title text-[12px] font-bold text-navy-700';
            ['scid', 'pension', 'centenarian'].forEach(s => {
                document.getElementById('svc-' + s).classList.toggle('hidden', s !== svc);
            });
        }
        function fileSelected(input, zoneId) {
            if (!input.files || !input.files[0]) return;
            const zone = document.getElementById(zoneId);
            const name = input.files[0].name;
            zone.classList.add('has-file');
            zone.querySelector('.upload-content').innerHTML = `<i class="fas fa-check-circle text-navy-600 text-2xl mb-1"></i><p class="text-[12px] font-semibold text-navy-700">${name}</p><p class="text-[10px] text-navy-500">File ready</p>`;
        }
        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            document.getElementById('toastMsg').textContent = msg;
            t.querySelector('i').className = type === 'error' ? 'fas fa-exclamation-circle text-red-400' : 'fas fa-check-circle text-navy-400';
            t.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
            t.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => { t.classList.add('opacity-0', 'translate-y-4'); t.classList.remove('opacity-100', 'translate-y-0'); }, 3000);
        }
        function saveDraft() { showToast('Draft saved!'); }
        function saveComplete() { showToast('Senior citizen record submitted ✓'); }
    </script>
</body>

</html>