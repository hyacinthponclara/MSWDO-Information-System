<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Case Study | MSWDO San Enrique</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- jsPDF AutoTable -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['DM Sans', 'sans-serif'],
                        serif: ['DM Serif Display', 'serif']
                    },
                    colors: {
                        mswdo: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d'
                        },
                        slate2: '#F4F7FC'
                    }
                }
            }
        }
    </script>

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #F4F7FC;
            color: #1e293b;
            margin: 0;
        }



        .main-wrapper {
            margin-left: 256px;
            min-height: 100vh;
        }

        .mobile-overlay {
            display: none;
        }

        /* =====================================================
           FIELDS
        ===================================================== */

        .field-label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #64748B;
            margin-bottom: 6px;
        }

        .readonly-field {
            display: block;
            width: 100%;
            border-radius: .75rem;
            border: 1px solid #D7E4DA;
            background: #F8FCF9;
            padding: 10px 14px;
            font-size: 13px;
            color: #1f2937;
            min-height: 42px;
            line-height: 1.5;
        }

        .readonly-field.empty {
            color: #94a3b8;
            font-style: italic;
        }

        .readonly-textarea {
            min-height: 92px;
            white-space: pre-wrap;
        }


        .section-card {
            background: #fff;
            border-radius: 1rem;
            border: 1px solid #E2E8F0;
            overflow: hidden;
            margin-bottom: 1.25rem;

            box-shadow:
                0 2px 10px rgba(20, 83, 45, .035);
        }

        .section-head {
            display: flex;
            align-items: center;
            gap: .75rem;

            padding: .875rem 1.5rem;

            border-bottom: 1px solid #E5EFE7;

            background:
                linear-gradient(90deg,
                    #F3FBF5,
                    #FAFDFC);
        }

        .section-num {
            width: 30px;
            height: 30px;

            border-radius: 50%;

            background: #15803d;
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


        .info-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            background: #F0FDF4;
            border: 1px solid #DCFCE7;

            color: #166534;

            border-radius: 999px;

            padding: 5px 10px;

            font-size: 10px;
            font-weight: 600;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            background: #ECFDF5;
            border: 1px solid #BBF7D0;

            color: #166534;

            border-radius: 999px;

            padding: 5px 10px;

            font-size: 10px;
            font-weight: 700;
        }


        .family-table-wrap {
            width: 100%;
            overflow-x: auto;

            border: 1px solid #E2E8F0;
            border-radius: .875rem;
        }

        .family-table {
            width: 100%;
            min-width: 850px;

            border-collapse: collapse;

            font-size: 11px;
        }

        .family-table th {
            text-align: left;

            padding: 10px 12px;

            background: #F3F8F4;

            color: #64748B;

            font-size: 9px;

            text-transform: uppercase;
            letter-spacing: .05em;

            font-weight: 700;

            border-bottom: 1px solid #E2E8F0;
        }

        .family-table td {
            padding: 10px 12px;

            border-bottom: 1px solid #F1F5F9;

            vertical-align: top;
        }

        .family-table tbody tr:last-child td {
            border-bottom: none;
        }

        .family-table tbody tr:hover {
            background: #FAFDFC;
        }

        .client-row {
            background: #F0FDF4;
        }


        .money-card {
            border-radius: .875rem;
            padding: 14px;
            text-align: center;
        }

        .money-card.income {
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
        }

        .money-card.expense {
            background: #FFF7F7;
            border: 1px solid #FECACA;
        }

        .money-card.net {
            background: #F4F7FC;
            border: 1px solid #DDE5E1;
        }


        .assessment-card {
            border: 2px solid #BBF7D0;

            background: #F0FDF4;

            border-radius: 1rem;

            padding: 16px;
        }

        .recommendation-box {
            border-left: 4px solid #16a34a;

            background: #F6FCF7;

            border-radius: 0 .75rem .75rem 0;

            padding: 16px 18px;

            white-space: pre-wrap;

            line-height: 1.7;

            font-size: 13px;
        }


        .fade-up {
            animation: fadeUp .35s ease both;
        }

        @keyframes fadeUp {

            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }

        }


        @media (max-width: 900px) {

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-wrapper {
                margin-left: 0;
            }

            .mobile-overlay.show {
                display: block;

                position: fixed;

                inset: 0;

                background: rgba(15, 23, 42, .45);

                z-index: 40;
            }

            .desktop-sidebar-space {
                display: none;
            }

        }

        @media (max-width: 640px) {

            .section-body {
                padding: 1rem;
            }

            .section-head {
                padding: .8rem 1rem;
            }

            .topbar {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            .page-content {
                padding: 1rem !important;
            }

            .client-banner {
                align-items: flex-start !important;
                flex-direction: column;
            }

            .mobile-grid-1 {
                grid-template-columns: 1fr !important;
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
  <!-- ═══════════ MAIN ═══════════ -->
  <div class="md:ml-64 flex-1 flex flex-col min-h-screen w-full">

        <!-- TOPBAR -->

        <header
            class="topbar bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-30">

            <div class="flex items-center gap-3 text-[13px] min-w-0">

                <button onclick="openSidebar()"
                    class="lg:hidden w-9 h-9 rounded-lg border border-slate-200 text-slate-600 flex items-center justify-center">

                    <i class="fas fa-bars"></i>

                </button>


                <div class="truncate">

                    <span class="text-mswdo-700 font-semibold">
                        Case Study
                    </span>

                </div>

            </div>


        </header>



        <main class="page-content flex-1 p-6">

            <div class="max-w-4xl mx-auto">


                <!-- PAGE TITLE -->

                <div class="fade-up mb-6">

                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">

                        <div>

                            <p class="text-[10px] font-bold uppercase tracking-[.12em] text-mswdo-600 mb-1">
                                Retrieved Record
                            </p>

                            <h1 class="text-xl font-serif text-mswdo-800">
                                Case Study / Social Case Summary
                            </h1>

                            <p class="text-[11px] text-slate-400 mt-1">
                                Submitted information is displayed in read-only format.
                            </p>

                        </div>


                    </div>

                </div>


                <!-- CLIENT BANNER -->

                <div
                    class="client-banner fade-up bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-4 mb-5">

                    <div
                        class="w-11 h-11 rounded-xl bg-mswdo-50 border border-mswdo-100 flex items-center justify-center flex-shrink-0">

                        <i class="fas fa-user text-mswdo-700"></i>

                    </div>


                    <div class="flex-1 min-w-0">

                        <p id="clientName" class="text-[14px] font-bold text-mswdo-800">
                        </p>

                        <p id="clientSubtitle" class="text-[11px] text-slate-400">
                        </p>

                    </div>


                    <div class="text-right">

                        <p class="text-[9px] uppercase tracking-wider text-slate-400">
                            Record Status
                        </p>

                        <p id="recordStatus" class="text-[11px] font-bold text-mswdo-700">
                        </p>

                    </div>

                </div>


                <!-- SECTION 1 -->

                <section class="section-card fade-up">

                    <div class="section-head">

                        <div class="section-num">
                            1
                        </div>

                        <div>

                            <h2 class="text-[14px] font-semibold text-mswdo-800">
                                Interview Details
                            </h2>

                            <p class="text-[11px] text-slate-400">
                                Basic information about this case study interview
                            </p>

                        </div>

                    </div>


                    <div class="section-body">

                        <div class="grid grid-cols-2 gap-4 mobile-grid-1">

                            <div>

                                <label class="field-label">
                                    Interview Date
                                </label>

                                <div id="interviewDate" class="readonly-field">
                                </div>

                            </div>


                            <div>

                                <label class="field-label">
                                    Type of Case Study
                                </label>

                                <div id="caseType" class="readonly-field">
                                </div>

                            </div>

                        </div>


                        <div
                            class="mt-4 bg-mswdo-50 border border-mswdo-100 rounded-xl px-4 py-3 flex items-start gap-3">

                            <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center flex-shrink-0">

                                <i class="fas fa-user-injured text-mswdo-600 text-sm"></i>

                            </div>


                            <div class="flex-1">

                                <p class="text-[12px] font-semibold text-mswdo-800">
                                    Patient / Person Being Assisted
                                </p>

                                <p id="patientSummary" class="text-[11px] text-slate-500 mt-0.5">
                                </p>

                            </div>

                        </div>


                        <div id="patientDetails" class="grid grid-cols-2 gap-4 mobile-grid-1 mt-4">
                        </div>

                    </div>

                </section>


                <!-- SECTION 2 -->

                <section class="section-card fade-up">

                    <div class="section-head">

                        <div class="section-num">
                            2
                        </div>

                        <div>

                            <h2 class="text-[14px] font-semibold text-mswdo-800">
                                Family Composition
                            </h2>

                            <p class="text-[11px] text-slate-400">
                                Household members recorded in the submitted case study
                            </p>

                        </div>

                    </div>


                    <div class="section-body">

                        <div class="family-table-wrap">

                            <table class="family-table">

                                <thead>

                                    <tr>

                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Relationship</th>
                                        <th>Age</th>
                                        <th>Sex</th>
                                        <th>Civil Status</th>
                                        <th>Education</th>
                                        <th>Occupation</th>
                                        <th>Income/mo (₱)</th>

                                    </tr>

                                </thead>

                                <tbody id="familyBody"></tbody>

                            </table>

                        </div>


                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mt-4 gap-2">

                            <span class="text-[11px] text-slate-400">

                                <i class="fas fa-users mr-1"></i>

                                <span id="familyCount"></span>

                            </span>


                            <div class="flex items-center gap-3 text-[12px]">

                                <span class="text-slate-400">
                                    Combined monthly income:
                                </span>

                                <span id="combinedIncome" class="font-bold text-mswdo-700 text-[14px]">
                                </span>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- SECTION 3 -->

                <section class="section-card fade-up">

                    <div class="section-head">

                        <div class="section-num">
                            3
                        </div>

                        <div>

                            <h2 class="text-[14px] font-semibold text-mswdo-800">
                                Income & Financial Resources
                            </h2>

                            <p class="text-[11px] text-slate-400">
                                Monthly financial picture recorded in the submission
                            </p>

                        </div>

                    </div>


                    <div class="section-body">

                        <div class="grid grid-cols-2 gap-6 mobile-grid-1">


                            <!-- INCOME -->

                            <div>

                                <p class="text-[11px] font-bold uppercase tracking-wider text-mswdo-600 mb-3">
                                    Income Sources
                                </p>

                                <div id="incomeLedger" class="space-y-0">
                                </div>


                                <div class="mt-4 space-y-3">

                                    <div>

                                        <label class="field-label">
                                            Insurance / PhilHealth / SSS / GSIS
                                        </label>

                                        <div id="insurance" class="readonly-field">
                                        </div>

                                    </div>


                                    <div>

                                        <label class="field-label">
                                            Savings
                                        </label>

                                        <div id="savings" class="readonly-field">
                                        </div>

                                    </div>


                                    <div>

                                        <label class="field-label">
                                            Emergency Fund Available
                                        </label>

                                        <div id="emergencyFund" class="readonly-field">
                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- EXPENSES -->

                            <div>

                                <p class="text-[11px] font-bold uppercase tracking-wider text-red-500 mb-3">
                                    Monthly Expenses
                                </p>

                                <div id="expenseLedger"></div>

                            </div>

                        </div>


                        <!-- TOTALS -->

                        <div class="mt-5 grid grid-cols-3 gap-3 mobile-grid-1">

                            <div class="money-card income">

                                <p class="text-[9px] uppercase tracking-wide text-mswdo-600 font-bold mb-1">
                                    Total Income
                                </p>

                                <p id="totalIncome" class="text-[18px] font-bold text-mswdo-700">
                                </p>

                            </div>


                            <div class="money-card expense">

                                <p class="text-[9px] uppercase tracking-wide text-red-500 font-bold mb-1">
                                    Total Expenses
                                </p>

                                <p id="totalExpenses" class="text-[18px] font-bold text-red-600">
                                </p>

                            </div>


                            <div class="money-card net">

                                <p class="text-[9px] uppercase tracking-wide text-slate-500 font-bold mb-1">
                                    Net Monthly
                                </p>

                                <p id="netMonthly" class="text-[18px] font-bold text-mswdo-700">
                                </p>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- SECTION 4 -->

                <section class="section-card fade-up">

                    <div class="section-head">

                        <div class="section-num">
                            4
                        </div>

                        <div>

                            <h2 class="text-[14px] font-semibold text-mswdo-800">
                                Problem Presented & Home Condition
                            </h2>

                            <p class="text-[11px] text-slate-400">
                                Narrative details stated by the client and observed by the social worker
                            </p>

                        </div>

                    </div>


                    <div class="section-body space-y-4">

                        <div>

                            <label class="field-label">
                                Problem Presented
                            </label>

                            <div id="problemPresented" class="readonly-field readonly-textarea">
                            </div>

                        </div>


                        <div>

                            <label class="field-label">
                                Home & Economic Condition
                            </label>

                            <div id="homeCondition" class="readonly-field readonly-textarea">
                            </div>

                        </div>

                    </div>

                </section>


                <!-- SECTION 5 -->

                <section class="section-card fade-up">

                    <div class="section-head">

                        <div class="section-num">
                            5
                        </div>

                        <div>

                            <h2 class="text-[14px] font-semibold text-mswdo-800">
                                Indigency Assessment
                            </h2>

                            <p class="text-[11px] text-slate-400">
                                Assessment classification recorded in the submitted case study
                            </p>

                        </div>

                    </div>


                    <div class="section-body">

                        <div class="assessment-card">

                            <div class="flex items-center gap-3">

                                <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center">

                                    <i class="fas fa-clipboard-check text-mswdo-600"></i>

                                </div>


                                <div>

                                    <p class="text-[9px] uppercase tracking-wider text-mswdo-600 font-bold">
                                        Assessment Result
                                    </p>

                                    <p id="indigency" class="text-lg font-bold text-mswdo-800">
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- SECTION 6 -->

                <section class="section-card fade-up">

                    <div class="section-head">

                        <div class="section-num">
                            6
                        </div>

                        <div>

                            <h2 class="text-[14px] font-semibold text-mswdo-800">
                                Previous DSWD Assistance
                            </h2>

                            <p class="text-[11px] text-slate-400">
                                Previous assistance information recorded in the case study
                            </p>

                        </div>

                    </div>


                    <div class="section-body space-y-4">

                        <div id="previousNotice" class="rounded-xl p-3 border flex items-start gap-3">
                        </div>


                        <div>

                            <label class="field-label">
                                Details of Previous Assistance
                            </label>

                            <div id="previousDetails" class="readonly-field readonly-textarea">
                            </div>

                        </div>


                        <div class="max-w-sm">

                            <label class="field-label">
                                Date of Previous Assistance
                            </label>

                            <div id="previousDate" class="readonly-field">
                            </div>

                        </div>

                    </div>

                </section>


                <!-- SECTION 7 -->

                <section class="section-card fade-up">

                    <div class="section-head">

                        <div class="section-num">
                            7
                        </div>

                        <div>

                            <h2 class="text-[14px] font-semibold text-mswdo-800">
                                Evaluation & Recommendation
                            </h2>

                            <p class="text-[11px] text-slate-400">
                                Social worker's professional assessment and formal recommendation
                            </p>

                        </div>

                    </div>


                    <div class="section-body">

                        <label class="field-label">
                            Recommendation
                        </label>

                        <div id="recommendation" class="recommendation-box">
                        </div>

                    </div>

                </section>


                <!-- SUBMISSION DETAILS -->

                <section class="section-card fade-up">

                    <div class="section-head">

                        <div class="section-num">

                            <i class="fas fa-check text-xs"></i>

                        </div>

                        <div>

                            <h2 class="text-[14px] font-semibold text-mswdo-800">
                                Submission Details
                            </h2>

                            <p class="text-[11px] text-slate-400">
                                Record information for this submitted case study
                            </p>

                        </div>

                    </div>


                    <div class="section-body">

                        <div class="grid grid-cols-3 gap-4 mobile-grid-1">

                            <div>

                                <label class="field-label">
                                    Status
                                </label>

                                <div id="submittedStatus" class="readonly-field">
                                </div>

                            </div>


                            <div>

                                <label class="field-label">
                                    Submitted Date
                                </label>

                                <div id="submittedDate" class="readonly-field">
                                </div>

                            </div>


                            <div>

                                <label class="field-label">
                                    Submitted By
                                </label>

                                <div id="submittedBy" class="readonly-field">
                                </div>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- VIEW PDF BUTTON -->

                <div class="flex flex-col sm:flex-row items-center justify-end gap-3 mt-2 mb-8">

                    <button onclick="previewCaseSummaryPDF()"
                        class="w-full sm:w-auto text-[13px] font-semibold text-white bg-mswdo-700 rounded-xl px-6 py-2.5 hover:bg-mswdo-800 transition-all shadow-sm">

                        <i class="fas fa-file-pdf mr-2"></i>

                        View Case Summary

                    </button>

                </div>


                <!-- FOOTER -->

                <footer
                    class="border-t border-slate-200 pt-4 pb-6 flex items-center justify-between text-[10px] text-slate-400">

                    <span>
                        MSWDO San Enrique Information System
                    </span>

                    <span>
                        Case Study Record
                    </span>

                </footer>

            </div>

        </main>

    </div>


    <!-- JAVASCRIPT -->

    <script>

        /* REMOTE LOGOS */

        const LOGO_LEFT_PATH =
            'data:image/jpeg;base64,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';

        const LOGO_RIGHT_PATH =
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTr0BysTwczGeP214v9nOSrBcKalK7hRBFzi_5NZkbpVw&s=10';


        /* ============================================================
           DEMO CASE DATA
        
           Replace this object with your actual retrieved
           caseStudyData from your system.
        ============================================================ */

        const caseStudyData = {

            client: {

                fullName: 'Juan Dela Cruz',

                age: 45,

                sex: 'Male',

                dateOfBirth: 'January 15, 1981',

                address: 'Barangay Poblacion, San Enrique, Negros Occidental',

                barangay: 'Poblacion',

                nearestKin: 'Maria Dela Cruz'

            },


            interview: {

                date: 'August 8, 2026',

                type: 'Financial Assistance / Medical Assistance'

            },


            patient: {

                fullName: 'Juan Dela Cruz',

                relation: 'Client',

                condition: 'Medical assistance requested'

            },


            family: [

                {

                    name: 'Juan Dela Cruz',

                    relationship: 'Client',

                    age: 45,

                    sex: 'Male',

                    civilStatus: 'Married',

                    education: 'High School',

                    occupation: 'Laborer',

                    income: 8500

                },

                {

                    name: 'Maria Dela Cruz',

                    relationship: 'Spouse',

                    age: 42,

                    sex: 'Female',

                    civilStatus: 'Married',

                    education: 'High School',

                    occupation: 'Household',

                    income: 0

                },

                {

                    name: 'Pedro Dela Cruz',

                    relationship: 'Son',

                    age: 18,

                    sex: 'Male',

                    civilStatus: 'Single',

                    education: 'College',

                    occupation: 'Student',

                    income: 0

                }

            ],


            finances: {

                incomeSources: [

                    {
                        label: 'Employment / Labor Income',
                        amount: 8500
                    }

                ],

                insurance: 'PhilHealth',

                savings: '₱1,500',

                emergencyFund: 'None',

                expenses: [

                    {
                        label: 'Food',
                        amount: 4500
                    },

                    {
                        label: 'Electricity',
                        amount: 1200
                    },

                    {
                        label: 'Water',
                        amount: 500
                    },

                    {
                        label: 'Transportation',
                        amount: 800
                    },

                    {
                        label: 'Medicine',
                        amount: 1200
                    }

                ]

            },


            assessment: {

                problemPresented:
                    'The client is requesting financial assistance to help cover medical expenses related to his current condition. The household is experiencing difficulty meeting the required expenses because of limited and irregular income.',

                homeCondition:
                    'The family resides in a modest household within the municipality. The primary source of income is labor work, which is not always stable. Available household resources are insufficient to fully cover the family’s regular expenses and the additional medical costs.'

            },


            indigency: 'INDIGENT / FINANCIALLY VULNERABLE',


            previousAssistance: {

                hasPrevious: true,

                details:
                    'The client previously received assistance from DSWD/MSWDO for medical expenses.',

                date: 'March 15, 2026'

            },


            recommendation:
                'Based on the assessment conducted, the client and his family are considered financially vulnerable and in need of assistance. It is recommended that appropriate assistance be extended subject to the applicable MSWDO and program requirements.',


            submitted: {

                status: 'Submitted',

                submittedDate: 'August 8, 2026',

                submittedBy: 'MSWDO Staff',

                preparedBy: 'MA. TERESA C. PONCLARA, RSW',

                designation: 'MSWDO',

                prcLicense: '0011198',

                licenseValidity: 'August 2025'

            }

        };


        /* HELPER FUNCTIONS */

        function safeText(value) {

            if (
                value === null ||
                value === undefined ||
                value === ''
            ) {
                return '—';
            }

            return String(value);

        }


        function safePdf(value) {

            if (
                value === null ||
                value === undefined ||
                value === ''
            ) {
                return '—';
            }

            return String(value)
                .replace(/&/g, '&')
                .replace(/</g, '<')
                .replace(/>/g, '>')
                .replace(/"/g, '"')
                .replace(/'/g, "'");
        }


        function formatCurrency(value) {

            const amount = Number(value) || 0;

            return '₱' + amount.toLocaleString(
                'en-PH',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );

        }


        /* SIDEBAR */

        function openSidebar() {

            document
                .getElementById('sidebar')
                .classList
                .add('open');

            document
                .getElementById('mobileOverlay')
                .classList
                .add('show');

        }


        function closeSidebar() {

            document
                .getElementById('sidebar')
                .classList
                .remove('open');

            document
                .getElementById('mobileOverlay')
                .classList
                .remove('show');

        }


        /* RENDER CLIENT */

        function renderClient() {

            const c = caseStudyData.client || {};

            document.getElementById('clientName').textContent =
                safeText(c.fullName);

            document.getElementById('clientSubtitle').textContent =
                `${safeText(c.age)} years old • ${safeText(c.sex)} • ${safeText(c.barangay || c.address)}`;

            document.getElementById('recordStatus').textContent =
                caseStudyData.submitted?.status || 'Submitted';


            document.getElementById('patientSummary').textContent =
                `${safeText(caseStudyData.patient?.fullName)} • ${safeText(caseStudyData.patient?.relation)}`;


            const details = [

                ['Full Name', c.fullName],

                ['Age', c.age ? `${c.age} years old` : ''],

                ['Sex', c.sex],

                ['Date of Birth', c.dateOfBirth],

                ['Address', c.address || c.barangay],

                ['Nearest Kin', c.nearestKin]

            ];


            document.getElementById('patientDetails').innerHTML =
                details.map(item => `

            <div>

                <label class="field-label">
                    ${item[0]}
                </label>

                <div class="readonly-field">
                    ${safeText(item[1])}
                </div>

            </div>

        `).join('');

        }


        /* RENDER INTERVIEW */

        function renderInterview() {

            document.getElementById('interviewDate').textContent =
                safeText(caseStudyData.interview?.date);

            document.getElementById('caseType').textContent =
                safeText(caseStudyData.interview?.type);

        }


        /* RENDER FAMILY */

        function renderFamily() {

            const family =
                caseStudyData.family || [];

            const tbody =
                document.getElementById('familyBody');


            tbody.innerHTML = '';


            if (!family.length) {

                tbody.innerHTML = `

            <tr>

                <td colspan="9"
                    class="text-center text-slate-400 py-5">

                    No family members recorded.

                </td>

            </tr>

        `;

            } else {

                family.forEach((member, index) => {

                    const isClient =
                        index === 0;

                    const row =
                        document.createElement('tr');

                    if (isClient) {
                        row.classList.add('client-row');
                    }

                    row.innerHTML = `

                <td>${index + 1}</td>

                <td class="font-semibold text-slate-700">
                    ${safeText(member.name)}
                </td>

                <td>
                    ${safeText(member.relationship)}
                </td>

                <td>
                    ${safeText(member.age)}
                </td>

                <td>
                    ${safeText(member.sex)}
                </td>

                <td>
                    ${safeText(member.civilStatus)}
                </td>

                <td>
                    ${safeText(member.education)}
                </td>

                <td>
                    ${safeText(member.occupation)}
                </td>

                <td>
                    ${formatCurrency(member.income)}
                </td>

            `;

                    tbody.appendChild(row);

                });

            }


            document.getElementById('familyCount').textContent =
                `${family.length} household member${family.length === 1 ? '' : 's'}`;


            const combinedIncome =
                family.reduce(
                    (sum, member) =>
                        sum + (Number(member.income) || 0),
                    0
                );


            document.getElementById('combinedIncome').textContent =
                formatCurrency(combinedIncome);

        }


        /* RENDER FINANCES */

        function renderFinances() {

            const finances =
                caseStudyData.finances || {};


            /* INCOME */

            const incomeLedger =
                document.getElementById('incomeLedger');

            incomeLedger.innerHTML = '';


            const incomeSources =
                finances.incomeSources || [];


            incomeSources.forEach(source => {

                const row =
                    document.createElement('div');

                row.className =
                    'flex items-center justify-between py-2.5 border-b border-slate-100';


                row.innerHTML = `

            <span class="text-[11px] text-slate-600">
                ${safeText(source.label)}
            </span>

            <span class="text-[12px] font-semibold text-mswdo-700">
                ${formatCurrency(source.amount)}
            </span>

        `;

                incomeLedger.appendChild(row);

            });


            document.getElementById('insurance').textContent =
                safeText(finances.insurance);

            document.getElementById('savings').textContent =
                safeText(finances.savings);

            document.getElementById('emergencyFund').textContent =
                safeText(finances.emergencyFund);


            /* EXPENSES */

            const expenseLedger =
                document.getElementById('expenseLedger');

            expenseLedger.innerHTML = '';


            const expenses =
                finances.expenses || [];


            expenses.forEach(expense => {

                const row =
                    document.createElement('div');

                row.className =
                    'flex items-center justify-between py-2.5 border-b border-slate-100';


                row.innerHTML = `

            <span class="text-[11px] text-slate-600">
                ${safeText(expense.label)}
            </span>

            <span class="text-[12px] font-semibold text-red-600">
                ${formatCurrency(expense.amount)}
            </span>

        `;

                expenseLedger.appendChild(row);

            });


            const totalIncome =
                incomeSources.reduce(
                    (sum, item) =>
                        sum + (Number(item.amount) || 0),
                    0
                );


            const totalExpenses =
                expenses.reduce(
                    (sum, item) =>
                        sum + (Number(item.amount) || 0),
                    0
                );


            const netMonthly =
                totalIncome - totalExpenses;


            document.getElementById('totalIncome').textContent =
                formatCurrency(totalIncome);

            document.getElementById('totalExpenses').textContent =
                formatCurrency(totalExpenses);

            document.getElementById('netMonthly').textContent =
                formatCurrency(netMonthly);

        }


        /* RENDER ASSESSMENT */

        function renderAssessment() {

            document.getElementById('problemPresented').textContent =
                safeText(
                    caseStudyData.assessment?.problemPresented
                );


            document.getElementById('homeCondition').textContent =
                safeText(
                    caseStudyData.assessment?.homeCondition
                );


            document.getElementById('indigency').textContent =
                safeText(caseStudyData.indigency);

        }


        /* RENDER PREVIOUS ASSISTANCE */

        function renderPreviousAssistance() {

            const previous =
                caseStudyData.previousAssistance || {};


            const notice =
                document.getElementById('previousNotice');


            if (previous.hasPrevious) {

                notice.className =
                    'rounded-xl p-3 border flex items-start gap-3 bg-amber-50 border-amber-200';


                notice.innerHTML = `

            <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center flex-shrink-0">

                <i class="fas fa-history text-amber-600"></i>

            </div>

            <div>

                <p class="text-[12px] font-semibold text-amber-800">
                    Previous assistance recorded
                </p>

                <p class="text-[11px] text-amber-700 mt-0.5">
                    The client has a previous DSWD/MSWDO assistance record.
                </p>

            </div>

        `;

            } else {

                notice.className =
                    'rounded-xl p-3 border flex items-start gap-3 bg-mswdo-50 border-mswdo-100';


                notice.innerHTML = `

            <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center flex-shrink-0">

                <i class="fas fa-circle-check text-mswdo-600"></i>

            </div>

            <div>

                <p class="text-[12px] font-semibold text-mswdo-800">
                    No previous assistance recorded
                </p>

                <p class="text-[11px] text-mswdo-700 mt-0.5">
                    No previous DSWD/MSWDO assistance was recorded.
                </p>

            </div>

        `;

            }


            document.getElementById('previousDetails').textContent =
                safeText(previous.details);


            document.getElementById('previousDate').textContent =
                safeText(previous.date);

        }


        /* RENDER RECOMMENDATION */

        function renderRecommendation() {

            document.getElementById('recommendation').textContent =
                safeText(caseStudyData.recommendation);

        }


        /* RENDER SUBMISSION */

        function renderSubmission() {

            const submitted =
                caseStudyData.submitted || {};


            document.getElementById('submittedStatus').textContent =
                safeText(submitted.status);


            document.getElementById('submittedDate').textContent =
                safeText(submitted.submittedDate);


            document.getElementById('submittedBy').textContent =
                safeText(
                    submitted.submittedBy ||
                    submitted.preparedBy
                );

        }



        function imageToSquareDataURL(url) {

            return new Promise((resolve, reject) => {

                const img =
                    new Image();

                img.crossOrigin = 'anonymous';


                img.onload = function () {

                    try {


                        const squareSize =
                            Math.min(
                                img.naturalWidth,
                                img.naturalHeight
                            );


                        const canvas =
                            document.createElement('canvas');


                        canvas.width =
                            squareSize;

                        canvas.height =
                            squareSize;


                        const ctx =
                            canvas.getContext('2d');



                        const sourceX =
                            (img.naturalWidth - squareSize) / 2;

                        const sourceY =
                            (img.naturalHeight - squareSize) / 2;


                        ctx.drawImage(

                            img,

                            sourceX,
                            sourceY,

                            squareSize,
                            squareSize,

                            0,
                            0,

                            squareSize,
                            squareSize

                        );


                        resolve(
                            canvas.toDataURL(
                                'image/jpeg',
                                0.95
                            )
                        );


                    } catch (error) {

                        reject(error);

                    }

                };


                img.onerror = function () {

                    reject(
                        new Error(
                            'Unable to load remote logo.'
                        )
                    );

                };


                img.src = url;

            });

        }


        /* PDF PREVIEW */

        async function previewCaseSummaryPDF() {


            const previewWindow =
                window.open('', '_blank');


            if (!previewWindow) {

                alert(
                    'Please allow pop-ups for this page to view the Case Summary PDF.'
                );

                return;

            }



            previewWindow.document.write(`

        <!DOCTYPE html>

        <html>

        <head>

            <title>
                Preparing Case Summary...
            </title>

            <style>

                body {

                    margin: 0;

                    min-height: 100vh;

                    display: flex;

                    align-items: center;

                    justify-content: center;

                    background: #f4f7f5;

                    font-family: Arial, sans-serif;

                    color: #14532d;

                }

                .box {

                    text-align: center;

                    background: white;

                    border: 1px solid #d7e4da;

                    border-radius: 16px;

                    padding: 32px 42px;

                    box-shadow:
                        0 10px 30px
                        rgba(20,83,45,.08);

                }

                .spin {

                    width: 32px;

                    height: 32px;

                    border: 3px solid #dcfce7;

                    border-top-color: #15803d;

                    border-radius: 50%;

                    margin: 0 auto 16px;

                    animation: spin 1s linear infinite;

                }

                @keyframes spin {

                    to {
                        transform: rotate(360deg);
                    }

                }

                p {

                    font-size: 12px;

                    color: #64748b;

                }

            </style>

        </head>

        <body>

            <div class="box">

                <div class="spin"></div>

                <strong>
                    Preparing Case Summary...
                </strong>

                <p>
                    Your official PDF preview is being generated.
                </p>

            </div>

        </body>

        </html>

    `);

            previewWindow.document.close();


            /* BUTTON LOADING STATE */

            const buttons =
                document.querySelectorAll(
                    '[onclick="previewCaseSummaryPDF()"]'
                );


            buttons.forEach(button => {

                button.disabled = true;

                button.innerHTML =
                    '<i class="fas fa-spinner fa-spin mr-2"></i> Preparing...';

            });


            try {

                /* CHECK jsPDF */

                if (
                    !window.jspdf ||
                    !window.jspdf.jsPDF
                ) {

                    throw new Error(
                        'PDF library is unavailable. Please refresh the page.'
                    );

                }


                const { jsPDF } =
                    window.jspdf;


                /* LOAD THE REMOTE LOGOS */

                const LOGO_LEFT =
                    await imageToSquareDataURL(
                        LOGO_LEFT_PATH
                    );


                const LOGO_RIGHT =
                    await imageToSquareDataURL(
                        LOGO_RIGHT_PATH
                    );


                /* CREATE PDF */

                const doc =
                    new jsPDF({

                        orientation: 'portrait',

                        unit: 'mm',

                        format: 'legal',

                        compress: true

                    });


                const pageW =
                    doc.internal.pageSize.getWidth();


                const pageH =
                    doc.internal.pageSize.getHeight();


                const margin = 17;


                const contentW =
                    pageW - margin * 2;


                let y = 12;


                /* COLORS */

                const DARK =
                    [20, 20, 20];


                const GRAY =
                    [95, 95, 95];


                const HEADER_FILL =
                    [232, 240, 234];


                const BORDER =
                    [135, 145, 138];


                const GREEN =
                    [21, 128, 61];


                /* SAFE PAGE SPACE */

                function ensureSpace(height) {

                    if (
                        y + height >
                        pageH - 18
                    ) {

                        doc.addPage();

                        y = 16;

                    }

                }


                /* WRAPPED TEXT */

                function addWrapped(
                    text,
                    x,
                    width,
                    fontSize = 10.5,
                    lineHeight = 5.1,
                    bold = false
                ) {

                    doc.setFont(
                        'helvetica',
                        bold ? 'bold' : 'normal'
                    );


                    doc.setFontSize(
                        fontSize
                    );


                    doc.setTextColor(
                        ...DARK
                    );


                    const lines =
                        doc.splitTextToSize(
                            safePdf(text),
                            width
                        );


                    ensureSpace(
                        lines.length *
                        lineHeight +
                        2
                    );


                    doc.text(
                        lines,
                        x,
                        y,
                        {
                            baseline: 'top'
                        }
                    );


                    y +=
                        lines.length *
                        lineHeight;


                    return lines.length;

                }


                /* PDF SECTION TITLE */

                function sectionTitle(
                    number,
                    title
                ) {

                    ensureSpace(12);


                    doc.setFont(
                        'helvetica',
                        'bold'
                    );


                    doc.setFontSize(
                        10.5
                    );


                    doc.setTextColor(
                        ...DARK
                    );


                    doc.text(
                        number + '.',
                        margin,
                        y
                    );


                    doc.text(
                        title.toUpperCase(),
                        margin + 10,
                        y
                    );


                    y += 7;

                }


                /* FORMAL LETTERHEAD */

                try {


                    const logoSize = 22;


                    const headerTop = 8;

                    const headerHeight = 24;



                    const logoY =
                        headerTop +
                        (
                            (headerHeight - logoSize) /
                            2
                        );


                    /* LEFT LOGO */

                    doc.addImage(

                        LOGO_LEFT,

                        'JPEG',

                        margin + 2,

                        logoY,

                        logoSize,

                        logoSize

                    );


                    /* RIGHT LOGO */

                    doc.addImage(

                        LOGO_RIGHT,

                        'JPEG',

                        pageW -
                        margin -
                        2 -
                        logoSize,

                        logoY,

                        logoSize,

                        logoSize

                    );


                } catch (logoError) {

                    console.warn(
                        'Logo could not be added to PDF:',
                        logoError
                    );

                }


                /* LETTERHEAD TEXT */

                doc.setTextColor(
                    ...DARK
                );


                doc.setFont(
                    'helvetica',
                    'bold'
                );


                doc.setFontSize(
                    10.5
                );


                doc.text(
                    'Republic of the Philippines',
                    pageW / 2,
                    11,
                    {
                        align: 'center'
                    }
                );


                doc.text(
                    'Province of Negros Occidental',
                    pageW / 2,
                    16,
                    {
                        align: 'center'
                    }
                );


                doc.text(
                    'Municipality of San Enrique',
                    pageW / 2,
                    21,
                    {
                        align: 'center'
                    }
                );


                doc.setFontSize(
                    11.5
                );


                doc.text(
                    'MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICE',
                    pageW / 2,
                    27,
                    {
                        align: 'center'
                    }
                );


                /* DOCUMENT TITLE */

                y = 42;


                doc.setFont(
                    'helvetica',
                    'bold'
                );


                doc.setFontSize(
                    11.5
                );


                doc.text(
                    'SOCIAL CASE SUMMARY',
                    pageW / 2,
                    y,
                    {
                        align: 'center'
                    }
                );


                y += 6;


                doc.setFont(
                    'helvetica',
                    'normal'
                );


                doc.setFontSize(
                    9.5
                );


                doc.text(
                    safePdf(
                        caseStudyData.interview?.date
                    ),
                    pageW / 2,
                    y,
                    {
                        align: 'center'
                    }
                );


                y += 11;


                /*  I. IDENTIFYING DATA */

                sectionTitle(
                    'I',
                    'IDENTIFYING DATA'
                );


                const c =
                    caseStudyData.client || {};


                const identifying = [

                    [
                        'Name',
                        safePdf(c.fullName)
                    ],

                    [
                        'Age',
                        safePdf(
                            c.age
                                ? c.age + ' YEARS OLD'
                                : ''
                        )
                    ],

                    [
                        'Sex',
                        safePdf(c.sex)
                    ],

                    [
                        'Date of Birth',
                        safePdf(c.dateOfBirth)
                    ],

                    [
                        'Address',
                        safePdf(
                            c.address ||
                            c.barangay
                        )
                    ],

                    [
                        'Nearest Kin',
                        safePdf(c.nearestKin)
                    ]

                ];


                identifying.forEach(
                    ([label, value]) => {

                        ensureSpace(6);


                        doc.setFont(
                            'helvetica',
                            'bold'
                        );


                        doc.setFontSize(
                            10
                        );


                        doc.text(
                            label,
                            margin,
                            y
                        );


                        doc.text(
                            ':',
                            margin + 48,
                            y
                        );


                        doc.setFont(
                            'helvetica',
                            'normal'
                        );


                        const lines =
                            doc.splitTextToSize(
                                value,
                                contentW - 53
                            );


                        doc.text(
                            lines,
                            margin + 52,
                            y
                        );


                        y += Math.max(
                            5.1,
                            lines.length * 4.8
                        );

                    }
                );


                y += 5;


                /* II. FAMILY COMPOSITION */

                sectionTitle(
                    'II',
                    'FAMILY COMPOSITION'
                );


                const family =
                    caseStudyData.family || [];


                const rows =
                    family.map(
                        (m, i) => [

                            String(i + 1),

                            safePdf(m.name),

                            safePdf(m.age),

                            safePdf(
                                m.relationship
                            ),

                            safePdf(
                                m.education
                            ),

                            safePdf(
                                m.occupation
                            )

                        ]
                    );


                if (
                    typeof doc.autoTable !==
                    'function'
                ) {

                    throw new Error(
                        'AutoTable plugin is not loaded.'
                    );

                }


                doc.autoTable({

                    startY: y,

                    margin: {
                        left: margin,
                        right: margin
                    },

                    tableWidth: contentW,

                    head: [[

                        '',

                        'NAME',

                        'AGE',

                        'RELATION TO CLIENT',

                        'EDUCATIONAL ATTAINMENT',

                        'OCCUPATION'

                    ]],

                    body:
                        rows.length
                            ? rows
                            : [
                                [
                                    '1',
                                    '—',
                                    '—',
                                    '—',
                                    '—',
                                    '—'
                                ]
                            ],

                    theme: 'grid',

                    styles: {

                        font: 'helvetica',

                        fontSize: 8.1,

                        textColor: DARK,

                        cellPadding: 2.2,

                        lineColor: BORDER,

                        lineWidth: 0.25,

                        valign: 'middle'

                    },

                    headStyles: {

                        fillColor:
                            HEADER_FILL,

                        textColor:
                            DARK,

                        fontStyle:
                            'bold',

                        fontSize:
                            7.3,

                        halign:
                            'center'

                    },

                    columnStyles: {

                        0: {
                            cellWidth: 8,
                            halign: 'center'
                        },

                        1: {
                            cellWidth: 42
                        },

                        2: {
                            cellWidth: 15
                        },

                        3: {
                            cellWidth: 34
                        },

                        4: {
                            cellWidth: 43
                        },

                        5: {
                            cellWidth: 38
                        }

                    }

                });


                y =
                    doc.lastAutoTable.finalY +
                    9;


                /* III. PROBLEM PRESENTED */

                sectionTitle(
                    'III',
                    'PROBLEM PRESENTED'
                );


                addWrapped(
                    caseStudyData.assessment
                        ?.problemPresented,
                    margin,
                    contentW,
                    10.5,
                    5.1
                );


                y += 8;


                /* IV. HOME AND ECONOMIC CONDITION */

                sectionTitle(
                    'IV',
                    'HOME AND ECONOMIC CONDITION'
                );


                addWrapped(
                    caseStudyData.assessment
                        ?.homeCondition,
                    margin,
                    contentW,
                    10.5,
                    5.1
                );


                y += 7;


                /* V. EVALUATION / RECOMMENDATION */

                sectionTitle(
                    'V',
                    'EVALUATION / RECOMMENDATION'
                );


                addWrapped(
                    caseStudyData.recommendation,
                    margin,
                    contentW,
                    10.5,
                    5.1
                );


                y += 13;


                /* PREPARED BY */

                ensureSpace(35);


                doc.setFont(
                    'helvetica',
                    'normal'
                );


                doc.setFontSize(
                    10.5
                );


                doc.text(
                    'Prepared by:',
                    margin,
                    y
                );


                y += 20;


                doc.setFont(
                    'helvetica',
                    'bold'
                );


                doc.text(

                    safePdf(
                        caseStudyData.submitted
                            ?.preparedBy ||
                        caseStudyData.submitted
                            ?.submittedBy
                    ),

                    margin,
                    y

                );


                y += 5;


                doc.setFont(
                    'helvetica',
                    'normal'
                );


                doc.text(

                    safePdf(
                        caseStudyData.submitted
                            ?.designation ||
                        'MSWDO'
                    ),

                    margin,
                    y

                );


                y += 5;


                if (
                    caseStudyData.submitted
                        ?.prcLicense
                ) {

                    doc.text(

                        'PRC License # ' +
                        caseStudyData.submitted
                            .prcLicense,

                        margin,
                        y

                    );

                    y += 5;

                }


                if (
                    caseStudyData.submitted
                        ?.licenseValidity
                ) {

                    doc.text(

                        'Valid until ' +
                        caseStudyData.submitted
                            .licenseValidity,

                        margin,
                        y

                    );

                }


                /* FOOTER */

                const totalPages =
                    doc.getNumberOfPages();


                for (
                    let page = 1;
                    page <= totalPages;
                    page++
                ) {

                    doc.setPage(page);


                    doc.setFont(
                        'helvetica',
                        'normal'
                    );


                    doc.setFontSize(
                        7
                    );


                    doc.setTextColor(
                        145,
                        145,
                        145
                    );


                    doc.text(

                        'MSWDO San Enrique Information System',

                        margin,

                        pageH - 7

                    );


                    doc.text(

                        `Page ${page} of ${totalPages}`,

                        pageW - margin,

                        pageH - 7,

                        {
                            align: 'right'
                        }

                    );

                }


                /* OPEN PDF PREVIEW */

                const pdfBlob =
                    doc.output('blob');


                const pdfUrl =
                    URL.createObjectURL(
                        pdfBlob
                    );


                previewWindow.location.href =
                    pdfUrl;


                /*
                 * Do NOT call URL.revokeObjectURL()
                 * immediately because the browser PDF
                 * viewer is still using the URL.
                 */

            } catch (error) {

                console.error(
                    'Case Summary PDF error:',
                    error
                );


                previewWindow.document.body.innerHTML = `

            <div style="
                font-family:Arial;
                padding:40px;
                text-align:center;
                color:#991b1b;
            ">

                <h3>
                    Unable to create the Case Summary PDF.
                </h3>

                <p style="
                    color:#64748b;
                ">
                    Please refresh the page and try again.
                </p>

                <p style="
                    font-size:11px;
                    color:#94a3b8;
                ">
                    ${safePdf(
                    error.message ||
                    'Unknown error'
                )}
                </p>

            </div>

        `;

            } finally {

                buttons.forEach(button => {

                    button.disabled = false;

                    button.innerHTML =
                        '<i class="fas fa-file-pdf mr-2"></i> View Case Summary';

                });

            }

        }


        /* INITIALIZE PAGE */

        document.addEventListener(
            'DOMContentLoaded',
            () => {

                renderClient();

                renderInterview();

                renderFamily();

                renderFinances();

                renderAssessment();

                renderPreviousAssistance();

                renderRecommendation();

                renderSubmission();

            }
        );

    </script>

</body>

</html>