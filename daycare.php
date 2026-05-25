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
    <title>Day Care Assessment – MSWDO San Enrique</title>
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

        .check-item {
            transition: all .15s;
            cursor: pointer;
        }

        .check-item:has(input:checked) {
            border-color: #0B2545;
            background: #E8EDF5;
        }

        .accred-opt {
            transition: all .18s;
            cursor: pointer;
        }

        .accred-opt:hover {
            border-color: #94A3B8;
        }

        .accred-opt.a-accredited {
            border-color: #0B2545;
            background: #E8EDF5;
        }

        .accred-opt.a-pending {
            border-color: #3A5F93;
            background: #EBF0F8;
        }

        .accred-opt.a-not {
            border-color: #163566;
            background: #E2E8F0;
        }

        .score-badge {
            display: inline-flex;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
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
                <a href="#" class="text-slate-400 hover:text-navy-600">Centers</a>
                <span class="text-slate-300">/</span>
                <span class="text-navy-600 font-semibold">Day Care Accreditation Assessment</span>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="saveDraft()"
                    class="text-[12px] font-medium text-navy-600 border border-navy-200 bg-navy-50 rounded-lg px-3 py-1.5 hover:bg-navy-100">Save
                    Draft</button>
            </div>
        </header>

        <main class="p-6 overflow-y-auto">
            <div class="max-w-4xl mx-auto space-y-5">

                <!-- Title -->
                <div class="animate-fade-up">
                    <h1 class="text-xl font-serif text-navy-600">Day Care Center Accreditation Assessment</h1>
                    <p class="text-[13px] text-slate-500 mt-1">DSWD Assessment Tool for Day Care Centers &amp; Day Care
                        Workers
                        (LGU-Managed)</p>
                </div>

                <!-- ===== SECTION: APPLICATION STATUS ===== -->
                <div class="animate-fade-up-1 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Status of Application &amp; Source of
                                Funds</h2>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="field-label req">Application Status</label>
                                <div class="flex gap-3 mt-1">
                                    <label class="flex items-center gap-2 text-[13px]"><input type="radio"
                                            name="appStatus" class="accent-navy-600"> New Application</label>
                                    <label class="flex items-center gap-2 text-[13px]"><input type="radio"
                                            name="appStatus" class="accent-navy-600"> Renewal</label>
                                </div>
                            </div>
                            <div>
                                <label class="field-label req">Source of Funds</label>
                                <div class="flex flex-wrap gap-2 mt-1">
                                    <label class="flex items-center gap-1.5 text-[12px]"><input type="checkbox"
                                            class="accent-navy-600">
                                        NGA</label>
                                    <label class="flex items-center gap-1.5 text-[12px]"><input type="checkbox"
                                            class="accent-navy-600">
                                        GOCC</label>
                                    <label class="flex items-center gap-1.5 text-[12px]"><input type="checkbox"
                                            class="accent-navy-600">
                                        LGU</label>
                                    <label class="flex items-center gap-1.5 text-[12px]"><input type="checkbox"
                                            class="accent-navy-600">
                                        NGO</label>
                                    <label class="flex items-center gap-1.5 text-[12px]"><input type="checkbox"
                                            class="accent-navy-600">
                                        PO</label>
                                    <label class="flex items-center gap-1.5 text-[12px]"><input type="checkbox"
                                            class="accent-navy-600">
                                        Private</label>
                                    <label class="flex items-center gap-1.5 text-[12px]"><input type="checkbox"
                                            class="accent-navy-600">
                                        Others</label>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div><label class="field-label">DSWD Certificate No.</label><input type="text" class="field"
                                    placeholder="If previously issued"></div>
                            <div><label class="field-label">Date of Issuance</label><input type="date" class="field">
                            </div>
                            <div><label class="field-label">Date of Expiration</label><input type="date" class="field">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== SECTION: CENTER & WORKER INFO ===== -->
                <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Day Care Center &amp; Worker Information
                            </h2>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2"><label class="field-label req">Name of Day Care Center</label><input
                                    type="text" class="field" placeholder="Official name"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2"><label class="field-label req">Address</label><input type="text"
                                    class="field" placeholder="Full address"></div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div><label class="field-label req">Date Established</label><input type="date"
                                    class="field"></div>
                            <div><label class="field-label req">Barangay</label><select class="field">
                                    <option value="">Select</option>
                                    <option>Brgy. Bagonawa</option>
                                    <option>Brgy. Baliwagan</option>
                                    <option>Brgy. Batuan</option>
                                    <option>Brgy. Guintorilan</option>
                                    <option>Brgy. Nayon</option>
                                    <option>Brgy. Poblacion</option>
                                    <option>Brgy. Sibucao</option>
                                    <option>Brgy. Tabao Baybay</option>
                                    <option>Brgy. Tabao Rizal</option>
                                    <option>Brgy. Tibsoc</option>
                                </select></div>
                            <div><label class="field-label req">Assessment Date</label><input type="date" class="field"
                                    id="dateApplied"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="field-label req">Day Care Worker Name</label><input type="text"
                                    class="field" placeholder="Full name"></div>
                            <div><label class="field-label req">Age</label><input type="number" min="18" class="field"
                                    placeholder="Years"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="field-label req">Educational Attainment</label><select class="field">
                                    <option value="">Select</option>
                                    <option>High School Level</option>
                                    <option>High School Graduate</option>
                                    <option>College Level</option>
                                    <option>College Graduate</option>
                                    <option>Vocational</option>
                                    <option>Master's Degree</option>
                                </select></div>
                            <div><label class="field-label req">Status of Appointment</label><select class="field">
                                    <option value="">Select</option>
                                    <option>Contractual / MOA</option>
                                    <option>Casual</option>
                                    <option>Permanent / Regular</option>
                                </select></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="field-label req">C/MSWDO ECCD Focal Person</label><input type="text"
                                    class="field" placeholder="DCS Supervisor / Administrator"></div>
                            <div><label class="field-label">Contact Number</label><input type="text" class="field"
                                    placeholder="Telephone / Mobile"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="field-label">Email Address</label><input type="email" class="field"
                                    placeholder="email@example.com"></div>
                            <div><label class="field-label">Registration &amp; License No.</label><input type="text"
                                    class="field" placeholder="If applicable"></div>
                        </div>
                    </div>
                </div>

                <!-- ===== AREA A: Advancement of Children's Growth & Development ===== -->
                <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-child"></i>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Area A: Advancement of Children's Growth
                                &amp;
                                Development</h2>
                            <p class="text-[11px] text-slate-400">Assessment of Children · Health &amp; Nutrition ·
                                Curriculum ·
                                Guidance &amp; Interactions</p>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">

                        <!-- I. Assessment of Children -->
                        <div>
                            <h3 class="text-[12px] font-semibold text-navy-600 mb-3 flex items-center gap-2"><i
                                    class="fas fa-clipboard-check text-navy-400"></i> I. Assessment of Children</h3>
                            <div class="grid grid-cols-1 gap-2">
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">ECCD Checklist available <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Intake form per child <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Growth monitoring chart <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Recorded observations per child <span
                                            class="score-badge bg-navy-400 text-white ml-2">2 pts</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Compilation of children's work samples <span
                                            class="score-badge bg-navy-600 text-white ml-2">3 pts</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Recording of one-on-one assessment tasks
                                        <span class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                            </div>
                        </div>

                        <!-- II. Health & Nutrition -->
                        <div class="pt-3 border-t border-slate-100">
                            <h3 class="text-[12px] font-semibold text-navy-600 mb-3 flex items-center gap-2"><i
                                    class="fas fa-heartbeat text-navy-400"></i> II. Health &amp; Nutrition</h3>
                            <p class="text-[11px] text-slate-500 mb-2 font-medium">A. Nutrition &amp; Feeding Practices
                            </p>
                            <div class="grid grid-cols-1 gap-2">
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Nutritious food served to children <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Safe drinking water available <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Food brought by Parents Committee members
                                        <span class="score-badge bg-navy-400 text-white ml-2">2
                                            pts</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Food cooked/prepared at the Center <span
                                            class="score-badge bg-navy-600 text-white ml-2">3 pts</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Special diets/food allergies considered <span
                                            class="score-badge bg-navy-600 text-white ml-2">3 pts</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Clean utensils used in food preparation <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Food covers &amp; storage available <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                            </div>
                            <p class="text-[11px] text-slate-500 mb-2 mt-3 font-medium">B. Health &amp; Sanitation
                                Practices</p>
                            <div class="grid grid-cols-1 gap-2">
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Health history records per child <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">First aid kit available at all times <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Medications &amp; hazardous objects stored
                                        securely <span class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Toilets &amp; sinks washed daily <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Children wear clean clothes daily <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Hand washing before/after eating <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Labeled washcloth/towel per child <span
                                            class="score-badge bg-navy-400 text-white ml-2">2 pts</span></span></label>
                            </div>
                        </div>

                        <!-- III. Curriculum -->
                        <div class="pt-3 border-t border-slate-100">
                            <h3 class="text-[12px] font-semibold text-navy-600 mb-3 flex items-center gap-2"><i
                                    class="fas fa-book-open text-navy-400"></i> III. Curriculum</h3>
                            <div class="grid grid-cols-1 gap-2">
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Safe, durable, non-toxic materials <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Local/reusable resources utilized <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Storybooks/picture books available <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Musical instruments available <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Art materials from local/reusable sources
                                        <span class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Unstructured materials (sand, water, clay)
                                        <span class="score-badge bg-navy-400 text-white ml-2">2
                                            pts</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Audio-visual facilities for children <span
                                            class="score-badge bg-navy-600 text-white ml-2">3 pts</span></span></label>
                            </div>
                        </div>

                        <!-- IV. Guidance & Interactions -->
                        <div class="pt-3 border-t border-slate-100">
                            <h3 class="text-[12px] font-semibold text-navy-600 mb-3 flex items-center gap-2"><i
                                    class="fas fa-hand-holding-heart text-navy-400"></i> IV. Guidance &amp; Interactions
                            </h3>
                            <div class="grid grid-cols-1 gap-2">
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Learning activity areas provided (story,
                                        table games, arts, news
                                        sharing) <span class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Science/discovery or music/movement area
                                        <span class="score-badge bg-navy-400 text-white ml-2">2
                                            pts</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Learning areas defined by dividers/markers
                                        <span class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Children's works displayed at eye level <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Daily schedule posted in visible area <span
                                            class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                            pt</span></span></label>
                                <label
                                    class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                        type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                        class="text-[12px] text-slate-700">Outdoor play area available &amp; supervised
                                        <span class="score-badge bg-navy-400 text-white ml-2">2
                                            pts</span></span></label>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ===== AREA B: Partnership ===== -->
                <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Area B: Partnership with Families,
                                Communities &amp;
                                Local Government</h2>
                        </div>
                    </div>
                    <div class="p-6 space-y-3">
                        <h3 class="text-[12px] font-semibold text-navy-600 flex items-center gap-2"><i
                                class="fas fa-users text-navy-400"></i> I. Parents' Committee</h3>
                        <div class="grid grid-cols-1 gap-2">
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Coordinates parent education sessions <span
                                        class="score-badge bg-navy-400 text-white ml-2">2 pts</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Manages complementary feeding program <span
                                        class="score-badge bg-navy-600 text-white ml-2">3 pts</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Monthly menu plan prepared/approved <span
                                        class="score-badge bg-navy-600 text-white ml-2">3 pts</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Written menu info provided to parents monthly
                                    <span class="score-badge bg-navy-400 text-white ml-2">2 pts</span></span></label>
                        </div>
                        <h3 class="text-[12px] font-semibold text-navy-600 flex items-center gap-2 mt-4"><i
                                class="fas fa-building text-navy-400"></i> II. Community Involvement</h3>
                        <div class="grid grid-cols-1 gap-2">
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">75% BECCDCC/BCPC meeting attendance <span
                                        class="score-badge bg-navy-600 text-white ml-2">3 pts</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Annual action plan prepared <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Monthly meetings held <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">LGU fully supports (no user's fee imposed) <span
                                        class="score-badge bg-navy-600 text-white ml-2">3 pts</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Financial records maintained <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                        </div>
                    </div>
                </div>

                <!-- ===== AREA C: Human Resource Development ===== -->
                <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Area C: Human Resource Development</h2>
                        </div>
                    </div>
                    <div class="p-6 space-y-3">
                        <div class="grid grid-cols-1 gap-2">
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Adult-child ratio: 1:10 maintained <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Adult-child ratio: 1:6–9 <span
                                        class="score-badge bg-navy-400 text-white ml-2">2 pts</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Adult-child ratio: 1:5 <span
                                        class="score-badge bg-navy-600 text-white ml-2">3 pts</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Max 30 children per session <span
                                        class="score-badge bg-navy-400 text-white ml-2">2 pts</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Monthly meetings with DCW for TA <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Session observations twice a year <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Performance appraisal tool used <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">DCW honorarium: Satisfactory level <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">DCW honorarium: Highly Satisfactory level <span
                                        class="score-badge bg-navy-400 text-white ml-2">2 pts</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">DCW honorarium: Outstanding level <span
                                        class="score-badge bg-navy-600 text-white ml-2">3 pts</span></span></label>
                        </div>
                    </div>
                </div>

                <!-- ===== AREA D: Program Management ===== -->
                <div class="animate-fade-up-4 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Area D: Program Management &amp;
                                Administration</h2>
                        </div>
                    </div>
                    <div class="p-6 space-y-3">
                        <div class="grid grid-cols-1 gap-2">
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Annual work &amp; financial plan available <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Previous evaluation results used for planning
                                    <span class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Annual budget for compensation, education,
                                    materials <span class="score-badge bg-navy-400 text-white ml-2">2
                                        pts</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Semestral supervision of DCWs <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Written policies: assessment, records, child
                                    protection <span class="score-badge bg-navy-600 text-white ml-2">3
                                        pts</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Annual evaluation of work &amp; financial plan
                                    <span class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Complete accounting of receipts &amp;
                                    expenditures <span class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                        pt</span></span></label>
                        </div>
                    </div>
                </div>

                <!-- ===== AREA E: Physical Environment & Safety ===== -->
                <div class="animate-fade-up-4 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Area E: Physical Environment &amp;
                                Safety</h2>
                        </div>
                    </div>
                    <div class="p-6 space-y-3">
                        <h3 class="text-[12px] font-semibold text-navy-600 flex items-center gap-2"><i
                                class="fas fa-map-marker-alt text-navy-400"></i> I. Location</h3>
                        <div class="grid grid-cols-1 gap-2">
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Fenced by non-climbable barrier or natural
                                    barriers <span class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                        pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Free from hazards <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">No gambling/beerhouses within 200m radius <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Smoke-free zone <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                        </div>
                        <h3 class="text-[12px] font-semibold text-navy-600 flex items-center gap-2 mt-4"><i
                                class="fas fa-home text-navy-400"></i> II. Indoor Environment</h3>
                        <div class="grid grid-cols-1 gap-2">
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">1 child = 1 sq. meter space ratio <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Well-lighted room <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Well-ventilated room <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">At least 1 toilet/bath &amp; 1 lavatory
                                    (child-sized) <span class="score-badge bg-navy-100 text-navy-600 ml-2">1
                                        pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Child-sized furniture (tables, chairs, shelves)
                                    <span class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Fire extinguisher available <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Electrical cords out of children's reach <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Garbage containers segregated &amp; covered <span
                                        class="score-badge bg-navy-100 text-navy-600 ml-2">1 pt</span></span></label>
                        </div>
                        <h3 class="text-[12px] font-semibold text-navy-600 flex items-center gap-2 mt-4"><i
                                class="fas fa-tree text-navy-400"></i> III. Outdoor Environment (if applicable)</h3>
                        <div class="grid grid-cols-1 gap-2">
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Outdoor play area available (auto 2 pts) <span
                                        class="score-badge bg-navy-400 text-white ml-2">2 pts</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Outdoor area fenced &amp; in clear view of staff
                                    <span class="score-badge bg-navy-400 text-white ml-2">2 pts</span></span></label>
                            <label
                                class="check-item flex items-center gap-3 p-2.5 border-2 border-slate-200 rounded-xl cursor-pointer"><input
                                    type="checkbox" class="w-4 h-4 accent-navy-600 flex-shrink-0"><span
                                    class="text-[12px] text-slate-700">Outdoor structures firmly anchored &amp; safe
                                    <span class="score-badge bg-navy-400 text-white ml-2">2 pts</span></span></label>
                        </div>
                    </div>
                </div>

                <!-- ===== SUMMARY OF RATING ===== -->
                <div class="animate-fade-up-4 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-star"></i>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Summary of Rating – Day Care Center</h2>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full text-[12px] border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 text-[10px] uppercase tracking-wide">
                                        <th class="px-3 py-2 text-left font-semibold">Work Area</th>
                                        <th class="px-3 py-2 text-center font-semibold">Level 1<br>(1 pt)</th>
                                        <th class="px-3 py-2 text-center font-semibold">Level 2<br>(2 pts)</th>
                                        <th class="px-3 py-2 text-center font-semibold">Level 3<br>(3 pts)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-navy-600">A. Children's Growth &amp;
                                            Development</td>
                                        <td class="px-3 py-2 text-center"><input type="number" min="0"
                                                class="w-16 py-1 px-2 border border-slate-200 rounded-lg text-center text-[12px]"
                                                placeholder="0"></td>
                                        <td class="px-3 py-2 text-center"><input type="number" min="0"
                                                class="w-16 py-1 px-2 border border-slate-200 rounded-lg text-center text-[12px]"
                                                placeholder="0"></td>
                                        <td class="px-3 py-2 text-center"><input type="number" min="0"
                                                class="w-16 py-1 px-2 border border-slate-200 rounded-lg text-center text-[12px]"
                                                placeholder="0"></td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-navy-600">B. Partnership with Families
                                        </td>
                                        <td class="px-3 py-2 text-center"><input type="number" min="0"
                                                class="w-16 py-1 px-2 border border-slate-200 rounded-lg text-center text-[12px]"
                                                placeholder="0"></td>
                                        <td class="px-3 py-2 text-center"><input type="number" min="0"
                                                class="w-16 py-1 px-2 border border-slate-200 rounded-lg text-center text-[12px]"
                                                placeholder="0"></td>
                                        <td class="px-3 py-2 text-center"><input type="number" min="0"
                                                class="w-16 py-1 px-2 border border-slate-200 rounded-lg text-center text-[12px]"
                                                placeholder="0"></td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-navy-600">C. Human Resource Development
                                        </td>
                                        <td class="px-3 py-2 text-center"><input type="number" min="0"
                                                class="w-16 py-1 px-2 border border-slate-200 rounded-lg text-center text-[12px]"
                                                placeholder="0"></td>
                                        <td class="px-3 py-2 text-center"><input type="number" min="0"
                                                class="w-16 py-1 px-2 border border-slate-200 rounded-lg text-center text-[12px]"
                                                placeholder="0"></td>
                                        <td class="px-3 py-2 text-center"><input type="number" min="0"
                                                class="w-16 py-1 px-2 border border-slate-200 rounded-lg text-center text-[12px]"
                                                placeholder="0"></td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-navy-600">D. Program Management</td>
                                        <td class="px-3 py-2 text-center"><input type="number" min="0"
                                                class="w-16 py-1 px-2 border border-slate-200 rounded-lg text-center text-[12px]"
                                                placeholder="0"></td>
                                        <td class="px-3 py-2 text-center"><input type="number" min="0"
                                                class="w-16 py-1 px-2 border border-slate-200 rounded-lg text-center text-[12px]"
                                                placeholder="0"></td>
                                        <td class="px-3 py-2 text-center"><input type="number" min="0"
                                                class="w-16 py-1 px-2 border border-slate-200 rounded-lg text-center text-[12px]"
                                                placeholder="0"></td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-navy-600">E. Physical Environment &amp;
                                            Safety</td>
                                        <td class="px-3 py-2 text-center"><input type="number" min="0"
                                                class="w-16 py-1 px-2 border border-slate-200 rounded-lg text-center text-[12px]"
                                                placeholder="0"></td>
                                        <td class="px-3 py-2 text-center"><input type="number" min="0"
                                                class="w-16 py-1 px-2 border border-slate-200 rounded-lg text-center text-[12px]"
                                                placeholder="0"></td>
                                        <td class="px-3 py-2 text-center"><input type="number" min="0"
                                                class="w-16 py-1 px-2 border border-slate-200 rounded-lg text-center text-[12px]"
                                                placeholder="0"></td>
                                    </tr>
                                    <tr class="bg-navy-50">
                                        <td class="px-3 py-2 font-semibold text-navy-700">TOTAL</td>
                                        <td class="px-3 py-2 text-center font-semibold text-navy-700" id="total1">0</td>
                                        <td class="px-3 py-2 text-center font-semibold text-navy-700" id="total2">0</td>
                                        <td class="px-3 py-2 text-center font-semibold text-navy-700" id="total3">0</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-3">
                            <strong>Level 1 (3 Stars):</strong> Min 97 pts · <strong>Level 2 (4 Stars):</strong> Level 1
                            + min 30 pts
                            · <strong>Level 3 (5 Stars):</strong> Level 1 + Level 2 + min 26 pts
                        </p>
                    </div>
                </div>

                <!-- ===== ACCREDITATION STATUS & RECOMMENDATIONS ===== -->
                <div class="animate-fade-up-4 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-award"></i>
                        </div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Accreditation Recommendation</h2>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-3 gap-3" id="accredSelector">
                            <div onclick="setAccred(this,'accredited','a-accredited')"
                                class="accred-opt border-2 border-navy-600 bg-navy-50 rounded-2xl p-4 text-center">
                                <i class="fas fa-trophy text-navy-600 text-2xl mb-2"></i>
                                <p class="text-[13px] font-semibold text-navy-700">For Issuance</p>
                                <p class="text-[10px] text-slate-500 mt-1">Meets accreditation standards</p>
                            </div>
                            <div onclick="setAccred(this,'pending','a-pending')"
                                class="accred-opt border-2 border-navy-200 rounded-2xl p-4 text-center">
                                <i class="fas fa-hourglass-half text-navy-500 text-2xl mb-2"></i>
                                <p class="text-[13px] font-semibold text-navy-600">Pending Compliance</p>
                                <p class="text-[10px] text-slate-500 mt-1">Held in abeyance</p>
                            </div>
                            <div onclick="setAccred(this,'not','a-not')"
                                class="accred-opt border-2 border-navy-200 rounded-2xl p-4 text-center">
                                <i class="fas fa-times-circle text-navy-400 text-2xl mb-2"></i>
                                <p class="text-[13px] font-semibold text-navy-600">Non-Issuance</p>
                                <p class="text-[10px] text-slate-500 mt-1">Fails minimum standards</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="field-label">Validity Period (years)</label><input type="number" min="0"
                                    max="5" class="field" placeholder="3, 4, or 5"></div>
                            <div><label class="field-label">Compliance Period (months)</label><input type="number"
                                    min="0" max="6" class="field" placeholder="If pending"></div>
                        </div>
                        <div><label class="field-label">Areas for Compliance / Action Plan</label><textarea
                                class="field resize-none" rows="3"
                                placeholder="List areas needing compliance, activities, timeframe, responsible person..."></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="field-label">Assessed By (DSWD Accreditor)</label><input type="text"
                                    class="field" placeholder="Name & Signature"></div>
                            <div><label class="field-label">Date</label><input type="date" class="field"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="field-label">Concurred By (DCW/Agency Head)</label><input type="text"
                                    class="field" placeholder="Name & Signature"></div>
                            <div><label class="field-label">Date</label><input type="date" class="field"></div>
                        </div>
                        <div><label class="field-label">Highlights of Interview / Observation</label><textarea
                                class="field resize-none" rows="4"
                                placeholder="Record key findings, notes from interviews with DCW, Parents Committee, BECCDCC/BCPC members..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button onclick="saveComplete()"
                        class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500">Submit
                        Assessment</button>
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
        function setAccred(el, type, cls) {
            document.querySelectorAll('#accredSelector .accred-opt').forEach(e => {
                e.className = 'accred-opt border-2 border-navy-200 rounded-2xl p-4 text-center cursor-pointer';
                e.querySelector('p').className = 'text-[13px] font-semibold text-navy-600';
            });
            el.classList.add(cls);
        }
        function showToast(msg, type = 'success') {
            const toast = document.getElementById('toast');
            const icon = toast.querySelector('i');
            const msgSpan = document.getElementById('toastMsg');
            msgSpan.textContent = msg;
            if (type === 'error') {
                icon.className = 'fas fa-exclamation-circle text-red-400';
                toast.style.backgroundColor = '#7F1D1D';
            } else {
                icon.className = 'fas fa-check-circle text-navy-400';
                toast.style.backgroundColor = '#0B2545';
            }
            toast.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
            toast.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-4');
                toast.classList.remove('opacity-100', 'translate-y-0');
            }, 3000);
        }
        function saveDraft() { showToast('Draft saved!'); }
        function saveComplete() {
            const centerName = document.querySelector('input[placeholder="Official name"]');
            if (!centerName || !centerName.value.trim()) {
                showToast('Please enter the Day Care Center name.', 'error');
                if (centerName) centerName.focus();
                return;
            }
            showToast('Accreditation assessment submitted!');
        }
    </script>
</body>

</html>