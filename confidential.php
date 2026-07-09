<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';

// Flash message from confidential_case_action.php (after a redirect)
$flashMsg  = $_GET['msg'] ?? '';
$flashType = $_GET['type'] ?? 'success';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Confidential Case Intake – MSWDO San Enrique</title>
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
        body { font-family: 'DM Sans', sans-serif; }
        .sidebar-item { transition: all .15s; }
        .sidebar-item:hover { background: rgba(255, 255, 255, .07); color: rgba(255, 255, 255, .95); }
        .sidebar-item.active { background: rgba(29, 111, 164, .28); border-left-color: #C49A2A; color: #fff; }
        .screen-panel { display: block; }
        .conf-shimmer {
            background: linear-gradient(90deg, #0B2545 0%, #3A5F93 50%, #0B2545 100%);
            background-size: 600px 100%;
            animation: shimmer 3s linear infinite;
        }
        .field {
            display: block; width: 100%; border-radius: .75rem; border: 1.5px solid #E2E8F0;
            background: #F8FAFC; padding: .625rem .875rem; font-size: 13px; color: #1e293b;
            outline: none; font-family: 'DM Sans', sans-serif; transition: all .2s;
        }
        .field:focus { border-color: #3A5F93; background: #fff; box-shadow: 0 0 0 3px rgba(58, 95, 147, .1); }
        .field::placeholder { color: #94A3B8; }
        select.field {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236B7280' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 10px center; background-size: 16px; appearance: none;
        }
        textarea.field { resize: vertical; min-height: 80px; }
        .field-label { display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #64748B; margin-bottom: 6px; }
        .req::after { content: '*'; color: #EF4444; margin-left: 2px; }
        .section-card { background: #fff; border-radius: 1rem; border: 1px solid #E2E8F0; overflow: hidden; margin-bottom: 1.25rem; }
        .section-head { display: flex; align-items: center; gap: .75rem; padding: .875rem 1.5rem; border-bottom: 1px solid #F1F5F9; background: #F8FAFC; }
        .section-num { width: 28px; height: 28px; border-radius: 50%; background: #0B2545; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; }
        .section-body { padding: 1.5rem; }
        .case-type-opt { transition: all .18s; cursor: pointer; }
        .case-type-opt:hover { border-color: #94A3B8; transform: translateY(-1px); }
        .case-type-opt.active { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(11, 37, 69, .15); border-color: #0B2545 !important; background: #E8EDF5 !important; }
        .case-type-opt.active .ct-label { color: #0B2545 !important; }
        .action-check { transition: all .15s; cursor: pointer; }
        .action-check:has(input:checked) { border-color: #0B2545; background: #E8EDF5; }
        .action-check:has(input:checked) .ac-text { color: #0B2545; font-weight: 500; }
        .action-check:hover { border-color: #3A5F93; }
        .status-opt { transition: all .18s; cursor: pointer; }
        .status-opt:hover { border-color: #94A3B8; }
        .status-opt.active { border-color: #0B2545 !important; background: #E8EDF5 !important; }
        .status-opt.active .so-lbl { color: #0B2545 !important; }
        .upload-zone {
            display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 130px;
            border: 2px dashed #CBD5E1; border-radius: 0.875rem; padding: 1.25rem 1rem; text-align: center;
            cursor: pointer; transition: all .2s; background: #F8FAFC; width: 100%; box-sizing: border-box;
        }
        .upload-zone:hover { border-color: #3A5F93; background: #EBF4FB; }
        .upload-zone.has-file { border-color: #0B2545; background: #E8EDF5; border-style: solid; }
        .upload-zone input[type=file] { display: none; }
        .upload-zone .upload-content { display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; gap: 0.25rem; }
        .char-count { font-size: 10px; color: #94A3B8; text-align: right; margin-top: 4px; }
        .char-count.warn { color: #F59E0B; }
        .char-count.limit { color: #EF4444; }
        #clientDropdown { max-height: 260px; overflow-y: auto; }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, .15); border-radius: 2px; }
    </style>
</head>

<body class="bg-slate2 min-h-screen flex">

    <?php require 'sidebar.php'; ?>

    <div class="ml-64 flex-1 flex flex-col min-h-screen">

        <header class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <span class="text-slate-400">Women &amp; Child Protection</span>
                <span class="text-slate-300">/</span>
                <span class="text-navy-600 font-semibold">New Case Intake</span>
            </div>
            <div class="flex items-center gap-2">
                <a href="confidential_case_search.php"
                    class="text-[12px] font-medium text-navy-600 border border-navy-200 bg-navy-50 rounded-lg px-3 py-1.5 hover:bg-navy-100 transition-all">
                    <i class="fas fa-list mr-1"></i> View All Cases
                </a>
            </div>
        </header>

        <main class="flex-1 p-6 overflow-y-auto">

            <div class="screen-panel" id="panel-intake">
                <form id="intakeForm" method="POST" action="confidential_case_action.php" enctype="multipart/form-data" class="max-w-3xl mx-auto space-y-5">

                    <div class="animate-fade-up">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-slate-300">·</span>
                            <span class="text-[12px] text-slate-400">Women &amp; Child Protection — Confidential</span>
                        </div>
                        <h1 class="text-xl font-serif text-navy-600">Confidential Case Intake Form</h1>
                        <p class="text-[13px] text-slate-500 mt-1">VAWC · CICL · Child at Risk · Child Abuse — All data
                            is restricted and access-logged.</p>
                    </div>

                    <div class="animate-fade-up-1 relative overflow-hidden rounded-2xl border border-navy-200 bg-navy-50">
                        <div class="conf-shimmer absolute top-0 left-0 right-0 h-1 opacity-80"></div>
                        <div class="px-5 py-4 flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-navy-100 flex items-center justify-center text-xl flex-shrink-0 mt-0.5">
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
                                <p class="text-[11px] text-slate-400">Search for an existing client by name or client ID</p>
                            </div>
                        </div>
                        <div class="section-body space-y-4">
                            <div>
                                <label class="field-label req">Client</label>
                                <input type="hidden" name="client_id" id="selectedClientId" required>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="fas fa-search"></i></span>
                                    <input type="text" id="clientSearch" class="field pl-10"
                                        placeholder="Search by name or Client ID..."
                                        autocomplete="off"
                                        oninput="searchClients(this.value)">
                                </div>
                                <div id="clientDropdown"
                                    class="hidden mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden z-10 relative">
                                </div>
                                <div id="selectedClientChip"
                                    class="hidden mt-2 flex items-center gap-3 bg-navy-50 border border-navy-200 rounded-xl px-4 py-2.5">
                                    <div id="chipAvatar" class="w-8 h-8 rounded-lg bg-navy-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0"></div>
                                    <div class="flex-1">
                                        <p id="chipName" class="text-[12px] font-semibold text-navy-600"></p>
                                        <p id="chipMeta" class="text-[10px] text-slate-400"></p>
                                    </div>
                                    <button type="button" onclick="clearClient()" class="text-slate-400 hover:text-red-500 transition-colors text-sm"><i class="fas fa-times"></i></button>
                                </div>
                            </div>

                            <!-- Two-step Case Type (Victim / Offender) -->
                            <div>
                                <label class="field-label req">Case Category</label>
                                <input type="hidden" name="wc_case_type" id="selectedCaseType" required>
                                <div class="grid grid-cols-2 gap-3 mt-1" id="caseCategorySelector">
                                    <div onclick="setCaseCategory(this, 'victim')"
                                        class="case-type-opt border-2 border-slate-200 rounded-xl p-4 text-center">
                                        <p class="ct-label text-[13px] font-bold text-slate-700">Victim Case</p>
                                        <p class="text-[10px] text-slate-400 mt-1 leading-tight">VAWC · Child
                                            Abuse<br>Women & Children subjected to violence</p>
                                    </div>
                                    <div onclick="setCaseCategory(this, 'offender')"
                                        class="case-type-opt border-2 border-slate-200 rounded-xl p-4 text-center">
                                        <p class="ct-label text-[13px] font-bold text-slate-700">Offender / At‑Risk Case</p>
                                        <p class="text-[10px] text-slate-400 mt-1 leading-tight">CICL · Child at
                                            Risk<br>Children in conflict or at risk of offending</p>
                                    </div>
                                </div>

                                <div id="caseTypeGroup" class="hidden mt-4">
                                    <label class="field-label req">Specific Case Type</label>
                                    <div class="grid grid-cols-2 gap-2 mt-1" id="caseTypeSelector"></div>
                                </div>
                            </div>

                            <div>
                                <label class="field-label">Case Number</label>
                                <input type="text" class="field bg-slate-100 text-slate-400" value="Assigned automatically when saved" disabled>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label req">Incident Date</label>
                                    <input type="date" name="wc_incident_date" class="field" required>
                                </div>
                                <div>
                                    <label class="field-label req">Incident Place / Location</label>
                                    <input type="text" name="wc_incident_place" class="field" placeholder="Address or description of where incident occurred" required>
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
                                <p class="text-[11px] text-slate-400">Detailed account of the incident — restricted to authorized personnel</p>
                            </div>
                        </div>
                        <div class="section-body space-y-4">
                            <div>
                                <label class="field-label req">Narrative Report <span class="text-[10px] font-normal text-slate-400 ml-1 lowercase">(detailed account of the incident)</span></label>
                                <textarea name="wc_narrative" class="field" rows="5" id="narrativeText" maxlength="2000"
                                    oninput="countChars('narrativeText','narrativeCount',2000)"
                                    placeholder="Record the detailed account of the incident as reported by the victim, witness, or referral source..." required></textarea>
                                <div class="char-count" id="narrativeCount">0 / 2000 characters</div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Offender Information <span class="text-[10px] font-normal text-slate-400 ml-1 lowercase">(if known)</span></label>
                                    <textarea name="wc_offender_info" class="field" rows="3" placeholder="Name, age, relationship to victim, contact details, known location..."></textarea>
                                </div>
                                <div>
                                    <label class="field-label">Witness Information <span class="text-[10px] font-normal text-slate-400 ml-1 lowercase">(if any)</span></label>
                                    <textarea name="wc_witness_info" class="field" rows="3" placeholder="Names, contact details, and relationship to victim/offender of any witnesses..."></textarea>
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
                                <p class="text-[11px] text-slate-400">Check all interventions that have already been conducted or arranged</p>
                            </div>
                            <div class="ml-auto"><span id="actionsCount" class="text-[11px] font-semibold text-navy-600 bg-navy-50 border border-navy-200 px-2.5 py-0.5 rounded-full">0 actions</span></div>
                        </div>
                        <div class="section-body">
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4">
                                <?php
                                $actionOptions = [
                                    ['fas fa-brain', 'Counseling / Psychosocial', 'Immediate psychological support'],
                                    ['fas fa-hospital', 'Medical Referral', 'Referred to health facility'],
                                    ['fas fa-balance-scale', 'Legal Assistance', 'PAO / legal aid referral'],
                                    ['fas fa-ambulance', 'Rescue Operation', 'Coordinated rescue conducted'],
                                    ['fas fa-home', 'Temporary Shelter', "DSWD / women's shelter"],
                                    ['fas fa-handshake', 'Barangay Coordination', 'BCPC / barangay officials alerted'],
                                    ['fas fa-shield-alt', 'Police Coordination', 'PNP / WCPD notified'],
                                    ['fas fa-gavel', 'Court Referral', 'Filed or referred to court'],
                                    ['fas fa-building', 'DSWD Referral', 'Escalated to DSWD province'],
                                ];
                                foreach ($actionOptions as $opt):
                                    [$icon, $label, $desc] = $opt;
                                ?>
                                <label class="action-check flex items-center gap-3 p-3.5 border-2 border-slate-200 rounded-xl cursor-pointer" onchange="updateActionsCount()">
                                    <input type="checkbox" name="actions[]" value="<?= htmlspecialchars($label) ?>" class="w-4 h-4 accent-navy-600 flex-shrink-0 action-cb">
                                    <div><span class="ac-text text-[12px] font-medium text-slate-700 block"><i class="<?= $icon ?> mr-1"></i> <?= htmlspecialchars($label) ?></span><span class="text-[10px] text-slate-400"><?= htmlspecialchars($desc) ?></span></div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <div>
                                <label class="field-label">Other Actions / Remarks</label>
                                <textarea name="other_actions" class="field" rows="2" placeholder="Describe any other actions taken not listed above..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Assignment & Status -->
                    <div class="section-card animate-fade-up-5">
                        <div class="section-head">
                            <div class="section-num">4</div>
                            <div><h2 class="text-[14px] font-semibold text-navy-600">Assignment &amp; Case Status</h2></div>
                        </div>
                        <div class="section-body space-y-5">
                            <div>
                                <label class="field-label req">Assigned Social Worker</label>
                                <input type="text" name="wc_assigned_worker" class="field" placeholder="Ma. Teresa C. Ponclara, RSW" required>
                            </div>
                            <div>
                                <label class="field-label req">Case Status</label>
                                <input type="hidden" name="wc_status" id="selectedCaseStatus" value="Active" required>
                                <div class="grid grid-cols-5 gap-2 mt-2" id="caseStatusSelector">
                                    <div onclick="setCaseStatus(this, 'Active')" class="status-opt active border-2 border-slate-200 rounded-xl p-3 text-center">
                                        <div class="text-lg mb-1"><i class="fas fa-circle text-navy-600"></i></div>
                                        <p class="so-lbl text-[11px] font-bold text-navy-700">Active</p>
                                        <p class="text-[9px] text-slate-400 mt-0.5">Ongoing intervention</p>
                                    </div>
                                    <div onclick="setCaseStatus(this, 'Monitoring')" class="status-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                                        <div class="text-lg mb-1"><i class="fas fa-circle text-navy-400"></i></div>
                                        <p class="so-lbl text-[11px] font-semibold text-slate-600">Monitoring</p>
                                        <p class="text-[9px] text-slate-400 mt-0.5">Regular follow-up</p>
                                    </div>
                                    <div onclick="setCaseStatus(this, 'Resolved')" class="status-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                                        <div class="text-lg mb-1"><i class="fas fa-check-circle text-navy-500"></i></div>
                                        <p class="so-lbl text-[11px] font-semibold text-slate-600">Resolved</p>
                                        <p class="text-[9px] text-slate-400 mt-0.5">Issue addressed</p>
                                    </div>
                                    <div onclick="setCaseStatus(this, 'Closed')" class="status-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                                        <div class="text-lg mb-1"><i class="fas fa-lock text-navy-400"></i></div>
                                        <p class="so-lbl text-[11px] font-semibold text-slate-600">Closed</p>
                                        <p class="text-[9px] text-slate-400 mt-0.5">Case closed</p>
                                    </div>
                                    <div onclick="setCaseStatus(this, 'Referred')" class="status-opt border-2 border-slate-200 rounded-xl p-3 text-center">
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
                                <p class="text-[11px] text-slate-400">All uploaded files are stored with restricted access — confidential</p>
                            </div>
                        </div>
                        <div class="section-body">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <div class="field-label text-[10px] mb-1">Police Blotter / Incident Report</div>
                                    <label class="upload-zone" id="uz-conf-blotter"><input type="file" name="conf_blotter" accept=".pdf,.jpg,.png" onchange="fileSelected(this,'uz-conf-blotter')">
                                        <div class="upload-content"><i class="fas fa-file-alt text-2xl mb-1"></i><p class="text-[12px] font-medium text-slate-600">Click to upload</p><p class="text-[10px] text-slate-400">PDF, JPG, PNG</p></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label text-[10px] mb-1">Medical / Medico-Legal Records</div>
                                    <label class="upload-zone" id="uz-conf-med"><input type="file" name="conf_medical" accept=".pdf,.jpg,.png" onchange="fileSelected(this,'uz-conf-med')">
                                        <div class="upload-content"><i class="fas fa-notes-medical text-2xl mb-1"></i><p class="text-[12px] font-medium text-slate-600">Click to upload</p><p class="text-[10px] text-slate-400">PDF, JPG, PNG</p></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label text-[10px] mb-1">Court / Legal Documents</div>
                                    <label class="upload-zone" id="uz-conf-court"><input type="file" name="conf_court" accept=".pdf,.jpg,.png" onchange="fileSelected(this,'uz-conf-court')">
                                        <div class="upload-content"><i class="fas fa-gavel text-2xl mb-1"></i><p class="text-[12px] font-medium text-slate-600">Click to upload</p><p class="text-[10px] text-slate-400">PDF, JPG, PNG</p></div>
                                    </label>
                                </div>
                                <div>
                                    <div class="field-label text-[10px] mb-1">Photographs / Visual Evidence</div>
                                    <label class="upload-zone" id="uz-conf-photos"><input type="file" name="conf_photos[]" accept=".jpg,.png" multiple onchange="fileSelected(this,'uz-conf-photos')">
                                        <div class="upload-content"><i class="fas fa-camera text-2xl mb-1"></i><p class="text-[12px] font-medium text-slate-600">Click to upload (multiple)</p><p class="text-[10px] text-slate-400">JPG, PNG only</p></div>
                                    </label>
                                </div>
                                <div class="col-span-2">
                                    <div class="field-label text-[10px] mb-1">Other Supporting Documents</div>
                                    <label class="upload-zone" id="uz-conf-other"><input type="file" name="conf_other[]" accept=".pdf,.jpg,.png" multiple onchange="fileSelected(this,'uz-conf-other')">
                                        <div class="upload-content"><i class="fas fa-folder-open text-2xl mb-1"></i><p class="text-[12px] font-medium text-slate-600">Attach any other relevant documents</p><p class="text-[10px] text-slate-400">PDF, JPG, PNG</p></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="submit" class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500 transition-all">
                            <i class="fas fa-lock mr-1"></i> Save Case
                        </button>
                    </div>

                </form>
            </div>

        </main>

        <footer class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <div id="toast" class="fixed bottom-6 right-6 bg-navy-600 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-lock text-navy-400"></i>
        <span id="toastMsg">Saved!</span>
    </div>

    <script>
        // ── Client search (real DB lookup via client_search_api.php) ──
        let searchTimer = null;
        function searchClients(q) {
            clearTimeout(searchTimer);
            if (q.trim().length < 1) {
                document.getElementById('clientDropdown').classList.add('hidden');
                return;
            }
            searchTimer = setTimeout(() => {
                fetch('client_search_api.php?q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(renderClientDropdown)
                    .catch(() => showToast('Client search failed. Check your connection.'));
            }, 250); // small debounce so we don't hit the DB on every keystroke
        }

        function renderClientDropdown(results) {
            const dropdown = document.getElementById('clientDropdown');
            if (!results.length) {
                dropdown.innerHTML = `<div class="px-4 py-3 text-[12px] text-slate-400">No matching clients found.</div>`;
                dropdown.classList.remove('hidden');
                return;
            }
            dropdown.innerHTML = results.map(c => {
                const initials = c.name.split(' ').filter((_, i, a) => i === 0 || i === a.length - 1).map(w => w[0]).join('').slice(0, 2);
                return `
                <div onclick='selectClient(${JSON.stringify(c)})' class="flex items-center gap-3 px-4 py-3 hover:bg-navy-50 cursor-pointer border-b border-slate-100">
                    <div class="w-8 h-8 rounded-lg bg-navy-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">${initials}</div>
                    <div>
                        <p class="text-[12px] font-semibold text-navy-600">${c.name}</p>
                        <p class="text-[10px] text-slate-400">Client #${c.client_id} · ${c.barangay} · ${c.meta}</p>
                    </div>
                </div>`;
            }).join('');
            dropdown.classList.remove('hidden');
        }

        function selectClient(c) {
            document.getElementById('selectedClientId').value = c.client_id;
            document.getElementById('clientSearch').value = '';
            document.getElementById('clientDropdown').classList.add('hidden');
            document.getElementById('chipName').textContent = c.name;
            document.getElementById('chipMeta').textContent = 'Client #' + c.client_id + ' · ' + c.barangay + ' · ' + c.meta;
            document.getElementById('chipAvatar').textContent = c.name.split(' ').filter((_, i, a) => i === 0 || i === a.length - 1).map(w => w[0]).join('').slice(0, 2);
            document.getElementById('selectedClientChip').classList.remove('hidden');
            document.getElementById('selectedClientChip').classList.add('flex');
        }

        function clearClient() {
            document.getElementById('selectedClientId').value = '';
            document.getElementById('selectedClientChip').classList.add('hidden');
            document.getElementById('selectedClientChip').classList.remove('flex');
        }

        // ── Two-step case type (Victim / Offender) ──
        let currentCategory = null;
        const caseTypesByCategory = {
            victim: [
                { icon: 'fas fa-exclamation-triangle', label: 'VAWC', desc: 'Violence Against Women & Children' },
                { icon: 'fas fa-exclamation-circle', label: 'Child Abuse', desc: 'Physical / Sexual / Emotional' }
            ],
            offender: [
                { icon: 'fas fa-child', label: 'CICL', desc: 'Child in Conflict with the Law' },
                { icon: 'fas fa-shield-alt', label: 'CAR', desc: 'Child at Risk' }
            ]
        };

        function setCaseCategory(el, category) {
            document.querySelectorAll('#caseCategorySelector .case-type-opt').forEach(e => e.classList.remove('active'));
            el.classList.add('active');
            currentCategory = category;
            document.getElementById('selectedCaseType').value = '';

            const types = caseTypesByCategory[category];
            const container = document.getElementById('caseTypeSelector');
            container.innerHTML = types.map(t => `
                <div onclick="setCaseType(this, '${t.label}')" class="case-type-opt border-2 border-slate-200 rounded-xl p-3 text-center">
                    <div class="text-xl mb-1.5"><i class="${t.icon} text-navy-600"></i></div>
                    <p class="ct-label text-[11px] font-semibold text-slate-600">${t.label}</p>
                    <p class="text-[9px] text-slate-400 mt-0.5 leading-tight">${t.desc}</p>
                </div>
            `).join('');
            document.getElementById('caseTypeGroup').classList.remove('hidden');
        }

        function setCaseType(el, typeLabel) {
            document.querySelectorAll('#caseTypeSelector .case-type-opt').forEach(e => {
                e.classList.remove('active');
                e.querySelector('.ct-label').className = 'ct-label text-[11px] font-semibold text-slate-600';
            });
            el.classList.add('active');
            el.querySelector('.ct-label').className = 'ct-label text-[11px] font-bold text-navy-700';
            document.getElementById('selectedCaseType').value = typeLabel;
        }

        function setCaseStatus(el, status) {
            document.querySelectorAll('#caseStatusSelector .status-opt').forEach(e => {
                e.classList.remove('active');
                e.querySelector('.so-lbl').className = 'so-lbl text-[11px] font-semibold text-slate-600';
            });
            el.classList.add('active');
            document.getElementById('selectedCaseStatus').value = status;
        }

        function updateActionsCount() {
            const n = document.querySelectorAll('.action-cb:checked').length;
            document.getElementById('actionsCount').textContent = n + ' action' + (n !== 1 ? 's' : '');
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
            zone.querySelector('.upload-content').innerHTML = `<i class="fas fa-check-circle text-navy-600 text-2xl mb-1"></i><p class="text-[11px] font-semibold text-navy-700 truncate px-2">${count > 1 ? count + ' files attached' : input.files[0].name}</p><p class="text-[10px] text-navy-500 mt-0.5">Ready to upload</p>`;
        }

        function showToast(msg) {
            document.getElementById('toastMsg').textContent = msg;
            const t = document.getElementById('toast');
            t.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
            t.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => { t.classList.add('opacity-0', 'translate-y-4'); t.classList.remove('opacity-100', 'translate-y-0'); }, 2800);
        }

        // Close the client dropdown when clicking outside it
        document.addEventListener('click', function(e) {
            const wrap = document.getElementById('clientSearch').closest('.section-body');
            if (wrap && !wrap.contains(e.target)) {
                document.getElementById('clientDropdown').classList.add('hidden');
            }
        });

        // Basic guard so the form can't submit without a client or case type selected
        document.getElementById('intakeForm').addEventListener('submit', function(e) {
            if (!document.getElementById('selectedClientId').value) {
                e.preventDefault();
                showToast('Please select a client first.');
                return;
            }
            if (!document.getElementById('selectedCaseType').value) {
                e.preventDefault();
                showToast('Please select a case category and type.');
            }
        });

        <?php if ($flashMsg): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showToast(<?= json_encode($flashMsg) ?>);
        });
        <?php endif; ?>
    </script>
</body>

</html>