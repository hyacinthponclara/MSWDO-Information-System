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
    <title>PWD – MSWDO San Enrique</title>
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
                <span class="text-navy-600 font-semibold">PWD Availment</span>
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
                    <h1 class="text-xl font-serif text-navy-600">PWD Availment Form</h1>
                    <p class="text-[13px] text-slate-500 mt-1">PWD ID issuance and financial assistance for persons with
                        disability.</p>
                </div>

                <div class="animate-fade-up-1 bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-4">
                    <div
                        class="w-11 h-11 rounded-xl bg-navy-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        RC</div>
                    <div class="flex-1">
                        <p class="text-[14px] font-semibold text-navy-600">Rodrigo P. Cruz</p>
                        <p class="text-[11px] text-slate-400">CLT-2024-00203 · San Jose · 45 yrs, Male</p>
                    </div>
                    <span class="bg-navy-50 text-navy-600 text-[11px] font-bold px-3 py-1 rounded-full"><i
                            class="fas fa-wheelchair mr-1"></i> PWD</span>
                </div>

                <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-list-ul"></i></div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600">Service Type &amp; Disability
                                Information</h2>
                        </div>
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-2 gap-3" id="pwdSvcSelector">
                            <div onclick="setPWDSvc(this,'id')"
                                class="svc-tab active border-2 border-navy-600 bg-navy-50 rounded-2xl p-4 flex items-center gap-3">
                                <div
                                    class="st-icon w-10 h-10 rounded-xl bg-navy-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-id-card text-navy-600 text-lg"></i></div>
                                <div>
                                    <p class="st-title text-[13px] font-bold text-navy-700">PWD ID Issuance</p>
                                    <p class="text-[10px] text-slate-400">New, renewal, or replacement</p>
                                </div>
                            </div>
                            <div onclick="setPWDSvc(this,'financial')"
                                class="svc-tab border-2 border-slate-200 rounded-2xl p-4 flex items-center gap-3">
                                <div
                                    class="st-icon w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-coins text-navy-500 text-lg"></i></div>
                                <div>
                                    <p class="st-title text-[13px] font-semibold text-slate-600">Financial Assistance
                                    </p>
                                    <p class="text-[10px] text-slate-400">Cash assistance for PWD</p>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="field-label req">Disability Type</label><select class="field">
                                    <option value="">Select</option>
                                    <optgroup label="Sensory">
                                        <option>Deaf / Hard of Hearing</option>
                                        <option>Visual Disability</option>
                                        <option>Speech Impairment</option>
                                    </optgroup>
                                    <optgroup label="Physical">
                                        <option>Physical Disability</option>
                                        <option>Cerebral Palsy</option>
                                        <option>Acquired Injury</option>
                                    </optgroup>
                                    <optgroup label="Developmental">
                                        <option>Intellectual Disability</option>
                                        <option>Autism Spectrum</option>
                                        <option>Down Syndrome</option>
                                    </optgroup>
                                    <optgroup label="Medical">
                                        <option>Chronic Illness</option>
                                        <option>Cancer</option>
                                    </optgroup>
                                    <option>Others</option>
                                </select></div>
                            <div><label class="field-label">Disability Cause</label><input type="text" class="field"
                                    placeholder="Describe cause or nature"></div>
                        </div>
                    </div>
                </div>

                <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                        <div
                            class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                            <i class="fas fa-paperclip"></i></div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-navy-600" id="pwdDocTitle">Required Documents —
                                PWD ID Issuance</h2>
                        </div>
                    </div>
                    <div id="pwd-docs-id" class="p-6 space-y-4">
                        <div class="bg-navy-50 border border-navy-100 rounded-xl px-4 py-3 flex items-start gap-3">
                            <i class="fas fa-info-circle text-navy-500 text-lg mt-0.5"></i>
                            <p class="text-[12px] text-navy-700">Photos must be recent (within 6 months). The PWD
                                application form requires <strong>physician's signature and clinic stamp</strong>.</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="field-label flex items-center text-[10px]">Whole Body Picture <span
                                        class="copy-badge">1 pc</span></div><label class="upload-zone"
                                    id="uz-pwd-whole"><input type="file" onchange="fileSelected(this,'uz-pwd-whole')">
                                    <div class="upload-content"><i class="fas fa-user text-2xl mb-1"></i>
                                        <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                    </div>
                                </label>
                            </div>
                            <div>
                                <div class="field-label flex items-center text-[10px]">1×1 Picture <span
                                        class="copy-badge">2 pcs</span></div><label class="upload-zone"
                                    id="uz-pwd-1x1"><input type="file" onchange="fileSelected(this,'uz-pwd-1x1')">
                                    <div class="upload-content"><i class="fas fa-camera text-2xl mb-1"></i>
                                        <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                    </div>
                                </label>
                            </div>
                            <div>
                                <div class="field-label flex items-center text-[10px]">2×2 Picture <span
                                        class="copy-badge">2 pcs</span></div><label class="upload-zone"
                                    id="uz-pwd-2x2"><input type="file" onchange="fileSelected(this,'uz-pwd-2x2')">
                                    <div class="upload-content"><i class="fas fa-image text-2xl mb-1"></i>
                                        <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                    </div>
                                </label>
                            </div>
                            <div>
                                <div class="field-label flex items-center text-[10px]">Signed PWD Application Form <span
                                        class="copy-badge">With physician sig.</span></div><label class="upload-zone"
                                    id="uz-pwd-form"><input type="file" onchange="fileSelected(this,'uz-pwd-form')">
                                    <div class="upload-content"><i class="fas fa-file-signature text-2xl mb-1"></i>
                                        <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                    </div>
                                </label>
                            </div>
                            <div>
                                <div class="field-label flex items-center text-[10px]">Medical Certificate <span
                                        class="copy-badge">1 orig + 2 copies</span></div><label class="upload-zone"
                                    id="uz-pwd-medcert"><input type="file"
                                        onchange="fileSelected(this,'uz-pwd-medcert')">
                                    <div class="upload-content"><i class="fas fa-notes-medical text-2xl mb-1"></i>
                                        <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                    </div>
                                </label>
                            </div>
                            <div>
                                <div class="field-label flex items-center text-[10px]">Expired PWD ID <span
                                        class="opt-badge">If renewal</span></div><label class="upload-zone"
                                    id="uz-pwd-old"><input type="file" onchange="fileSelected(this,'uz-pwd-old')">
                                    <div class="upload-content"><i class="fas fa-history text-2xl mb-1"></i>
                                        <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                    </div>
                                </label>
                            </div>
                            <div class="col-span-2">
                                <div class="field-label flex items-center text-[10px]">Affidavit of Loss <span
                                        class="opt-badge">If lost</span></div><label class="upload-zone"
                                    id="uz-pwd-affidavit"><input type="file"
                                        onchange="fileSelected(this,'uz-pwd-affidavit')">
                                    <div class="upload-content"><i class="fas fa-file-alt text-2xl mb-1"></i>
                                        <p class="text-[12px] font-medium text-slate-600">Upload Affidavit of Loss</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div id="pwd-docs-financial" class="hidden p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="field-label req">Amount (₱)</label>
                                <div class="relative"><span
                                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[13px]">₱</span><input
                                        type="number" min="0" class="field pl-7" placeholder="0.00"></div>
                            </div>
                            <div><label class="field-label req">Date</label><input type="date" class="field"></div>
                        </div>
                        <div><label class="field-label">Purpose</label><textarea class="field" rows="2"
                                placeholder="Describe the purpose..."></textarea></div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="field-label flex items-center text-[10px]">Valid ID / PWD ID <span
                                        class="copy-badge">1 orig + 2 copies</span></div><label class="upload-zone"
                                    id="uz-pwdf-id"><input type="file" onchange="fileSelected(this,'uz-pwdf-id')">
                                    <div class="upload-content"><i class="fas fa-id-card text-2xl mb-1"></i>
                                        <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                    </div>
                                </label>
                            </div>
                            <div>
                                <div class="field-label flex items-center text-[10px]">Medical Certificate <span
                                        class="copy-badge">1 orig + 2 copies</span></div><label class="upload-zone"
                                    id="uz-pwdf-med"><input type="file" onchange="fileSelected(this,'uz-pwdf-med')">
                                    <div class="upload-content"><i class="fas fa-notes-medical text-2xl mb-1"></i>
                                        <p class="text-[12px] font-medium text-slate-600">Click to upload</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button onclick="saveComplete()"
                        class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500">Submit
                        PWD Record</button>
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
        function setPWDSvc(el, svc) {
            document.querySelectorAll('#pwdSvcSelector .svc-tab').forEach(t => {
                t.classList.remove('active', 'border-navy-600', 'bg-navy-50');
                t.querySelector('.st-title').className = 'st-title text-[13px] font-semibold text-slate-600';
            });
            el.classList.add('active', 'border-navy-600', 'bg-navy-50');
            el.querySelector('.st-title').className = 'st-title text-[13px] font-bold text-navy-700';
            document.getElementById('pwd-docs-id').classList.toggle('hidden', svc !== 'id');
            document.getElementById('pwd-docs-financial').classList.toggle('hidden', svc !== 'financial');
            document.getElementById('pwdDocTitle').textContent = svc === 'id' ? 'Required Documents — PWD ID Issuance' : 'Required Documents — Financial Assistance';
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
        function saveComplete() { showToast('PWD record submitted ✓'); }
    </script>
</body>

</html>