<?php
require 'auth.php';
requireRole('Staff');
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Confidential Cases – MSWDO San Enrique</title>
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
                        shimmer: { '0%': { backgroundPosition: '-600px 0' }, '100%': { backgroundPosition: '600px 0' } },
                        pulse2: { '0%,100%': { opacity: '1' }, '50%': { opacity: '.45' } },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.35s ease both',
                        'fade-up-1': 'fadeUp 0.35s 0.05s ease both',
                        'fade-up-2': 'fadeUp 0.35s 0.10s ease both',
                        'fade-up-3': 'fadeUp 0.35s 0.15s ease both',
                        'fade-up-4': 'fadeUp 0.35s 0.20s ease both',
                        'fade-up-5': 'fadeUp 0.35s 0.25s ease both',
                        'fade-up-6': 'fadeUp 0.35s 0.30s ease both',
                        'pulse2': 'pulse2 2s ease-in-out infinite',
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

        .sidebar-conf {
            transition: all .15s;
            border-left: 3px solid transparent;
        }

        .sidebar-conf:hover {
            background: rgba(58, 95, 147, .15);
            color: #C5D1E6;
        }

        .sidebar-conf.active-c {
            background: rgba(58, 95, 147, .28);
            border-left-color: #C49A2A;
            color: #E8EDF5;
        }

        .screen-panel {
            display: none;
        }

        .screen-panel.active {
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

        .conf-shimmer {
            background: linear-gradient(90deg, #0B2545 0%, #3A5F93 50%, #0B2545 100%);
            background-size: 600px 100%;
            animation: shimmer 3s linear infinite;
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

        textarea.field {
            resize: vertical;
            min-height: 80px;
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

        .section-card {
            background: #fff;
            border-radius: 1rem;
            border: 1px solid #E2E8F0;
            overflow: hidden;
            margin-bottom: 1.25rem;
        }

        .section-head {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .875rem 1.5rem;
            border-bottom: 1px solid #F1F5F9;
            background: #F8FAFC;
        }

        .section-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #0B2545;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .section-body {
            padding: 1.5rem;
        }

        .case-type-opt {
            transition: all .18s;
            cursor: pointer;
        }

        .case-type-opt:hover {
            border-color: #94A3B8;
            transform: translateY(-1px);
        }

        .case-type-opt.active {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(11, 37, 69, .15);
            border-color: #0B2545 !important;
            background: #E8EDF5 !important;
        }

        .case-type-opt.active .ct-label {
            color: #0B2545 !important;
        }

        .action-check {
            transition: all .15s;
            cursor: pointer;
        }

        .action-check:has(input:checked) {
            border-color: #0B2545;
            background: #E8EDF5;
        }

        .action-check:has(input:checked) .ac-text {
            color: #0B2545;
            font-weight: 500;
        }

        .action-check:hover {
            border-color: #3A5F93;
        }

        .status-opt {
            transition: all .18s;
            cursor: pointer;
        }

        .status-opt:hover {
            border-color: #94A3B8;
        }

        .status-opt.active {
            border-color: #0B2545 !important;
            background: #E8EDF5 !important;
        }

        .status-opt.active .so-lbl {
            color: #0B2545 !important;
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

        .char-count {
            font-size: 10px;
            color: #94A3B8;
            text-align: right;
            margin-top: 4px;
        }

        .char-count.warn {
            color: #F59E0B;
        }

        .char-count.limit {
            color: #EF4444;
        }

        .case-row {
            transition: background .12s;
            cursor: pointer;
        }

        .case-row:hover {
            background: #F8FAFC;
        }

        .case-row:hover .row-arr {
            opacity: 1;
            transform: translateX(0);
        }

        .row-arr {
            opacity: 0;
            transform: translateX(-4px);
            transition: all .15s;
        }

        #quickCard {
            transition: all .2s ease;
        }

        .fchip {
            transition: all .15s;
            cursor: pointer;
        }

        .fchip:hover {
            border-color: #3A5F93;
            color: #0B2545;
        }

        .fchip.active {
            background: #0B2545;
            color: #fff;
            border-color: #0B2545;
        }

        #caseSearch:focus {
            box-shadow: 0 0 0 4px rgba(58, 95, 147, .12);
        }

        .badge-pulse::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 9999px;
            background: currentColor;
            animation: pulse2 2s ease-in-out infinite;
            opacity: .4;
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
                <span class="text-slate-400">Women &amp; Child Protection</span>
                <span class="text-slate-300">/</span>
                <span class="text-navy-600 font-semibold" id="breadcrumb">New Case Intake</span>
                <span class="bg-navy-50 text-navy-600 text-[10px] font-bold px-2.5 py-0.5 rounded-full ml-1"><i
                        class="fas fa-lock mr-1"></i> Restricted</span>
            </div>
            <div class="flex items-center gap-2" id="topbarActions">
                <button onclick="saveDraft()"
                    class="text-[12px] font-medium text-navy-600 border border-navy-200 bg-navy-50 rounded-lg px-3 py-1.5 hover:bg-navy-100 transition-all">Save
                    Draft</button>
            </div>
        </header>

        <main class="flex-1 p-6 overflow-y-auto">

            <div class="screen-panel active" id="panel-intake">
                <div class="max-w-3xl mx-auto space-y-5">

                    <div class="animate-fade-up">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-slate-300">·</span>
                            <span class="text-[12px] text-slate-400">Women &amp; Child Protection — Confidential</span>
                        </div>
                        <h1 class="text-xl font-serif text-navy-600">Confidential Case Intake Form</h1>
                        <p class="text-[13px] text-slate-500 mt-1">VAWC · CICL · Child at Risk · Child Abuse — All data
                            is restricted and access-logged.</p>
                    </div>

                    <div
                        class="animate-fade-up-1 relative overflow-hidden rounded-2xl border border-navy-200 bg-navy-50">
                        <div class="conf-shimmer absolute top-0 left-0 right-0 h-1 opacity-80"></div>
                        <div class="px-5 py-4 flex items-start gap-4">
                            <div
                                class="w-10 h-10 rounded-xl bg-navy-100 flex items-center justify-center text-xl flex-shrink-0 mt-0.5">
                                <i class="fas fa-lock text-navy-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-[13px] font-bold text-navy-700 mb-1">Confidentiality Notice</p>
                                <p class="text-[12px] text-navy-600 leading-relaxed">All information entered here is
                                    strictly confidential and protected under <strong>RA 9262</strong> (Anti-VAWC Act),
                                    <strong>RA 7610</strong> (Special Protection of Children Against Abuse), and related
                                    laws. Unauthorized disclosure is a criminal offense. <strong>All access to this form
                                        is logged.</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Section 1: Client & Case Info -->
                    <div class="section-card animate-fade-up-2">
                        <div class="section-head">
                            <div class="section-num">1</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-navy-600">Client &amp; Case Information</h2>
                                <p class="text-[11px] text-slate-400">Search for an existing client or register a new
                                    one</p>
                            </div>
                        </div>
                        <div class="section-body space-y-4">
                            <div>
                                <label class="field-label req">Client</label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i
                                            class="fas fa-search"></i></span>
                                    <input type="text" id="clientSearch" class="field pl-10"
                                        placeholder="Search by name or Client ID..."
                                        oninput="filterClientDropdown(this.value)">
                                </div>
                                <div id="clientDropdown"
                                    class="hidden mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden z-10 relative">
                                    <div onclick="selectClient('Maria R. Santos','CLT-2024-00142','Poblacion','62 · Female')"
                                        class="flex items-center gap-3 px-4 py-3 hover:bg-navy-50 cursor-pointer border-b border-slate-100">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-navy-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                            MS</div>
                                        <div>
                                            <p class="text-[12px] font-semibold text-navy-600">Maria R. Santos</p>
                                            <p class="text-[10px] text-slate-400">CLT-2024-00142 · Poblacion · 62 yrs,
                                                Female</p>
                                        </div>
                                    </div>
                                    <div onclick="selectClient('Luz A. Bautista','CLT-2024-00187','Poblacion','34 · Female')"
                                        class="flex items-center gap-3 px-4 py-3 hover:bg-navy-50 cursor-pointer border-b border-slate-100">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-navy-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                            LB</div>
                                        <div>
                                            <p class="text-[12px] font-semibold text-navy-600">Luz A. Bautista</p>
                                            <p class="text-[10px] text-slate-400">CLT-2024-00187 · Poblacion · 34 yrs,
                                                Female</p>
                                        </div>
                                    </div>
                                    <div onclick="selectClient('Carla M. Ramos','CLT-2024-00355','Guintorilan','26 · Female')"
                                        class="flex items-center gap-3 px-4 py-3 hover:bg-navy-50 cursor-pointer">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-navy-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                            CR</div>
                                        <div>
                                            <p class="text-[12px] font-semibold text-navy-600">Carla M. Ramos</p>
                                            <p class="text-[10px] text-slate-400">CLT-2024-00355 · Guintorilan · 26 yrs,
                                                Female</p>
                                        </div>
                                    </div>
                                    <div
                                        class="flex items-center gap-2 px-4 py-2.5 bg-slate-50 border-t border-slate-100">
                                        <button onclick="showToast('Opening client registration form...')"
                                            class="text-[11px] font-medium text-navy-500 hover:text-navy-700 transition-colors">+
                                            Register new client instead</button>
                                    </div>
                                </div>
                                <div id="selectedClientChip"
                                    class="hidden mt-2 flex items-center gap-3 bg-navy-50 border border-navy-200 rounded-xl px-4 py-2.5">
                                    <div id="chipAvatar"
                                        class="w-8 h-8 rounded-lg bg-navy-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                    </div>
                                    <div class="flex-1">
                                        <p id="chipName" class="text-[12px] font-semibold text-navy-600"></p>
                                        <p id="chipMeta" class="text-[10px] text-slate-400"></p>
                                    </div>
                                    <button onclick="clearClient()"
                                        class="text-slate-400 hover:text-red-500 transition-colors text-sm"><i
                                            class="fas fa-times"></i></button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label class="field-label req">Case Type</label>
                                    <div class="grid grid-cols-4 gap-2 mt-1" id="caseTypeSelector">
                                        <div onclick="setCaseType(this)"
                                            class="case-type-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                                            <div class="text-xl mb-1.5"><i
                                                    class="fas fa-exclamation-triangle text-navy-600"></i></div>
                                            <p class="ct-label text-[11px] font-bold text-navy-700">VAWC</p>
                                            <p class="text-[9px] text-slate-400 mt-0.5 leading-tight">Violence Against
                                                Women &amp; Children</p>
                                        </div>
                                        <div onclick="setCaseType(this)"
                                            class="case-type-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                                            <div class="text-xl mb-1.5"><i class="fas fa-child text-navy-500"></i></div>
                                            <p class="ct-label text-[11px] font-semibold text-slate-600">CICL</p>
                                            <p class="text-[9px] text-slate-400 mt-0.5 leading-tight">Child in Conflict
                                                with the Law</p>
                                        </div>
                                        <div onclick="setCaseType(this)"
                                            class="case-type-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                                            <div class="text-xl mb-1.5"><i class="fas fa-shield-alt text-navy-500"></i>
                                            </div>
                                            <p class="ct-label text-[11px] font-semibold text-slate-600">CAR</p>
                                            <p class="text-[9px] text-slate-400 mt-0.5 leading-tight">Child at Risk</p>
                                        </div>
                                        <div onclick="setCaseType(this)"
                                            class="case-type-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                                            <div class="text-xl mb-1.5"><i
                                                    class="fas fa-exclamation-circle text-navy-500"></i></div>
                                            <p class="ct-label text-[11px] font-semibold text-slate-600">Child Abuse</p>
                                            <p class="text-[9px] text-slate-400 mt-0.5 leading-tight">Physical / Sexual
                                                / Emotional</p>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="field-label">Case Number</label>
                                    <input type="text" class="field" placeholder="Auto-generated" value="CV-2026-019">
                                    <p class="text-[10px] text-slate-400 mt-1.5">Auto-assigned on save</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="field-label req">Incident Date</label><input type="date"
                                        class="field"></div>
                                <div><label class="field-label req">Incident Place / Location</label><input type="text"
                                        class="field" placeholder="Address or description of where incident occurred">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Narrative Report -->
                    <div class="section-card animate-fade-up-3">
                        <div class="section-head">
                            <div class="section-num">2</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-navy-600">Narrative Report</h2>
                                <p class="text-[11px] text-slate-400">Detailed account of the incident — restricted to
                                    authorized personnel</p>
                            </div>
                        </div>
                        <div class="section-body space-y-4">
                            <div>
                                <label class="field-label req">Narrative Report <span
                                        class="text-[10px] font-normal text-slate-400 ml-1 lowercase">(detailed account
                                        of the incident)</span></label>
                                <textarea class="field" rows="5" id="narrativeText" maxlength="2000"
                                    oninput="countChars('narrativeText','narrativeCount',2000)"
                                    placeholder="Record the detailed account of the incident as reported by the victim, witness, or referral source..."></textarea>
                                <div class="char-count" id="narrativeCount">0 / 2000 characters</div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="field-label">Offender Information <span
                                            class="text-[10px] font-normal text-slate-400 ml-1 lowercase">(if
                                            known)</span></label><textarea class="field" rows="3"
                                        placeholder="Name, age, relationship to victim, contact details, known location..."></textarea>
                                </div>
                                <div><label class="field-label">Witness Information <span
                                            class="text-[10px] font-normal text-slate-400 ml-1 lowercase">(if
                                            any)</span></label><textarea class="field" rows="3"
                                        placeholder="Names, contact details, and relationship to victim/offender of any witnesses..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Actions Taken -->
                    <div class="section-card animate-fade-up-4">
                        <div class="section-head">
                            <div class="section-num">3</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-navy-600">Actions Taken</h2>
                                <p class="text-[11px] text-slate-400">Check all interventions that have already been
                                    conducted or arranged</p>
                            </div>
                            <div class="ml-auto"><span id="actionsCount"
                                    class="text-[11px] font-semibold text-navy-600 bg-navy-50 border border-navy-200 px-2.5 py-0.5 rounded-full">0
                                    actions</span></div>
                        </div>
                        <div class="section-body">
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4">
                                <label
                                    class="action-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"
                                    onchange="updateActionsCount()">
                                    <input type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0 action-cb">
                                    <div><span class="ac-text text-[12px] font-medium text-slate-700 block"><i
                                                class="fas fa-brain mr-1"></i> Counseling / Psychosocial</span><span
                                            class="text-[10px] text-slate-400">Immediate psychological support</span>
                                    </div>
                                </label>
                                <label
                                    class="action-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"
                                    onchange="updateActionsCount()">
                                    <input type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0 action-cb">
                                    <div><span class="ac-text text-[12px] font-medium text-slate-700 block"><i
                                                class="fas fa-hospital mr-1"></i> Medical Referral</span><span
                                            class="text-[10px] text-slate-400">Referred to health facility</span></div>
                                </label>
                                <label
                                    class="action-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"
                                    onchange="updateActionsCount()">
                                    <input type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0 action-cb">
                                    <div><span class="ac-text text-[12px] font-medium text-slate-700 block"><i
                                                class="fas fa-balance-scale mr-1"></i> Legal Assistance</span><span
                                            class="text-[10px] text-slate-400">PAO / legal aid referral</span></div>
                                </label>
                                <label
                                    class="action-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"
                                    onchange="updateActionsCount()">
                                    <input type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0 action-cb">
                                    <div><span class="ac-text text-[12px] font-medium text-slate-700 block"><i
                                                class="fas fa-ambulance mr-1"></i> Rescue Operation</span><span
                                            class="text-[10px] text-slate-400">Coordinated rescue conducted</span></div>
                                </label>
                                <label
                                    class="action-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"
                                    onchange="updateActionsCount()">
                                    <input type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0 action-cb">
                                    <div><span class="ac-text text-[12px] font-medium text-slate-700 block"><i
                                                class="fas fa-home mr-1"></i> Temporary Shelter</span><span
                                            class="text-[10px] text-slate-400">DSWD / women's shelter</span></div>
                                </label>
                                <label
                                    class="action-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"
                                    onchange="updateActionsCount()">
                                    <input type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0 action-cb">
                                    <div><span class="ac-text text-[12px] font-medium text-slate-700 block"><i
                                                class="fas fa-handshake mr-1"></i> Barangay Coordination</span><span
                                            class="text-[10px] text-slate-400">BCPC / barangay officials alerted</span>
                                    </div>
                                </label>
                                <label
                                    class="action-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"
                                    onchange="updateActionsCount()">
                                    <input type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0 action-cb">
                                    <div><span class="ac-text text-[12px] font-medium text-slate-700 block"><i
                                                class="fas fa-shield-haltered mr-1"></i> Police Coordination</span><span
                                            class="text-[10px] text-slate-400">PNP / WCPD notified</span></div>
                                </label>
                                <label
                                    class="action-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"
                                    onchange="updateActionsCount()">
                                    <input type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0 action-cb">
                                    <div><span class="ac-text text-[12px] font-medium text-slate-700 block"><i
                                                class="fas fa-gavel mr-1"></i> Court Referral</span><span
                                            class="text-[10px] text-slate-400">Filed or referred to court</span></div>
                                </label>
                                <label
                                    class="action-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer"
                                    onchange="updateActionsCount()">
                                    <input type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0 action-cb">
                                    <div><span class="ac-text text-[12px] font-medium text-slate-700 block"><i
                                                class="fas fa-building mr-1"></i> DSWD Referral</span><span
                                            class="text-[10px] text-slate-400">Escalated to DSWD province</span></div>
                                </label>
                            </div>
                            <div><label class="field-label">Other Actions / Remarks</label><textarea class="field"
                                    rows="2"
                                    placeholder="Describe any other actions taken not listed above..."></textarea></div>
                        </div>
                    </div>

                    <!-- Section 4: Assignment & Status -->
                    <div class="section-card animate-fade-up-5">
                        <div class="section-head">
                            <div class="section-num">4</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-navy-600">Assignment &amp; Case Status</h2>
                            </div>
                        </div>
                        <div class="section-body space-y-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="field-label req">Assigned Social Worker</label><input type="text"
                                        class="field" placeholder="Ma. Teresa C. Ponclara, RSW">
                                </div>
                                <div><label class="field-label">Date Received / Reported</label><input type="date"
                                        class="field"></div>
                            </div>
                            <div>
                                <label class="field-label req">Case Status</label>
                                <div class="grid grid-cols-5 gap-2 mt-2" id="caseStatusSelector">
                                    <div onclick="setCaseStatus(this)"
                                        class="status-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                                        <div class="text-lg mb-1"><i class="fas fa-circle text-navy-600"></i></div>
                                        <p class="so-lbl text-[11px] font-bold text-navy-700">Active</p>
                                        <p class="text-[9px] text-slate-400 mt-0.5">Ongoing intervention</p>
                                    </div>
                                    <div onclick="setCaseStatus(this)"
                                        class="status-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                                        <div class="text-lg mb-1"><i class="fas fa-circle text-navy-400"></i></div>
                                        <p class="so-lbl text-[11px] font-semibold text-slate-600">Monitoring</p>
                                        <p class="text-[9px] text-slate-400 mt-0.5">Regular follow-up</p>
                                    </div>
                                    <div onclick="setCaseStatus(this)"
                                        class="status-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                                        <div class="text-lg mb-1"><i class="fas fa-check-circle text-navy-500"></i>
                                        </div>
                                        <p class="so-lbl text-[11px] font-semibold text-slate-600">Resolved</p>
                                        <p class="text-[9px] text-slate-400 mt-0.5">Issue addressed</p>
                                    </div>
                                    <div onclick="setCaseStatus(this)"
                                        class="status-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                                        <div class="text-lg mb-1"><i class="fas fa-lock text-navy-400"></i></div>
                                        <p class="so-lbl text-[11px] font-semibold text-slate-600">Closed</p>
                                        <p class="text-[9px] text-slate-400 mt-0.5">Case closed</p>
                                    </div>
                                    <div onclick="setCaseStatus(this)"
                                        class="status-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                                        <div class="text-lg mb-1"><i class="fas fa-share text-navy-400"></i></div>
                                        <p class="so-lbl text-[11px] font-semibold text-slate-600">Referred</p>
                                        <p class="text-[9px] text-slate-400 mt-0.5">Escalated out</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 5: Supporting Documents -->
                    <div class="section-card animate-fade-up-6">
                        <div class="section-head">
                            <div class="section-num">5</div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-navy-600">Supporting Documents</h2>
                                <p class="text-[11px] text-slate-400">All uploaded files are stored with restricted
                                    access — confidential</p>
                            </div>
                        </div>
                        <div class="section-body">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <div class="field-label text-[10px] mb-1">Police Blotter / Incident Report</div>
                                    <label class="upload-zone" id="uz-conf-blotter"><input type="file"
                                            accept=".pdf,.jpg,.png" onchange="fileSelected(this,'uz-conf-blotter')">
                                        <div class="upload-content"><i class="fas fa-file-alt text-2xl mb-1"></i>
                                            <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            <p class="text-[10px] text-slate-400">PDF, JPG, PNG</p>
                                        </div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label text-[10px] mb-1">Medical / Medico-Legal Records</div><label
                                        class="upload-zone" id="uz-conf-med"><input type="file" accept=".pdf,.jpg,.png"
                                            onchange="fileSelected(this,'uz-conf-med')">
                                        <div class="upload-content"><i class="fas fa-notes-medical text-2xl mb-1"></i>
                                            <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            <p class="text-[10px] text-slate-400">PDF, JPG, PNG</p>
                                        </div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label text-[10px] mb-1">Court / Legal Documents</div><label
                                        class="upload-zone" id="uz-conf-court"><input type="file"
                                            accept=".pdf,.jpg,.png" onchange="fileSelected(this,'uz-conf-court')">
                                        <div class="upload-content"><i class="fas fa-gavel text-2xl mb-1"></i>
                                            <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                            <p class="text-[10px] text-slate-400">PDF, JPG, PNG</p>
                                        </div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label text-[10px] mb-1">Photographs / Visual Evidence</div><label
                                        class="upload-zone" id="uz-conf-photos"><input type="file" accept=".jpg,.png"
                                            multiple onchange="fileSelected(this,'uz-conf-photos')">
                                        <div class="upload-content"><i class="fas fa-camera text-2xl mb-1"></i>
                                            <p class="text-[12px] font-medium text-slate-600">Click to upload (multiple)
                                            </p>
                                            <p class="text-[10px] text-slate-400">JPG, PNG only</p>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-span-2">
                                    <div class="field-label text-[10px] mb-1">Other Supporting Documents</div><label
                                        class="upload-zone" id="uz-conf-other"><input type="file"
                                            accept=".pdf,.jpg,.png" multiple
                                            onchange="fileSelected(this,'uz-conf-other')">
                                        <div class="upload-content"><i class="fas fa-folder-open text-2xl mb-1"></i>
                                            <p class="text-[12px] font-medium text-slate-600">Attach any other relevant
                                                documents</p>
                                            <p class="text-[10px] text-slate-400">PDF, JPG, PNG</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button onclick="saveCase()"
                            class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500 transition-all"></i>
                            Save Case</button>
                    </div>

                </div>
            </div>

            <div class="screen-panel" id="panel-list">
                <div class="max-w-5xl mx-auto space-y-5">

                    <div class="animate-fade-up flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    class="bg-navy-50 text-navy-600 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wide">Screen
                                    22</span>
                                <span class="text-slate-300">·</span>
                                <span class="text-[12px] text-slate-400">Confidential Case List / Search</span>
                            </div>
                            <h1 class="text-xl font-serif text-navy-600">Confidential Case Registry</h1>
                            <p class="text-[13px] text-slate-500 mt-1">VAWC · CICL · CAR · Child Abuse — All access to
                                this registry is logged.</p>
                        </div>
                        <button onclick="switchScreen('intake')"
                            class="text-[12px] font-semibold text-white bg-navy-600 rounded-xl px-4 py-2.5 hover:bg-navy-500 transition-all flex items-center gap-1.5 flex-shrink-0"><i
                                class="fas fa-plus-circle mr-1"></i> New Case Intake</button>
                    </div>

                    <div
                        class="animate-fade-up-1 relative overflow-hidden rounded-xl border border-navy-200 bg-navy-50">
                        <div class="conf-shimmer absolute top-0 left-0 right-0 h-0.5 opacity-70"></div>
                        <div class="px-4 py-3 flex items-center gap-3">
                            <i class="fas fa-lock text-navy-500 text-base"></i>
                            <p class="text-[12px] text-navy-700"><strong class="font-semibold">Restricted
                                    Registry:</strong> Client names and personal details are masked in list view. Click
                                a case row to view full details. All access is logged per RA 9262 and RA 7610.</p>
                        </div>
                    </div>

                    <div class="animate-fade-up-2 grid grid-cols-2 sm:grid-cols-5 gap-3">
                        <div class="bg-white rounded-2xl border border-slate-200 px-4 py-3 text-center">
                            <p class="text-[22px] font-bold text-navy-600 leading-none">26</p>
                            <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-wide">Total Cases</p>
                        </div>
                        <div class="bg-navy-50 rounded-2xl border border-navy-100 px-4 py-3 text-center">
                            <p class="text-[22px] font-bold text-navy-600 leading-none">3</p>
                            <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-wide">Active</p>
                        </div>
                        <div class="bg-navy-50 rounded-2xl border border-navy-100 px-4 py-3 text-center">
                            <p class="text-[22px] font-bold text-navy-600 leading-none">4</p>
                            <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-wide">Monitoring</p>
                        </div>
                        <div class="bg-navy-50 rounded-2xl border border-navy-100 px-4 py-3 text-center">
                            <p class="text-[22px] font-bold text-navy-600 leading-none">16</p>
                            <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-wide">Resolved</p>
                        </div>
                        <div class="bg-slate-50 rounded-2xl border border-slate-200 px-4 py-3 text-center">
                            <p class="text-[22px] font-bold text-slate-500 leading-none">3</p>
                            <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-wide">Closed</p>
                        </div>
                    </div>

                    <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 p-4">
                        <div class="relative mb-4">
                            <span
                                class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-base pointer-events-none"><i
                                    class="fas fa-search"></i></span>
                            <input id="caseSearch" type="text"
                                placeholder="Search by Case ID, barangay, or assigned worker..."
                                class="w-full pl-11 pr-4 py-3 rounded-xl border-2 border-slate-200 bg-slate-50 text-[13px] outline-none transition-all focus:border-navy-400 focus:bg-white"
                                oninput="filterCases(this.value)">
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mr-1">Filter:</span>
                            <button onclick="toggleFilter(this,'vawc')"
                                class="fchip flex items-center gap-1.5 border border-slate-200 rounded-full px-3 py-1 text-[11px] font-medium text-slate-600"><i
                                    class="fas fa-exclamation-triangle"></i> VAWC <span
                                    class="bg-slate-100 text-slate-500 text-[9px] font-bold px-1.5 py-0.5 rounded-full ml-0.5">13</span></button>
                            <button onclick="toggleFilter(this,'cicl')"
                                class="fchip flex items-center gap-1.5 border border-slate-200 rounded-full px-3 py-1 text-[11px] font-medium text-slate-600"><i
                                    class="fas fa-child"></i> CICL <span
                                    class="bg-slate-100 text-slate-500 text-[9px] font-bold px-1.5 py-0.5 rounded-full ml-0.5">7</span></button>
                            <button onclick="toggleFilter(this,'car')"
                                class="fchip flex items-center gap-1.5 border border-slate-200 rounded-full px-3 py-1 text-[11px] font-medium text-slate-600"><i
                                    class="fas fa-shield-alt"></i> CAR <span
                                    class="bg-slate-100 text-slate-500 text-[9px] font-bold px-1.5 py-0.5 rounded-full ml-0.5">4</span></button>
                            <button onclick="toggleFilter(this,'abuse')"
                                class="fchip flex items-center gap-1.5 border border-slate-200 rounded-full px-3 py-1 text-[11px] font-medium text-slate-600"><i
                                    class="fas fa-exclamation-circle"></i> Child Abuse <span
                                    class="bg-slate-100 text-slate-500 text-[9px] font-bold px-1.5 py-0.5 rounded-full ml-0.5">2</span></button>
                            <div class="w-px h-5 bg-slate-200 mx-1"></div>
                            <select onchange="filterCases(document.getElementById('caseSearch').value)"
                                class="text-[11px] border border-slate-200 rounded-full px-3 py-1 bg-white text-slate-600 outline-none focus:border-navy-400 appearance-none pr-7 cursor-pointer"
                                id="statusFilter"
                                style="background-image:url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 20 20%22%3E%3Cpath stroke=%22%236B7280%22 stroke-width=%221.5%22 d=%22M6 8l4 4 4-4%22/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 6px center;background-size:12px;">
                                <option value="">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Monitoring">Monitoring</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Closed">Closed</option>
                                <option value="Referred">Referred</option>
                            </select>
                            <select
                                class="text-[11px] border border-slate-200 rounded-full px-3 py-1 bg-white text-slate-600 outline-none focus:border-navy-400 appearance-none pr-7 cursor-pointer"
                                style="background-image:url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 20 20%22%3E%3Cpath stroke=%22%236B7280%22 stroke-width=%221.5%22 d=%22M6 8l4 4 4-4%22/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 6px center;background-size:12px;">
                                <option value=""><i class="fas fa-map-marker-alt mr-1"></i> All Barangays</option>
                                <option>Poblacion</option>
                                <option>San Jose</option>
                                <option>Beguiligan</option>
                                <option>Buenavista</option>
                                <option>Doldol</option>
                            </select>
                            <input type="date"
                                class="text-[11px] border border-slate-200 rounded-full px-3 py-1 bg-white text-slate-600 outline-none focus:border-navy-400">
                            <span class="ml-auto text-[11px] text-slate-400">Showing <strong id="caseCount"
                                    class="text-slate-600">8</strong> cases</span>
                        </div>
                    </div>

                    <div class="animate-fade-up-4 flex gap-4 items-start">
                        <div class="flex-1 bg-white rounded-2xl border border-slate-200 overflow-hidden min-w-0">
                            <div
                                class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 bg-slate-50/70">
                                <p class="text-[11px] font-semibold text-slate-500">Client names masked — click row to
                                    view full details</p>
                                <button onclick="showToast('Exporting case list...')"
                                    class="text-[11px] font-medium text-navy-600 border border-navy-200 bg-navy-50 rounded-lg px-3 py-1.5 hover:bg-navy-100 transition-all"><i
                                        class="fas fa-download mr-1"></i> Export</button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-[12px]">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-100">
                                            <th
                                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                                Case ID</th>
                                            <th
                                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                                Client</th>
                                            <th
                                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                                Case Type</th>
                                            <th
                                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                                Incident Date</th>
                                            <th
                                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                                Barangay</th>
                                            <th
                                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                                Status</th>
                                            <th
                                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                                Assigned</th>
                                            <th class="px-5 py-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="caseTableBody" class="divide-y divide-slate-100"></tbody>
                                </table>
                            </div>
                            <div
                                class="px-5 py-3 border-t border-slate-100 text-[11px] text-slate-400 flex items-center justify-between">
                                <span>All access to this registry is logged · RA 9262 / RA 7610 compliance</span>
                                <span class="text-navy-600 font-semibold">R. Villanueva · April 14, 2026 08:14 AM</span>
                            </div>
                        </div>

                        <div id="quickCard" class="w-64 flex-shrink-0 hidden">
                            <div class="bg-white rounded-2xl border border-navy-200 overflow-hidden sticky top-20">
                                <div class="conf-shimmer h-1"></div>
                                <div class="p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-navy-600">Case
                                            Details</p>
                                        <button onclick="closeQuick()"
                                            class="text-slate-300 hover:text-slate-500 text-lg"><i
                                                class="fas fa-times"></i></button>
                                    </div>
                                    <div
                                        class="bg-navy-50 border border-navy-100 rounded-xl px-3 py-2 mb-3 flex items-center gap-2">
                                        <i class="fas fa-lock text-navy-500"></i>
                                        <span class="text-[11px] text-navy-700 font-medium">Confidential — Access
                                            Logged</span>
                                    </div>
                                    <div class="space-y-2 mb-4">
                                        <div class="flex justify-between text-[11px]"><span class="text-slate-400">Case
                                                ID</span><span id="qcId"
                                                class="font-mono font-semibold text-navy-600"></span></div>
                                        <div class="flex justify-between text-[11px]"><span class="text-slate-400">Case
                                                Type</span><span id="qcType" class="font-medium"></span></div>
                                        <div class="flex justify-between text-[11px]"><span
                                                class="text-slate-400">Incident Date</span><span id="qcDate"
                                                class="font-medium text-slate-600"></span></div>
                                        <div class="flex justify-between text-[11px]"><span
                                                class="text-slate-400">Barangay</span><span id="qcBrgy"
                                                class="font-medium text-slate-600"></span></div>
                                        <div class="flex justify-between text-[11px]"><span
                                                class="text-slate-400">Assigned To</span><span id="qcAssigned"
                                                class="font-medium text-slate-600"></span></div>
                                        <div class="flex justify-between text-[11px]"><span
                                                class="text-slate-400">Status</span><span id="qcStatus"></span></div>
                                    </div>
                                    <div class="space-y-2">
                                        <button onclick="showToast('Opening full case details...')"
                                            class="w-full py-2.5 bg-navy-600 text-white text-[12px] font-semibold rounded-xl hover:bg-navy-500 transition-all">View
                                            Full Case →</button>
                                        <button onclick="showToast('Opening case for editing...')"
                                            class="w-full py-2.5 border border-navy-200 bg-navy-50 text-navy-700 text-[12px] font-medium rounded-xl hover:bg-navy-100 transition-all"><i
                                                class="fas fa-pen mr-1"></i> Edit Case</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </main>

        <footer
            class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <div id="toast"
        class="fixed bottom-6 right-6 bg-navy-600 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-lock text-navy-400"></i>
        <span id="toastMsg">Saved!</span>
    </div>

    <script>
        const CASES = [
        ];

        let filteredCases = [...CASES];
        let activeFilters = new Set();

        function renderCases() {
            document.getElementById('caseCount').textContent = filteredCases.length;
            const tbody = document.getElementById('caseTableBody');
            tbody.innerHTML = filteredCases.map((c, i) => `
    <tr class="case-row" onclick="openQuick(${CASES.indexOf(c)})">
      <td class="px-5 py-3.5"><span class="font-mono text-[11px] text-slate-500 bg-slate-100 px-2 py-0.5 rounded font-semibold">${c.id}</span></td>
      <td class="px-5 py-3.5"><div class="flex items-center gap-2"><div class="w-7 h-7 rounded-lg bg-slate-200 flex items-center justify-center text-slate-400 text-xs font-bold flex-shrink-0"><i class="fas fa-question"></i></div><div><p class="text-[12px] font-medium text-slate-500 italic">— Confidential —</p><p class="text-[10px] text-slate-400 mt-0.5">${c.barangay} · Identity masked</p></div></div></td>
      <td class="px-5 py-3.5"><span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[10px] font-bold bg-navy-50 text-navy-600">${c.type}</span></td>
      <td class="px-5 py-3.5 text-[12px] text-slate-500">${c.date}</td>
      <td class="px-5 py-3.5 text-[12px] text-slate-600">${c.barangay}</td>
      <td class="px-5 py-3.5"><span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-navy-50 text-navy-600 border border-navy-200">${c.status}</span></td>
      <td class="px-5 py-3.5 text-[12px] text-slate-500">R. Villanueva</td>
      <td class="px-5 py-3.5"><div class="flex items-center gap-2"><button onclick="event.stopPropagation();showToast('Opening case ${c.id}...')" class="text-[11px] font-medium text-navy-600 border border-navy-200 bg-navy-50 rounded-lg px-2.5 py-1.5 hover:bg-navy-100 transition-all">View</button><span class="row-arr text-navy-400 text-sm">→</span></div></td>
    </tr>`).join('');
        }

        function filterCases(q) {
            const q2 = q.toLowerCase();
            const st = document.getElementById('statusFilter').value;
            filteredCases = CASES.filter(c => {
                const matchQ = !q2 || c.id.toLowerCase().includes(q2) || c.barangay.toLowerCase().includes(q2);
                const matchStatus = !st || c.status === st;
                const matchType = activeFilters.size === 0 || [...activeFilters].some(f => c.type.toLowerCase().includes(f));
                return matchQ && matchStatus && matchType;
            });
            renderCases();
        }

        function toggleFilter(btn, type) {
            if (activeFilters.has(type)) { activeFilters.delete(type); btn.classList.remove('active'); }
            else { activeFilters.add(type); btn.classList.add('active'); }
            filterCases(document.getElementById('caseSearch').value);
        }

        function openQuick(idx) {
            const c = CASES[idx];
            document.getElementById('qcId').textContent = c.id;
            document.getElementById('qcType').innerHTML = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-navy-50 text-navy-600">${c.type}</span>`;
            document.getElementById('qcDate').textContent = c.date;
            document.getElementById('qcBrgy').textContent = c.barangay;
            document.getElementById('qcAssigned').textContent = c.assigned;
            document.getElementById('qcStatus').innerHTML = `<span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-navy-50 text-navy-600 border border-navy-200">${c.status}</span>`;
            document.getElementById('quickCard').classList.remove('hidden');
        }
        function closeQuick() { document.getElementById('quickCard').classList.add('hidden'); }

        function switchScreen(id) {
            document.querySelectorAll('.screen-panel').forEach(p => p.classList.remove('active'));
            document.getElementById('panel-' + id).classList.add('active');
            document.getElementById('breadcrumb').textContent = id === 'intake' ? 'New Case Intake' : 'Case List / Search';
            const topActs = document.getElementById('topbarActions');
            if (id === 'list') {
                topActs.innerHTML = `<button onclick="switchScreen('intake')" class="text-[12px] font-semibold text-white bg-navy-600 rounded-lg px-4 py-1.5 hover:bg-navy-500 transition-all flex items-center gap-1.5"><i class="fas fa-plus-circle mr-1"></i> New Case Intake</button>`;
            } else {
                topActs.innerHTML = `<button onclick="saveDraft()" class="text-[12px] font-medium text-navy-600 border border-navy-200 bg-navy-50 rounded-lg px-3 py-1.5 hover:bg-navy-100 transition-all">Save Draft</button><button onclick="saveCase()" class="text-[12px] font-semibold text-white bg-navy-600 rounded-lg px-4 py-1.5 hover:bg-navy-500 transition-all"><i class="fas fa-lock mr-1"></i> Save Case</button>`;
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function setCaseType(el) {
            document.querySelectorAll('#caseTypeSelector .case-type-opt').forEach(e => {
                e.classList.remove('active');
                e.querySelector('.ct-label').className = 'ct-label text-[11px] font-semibold text-slate-600';
            });
            el.classList.add('active');
        }

        function setCaseStatus(el) {
            document.querySelectorAll('#caseStatusSelector .status-opt').forEach(e => {
                e.classList.remove('active');
                e.querySelector('.so-lbl').className = 'so-lbl text-[11px] font-semibold text-slate-600';
            });
            el.classList.add('active');
        }

        function updateActionsCount() {
            const n = document.querySelectorAll('.action-cb:checked').length;
            const el = document.getElementById('actionsCount');
            el.textContent = n + ' action' + (n !== 1 ? 's' : '');
        }

        function filterClientDropdown(val) {
            document.getElementById('clientDropdown').classList.toggle('hidden', val.length < 1);
        }

        function selectClient(name, id, brgy, meta) {
            document.getElementById('clientSearch').value = '';
            document.getElementById('clientDropdown').classList.add('hidden');
            document.getElementById('chipName').textContent = name;
            document.getElementById('chipMeta').textContent = id + ' · ' + brgy + ' · ' + meta;
            document.getElementById('chipAvatar').textContent = name.split(' ').filter((_, i, a) => i === 0 || i === a.length - 1).map(w => w[0]).join('').slice(0, 2);
            document.getElementById('selectedClientChip').classList.remove('hidden');
            document.getElementById('selectedClientChip').classList.add('flex');
        }

        function clearClient() {
            document.getElementById('selectedClientChip').classList.add('hidden');
            document.getElementById('selectedClientChip').classList.remove('flex');
        }

        function countChars(id, countId, max) {
            const len = document.getElementById(id).value.length;
            const el = document.getElementById(countId);
            el.textContent = `${len} / ${max} characters`;
            el.className = `char-count ${len > max * .9 ? 'limit' : len > max * .75 ? 'warn' : ''}`;
        }

        function fileSelected(input, zoneId) {
            if (!input.files || !input.files[0]) return;
            const zone = document.getElementById(zoneId);
            const count = input.files.length;
            zone.classList.add('has-file');
            zone.querySelector('.upload-content').innerHTML = `<i class="fas fa-check-circle text-navy-600 text-2xl mb-1"></i><p class="text-[11px] font-semibold text-navy-700 truncate px-2">${count > 1 ? count + ' files attached' : input.files[0].name}</p><p class="text-[10px] text-navy-500 mt-0.5">Uploaded — access restricted</p>`;
        }

        function showToast(msg) {
            document.getElementById('toastMsg').textContent = msg;
            const t = document.getElementById('toast');
            t.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
            t.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => { t.classList.add('opacity-0', 'translate-y-4'); t.classList.remove('opacity-100', 'translate-y-0'); }, 2800);
        }

        function saveDraft() { showToast('Draft saved — access logged'); }
        function saveCase() { showToast('<i class="fas fa-lock mr-1"></i> Case saved securely ✓'); }

        renderCases();
    </script>
</body>

</html>