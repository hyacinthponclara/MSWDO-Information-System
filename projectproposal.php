<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Project Proposal Submission – MSWDO San Enrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['DM Sans', 'sans-serif'], serif: ['DM Serif Display', 'serif'] },
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
                        gold: { DEFAULT: '#C49A2A', 400: '#C49A2A' },
                        sage: '#F0F6F2',
                    },
                    keyframes: {
                        fadeUp: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.35s ease both',
                        'fade-up-1': 'fadeUp 0.35s 0.05s ease both',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'DM Sans', sans-serif;
            background: #F0F6F2;
        }

        .sidebar-item {
            transition: all .15s;
        }

        .sidebar-item:hover {
            background: rgba(26, 92, 58, .08);
            color: #1A5C3A;
        }

        .sidebar-item.active {
            background: rgba(26, 92, 58, .12);
            border-left-color: #C49A2A;
            color: #1A5C3A;
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

        textarea.field {
            resize: vertical;
            line-height: 1.7;
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

        .section-card {
            background: #fff;
            border-radius: 1rem;
            border: 1px solid #D4E8DC;
            overflow: hidden;
            margin-bottom: 1.25rem;
        }

        .section-head {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .875rem 1.5rem;
            border-bottom: 1px solid #D4E8DC;
            background: #EEF6F0;
        }

        .section-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #1A5C3A;
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

        .upload-zone {
            border: 2px dashed #D4E8DC;
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            transition: all .2s;
            cursor: pointer;
        }

        .upload-zone:hover {
            border-color: #1A5C3A;
            background: #EEF6F0;
        }

        .upload-zone.has-file {
            border-color: #1A5C3A;
            background: #EEF6F0;
        }

        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(26, 92, 58, .2);
            border-radius: 2px;
        }
    </style>
</head>

<body class="min-h-screen flex">

    <?php require 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="ml-64 flex-1 flex flex-col min-h-screen">

        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <span class="text-green-600 font-semibold">Project Proposal</span>
            </div>
        </header>

        <main class="p-6 overflow-y-auto">
            <div class="max-w-3xl mx-auto space-y-5">

                <div class="animate-fade-up">
                    <h1 class="text-xl font-serif text-green-600">Submit Project Proposal</h1>
                </div>

                <!-- ── Project Information ── -->
                <div class="section-card animate-fade-up-1">
                    <div class="section-head">
                        <div class="section-num">I</div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-green-600">Project Information</h2>
                        </div>
                    </div>
                    <div class="section-body space-y-4">

                        <div>
                            <label class="field-label req">A. Title / Name of Project</label>
                            <input type="text" id="projTitle" class="field">
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="field-label req">B. Duration</label>
                                <input type="text" id="durationText" class="field"
                                    value="Select dates to auto-calculate" readonly>
                            </div>
                            <div>
                                <label class="field-label">From</label>
                                <input type="date" id="durFrom" class="field">
                            </div>
                            <div>
                                <label class="field-label">To</label>
                                <input type="date" id="durTo" class="field">
                            </div>
                        </div>

                        <div>
                            <label class="field-label">C. Venue</label>
                            <input type="text" id="venue" class="field">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="field-label req">D. No. of Participants</label>
                                <input type="number" min="0" id="numParticipants" class="field">
                            </div>
                            <div>
                                <label class="field-label req">Description of Participants</label>
                                <input type="text" id="participantDesc" class="field">
                            </div>
                        </div>

                        <div>
                            <label class="field-label req">E. Budgetary Requirement (₱)</label>
                            <div class="relative">
                                <span
                                    class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-medium">₱</span>
                                <input type="number" min="0" step="0.01" id="budgetReq" class="field pl-8">
                            </div>
                        </div>

                        <div>
                            <label class="field-label req">F. Source of Fund</label>
                            <input type="text" id="fundSource" class="field">
                        </div>

                    </div>
                </div>

                <!-- ── Project Proposal File Upload ── -->
                <div class="section-card">
                    <div class="section-head">
                        <div class="section-num"><i class="fas fa-file-upload"></i></div>
                        <div>
                            <h2 class="text-[14px] font-semibold text-green-600">Attach Project Proposal Document</h2>
                        </div>
                    </div>
                    <div class="section-body">
                        <div id="uploadZone" class="upload-zone" onclick="document.getElementById('fileInput').click()">
                            <div id="uploadContent">
                                <i class="fas fa-cloud-upload-alt text-3xl text-green-400 mb-2 block"></i>
                                <p class="text-[13px] text-slate-600">Click to upload or drag and drop</p>
                                <p class="text-[11px] text-slate-400 mt-1">Accepted: .pdf, .doc, .docx (max 10MB)</p>
                            </div>
                            <input type="file" id="fileInput" class="hidden" accept=".pdf,.doc,.docx"
                                onchange="fileSelected(this)">
                        </div>
                        <p class="text-[11px] text-slate-400 mt-3"><i class="fas fa-info-circle mr-1"></i> Upload the
                            completed Project Proposal document (PDF/Word).</p>
                    </div>
                </div>

                <!-- Submit-->
                <div class="flex justify-end gap-3">
                    <button onclick="submitProposal()"
                        class="text-[13px] font-semibold text-white bg-green-600 rounded-xl px-6 py-2.5 hover:bg-green-500 transition-all">
                        Submit Proposal
                    </button>
                </div>

            </div>
        </main>

        <footer class="border-t border-slate-200 bg-white px-6 py-3 text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <!-- Toast -->
    <div id="toast"
        class="fixed bottom-6 right-6 bg-green-700 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-green-300"></i>
        <span id="toastMsg">Saved!</span>
    </div>

    <script>
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

        function fileSelected(input) {
            if (!input.files || !input.files[0]) return;
            const zone = document.getElementById('uploadZone');
            const content = document.getElementById('uploadContent');
            const name = input.files[0].name;
            zone.classList.add('has-file');
            content.innerHTML =
                `<i class="fas fa-check-circle text-green-600 text-2xl mb-1"></i><p class="text-[12px] font-semibold text-green-700">${name}</p><p class="text-[10px] text-green-500">File ready</p>`;
        }

        function calculateDuration() {
            const fromDate = document.getElementById('durFrom').value;
            const toDate = document.getElementById('durTo').value;
            const durationInput = document.getElementById('durationText');

            if (fromDate && toDate) {
                const from = new Date(fromDate);
                const to = new Date(toDate);
                const diffTime = to - from;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                if (diffDays < 0) {
                    durationInput.value = 'Invalid: To date must be after From';
                    showToast('"To" date cannot be before "From" date.', 'error');
                } else if (diffDays === 0) {
                    durationInput.value = '1 day (auto-calculated)';
                } else {
                    durationInput.value = diffDays + ' calendar days';
                }
            } else {
                durationInput.value = 'Select dates to auto-calculate';
            }
        }

        function setMinToDate() {
            const fromDate = document.getElementById('durFrom').value;
            const toInput = document.getElementById('durTo');
            if (fromDate) {
                toInput.setAttribute('min', fromDate);
                if (toInput.value && new Date(toInput.value) < new Date(fromDate)) {
                    toInput.value = '';
                }
            } else {
                toInput.removeAttribute('min');
            }
            calculateDuration();
        }

        document.getElementById('durFrom').addEventListener('change', setMinToDate);
        document.getElementById('durTo').addEventListener('change', calculateDuration);

        // Run on page load in case of pre‑filled dates
        window.addEventListener('DOMContentLoaded', function () {
            setMinToDate();
        });

        function submitProposal() {
            const title = document.getElementById('projTitle').value.trim();
            const duration = document.getElementById('durationText').value.trim();
            const participants = document.getElementById('numParticipants').value;
            const participantDesc = document.getElementById('participantDesc').value.trim();  
            const budget = document.getElementById('budgetReq').value;
            const fundSource = document.getElementById('fundSource').value.trim();
            const venue = document.getElementById('venue').value.trim();           
            const file = document.getElementById('fileInput').files[0];

            // Validation checks
            if (!title) { showToast('Please enter a Project Title.', 'error'); return; }
            if (!duration || duration === 'Select dates to auto-calculate' || duration.includes('Invalid')) {
                showToast('Please select valid From and To dates.', 'error');
                return;
            }
            if (!venue) { showToast('Please enter the Venue.', 'error'); return; }         
            if (!participants || parseInt(participants) <= 0) {
                showToast('Enter a valid number of participants.', 'error');
                return;
            }
            if (!participantDesc) { showToast('Please enter a description of participants.', 'error'); return; }  
            if (!budget || parseFloat(budget) <= 0) {
                showToast('Enter a valid budget amount.', 'error');
                return;
            }
            if (!fundSource) { showToast('Please enter the Source of Fund.', 'error'); return; }
            if (!file) { showToast('Please upload the proposal document.', 'error'); return; }

            showToast('Proposal submitted');
        }
    </script>

</body>

</html>