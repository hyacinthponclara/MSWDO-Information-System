<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>MSWDO Staff Dashboard – San Enrique</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['DM Sans', 'sans-serif'],
                        serif: ['DM Serif Display', 'serif']
                    },
                    colors: {
                        forest: {
                            DEFAULT: '#0F3D2E',
                            50: '#EAF4EE',
                            100: '#CFE8DA',
                            200: '#A3D2B6',
                            300: '#78BC93',
                            400: '#3F9A68',
                            500: '#1F7A4D',
                            600: '#0F3D2E',
                            700: '#0C3125',
                            800: '#082019',
                            900: '#04120E'
                        },
                        lime: {
                            DEFAULT: '#8A9A3A',
                            50: '#F6F8EC',
                            100: '#E9EECB',
                            200: '#D3DE9C',
                            300: '#BCC96D',
                            400: '#A5B44A',
                            500: '#8A9A3A',
                            600: '#6D7A2D'
                        },
                        slate2: '#F2F8F4'
                    },
                    keyframes: {
                        fadeUp: {
                            '0%': {
                                opacity: '0',
                                transform: 'translateY(12px)'
                            },
                            '100%': {
                                opacity: '1',
                                transform: 'translateY(0)'
                            }
                        },
                        modalIn: {
                            '0%': {
                                opacity: '0',
                                transform: 'scale(.96) translateY(10px)'
                            },
                            '100%': {
                                opacity: '1',
                                transform: 'scale(1) translateY(0)'
                            }
                        }
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.4s ease both',
                        'fade-up-1': 'fadeUp 0.4s ease 0.05s both',
                        'fade-up-2': 'fadeUp 0.4s ease 0.1s both',
                        'fade-up-3': 'fadeUp 0.4s ease 0.15s both',
                        'fade-up-4': 'fadeUp 0.4s ease 0.2s both',
                        'modal-in': 'modalIn 0.3s ease both'
                    }
                }
            }
        };
    </script>

    <style>
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: #F2F8F4;
        }

        .sidebar-item {
            transition: all .15s ease;
        }

        .sidebar-item:hover {
            background: rgba(255, 255, 255, .07);
        }

        .sidebar-item.active {
            background: rgba(255, 255, 255, .10);
            border-left: 3px solid #A5B44A;
        }

        .stat-card {
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(15, 61, 46, .12);
        }

        .prog-bar-fill {
            transition: width 1s cubic-bezier(.4, 0, .2, 1);
        }

        .btn-action,
        .report-button {
            transition: all .15s ease;
        }

        .btn-action:hover,
        .report-button:hover {
            transform: translateY(-1px);
        }

        .util-row {
            transition: background .12s;
            border-radius: .75rem;
        }

        .util-row:hover {
            background: #F6FAF7;
        }

        .modal-backdrop {
            background: rgba(0, 0, 0, .48);
            backdrop-filter: blur(4px);
        }

        .field {
            width: 100%;
            border: 1px solid #D7E4DC;
            border-radius: .75rem;
            padding: .65rem .8rem;
            font-size: 12px;
            color: #334155;
            background: #FAFCFB;
            outline: none;
            transition: all .2s ease;
        }

        .field:focus {
            border-color: #3F9A68;
            background: white;
            box-shadow: 0 0 0 3px rgba(63, 154, 104, .12);
        }

        .field-label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64806F;
            margin-bottom: 5px;
        }

        .scroll-thin::-webkit-scrollbar {
            width: 6px;
        }

        .scroll-thin::-webkit-scrollbar-track {
            background: transparent;
        }

        .scroll-thin::-webkit-scrollbar-thumb {
            background: #CFE8DA;
            border-radius: 999px;
        }

        @media (max-width: 767px) {
            .mobile-sidebar {
                display: none;
            }
        }
    </style>
</head>


<body class="bg-slate2 min-h-screen flex flex-col md:flex-row">
  <!-- Sidebar (desktop) -->
  <?php require 'sidebar.php'; ?>
  <!-- Mobile header -->
  <div class="md:hidden bg-forest-600 text-white p-3 flex items-center justify-between">
    <span class="font-serif text-xl">MSWDO</span>
    <button class="text-white"><i class="fas fa-bars text-xl"></i></button>
  </div>

    <!-- Main -->
    <div class="md:ml-64 flex-1 flex flex-col min-h-screen w-full">

        <!-- Top Bar -->
        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-4 md:px-6 sticky top-0 z-20">

            <div class="flex items-center gap-2 md:gap-3">
                <h1 class="text-[15px] font-semibold text-forest-600">
                    Staff Dashboard
                </h1>

                <span class="bg-green-100 text-green-700 text-[11px] font-semibold px-3 py-0.5 rounded-full">
                    Staff
                </span>

                <span class="text-slate-400 text-xs hidden sm:inline-block" id="currentDate"></span>
            </div>

        </header>

        <!-- Content -->
        <main class="flex-1 p-3 md:p-6 space-y-4 md:space-y-5 overflow-y-auto">

            <!-- Statistics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div
                    class="stat-card animate-fade-up-1 bg-white rounded-2xl border border-slate-200 p-4 relative overflow-hidden">

                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-forest-400 rounded-t-2xl"></div>

                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-1">
                        Clients Served Today
                    </p>

                    <p class="text-2xl md:text-3xl font-semibold text-forest-600 leading-none">
                        1,284
                    </p>

                    <div class="absolute right-3 top-3 text-2xl opacity-20 text-forest-500">
                        <i class="fas fa-users"></i>
                    </div>

                </div>

                <div
                    class="stat-card animate-fade-up-2 bg-white rounded-2xl border border-slate-200 p-4 relative overflow-hidden">

                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-emerald-500 rounded-t-2xl"></div>

                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-1">
                        Availments This Week
                    </p>

                    <p class="text-2xl md:text-3xl font-semibold text-forest-600 leading-none">
                        347
                    </p>

                    <div class="absolute right-3 top-3 text-2xl opacity-20 text-forest-500">
                        <i class="fas fa-clipboard-list"></i>
                    </div>

                </div>

            </div>

            <!-- Quick Actions -->
            <div class="animate-fade-up grid grid-cols-1 sm:grid-cols-2 gap-4">

                <a href="clientregistrationform.php" class="block w-full">
                    <button type="button"
                        class="btn-action bg-white border border-slate-200 hover:border-forest-400 rounded-xl px-3 py-3 flex items-center gap-2 text-left group w-full hover:shadow-md">

                        <div
                            class="w-8 h-8 rounded-lg bg-forest-50 flex items-center justify-center text-forest-600 group-hover:bg-forest-100 flex-shrink-0">
                            <i class="fas fa-user-plus text-sm"></i>
                        </div>

                        <span class="text-[11px] md:text-[12px] font-medium text-slate-700">
                            Register Client
                        </span>

                    </button>
                </a>

                <a href="aics.php" class="block w-full">
                    <button type="button"
                        class="btn-action bg-white border border-slate-200 hover:border-forest-400 rounded-xl px-3 py-3 flex items-center gap-2 text-left group w-full hover:shadow-md">

                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 flex-shrink-0">
                            <i class="fas fa-clipboard-list text-sm"></i>
                        </div>

                        <span class="text-[11px] md:text-[12px] font-medium text-slate-700">
                            New AICS Availment
                        </span>

                    </button>
                </a>

            </div>

            <!-- Budget Summary -->
            <section class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 p-4 md:p-6">

                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">

                    <div>
                        <h2 class="text-[14px] font-semibold text-forest-600">
                            Budget Summary
                        </h2>

                        <p class="text-[11px] text-slate-400">
                            FY 2026 · All 9 Programs
                        </p>
                    </div>

                </div>

                <div class="space-y-2" id="budgetBars"></div>

                <div
                    class="flex flex-wrap items-center gap-4 mt-4 pt-3 border-t border-slate-100 text-[11px] text-slate-500">

                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        OK
                    </span>

                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        Low
                    </span>

                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        Critical
                    </span>

                </div>

            </section>

        </main>

        <!-- Footer -->
        <footer
            class="border-t border-slate-200 bg-white px-4 py-3 flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-400 gap-1">

            <span>
                MSWDO San Enrique Information System
            </span>

        </footer>

    </div>


    <!-- Toast -->
    <div id="toast"
        class="fixed bottom-5 right-5 bg-forest-700 text-white px-4 py-3 rounded-xl shadow-xl flex items-center gap-2 text-[12px] opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-[60]">

        <i class="fas fa-check-circle text-emerald-300"></i>

        <span id="toastMessage">
            Report submitted.
        </span>

    </div>

    <script>
        // Current date
        const dateSpan = document.getElementById('currentDate');

        if (dateSpan) {
            const today = new Date();

            dateSpan.textContent = today.toLocaleDateString('en-PH', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        // Budget data
        const programs = [
            {
                name: 'AICS FBML',
                cycle: 'Quarterly',
                icon: 'fa-hand-holding-heart',
                pct: 88,
                spent: 211600,
                remaining: 28400,
                beneficiaries: 128
            },
            {
                name: 'AICS Educational',
                cycle: 'Quarterly',
                icon: 'fa-graduation-cap',
                pct: 65,
                spent: 130000,
                remaining: 70000,
                beneficiaries: 96
            },
            {
                name: '4Ps',
                cycle: 'Annually',
                icon: 'fa-home',
                pct: 42,
                spent: 252000,
                remaining: 348000,
                beneficiaries: 47
            },
            {
                name: 'SLP',
                cycle: 'Annually',
                icon: 'fa-seedling',
                pct: 92,
                spent: 138000,
                remaining: 12000,
                beneficiaries: 22
            },
            {
                name: 'SFP',
                cycle: 'Quarterly',
                icon: 'fa-utensils',
                pct: 35,
                spent: 70000,
                remaining: 130000,
                beneficiaries: 65
            },
            {
                name: 'Day Care',
                cycle: 'Annually',
                icon: 'fa-child',
                pct: 55,
                spent: 110000,
                remaining: 90000,
                beneficiaries: 40
            },
            {
                name: 'Senior Citizen',
                cycle: 'Annually',
                icon: 'fa-user-friends',
                pct: 71,
                spent: 213000,
                remaining: 87000,
                beneficiaries: 74
            },
            {
                name: 'PWD',
                cycle: 'Half-year',
                icon: 'fa-wheelchair',
                pct: 28,
                spent: 56000,
                remaining: 144000,
                beneficiaries: 58
            },
            {
                name: 'Solo Parents',
                cycle: 'Quarterly',
                icon: 'fa-user-shield',
                pct: 68,
                spent: 102000,
                remaining: 48000,
                beneficiaries: 34
            },
            {
                name: 'Women and Children',
                cycle: 'Annually',
                icon: 'fa-people-roof',
                pct: 47,
                spent: 94000,
                remaining: 106000,
                beneficiaries: 51
            }
        ];

        const peso = n => '₱' + n.toLocaleString('en-PH');

        const barColor = p => {
            if (p >= 80) return 'bg-red-500';
            if (p >= 60) return 'bg-amber-400';
            return 'bg-emerald-500';
        };

        const pctColor = p => {
            if (p >= 80) return 'text-red-500';
            if (p >= 60) return 'text-amber-500';
            return 'text-emerald-600';
        };

        const cycleStyle = c => {
            if (c === 'Quarterly') {
                return 'text-blue-600 bg-blue-50';
            }

            if (c === 'Half-year') {
                return 'text-teal-600 bg-teal-50';
            }

            return 'text-purple-600 bg-purple-50';
        };

        // Render budget summary
        const barsContainer = document.getElementById('budgetBars');

        if (barsContainer) {
            programs.forEach(p => {

                barsContainer.innerHTML += `
                    <div class="util-row px-3 py-2.5">

                        <div class="flex items-center justify-between mb-1.5 flex-wrap gap-x-3 gap-y-1">

                            <span class="text-[12px] font-medium text-slate-700 flex items-center gap-1.5 min-w-0">

                                <i class="fas ${p.icon} text-forest-400 text-[11px]"></i>

                                <span>${p.name}</span>

                                <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full ${cycleStyle(p.cycle)}">
                                    ${p.cycle}
                                </span>

                            </span>

                            <span class="flex items-center gap-2 text-[11px]">

                                <span class="text-forest-600 font-semibold">
                                    <i class="fas fa-user-group text-[10px] mr-1 opacity-60"></i>
                                    ${p.beneficiaries}
                                </span>

                                <span class="${pctColor(p.pct)} font-bold w-8 text-right">
                                    ${p.pct}%
                                </span>

                            </span>

                        </div>

                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">

                            <div
                                class="prog-bar-fill h-2 rounded-full ${barColor(p.pct)}"
                                style="width:0%"
                                data-target="${p.pct}%">
                            </div>

                        </div>

                        <p class="text-[10px] text-slate-400 mt-1">
                            ${peso(p.remaining)} left of ${peso(p.spent + p.remaining)}
                        </p>

                    </div>
                `;
            });

            requestAnimationFrame(() => {
                setTimeout(() => {
                    document.querySelectorAll('.prog-bar-fill').forEach(el => {
                        el.style.width = el.dataset.target;
                    });
                }, 200);
            });
        }

        // Report modal
        function openReportModal() {
            const modal = document.getElementById('reportModal');

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            document.getElementById('problemType')?.focus();
        }

        function closeReportModal() {
            const modal = document.getElementById('reportModal');

            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Submit report
        function submitReport(event) {
            event.preventDefault();

            const type = document.getElementById('problemType').value;
            const subject = document.getElementById('problemSubject').value.trim();
            const description = document.getElementById('problemDescription').value.trim();

            if (!type) {
                showToast('Please select a problem type.', 'error');
                return false;
            }

            if (!subject) {
                showToast('Please enter a subject.', 'error');
                return false;
            }

            if (!description) {
                showToast('Please describe the problem.', 'error');
                return false;
            }

            closeReportModal();

            document.getElementById('reportForm').reset();

            showToast('Problem report submitted to Admin.');

            return false;
        }

        // Toast
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const messageElement = document.getElementById('toastMessage');
            const icon = toast.querySelector('i');

            messageElement.textContent = message;

            if (type === 'error') {
                icon.className = 'fas fa-exclamation-circle text-red-300';
            } else {
                icon.className = 'fas fa-check-circle text-emerald-300';
            }

            toast.classList.remove(
                'opacity-0',
                'translate-y-4',
                'pointer-events-none'
            );

            toast.classList.add(
                'opacity-100',
                'translate-y-0'
            );

            setTimeout(() => {
                toast.classList.add(
                    'opacity-0',
                    'translate-y-4',
                    'pointer-events-none'
                );

                toast.classList.remove(
                    'opacity-100',
                    'translate-y-0'
                );
            }, 3000);
        }

        // Close modal when clicking outside
        document.getElementById('reportModal').addEventListener('click', function (event) {
            if (event.target === this) {
                closeReportModal();
            }
        });

        // Close modal with Escape
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeReportModal();
            }
        });
    </script>

</body>

</html>