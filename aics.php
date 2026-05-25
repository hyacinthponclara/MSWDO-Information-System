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
    <title>AICS Availment Forms – MSWDO San Enrique</title>
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
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.35s ease both',
                        'fade-up-1': 'fadeUp 0.35s 0.05s ease both',
                        'fade-up-2': 'fadeUp 0.35s 0.10s ease both',
                        'fade-up-3': 'fadeUp 0.35s 0.15s ease both',
                        'fade-up-4': 'fadeUp 0.35s 0.20s ease both',
                        'fade-in': 'fadeIn 0.25s ease both',
                        'slide-up': 'slideUp 0.3s ease both',
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
            display: none;
        }

        .screen-panel.active {
            display: block;
            animation: fadeUp 0.3s ease both;
        }

        .sub-nav {
            transition: all .18s ease;
        }

        .sub-nav:hover {
            background: #F1F5F9;
        }

        .sub-nav.active {
            background: #0B2545;
            color: #fff;
        }

        .sub-nav.active .sub-icon {
            background: rgba(255, 255, 255, .15);
        }

        .sub-nav.active .sub-check {
            opacity: 1;
        }

        .sub-check {
            opacity: 0;
            transition: opacity .15s;
        }

        .type-card {
            transition: all .2s ease;
            cursor: pointer;
        }

        .type-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .type-card.active-card {
            border-color: #0B2545 !important;
            background: #F8FAFE !important;
            box-shadow: 0 4px 16px rgba(11, 37, 69, 0.12);
        }

        .type-card .card-icon {
            transition: transform .2s ease;
        }

        .type-card:hover .card-icon {
            transform: scale(1.1);
        }

        .field {
            display: block;
            width: 100%;
            border-radius: 0.75rem;
            border: 1.5px solid #E2E8F0;
            background: #F8FAFC;
            padding: 0.625rem 0.875rem;
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
            letter-spacing: 0.05em;
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
            align-items: center;
            padding: 1px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            background: #FEF3C7;
            color: #92400E;
            margin-left: 6px;
        }

        .opt-badge {
            display: inline-flex;
            align-items: center;
            padding: 1px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 500;
            background: #F1F5F9;
            color: #64748B;
            margin-left: 6px;
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

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, .15);
            border-radius: 2px;
        }

        #toast {
            transition: all .3s ease;
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
    </style>
</head>

<body class="bg-slate2 min-h-screen flex">

    <!-- ═══════════════════ SIDEBAR ═══════════════════ -->
    <?php require 'sidebar.php'; ?>

    <div class="ml-64 flex-1 flex flex-col min-h-screen">
        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <a href="#" class="text-slate-400 hover:text-navy-600">Clients</a>
                <span class="text-slate-300">/</span>
                <a href="#" class="text-slate-400 hover:text-navy-600">Program Selection</a>
                <span class="text-slate-300">/</span>
                <span class="text-navy-600 font-semibold" id="breadcrumbLast">AICS Availment</span>
            </div>
        </header>

        <div class="flex flex-1">
            <main class="flex-1 p-6 overflow-y-auto">

                <!-- MAIN FORM -->
                <div class="screen-panel active" id="panel-main">
                    <div class="max-w-3xl mx-auto space-y-5">
                        <div class="animate-fade-up">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-slate-300">·</span>
                                <span class="text-[12px] text-slate-400">Common fields for all AICS subtypes</span>
                            </div>
                            <h1 class="text-xl font-serif text-navy-600">AICS Availment — Main Form</h1>
                            <p class="text-[13px] text-slate-500 mt-1">Complete the transaction details, then proceed to
                                the subtype-specific requirements form.</p>
                        </div>

                        <!-- Budget Info Card -->
                        <div class="animate-fade-up-1 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                                <h2 class="text-[13px] font-semibold text-navy-600">AICS Budget Status</h2>
                                <span
                                    class="text-[10px] text-red-500 font-semibold bg-red-50 border border-red-200 px-2.5 py-0.5 rounded-full"><i
                                        class="fas fa-exclamation-triangle mr-1"></i> Critical — 12% remaining</span>
                            </div>
                            <div class="px-5 py-4 grid grid-cols-3 gap-4">
                                <div>
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold mb-1">
                                        Annual Budget</p>
                                    <p class="text-[18px] font-bold text-navy-600">₱240,000</p>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold mb-1">
                                        Spent This Year</p>
                                    <p class="text-[18px] font-bold text-slate-700">₱211,600</p>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold mb-1">
                                        Remaining</p>
                                    <p class="text-[18px] font-bold text-red-500">₱28,400</p>
                                </div>
                            </div>
                            <div class="px-5 pb-4">
                                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="budget-bar-fill h-2 rounded-full bg-red-400" style="width:0%"
                                        data-target="88%"></div>
                                </div>
                                <div class="flex justify-between text-[10px] text-slate-400 mt-1.5"><span>0%</span><span
                                        class="text-red-500 font-semibold">88% utilized</span><span>100%</span></div>
                            </div>
                        </div>

                        <!-- Transaction Details -->
                        <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                <div
                                    class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                                    1</div>
                                <div>
                                    <h2 class="text-[14px] font-semibold text-navy-600">Transaction Details</h2>
                                    <p class="text-[11px] text-slate-400">Amount, dates, and limit verification</p>
                                </div>
                            </div>
                            <div class="p-6 space-y-5">
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="field-label req">Amount (₱)</label>
                                        <div class="relative"><span
                                                class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-medium text-[13px]">₱</span><input
                                                type="number" min="500" max="5000" class="field pl-7"
                                                placeholder="500 – 5,000" oninput="checkAmount(this)" id="amountField">
                                        </div>
                                        <p class="text-[10px] text-slate-400 mt-1.5">Min ₱500 · Max ₱5,000</p>
                                    </div>
                                    <div><label class="field-label req">Date Applied</label><input type="date"
                                            class="field" id="dateApplied"></div>
                                    <div><label class="field-label">Date Released</label><input type="date"
                                            class="field" id="dateReleased"></div>
                                </div>

                                <!-- Limit Check Panel -->
                                <div class="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden"
                                    id="limitPanel">
                                    <div
                                        class="px-4 py-2.5 bg-slate-100/80 border-b border-slate-200 flex items-center justify-between">
                                        <p class="text-[11px] font-semibold text-navy-600">Automatic Limit Check — Maria
                                            Santos · AICS</p>
                                        <button onclick="runLimitCheck()"
                                            class="text-[11px] text-blue-600 font-medium hover:underline">Recheck</button>
                                    </div>
                                    <div id="limitRows" class="divide-y divide-slate-100">
                                        <div class="limit-row flex items-center justify-between px-4 py-2.5">
                                            <div class="flex items-center gap-2"><i
                                                    class="fas fa-calendar-alt text-slate-500 text-sm"></i><span
                                                    class="text-[12px] text-slate-600">Availments this quarter</span>
                                            </div><span
                                                class="text-[12px] font-semibold text-emerald-600 flex items-center gap-1">✓
                                                0 of 1 — eligible</span>
                                        </div>
                                        <div class="limit-row flex items-center justify-between px-4 py-2.5">
                                            <div class="flex items-center gap-2"><i
                                                    class="fas fa-calendar-week text-slate-500 text-sm"></i><span
                                                    class="text-[12px] text-slate-600">Availments this year</span></div>
                                            <span
                                                class="text-[12px] font-semibold text-emerald-600 flex items-center gap-1">✓
                                                2 of 4 — 2 remaining</span>
                                        </div>
                                        <div class="limit-row flex items-center justify-between px-4 py-2.5">
                                            <div class="flex items-center gap-2"><i
                                                    class="fas fa-chart-line text-slate-500 text-sm"></i><span
                                                    class="text-[12px] text-slate-600">Budget sufficient</span></div>
                                            <span class="text-[12px] font-semibold text-emerald-600">✓ ₱28,400
                                                available</span>
                                        </div>
                                        <div class="limit-row flex items-center justify-between px-4 py-2.5"
                                            id="amountCheck">
                                            <div class="flex items-center gap-2"><i
                                                    class="fas fa-dollar-sign text-slate-500 text-sm"></i><span
                                                    class="text-[12px] text-slate-600">Amount within range</span></div>
                                            <span class="text-[12px] font-semibold text-slate-400">— Enter amount
                                                above</span>
                                        </div>
                                    </div>
                                </div>

                                <div><label class="field-label">Remarks</label><textarea class="field resize-none"
                                        rows="3" placeholder="Optional notes about this transaction..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Assistance Type Selection Cards -->
                        <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 p-6">
                            <h2 class="text-[14px] font-semibold text-navy-600 mb-1">Select Assistance Type</h2>
                            <p class="text-[12px] text-slate-400 mb-5">Choose the AICS program subtype to proceed with
                                the corresponding requirements.</p>
                            <div class="grid grid-cols-5 gap-3" id="typeSelector">
                                <div onclick="selectType(this,'medical')"
                                    class="type-card bg-white border-2 border-slate-200 rounded-2xl p-4 text-center hover:border-navy-600 group">
                                    <div
                                        class="card-icon w-12 h-12 mx-auto mb-3 rounded-xl bg-slate-100 flex items-center justify-center text-2xl">
                                        <i class="fas fa-capsules text-navy-500"></i>
                                    </div>
                                    <p class="text-[13px] font-semibold text-slate-700">Medical</p>
                                    <p class="text-[10px] text-slate-400 mt-1">9 documents</p>
                                </div>
                                <div onclick="selectType(this,'financial')"
                                    class="type-card bg-white border-2 border-slate-200 rounded-2xl p-4 text-center hover:border-navy-600 group">
                                    <div
                                        class="card-icon w-12 h-12 mx-auto mb-3 rounded-xl bg-slate-100 flex items-center justify-center text-2xl">
                                        <i class="fas fa-coins text-navy-600"></i>
                                    </div>
                                    <p class="text-[13px] font-semibold text-slate-700">Financial</p>
                                    <p class="text-[10px] text-slate-400 mt-1">4 documents</p>
                                </div>
                                <div onclick="selectType(this,'educational')"
                                    class="type-card bg-white border-2 border-slate-200 rounded-2xl p-4 text-center hover:border-navy-600 group">
                                    <div
                                        class="card-icon w-12 h-12 mx-auto mb-3 rounded-xl bg-slate-100 flex items-center justify-center text-2xl">
                                        <i class="fas fa-graduation-cap text-navy-600"></i>
                                    </div>
                                    <p class="text-[13px] font-semibold text-slate-700">Educational</p>
                                    <p class="text-[10px] text-slate-400 mt-1">6 documents</p>
                                </div>
                                <div onclick="selectType(this,'livelihood')"
                                    class="type-card bg-white border-2 border-slate-200 rounded-2xl p-4 text-center hover:border-navy-600 group">
                                    <div
                                        class="card-icon w-12 h-12 mx-auto mb-3 rounded-xl bg-slate-100 flex items-center justify-center text-2xl">
                                        <i class="fas fa-briefcase text-navy-600"></i>
                                    </div>
                                    <p class="text-[13px] font-semibold text-slate-700">Livelihood</p>
                                    <p class="text-[10px] text-slate-400 mt-1">6 documents</p>
                                </div>
                                <div onclick="selectType(this,'burial')"
                                    class="type-card bg-white border-2 border-slate-200 rounded-2xl p-4 text-center hover:border-navy-600 group">
                                    <div
                                        class="card-icon w-12 h-12 mx-auto mb-3 rounded-xl bg-slate-100 flex items-center justify-center text-2xl">
                                        <i class="fas fa-dove text-navy-600"></i>
                                    </div>
                                    <p class="text-[13px] font-semibold text-slate-700">Burial</p>
                                    <p class="text-[10px] text-slate-400 mt-1">5 documents</p>
                                </div>
                            </div>
                        </div>

                        <div class="animate-fade-up-4 flex justify-end gap-3">
                            <button id="proceedToSubtype"
                                class="flex items-center gap-2 text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-3 hover:bg-navy-500 transition-all">Proceed
                                to Requirements →</button>
                        </div>
                    </div>
                </div>

                <!-- MEDICAL -->
                <div class="screen-panel" id="panel-medical">
                    <div class="max-w-3xl mx-auto space-y-5">
                        <div class="animate-fade-up">
                            <div class="flex items-center gap-2 mb-1"><span class="text-[12px] text-slate-400">AICS
                                    Medical Requirements</span></div>
                            <h1 class="text-xl font-serif text-navy-600">Medical Assistance — Requirements</h1>
                            <p class="text-[13px] text-slate-500 mt-1">Upload all required documents. Copy counts follow
                                DSWD guidelines.</p>
                        </div>
                        <div
                            class="animate-fade-up-1 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 flex items-start gap-3">
                            <i class="fas fa-info-circle text-blue-400 text-lg mt-0.5"></i>
                            <div class="text-[12px] text-blue-800"><strong class="font-semibold">DSWD Document
                                    Standard:</strong> All required documents must be submitted as <strong>1 original +
                                    2 photocopies</strong> each, unless otherwise noted. Optional documents are required
                                only when applicable to the client's situation.</div>
                        </div>
                        <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                <div
                                    class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                    1</div>
                                <div>
                                    <h2 class="text-[14px] font-semibold text-navy-600">Patient Information</h2>
                                    <p class="text-[11px] text-slate-400">Fill in only if the patient is different from
                                        the client/claimant</p>
                                </div><label class="ml-auto flex items-center gap-2 cursor-pointer"><span
                                        class="text-[12px] text-slate-500">Different patient</span>
                                    <div class="relative w-9 h-5 bg-slate-200 rounded-full transition-colors"
                                        id="patientToggleTrack" onclick="togglePatient()">
                                        <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform"
                                            id="patientToggleThumb"></div>
                                    </div>
                                </label>
                            </div>
                            <div id="patientFields" class="p-6 hidden">
                                <div class="grid grid-cols-3 gap-4">
                                    <div><label class="field-label req">Patient Name</label><input type="text"
                                            class="field" placeholder="Full name of patient"></div>
                                    <div><label class="field-label">Age</label><input type="number" class="field"
                                            placeholder="Age" min="0"></div>
                                    <div><label class="field-label">Relationship to Client</label><input type="text"
                                            class="field" placeholder="e.g. Child, Spouse"></div>
                                </div>
                            </div>
                        </div>
                        <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                <div
                                    class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                    2</div>
                                <div>
                                    <h2 class="text-[14px] font-semibold text-navy-600">Required Documents</h2>
                                    <p class="text-[11px] text-slate-400">Upload scanned copies or photos of each
                                        document</p>
                                </div>
                            </div>
                            <div class="p-6 grid grid-cols-2 gap-4">
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Medical Certificate /
                                        Abstract <span class="copy-badge">1 orig + 2 copies</span></div><label
                                        class="upload-zone" id="uz-medcert"><input type="file"
                                            accept=".pdf,.jpg,.jpeg,.png" onchange="fileSelected(this,'uz-medcert')">
                                        <div class="upload-content"><i class="fas fa-paperclip upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Laboratory Results /
                                        Resita <span class="copy-badge">1 orig + 2 copies</span></div><label
                                        class="upload-zone" id="uz-labresults"><input type="file"
                                            accept=".pdf,.jpg,.jpeg,.png" onchange="fileSelected(this,'uz-labresults')">
                                        <div class="upload-content"><i class="fas fa-flask upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Valid ID <span
                                            class="copy-badge">1 orig + 2 copies</span></div><label class="upload-zone"
                                        id="uz-validid"><input type="file" accept=".pdf,.jpg,.jpeg,.png"
                                            onchange="fileSelected(this,'uz-validid')">
                                        <div class="upload-content"><i class="fas fa-id-card upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Barangay Indigency
                                        Certificate <span class="copy-badge">1 orig + 2 copies</span></div><label
                                        class="upload-zone" id="uz-indigency"><input type="file"
                                            accept=".pdf,.jpg,.jpeg,.png" onchange="fileSelected(this,'uz-indigency')">
                                        <div class="upload-content"><i class="fas fa-file-alt upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Hospital Bill <span
                                            class="opt-badge">Optional — if admitted</span></div><label
                                        class="upload-zone" id="uz-hospitalbill"><input type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            onchange="fileSelected(this,'uz-hospitalbill')">
                                        <div class="upload-content"><i
                                                class="fas fa-hospital-user upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Discharge Summary <span
                                            class="opt-badge">Optional</span></div><label class="upload-zone"
                                        id="uz-discharge"><input type="file" accept=".pdf,.jpg,.jpeg,.png"
                                            onchange="fileSelected(this,'uz-discharge')">
                                        <div class="upload-content"><i
                                                class="fas fa-clipboard-list upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Medical Quotation
                                        (Dialysis) <span class="opt-badge">Optional</span></div><label
                                        class="upload-zone" id="uz-dialysis"><input type="file"
                                            accept=".pdf,.jpg,.jpeg,.png" onchange="fileSelected(this,'uz-dialysis')">
                                        <div class="upload-content"><i class="fas fa-syringe upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Medical Protocol (Chemo)
                                        <span class="opt-badge">Optional</span>
                                    </div><label class="upload-zone" id="uz-chemo"><input type="file"
                                            accept=".pdf,.jpg,.jpeg,.png" onchange="fileSelected(this,'uz-chemo')">
                                        <div class="upload-content"><i class="fas fa-flask upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div class="col-span-2">
                                    <div class="field-label flex items-center flex-wrap gap-1">Mayor's Approval <span
                                            class="opt-badge">LGU AICS only</span></div><label class="upload-zone"
                                        id="uz-mayors"><input type="file" accept=".pdf,.jpg,.jpeg,.png"
                                            onchange="fileSelected(this,'uz-mayors')">
                                        <div class="upload-content"><i class="fas fa-landmark upload-icon"></i><span
                                                class="upload-title">Click to upload Mayor's Approval</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between"><button onclick="switchSub('main')"
                                class="text-[13px] font-medium text-slate-500 border border-slate-200 rounded-xl px-5 py-2.5 hover:border-navy-400 hover:text-navy-600 transition-all">←
                                Back to Main Form</button><button onclick="saveComplete()"
                                class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500 transition-all">Save
                                & Complete ✓</button></div>
                    </div>
                </div>

                <!-- FINANCIAL-->
                <div class="screen-panel" id="panel-financial">
                    <div class="max-w-3xl mx-auto space-y-5">
                        <div class="animate-fade-up">
                            <div class="flex items-center gap-2 mb-1"><span class="text-[12px] text-slate-400">AICS
                                    Financial Requirements</span></div>
                            <h1 class="text-xl font-serif text-navy-600">Financial Assistance — Requirements</h1>
                            <p class="text-[13px] text-slate-500 mt-1">Upload the Mayor's approval and any supporting
                                documents for this financial assistance request.</p>
                        </div>
                        <div
                            class="animate-fade-up-1 bg-navy-50 border border-navy-100 rounded-xl px-4 py-3 flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle text-navy-400 text-lg mt-0.5"></i>
                            <p class="text-[12px] text-navy-800">Financial assistance requires <strong
                                    class="font-semibold">Mayor's approval</strong> before release. Ensure the approval
                                letter is signed and dated before submission.</p>
                        </div>
                        <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                <div
                                    class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                    1</div>
                                <div>
                                    <h2 class="text-[14px] font-semibold text-navy-600">Required Documents</h2>
                                </div>
                            </div>
                            <div class="p-6 grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <div class="field-label flex items-center flex-wrap gap-1">Mayor's Approval <span
                                            class="copy-badge">1 original required</span></div><label
                                        class="upload-zone" id="uz-fin-mayors"><input type="file"
                                            accept=".pdf,.jpg,.jpeg,.png" onchange="fileSelected(this,'uz-fin-mayors')">
                                        <div class="upload-content"><i class="fas fa-landmark upload-icon"></i><span
                                                class="upload-title">Click to upload Mayor's Approval</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Valid ID <span
                                            class="copy-badge">1 orig + 2 copies</span></div><label class="upload-zone"
                                        id="uz-fin-id"><input type="file" accept=".pdf,.jpg,.jpeg,.png"
                                            onchange="fileSelected(this,'uz-fin-id')">
                                        <div class="upload-content"><i class="fas fa-id-card upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Barangay Indigency <span
                                            class="copy-badge">1 orig + 2 copies</span></div><label class="upload-zone"
                                        id="uz-fin-indigency"><input type="file" accept=".pdf,.jpg,.jpeg,.png"
                                            onchange="fileSelected(this,'uz-fin-indigency')">
                                        <div class="upload-content"><i class="fas fa-file-alt upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div class="col-span-2">
                                    <div class="field-label flex items-center flex-wrap gap-1">Supporting Documents
                                        <span class="opt-badge">Multiple files allowed</span>
                                    </div><label class="upload-zone" id="uz-fin-support"><input type="file"
                                            accept=".pdf,.jpg,.jpeg,.png" multiple
                                            onchange="fileSelected(this,'uz-fin-support')">
                                        <div class="upload-content"><i class="fas fa-folder-open upload-icon"></i><span
                                                class="upload-title">Click to upload (multiple files
                                                accepted)</span><span class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                <div
                                    class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                    2</div>
                                <div>
                                    <h2 class="text-[14px] font-semibold text-navy-600">Additional Notes</h2>
                                </div>
                            </div>
                            <div class="p-6"><label class="field-label">Remarks</label><textarea
                                    class="field resize-none" rows="3"
                                    placeholder="Describe the financial need and purpose of assistance..."></textarea>
                            </div>
                        </div>
                        <div class="flex justify-between"><button onclick="switchSub('main')"
                                class="text-[13px] font-medium text-slate-500 border border-slate-200 rounded-xl px-5 py-2.5 hover:border-navy-400 hover:text-navy-600 transition-all">←
                                Back to Main Form</button><button onclick="saveComplete()"
                                class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500 transition-all">Save
                                & Complete ✓</button></div>
                    </div>
                </div>

                <!-- EDUCATIONAL -->
                <div class="screen-panel" id="panel-educational">
                    <div class="max-w-3xl mx-auto space-y-5">
                        <div class="animate-fade-up">
                            <div class="flex items-center gap-2 mb-1"><span class="text-[12px] text-slate-400">AICS
                                    Educational Requirements</span></div>
                            <h1 class="text-xl font-serif text-navy-600">Educational Assistance — Requirements</h1>
                            <p class="text-[13px] text-slate-500 mt-1">Maximum twice per school year · Max ₱20,000 per
                                year total.</p>
                        </div>
                        <div
                            class="animate-fade-up-1 bg-navy-50 border border-navy-100 rounded-xl px-4 py-3 flex items-start gap-3">
                            <i class="fas fa-info-circle text-navy-400 text-lg mt-0.5"></i>
                            <p class="text-[12px] text-navy-800">Educational assistance is limited to <strong>2 times
                                    per school year</strong> with a maximum of <strong>₱20,000/year</strong>. Enrollment
                                certificate and report card are required for every application.</p>
                        </div>
                        <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                <div
                                    class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                    1</div>
                                <div>
                                    <h2 class="text-[14px] font-semibold text-navy-600">Education Details</h2>
                                </div>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="grid grid-cols-3 gap-4">
                                    <div><label class="field-label req">Educational Level</label><select class="field">
                                            <option value="">Select</option>
                                            <option>K-12</option>
                                            <option>College</option>
                                            <option>Vocational / Technical</option>
                                            <option>Graduate Studies</option>
                                        </select></div>
                                    <div><label class="field-label req">Purpose</label><select class="field">
                                            <option value="">Select</option>
                                            <option>Tuition Fee</option>
                                            <option>Field Trip</option>
                                            <option>Diploma Processing</option>
                                            <option>School Supplies</option>
                                            <option>Miscellaneous Fees</option>
                                            <option>Other</option>
                                        </select></div>
                                    <div><label class="field-label req">School / Institution Name</label><input
                                            type="text" class="field" placeholder="Name of school or university"></div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div><label class="field-label">School Year</label><select class="field">
                                            <option>2025–2026</option>
                                            <option>2024–2025</option>
                                        </select></div>
                                    <div><label class="field-label">Semester / Term</label><select class="field">
                                            <option>1st Semester</option>
                                            <option>2nd Semester</option>
                                            <option>Summer</option>
                                        </select></div>
                                </div>
                            </div>
                        </div>
                        <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                <div
                                    class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                    2</div>
                                <div>
                                    <h2 class="text-[14px] font-semibold text-navy-600">Required Documents</h2>
                                </div>
                            </div>
                            <div class="p-6 grid grid-cols-2 gap-4">
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Report Card / Grades
                                        <span class="copy-badge">1 orig + 2 copies</span>
                                    </div><label class="upload-zone" id="uz-edu-card"><input type="file"
                                            onchange="fileSelected(this,'uz-edu-card')">
                                        <div class="upload-content"><i class="fas fa-chart-line upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Certificate of Enrollment
                                        <span class="copy-badge">1 orig + 2 copies</span>
                                    </div><label class="upload-zone" id="uz-edu-enroll"><input type="file"
                                            onchange="fileSelected(this,'uz-edu-enroll')">
                                        <div class="upload-content"><i
                                                class="fas fa-graduation-cap upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Certificate of Indigency
                                        <span class="copy-badge">1 orig + 2 copies</span>
                                    </div><label class="upload-zone" id="uz-edu-indigency"><input type="file"
                                            onchange="fileSelected(this,'uz-edu-indigency')">
                                        <div class="upload-content"><i class="fas fa-file-alt upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Certificate of Residency
                                        <span class="copy-badge">1 orig + 2 copies</span>
                                    </div><label class="upload-zone" id="uz-edu-residency"><input type="file"
                                            onchange="fileSelected(this,'uz-edu-residency')">
                                        <div class="upload-content"><i class="fas fa-home upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Student ID <span
                                            class="copy-badge">1 orig + 2 copies</span></div><label class="upload-zone"
                                        id="uz-edu-studentid"><input type="file"
                                            onchange="fileSelected(this,'uz-edu-studentid')">
                                        <div class="upload-content"><i class="fas fa-id-card upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Claimant's Valid ID <span
                                            class="copy-badge">1 orig + 2 copies</span></div><label class="upload-zone"
                                        id="uz-edu-claimantid"><input type="file"
                                            onchange="fileSelected(this,'uz-edu-claimantid')">
                                        <div class="upload-content"><i class="fas fa-user-check upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between"><button onclick="switchSub('main')"
                                class="text-[13px] font-medium text-slate-500 border border-slate-200 rounded-xl px-5 py-2.5 hover:border-navy-400 hover:text-navy-600 transition-all">←
                                Back to Main Form</button><button onclick="saveComplete()"
                                class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500 transition-all">Save
                                & Complete ✓</button></div>
                    </div>
                </div>

                <!-- LIVELIHOOD -->
                <div class="screen-panel" id="panel-livelihood">
                    <div class="max-w-3xl mx-auto space-y-5">
                        <div class="animate-fade-up">
                            <div class="flex items-center gap-2 mb-1"><span class="text-[12px] text-slate-400">AICS
                                    Livelihood Requirements</span></div>
                            <h1 class="text-xl font-serif text-navy-600">Livelihood Assistance — Requirements</h1>
                            <p class="text-[13px] text-slate-500 mt-1">Provide business details and upload all required
                                documents for livelihood assistance.</p>
                        </div>
                        <div class="animate-fade-up-1 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                <div
                                    class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                    1</div>
                                <div>
                                    <h2 class="text-[14px] font-semibold text-navy-600">Business Information</h2>
                                </div>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="grid grid-cols-3 gap-4">
                                    <div><label class="field-label req">Business Name</label><input type="text"
                                            class="field" placeholder="Proposed business name"></div>
                                    <div><label class="field-label req">Business Type</label><select class="field">
                                            <option value="">Select type</option>
                                            <option>Sari-sari Store</option>
                                            <option>Rice Retailing</option>
                                            <option>Frozen Goods</option>
                                            <option>Food Vending / Catering</option>
                                            <option>Farming / Gardening</option>
                                            <option>Livestock Raising</option>
                                            <option>Handicrafts</option>
                                            <option>Other</option>
                                        </select></div>
                                    <div><label class="field-label req">Start-up Cost (₱)</label>
                                        <div class="relative"><span
                                                class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-medium text-[13px]">₱</span><input
                                                type="number" min="0" class="field pl-7" placeholder="0.00"></div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div><label class="field-label">Business Location</label><input type="text"
                                            class="field" placeholder="Street, barangay, or stall location"></div>
                                    <div><label class="field-label">Target Start Date</label><input type="date"
                                            class="field"></div>
                                </div>
                            </div>
                        </div>
                        <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                <div
                                    class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                    2</div>
                                <div>
                                    <h2 class="text-[14px] font-semibold text-navy-600">Required Documents</h2>
                                </div>
                            </div>
                            <div class="p-6 grid grid-cols-2 gap-4">
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Letter of Intent <span
                                            class="copy-badge">1 orig + 2 copies</span></div><label class="upload-zone"
                                        id="uz-liv-intent"><input type="file"
                                            onchange="fileSelected(this,'uz-liv-intent')">
                                        <div class="upload-content"><i class="fas fa-pen-alt upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Business Proposal <span
                                            class="copy-badge">1 orig + 2 copies</span></div><label class="upload-zone"
                                        id="uz-liv-proposal"><input type="file"
                                            onchange="fileSelected(this,'uz-liv-proposal')">
                                        <div class="upload-content"><i class="fas fa-chart-pie upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Valid ID <span
                                            class="copy-badge">1 orig + 2 copies</span></div><label class="upload-zone"
                                        id="uz-liv-id"><input type="file" onchange="fileSelected(this,'uz-liv-id')">
                                        <div class="upload-content"><i class="fas fa-id-card upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Certificate of Indigency
                                        <span class="copy-badge">1 orig + 2 copies</span>
                                    </div><label class="upload-zone" id="uz-liv-indigency"><input type="file"
                                            onchange="fileSelected(this,'uz-liv-indigency')">
                                        <div class="upload-content"><i class="fas fa-file-alt upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Certificate of Residency
                                        <span class="copy-badge">1 orig + 2 copies</span>
                                    </div><label class="upload-zone" id="uz-liv-residency"><input type="file"
                                            onchange="fileSelected(this,'uz-liv-residency')">
                                        <div class="upload-content"><i class="fas fa-home upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Training Certificate
                                        <span class="opt-badge">If completed</span>
                                    </div><label class="upload-zone" id="uz-liv-training"><input type="file"
                                            onchange="fileSelected(this,'uz-liv-training')">
                                        <div class="upload-content"><i class="fas fa-certificate upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between"><button onclick="switchSub('main')"
                                class="text-[13px] font-medium text-slate-500 border border-slate-200 rounded-xl px-5 py-2.5 hover:border-navy-400 hover:text-navy-600 transition-all">←
                                Back to Main Form</button><button onclick="saveComplete()"
                                class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500 transition-all">Save
                                & Complete ✓</button></div>
                    </div>
                </div>

                <!--BURIAL-->
                <div class="screen-panel" id="panel-burial">
                    <div class="max-w-3xl mx-auto space-y-5">
                        <div class="animate-fade-up">
                            <div class="flex items-center gap-2 mb-1"><span class="text-[12px] text-navy-400">AICS
                                    Burial Requirements</span></div>
                            <h1 class="text-xl font-serif text-navy-600">Burial Assistance — Requirements</h1>
                            <p class="text-[13px] text-navy-500 mt-1">Documents for burial assistance require <strong>1
                                    original + 1 photocopy</strong> only (not 2).</p>
                        </div>
                        <div
                            class="animate-fade-up-1 bg-navy-50 border border-navy-200 rounded-xl px-4 py-3 flex items-start gap-3">
                            <i class="fas fa-info-circle text-navy-400 text-lg mt-0.5"></i>
                            <p class="text-[12px] text-navy-700">Burial assistance has a <strong
                                    class="font-semibold">reduced copy requirement</strong> — only <strong>1 original +
                                    1 photocopy</strong> is needed for each document, unlike other AICS subtypes which
                                require 1 original + 2 copies.</p>
                        </div>
                        <div class="animate-fade-up-2 bg-white rounded-2xl border border-navy-200 overflow-hidden">
                            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                <div
                                    class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                    1</div>
                                <div>
                                    <h2 class="text-[14px] font-semibold text-navy-600">Deceased Information</h2>
                                </div>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="grid grid-cols-3 gap-4">
                                    <div><label class="field-label req">Name of Deceased</label><input type="text"
                                            class="field" placeholder="Full legal name"></div>
                                    <div><label class="field-label req">Date of Death</label><input type="date"
                                            class="field"></div>
                                    <div><label class="field-label req">Relationship to Claimant</label><select
                                            class="field">
                                            <option value="">Select</option>
                                            <option>Spouse</option>
                                            <option>Parent</option>
                                            <option>Child</option>
                                            <option>Sibling</option>
                                            <option>Grandparent</option>
                                            <option>Other</option>
                                        </select></div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div><label class="field-label">Funeral Home / Parlor</label><input type="text"
                                            class="field" placeholder="Name of funeral establishment"></div>
                                    <div><label class="field-label">Funeral Contract Amount (₱)</label>
                                        <div class="relative"><span
                                                class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-medium text-[13px]">₱</span><input
                                                type="number" min="0" class="field pl-7" placeholder="0.00"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="animate-fade-up-3 bg-white rounded-2xl border border-navy-200 overflow-hidden">
                            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                <div
                                    class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                    2</div>
                                <div>
                                    <h2 class="text-[14px] font-semibold text-navy-600">Required Documents</h2>
                                    <p class="text-[11px] text-slate-400">Note: Burial only requires 1 original + 1
                                        photocopy each</p>
                                </div>
                            </div>
                            <div class="p-6 grid grid-cols-2 gap-4">
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Death Certificate
                                        (PSA/LCR) <span class="copy-badge">1 orig + 1 copy</span></div><label
                                        class="upload-zone" id="uz-bur-death"><input type="file"
                                            onchange="fileSelected(this,'uz-bur-death')">
                                        <div class="upload-content"><i class="fas fa-file-alt upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Funeral Contract <span
                                            class="copy-badge">1 orig + 1 copy</span></div><label class="upload-zone"
                                        id="uz-bur-contract"><input type="file"
                                            onchange="fileSelected(this,'uz-bur-contract')">
                                        <div class="upload-content"><i
                                                class="fas fa-file-signature upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Valid ID of Claimant
                                        <span class="copy-badge">1 orig + 1 copy</span>
                                    </div><label class="upload-zone" id="uz-bur-id"><input type="file"
                                            onchange="fileSelected(this,'uz-bur-id')">
                                        <div class="upload-content"><i class="fas fa-id-card upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label flex items-center flex-wrap gap-1">Barangay Indigency <span
                                            class="copy-badge">1 orig + 1 copy</span></div><label class="upload-zone"
                                        id="uz-bur-indigency"><input type="file"
                                            onchange="fileSelected(this,'uz-bur-indigency')">
                                        <div class="upload-content"><i class="fas fa-file-alt upload-icon"></i><span
                                                class="upload-title">Click to upload</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                                <div class="col-span-2">
                                    <div class="field-label flex items-center flex-wrap gap-1">Mayor's Approval <span
                                            class="opt-badge">LGU AICS only</span></div><label class="upload-zone"
                                        id="uz-bur-mayors"><input type="file"
                                            onchange="fileSelected(this,'uz-bur-mayors')">
                                        <div class="upload-content"><i class="fas fa-landmark upload-icon"></i><span
                                                class="upload-title">Click to upload Mayor's Approval</span><span
                                                class="upload-hint">PDF, JPG, PNG</span></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between"><button onclick="switchSub('main')"
                                class="text-[13px] font-medium text-slate-500 border border-slate-200 rounded-xl px-5 py-2.5 hover:border-navy-400 hover:text-navy-600 transition-all">←
                                Back to Main Form</button><button onclick="saveComplete()"
                                class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500 transition-all">Save
                                & Complete ✓</button></div>
                    </div>
                </div>

            </main>
        </div>

        <footer
            class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <div id="toast"
        class="fixed bottom-6 right-6 bg-navy-600 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-emerald-400 text-base"></i><span id="toastMsg">Saved successfully!</span>
    </div>

    <script>
        const subNames = { main: 'AICS Availment', medical: 'AICS — Medical', financial: 'AICS — Financial', educational: 'AICS — Educational', livelihood: 'AICS — Livelihood', burial: 'AICS — Burial' };

        function switchSub(id) {
            document.querySelectorAll('.screen-panel').forEach(p => p.classList.remove('active'));
            document.getElementById('panel-' + id).classList.add('active');
            document.querySelectorAll('.sub-nav').forEach(b => b.classList.remove('active'));
            const navEl = document.getElementById('nav-' + id);
            if (navEl) navEl.classList.add('active');
            document.getElementById('breadcrumbLast').textContent = subNames[id];
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Updated: only mark active card, do NOT navigate
        function selectType(card, sub) {
            document.querySelectorAll('.type-card').forEach(c => {
                c.classList.remove('active-card', 'border-navy-600', 'bg-navy-50', 'shadow-md');
                c.classList.add('border-slate-200');
            });
            card.classList.add('active-card', 'border-navy-600', 'bg-navy-50', 'shadow-md');
            card.classList.remove('border-slate-200');
            // no automatic switchSub
        }

        function checkAmount(input) {
            const val = parseFloat(input.value);
            const el = document.getElementById('amountCheck').querySelector('span');
            if (!val || isNaN(val)) {
                el.innerHTML = '— Enter amount above';
                el.className = 'text-[12px] font-semibold text-slate-400';
            } else if (val < 500) {
                el.innerHTML = '<i class="fas fa-times-circle text-red-500 mr-1"></i> Below minimum ₱500';
                el.className = 'text-[12px] font-semibold text-red-500';
            } else if (val > 5000) {
                el.innerHTML = '<i class="fas fa-times-circle text-red-500 mr-1"></i> Exceeds maximum ₱5,000';
                el.className = 'text-[12px] font-semibold text-red-500';
            } else {
                el.innerHTML = `<i class="fas fa-check-circle text-emerald-600 mr-1"></i> ₱${val.toLocaleString()} — within range`;
                el.className = 'text-[12px] font-semibold text-emerald-600';
            }
        }

        function runLimitCheck() { showToast('Limit check refreshed ✓'); }

        let patientOn = false;
        function togglePatient() {
            patientOn = !patientOn;
            const track = document.getElementById('patientToggleTrack');
            const thumb = document.getElementById('patientToggleThumb');
            const fields = document.getElementById('patientFields');
            track.classList.toggle('bg-navy-600', patientOn);
            track.classList.toggle('bg-slate-200', !patientOn);
            thumb.style.transform = patientOn ? 'translateX(16px)' : '';
            fields.classList.toggle('hidden', !patientOn);
        }

        function fileSelected(input, zoneId) {
            if (!input.files || !input.files[0]) return;
            const zone = document.getElementById(zoneId);
            const name = input.files[0].name;
            zone.classList.add('has-file');
            zone.querySelector('.upload-content').innerHTML = `<i class="fas fa-check-circle upload-icon text-navy-600"></i><span class="upload-title">${name}</span><span class="upload-hint">File uploaded</span>`;
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

        function saveDraft() { showToast('Draft saved successfully!'); }
        function saveComplete() { showToast('Availment saved & completed ✓'); }

        // UPDATED: Validate before proceeding
        document.getElementById('proceedToSubtype')?.addEventListener('click', () => {
            // 1. Amount validation
            const amountInput = document.getElementById('amountField');
            const amountVal = parseFloat(amountInput.value);
            if (!amountInput.value.trim() || isNaN(amountVal) || amountVal < 500 || amountVal > 5000) {
                showToast('Please enter a valid amount (₱500 – ₱5,000).');
                amountInput.focus();
                return;
            }

            // 2. Date Applied validation
            const dateApplied = document.getElementById('dateApplied').value;
            if (!dateApplied) {
                showToast('Please select Date Applied.');
                document.getElementById('dateApplied').focus();
                return;
            }

            // 3. Date Released validation
            const dateReleased = document.getElementById('dateReleased').value;
            if (!dateReleased) {
                showToast('Please select Date Released.');
                document.getElementById('dateReleased').focus();
                return;
            }

            // 4. Subtype selected?
            const activeCard = document.querySelector('.type-card.active-card');
            if (!activeCard) {
                showToast('Please select an assistance type first.');
                return;
            }

            // 5. Determine subtype and navigate
            const subtypeText = activeCard.querySelector('p')?.innerText.toLowerCase().trim();
            const subtypeMap = {
                'medical': 'medical',
                'financial': 'financial',
                'educational': 'educational',
                'livelihood': 'livelihood',
                'burial': 'burial'
            };
            const subtype = subtypeMap[subtypeText] || 'medical';
            switchSub(subtype);
        });

        requestAnimationFrame(() => setTimeout(() => {
            document.querySelectorAll('.budget-bar-fill').forEach(el => el.style.width = el.dataset.target);
        }, 400));
    </script>
</body>

</html>