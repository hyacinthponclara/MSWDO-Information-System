<?php
require 'auth.php';
requireRole(['Admin', 'Staff']);
require 'db_connect.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Case Studies for Availment – MSWDO San Enrique</title>


    <!-- =========================================================
         TAILWIND
    ========================================================== -->

    <script src="https://cdn.tailwindcss.com"></script>


    <!-- =========================================================
         GOOGLE FONTS
    ========================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap"
        rel="stylesheet"
    >


    <!-- =========================================================
         FONT AWESOME
    ========================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =========================================================
         TAILWIND CONFIG
    ========================================================== -->

    <script>

        tailwind.config = {

            theme: {

                extend: {

                    fontFamily: {

                        sans: [
                            'DM Sans',
                            'sans-serif'
                        ],

                        serif: [
                            'DM Serif Display',
                            'serif'
                        ]

                    },


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


                        gold: {

                            DEFAULT: '#C49A2A',

                            50: '#FBF5E6',

                            100: '#F5E4B3',

                            400: '#C49A2A'

                        },


                        slate2: '#F4F7FC'

                    },


                    keyframes: {

                        fadeUp: {

                            '0%': {

                                opacity: '0',

                                transform:
                                    'translateY(12px)'

                            },

                            '100%': {

                                opacity: '1',

                                transform:
                                    'translateY(0)'

                            }

                        }

                    },


                    animation: {

                        'fade-up':
                            'fadeUp 0.4s ease both',

                        'fade-up-1':
                            'fadeUp 0.4s ease 0.05s both',

                        'fade-up-2':
                            'fadeUp 0.4s ease 0.1s both',

                        'fade-up-3':
                            'fadeUp 0.4s ease 0.15s both',

                        'fade-up-4':
                            'fadeUp 0.4s ease 0.2s both'

                    }

                }

            }

        };

    </script>


    <!-- =========================================================
         PAGE STYLES
    ========================================================== -->

    <style>

        body {

            font-family:
                'DM Sans',
                sans-serif;

        }


        /* ---------------------------------------------------------
           SIDEBAR
        --------------------------------------------------------- */

        .sidebar-item {

            transition:
                all .15s ease;

        }


        .sidebar-item:hover {

            background:
                rgba(255, 255, 255, .07);

            color:
                rgba(255, 255, 255, .95);

        }


        .sidebar-item.active {

            background:
                rgba(26, 92, 58, .25);

            border-left-color:
                #C49A2A;

            color:
                #fff;

        }


        /* ---------------------------------------------------------
           ACTION BUTTONS
        --------------------------------------------------------- */

        .btn-action {

            transition:
                all .15s ease;

        }


        .btn-action:hover {

            transform:
                translateY(-1px);

        }


        /* ---------------------------------------------------------
           TABLE
        --------------------------------------------------------- */

        .table-row {

            transition:
                background .12s;

        }


        .table-row:hover {

            background:
                #EEF6F0;

        }


        /* ---------------------------------------------------------
           SCROLLBAR
        --------------------------------------------------------- */

        ::-webkit-scrollbar {

            width:
                4px;

            height:
                4px;

        }


        ::-webkit-scrollbar-thumb {

            background:
                rgba(26, 92, 58, .2);

            border-radius:
                2px;

        }


        /* ---------------------------------------------------------
           MODAL
        --------------------------------------------------------- */

        .modal-hidden {

            display:
                none;

        }


        .modal-visible {

            display:
                flex;

        }


        /* ---------------------------------------------------------
           MOBILE SIDEBAR
        --------------------------------------------------------- */

        @media (max-width: 767px) {

            .desktop-sidebar {

                transform:
                    translateX(-100%);

                transition:
                    transform .25s ease;

                z-index:
                    50;

            }


            .desktop-sidebar.mobile-open {

                transform:
                    translateX(0);

            }


            .main-content {

                margin-left:
                    0 !important;

            }

        }

    </style>

</head>


<body class="bg-slate2 min-h-screen flex">


    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <?php require 'sidebar.php'; ?>


    <!-- =========================================================
         MAIN
    ========================================================== -->

    <div
        class="main-content ml-64 flex-1 flex flex-col min-h-screen"
    >


        <!-- =====================================================
             MOBILE HEADER
        ====================================================== -->

        <div
            class="md:hidden bg-green-600 text-white h-14 flex items-center justify-between px-4"
        >

            <span class="font-serif text-lg">

                MSWDO

            </span>


            <button
                type="button"
                onclick="toggleMobileSidebar()"
                class="text-white"
            >

                <i class="fas fa-bars text-lg"></i>

            </button>

        </div>


        <!-- =====================================================
             TOP BAR
        ====================================================== -->

        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20"
        >

            <div class="flex items-center gap-2 text-[13px]">

                <span class="text-green-600 font-semibold">

                    Case Studies for Availment

                </span>

            </div>

        </header>


        <!-- =====================================================
             MAIN CONTENT
        ====================================================== -->

        <main
            class="flex-1 p-6 space-y-5 overflow-y-auto"
        >


            <!-- =================================================
                 PAGE TITLE
            ================================================== -->

            <div
                class="flex flex-wrap items-center justify-between gap-3 animate-fade-up"
            >

                <div>

                    <h1
                        class="text-xl font-serif text-green-600"
                    >

                        Case Studies for Availment

                    </h1>


                    <p
                        class="text-[13px] text-slate-500 mt-0.5"
                    >

                        View completed case studies that are ready
                        to be connected to their corresponding availment.

                    </p>

                </div>


                <!-- TOTAL -->

                <div
                    class="flex items-center gap-2"
                >

                    <div
                        class="bg-green-50 border border-green-100 rounded-lg px-3 py-2"
                    >

                        <span
                            class="text-[10px] uppercase tracking-wider text-slate-400"
                        >

                            For Availment

                        </span>


                        <span
                            id="headerCount"
                            class="ml-2 text-sm font-bold text-green-600"
                        >

                            0

                        </span>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 FILTERS
            ================================================== -->

            <div
                class="flex flex-wrap items-end gap-3 animate-fade-up-2 bg-white rounded-2xl border border-slate-200 p-4"
            >


                <!-- SEARCH -->

                <div
                    class="min-w-[220px] flex-1"
                >

                    <label
                        class="text-[10px] uppercase tracking-wider text-slate-400 block mb-1"
                    >

                        Search

                    </label>


                    <div class="relative">

                        <i
                            class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[11px]"
                        ></i>


                        <input
                            type="text"
                            id="searchInput"
                            placeholder="Client name or case study no."
                            class="w-full text-[12px] border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                        >

                    </div>

                </div>


                <!-- PROGRAM -->

                <div>

                    <label
                        class="text-[10px] uppercase tracking-wider text-slate-400 block mb-1"
                    >

                        Program

                    </label>


                    <select
                        id="programFilter"
                        class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none min-w-[175px]"
                    >

                        <option value="all">

                            All AICS Programs

                        </option>


                        <option value="Financial">

                            Financial

                        </option>


                        <option value="Burial">

                            Burial

                        </option>


                        <option value="Medical">

                            Medical

                        </option>


                        <option value="Livelihood">

                            Livelihood

                        </option>


                        <option value="Educational">

                            Educational

                        </option>

                    </select>

                </div>


                <!-- FROM -->

                <div>

                    <label
                        class="text-[10px] uppercase tracking-wider text-slate-400 block mb-1"
                    >

                        From

                    </label>


                    <input
                        type="date"
                        id="filterFrom"
                        class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                    >

                </div>


                <!-- TO -->

                <div>

                    <label
                        class="text-[10px] uppercase tracking-wider text-slate-400 block mb-1"
                    >

                        To

                    </label>


                    <input
                        type="date"
                        id="filterTo"
                        class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                    >

                </div>


                <!-- RESET -->

                <button
                    type="button"
                    onclick="resetFilters()"
                    class="btn-action text-[12px] font-medium text-slate-600 border border-slate-200 rounded-lg px-3 py-1.5 hover:bg-slate-50"
                >

                    <i class="fas fa-rotate-left mr-1"></i>

                    Reset

                </button>


                <!-- RESULT COUNT -->

                <div
                    class="ml-auto pb-1"
                >

                    <span
                        class="text-[11px] text-slate-400"
                        id="rowCount"
                    >

                        Showing 0 case studies

                    </span>

                </div>

            </div>


            <!-- =================================================
                 TABLE
            ================================================== -->

            <div
                class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-up-3"
            >

                <!--
                Center the table area horizontally while keeping
                the existing table design and full available width.
                -->

                <div
                    class="overflow-x-auto flex justify-center"
                >

                    <table
                        class="w-full text-[12px]"
                    >

                        <thead>

                            <tr
                                class="bg-slate-50 border-b border-slate-100"
                            >

                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                                >

                                    Case Study No.

                                </th>


                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                                >

                                    Client

                                </th>


                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                                >

                                    Barangay

                                </th>


                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                                >

                                    Date Interviewed

                                </th>


                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                                >

                                    Program

                                </th>


                                <th
                                    class="text-center px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                                >

                                    Action

                                </th>

                            </tr>

                        </thead>


                        <tbody
                            id="tableBody"
                            class="divide-y divide-slate-100"
                        >

                            <!-- JS -->

                        </tbody>

                    </table>

                </div>


                <!-- =================================================
                     TABLE FOOTER
                ================================================== -->

                <div
                    class="flex items-center justify-between px-5 py-3 border-t border-slate-100"
                >

                    <span
                        class="text-[11px] text-slate-400"
                        id="paginationInfo"
                    >

                        Showing 0–0 of 0

                    </span>


                    <div
                        class="flex items-center gap-1"
                    >

                        <button
                            type="button"
                            class="text-[11px] text-slate-400 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors"
                        >

                            Previous

                        </button>


                        <button
                            type="button"
                            class="text-[11px] font-medium text-white bg-green-600 rounded-lg px-3 py-1"
                        >

                            1

                        </button>


                        <button
                            type="button"
                            class="text-[11px] text-slate-600 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors"
                        >

                            Next

                        </button>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 EMPTY STATE
            ================================================== -->

            <div
                id="emptyState"
                class="hidden bg-white rounded-2xl border border-slate-200 p-12 text-center animate-fade-up-4"
            >

                <div
                    class="w-12 h-12 mx-auto mb-3 rounded-full bg-slate-100 flex items-center justify-center"
                >

                    <i
                        class="fas fa-folder-open text-slate-400"
                    ></i>

                </div>


                <h3
                    class="text-[13px] font-semibold text-slate-700"
                >

                    No Case Studies Found

                </h3>


                <p
                    class="text-[12px] text-slate-400 mt-1"
                >

                    No completed case studies match your current filters.

                </p>

            </div>

        </main>


        <!-- =====================================================
             FOOTER
        ====================================================== -->

        <footer
            class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400"
        >

            <span>

                MSWDO San Enrique Information System

            </span>

        </footer>

    </div>


    <!-- =========================================================
         CASE STUDY VIEW MODAL
    ========================================================== -->

    <div
        id="caseModal"
        class="modal-hidden fixed inset-0 bg-black/40 items-center justify-center p-4 z-50"
    >

        <div
            class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden"
        >


            <!-- HEADER -->

            <div
                class="px-6 py-4 border-b border-slate-200 flex items-center justify-between"
            >

                <div>

                    <p
                        class="text-[10px] uppercase tracking-wider text-slate-400"
                    >

                        Case Study Summary

                    </p>


                    <h2
                        id="modalCaseNo"
                        class="font-serif text-lg text-green-600"
                    >

                        —

                    </h2>

                </div>


                <button
                    type="button"
                    onclick="closeModal()"
                    class="w-8 h-8 rounded-lg hover:bg-slate-50 text-slate-400"
                >

                    <i class="fas fa-xmark"></i>

                </button>

            </div>


            <!-- BODY -->

            <div
                class="p-6"
            >

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5"
                >

                    <div>

                        <p
                            class="text-[10px] uppercase tracking-wider text-slate-400"
                        >

                            Client

                        </p>


                        <p
                            id="modalClient"
                            class="text-[13px] font-semibold text-slate-700 mt-1"
                        >

                            —

                        </p>

                    </div>


                    <div>

                        <p
                            class="text-[10px] uppercase tracking-wider text-slate-400"
                        >

                            Barangay

                        </p>


                        <p
                            id="modalBarangay"
                            class="text-[13px] font-semibold text-slate-700 mt-1"
                        >

                            —

                        </p>

                    </div>


                    <div>

                        <p
                            class="text-[10px] uppercase tracking-wider text-slate-400"
                        >

                            Date Interviewed

                        </p>


                        <p
                            id="modalDate"
                            class="text-[13px] font-semibold text-slate-700 mt-1"
                        >

                            —

                        </p>

                    </div>


                    <div>

                        <p
                            class="text-[10px] uppercase tracking-wider text-slate-400"
                        >

                            Program / Assistance

                        </p>


                        <p
                            id="modalProgram"
                            class="text-[13px] font-semibold text-green-600 mt-1"
                        >

                            —

                        </p>

                    </div>

                </div>


                <div
                    class="mt-6 bg-green-50 border border-green-100 rounded-xl p-4"
                >

                    <div class="flex gap-3">

                        <i
                            class="fas fa-circle-info text-green-500 text-sm mt-0.5"
                        ></i>


                        <p
                            class="text-[11px] text-green-700 leading-relaxed"
                        >

                            This case study has been completed and is
                            ready for its one corresponding availment.

                        </p>

                    </div>

                </div>

            </div>


            <!-- FOOTER -->

            <div
                class="px-6 py-4 border-t border-slate-200 flex justify-end gap-2"
            >

                <button
                    type="button"
                    onclick="closeModal()"
                    class="text-[12px] font-medium text-slate-600 border border-slate-200 rounded-lg px-3 py-1.5 hover:bg-slate-50"
                >

                    Close

                </button>


                <a
                    href="aics.php"
                    id="modalAvailmentButton"
                    class="btn-action text-[12px] font-semibold text-white bg-green-600 rounded-lg px-3 py-1.5 hover:bg-green-700 inline-flex items-center"
                >

                    <i class="fas fa-plus mr-1"></i>

                    Add AICS Availment

                </a>

            </div>

        </div>

    </div>


    <!-- =========================================================
         TOAST
    ========================================================== -->

    <div
        id="toast"
        class="fixed bottom-6 right-6 bg-green-700 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50"
    >

        <i
            class="fas fa-check-circle text-green-300"
        ></i>


        <span id="toastMsg">

            Action completed!

        </span>

    </div>


    <!-- =========================================================
         JAVASCRIPT
    ========================================================== -->

    <script>


        /* ========================================================
           SAMPLE DATA
        ========================================================= */

        const caseStudies = [

            {
                id: 1,

                caseNo: 'CS-2026-0001',

                firstName: 'Juan',

                middleName: 'Dela',

                lastName: 'Cruz',

                barangay: 'Poblacion',

                date: '2026-08-20',

                program: 'Medical',

                hasAvailment: false
            },


            {
                id: 2,

                caseNo: 'CS-2026-0002',

                firstName: 'Maria',

                middleName: 'Santos',

                lastName: 'Garcia',

                barangay: 'Bata',

                date: '2026-08-19',

                program: 'Educational',

                hasAvailment: false
            },


            {
                id: 3,

                caseNo: 'CS-2026-0003',

                firstName: 'Pedro',

                middleName: 'M.',

                lastName: 'Reyes',

                barangay: 'Balandra',

                date: '2026-08-17',

                program: 'Burial',

                hasAvailment: false
            },


            {
                id: 4,

                caseNo: 'CS-2026-0004',

                firstName: 'Ana',

                middleName: 'R.',

                lastName: 'Villanueva',

                barangay: 'Nabago',

                date: '2026-08-14',

                program: 'Livelihood',

                hasAvailment: false
            },


            {
                id: 5,

                caseNo: 'CS-2026-0005',

                firstName: 'Rosa',

                middleName: 'L.',

                lastName: 'Mendoza',

                barangay: 'Bago',

                date: '2026-08-10',

                program: 'Financial',

                hasAvailment: false
            },


            {
                id: 6,

                caseNo: 'CS-2026-0006',

                firstName: 'Jose',

                middleName: 'A.',

                lastName: 'Fernandez',

                barangay: 'Poblacion',

                date: '2026-08-05',

                program: 'Medical',

                hasAvailment: false
            }

        ];


        /* ========================================================
           FILTER
        ========================================================= */

        function applyFilters() {

            const search =
                document
                    .getElementById('searchInput')
                    .value
                    .toLowerCase()
                    .trim();


            const program =
                document
                    .getElementById('programFilter')
                    .value;


            const from =
                document
                    .getElementById('filterFrom')
                    .value;


            const to =
                document
                    .getElementById('filterTo')
                    .value;


            const filtered =
                caseStudies.filter(row => {


                    /*
                    ------------------------------------------------
                    1 CASE STUDY = 1 AVAILMENT

                    If an availment already exists, do NOT display
                    the case study here.
                    ------------------------------------------------
                    */

                    if (row.hasAvailment) {

                        return false;

                    }


                    /*
                    ------------------------------------------------
                    SEARCH
                    ------------------------------------------------
                    */

                    const name =
                        `${row.firstName} ${row.middleName} ${row.lastName}`
                            .toLowerCase();


                    const searchable =
                        `${row.caseNo} ${name} ${row.barangay} ${row.program}`
                            .toLowerCase();


                    if (
                        search &&
                        !searchable.includes(search)
                    ) {

                        return false;

                    }


                    /*
                    ------------------------------------------------
                    PROGRAM
                    ------------------------------------------------
                    */

                    if (
                        program !== 'all' &&
                        row.program !== program
                    ) {

                        return false;

                    }


                    /*
                    ------------------------------------------------
                    DATE RANGE
                    ------------------------------------------------
                    */

                    if (
                        from &&
                        row.date < from
                    ) {

                        return false;

                    }


                    if (
                        to &&
                        row.date > to
                    ) {

                        return false;

                    }


                    return true;

                });


            renderTable(filtered);

        }


        /* ========================================================
           RENDER TABLE
        ========================================================= */

        function renderTable(data) {

            const tbody =
                document.getElementById('tableBody');


            tbody.innerHTML = '';


            data.forEach(row => {


                const tr =
                    document.createElement('tr');


                tr.className =
                    'table-row';


                const fullName =
                    `${row.lastName}, ${row.firstName} ${row.middleName}`;


                tr.innerHTML = `

                    <td class="px-5 py-3">

                        <span class="font-medium text-green-700">

                            ${escapeHtml(row.caseNo)}

                        </span>

                    </td>


                    <td class="px-5 py-3">

                        <div class="font-medium text-slate-700">

                            ${escapeHtml(fullName)}

                        </div>

                    </td>


                    <td class="px-5 py-3 text-slate-600">

                        ${escapeHtml(row.barangay)}

                    </td>


                    <td class="px-5 py-3 text-slate-400">

                        ${formatDate(row.date)}

                    </td>


                    <td class="px-5 py-3">

                        ${programBadge(row.program)}

                    </td>


                    <td class="px-5 py-3 text-center">

                        <div class="flex items-center justify-center gap-2">

                            <!-- VIEW -->

                            <a
                                href="casestudy_view.php"
                                class="text-[12px] font-medium text-green-600 bg-green-50 border border-green-200 rounded-lg px-3 py-1.5 hover:bg-green-100 transition-colors inline-flex items-center gap-1.5"
                            >

                                <i class="fas fa-eye"></i>

                                View

                            </a>


                            <!-- ADD AICS AVAILMENT -->

                            <a
                                href="#"
                                class="text-[12px] font-medium text-green-600 bg-green-50 border border-green-200 rounded-lg px-3 py-1.5 hover:bg-green-100 transition-colors inline-flex items-center gap-1.5"
                            >

                                <i class="fas fa-plus"></i>

                                Add AICS Availment

                            </a>

                        </div>

                    </td>

                `;


                tbody.appendChild(tr);

            });


            /*
            --------------------------------------------------------
            EMPTY STATE
            --------------------------------------------------------
            */

            const emptyState =
                document.getElementById('emptyState');


            const tableContainer =
                tbody.closest('.bg-white');


            if (data.length === 0) {

                emptyState.classList.remove(
                    'hidden'
                );

                tableContainer.classList.add(
                    'hidden'
                );

            } else {

                emptyState.classList.add(
                    'hidden'
                );

                tableContainer.classList.remove(
                    'hidden'
                );

            }


            /*
            --------------------------------------------------------
            COUNTERS
            --------------------------------------------------------
            */

            document
                .getElementById('rowCount')
                .textContent =
                    `Showing ${data.length} case studies`;


            document
                .getElementById('paginationInfo')
                .textContent =
                    `Showing ${data.length ? 1 : 0}–${data.length} of ${data.length}`;


            const availableCount =
                caseStudies.filter(
                    row => !row.hasAvailment
                ).length;


            document
                .getElementById('headerCount')
                .textContent =
                    availableCount;

        }


        /* ========================================================
           PROGRAM BADGE
        ========================================================= */

        function programBadge(program) {

            let cls =
                'px-2 py-0.5 rounded text-[10px] font-semibold ';


            if (
                program === 'Financial'
            ) {

                cls +=
                    'bg-green-50 text-green-700';

            }

            else if (
                program === 'Medical'
            ) {

                cls +=
                    'bg-blue-100 text-blue-700';

            }

            else if (
                program === 'Burial'
            ) {

                cls +=
                    'bg-purple-100 text-purple-700';

            }

            else if (
                program === 'Livelihood'
            ) {

                cls +=
                    'bg-amber-100 text-amber-700';

            }

            else if (
                program === 'Educational'
            ) {

                cls +=
                    'bg-indigo-100 text-indigo-700';

            }

            else {

                cls +=
                    'bg-slate-100 text-slate-600';

            }


            return `

                <span class="${cls}">

                    ${escapeHtml(program)}

                </span>

            `;

        }


        /* ========================================================
           VIEW CASE STUDY
        ========================================================= */

        function viewCaseStudy(id) {

            const row =
                caseStudies.find(
                    item => item.id === id
                );


            if (!row) {

                return;

            }


            document
                .getElementById('modalCaseNo')
                .textContent =
                    row.caseNo;


            document
                .getElementById('modalClient')
                .textContent =
                    `${row.firstName} ${row.middleName} ${row.lastName}`;


            document
                .getElementById('modalBarangay')
                .textContent =
                    row.barangay;


            document
                .getElementById('modalDate')
                .textContent =
                    formatDate(row.date);


            document
                .getElementById('modalProgram')
                .textContent =
                    row.program;


            document
                .getElementById('caseModal')
                .classList
                .remove('modal-hidden');


            document
                .getElementById('caseModal')
                .classList
                .add('modal-visible');

        }


        /* ========================================================
           CLOSE MODAL
        ========================================================= */

        function closeModal() {

            document
                .getElementById('caseModal')
                .classList
                .remove('modal-visible');


            document
                .getElementById('caseModal')
                .classList
                .add('modal-hidden');

        }


        /* ========================================================
           RESET
        ========================================================= */

        function resetFilters() {

            document
                .getElementById('searchInput')
                .value = '';


            document
                .getElementById('programFilter')
                .value = 'all';


            document
                .getElementById('filterFrom')
                .value = '';


            document
                .getElementById('filterTo')
                .value = '';


            applyFilters();

        }


        /* ========================================================
           DATE FORMAT
        ========================================================= */

        function formatDate(dateString) {

            const date =
                new Date(
                    dateString + 'T00:00:00'
                );


            return date.toLocaleDateString(
                'en-PH',
                {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                }
            );

        }


        /* ========================================================
           HTML ESCAPE
        ========================================================= */

        function escapeHtml(value) {

            return String(value)
                .replace(
                    /&/g,
                    '&amp;'
                )
                .replace(
                    /</g,
                    '&lt;'
                )
                .replace(
                    />/g,
                    '&gt;'
                )
                .replace(
                    /"/g,
                    '&quot;'
                )
                .replace(
                    /'/g,
                    '&#039;'
                );

        }


        /* ========================================================
           MOBILE SIDEBAR
        ========================================================= */

        function toggleMobileSidebar() {

            const sidebar =
                document.querySelector(
                    '.desktop-sidebar'
                );


            if (sidebar) {

                sidebar.classList.toggle(
                    'mobile-open'
                );

            }

        }


        /* ========================================================
           CLOSE MODAL OUTSIDE
        ========================================================= */

        document
            .getElementById('caseModal')
            .addEventListener(
                'click',
                function (event) {

                    if (
                        event.target === this
                    ) {

                        closeModal();

                    }

                }
            );


        /* ========================================================
           FILTER EVENTS
        ========================================================= */

        document
            .getElementById('searchInput')
            .addEventListener(
                'input',
                applyFilters
            );


        document
            .getElementById('programFilter')
            .addEventListener(
                'change',
                applyFilters
            );


        document
            .getElementById('filterFrom')
            .addEventListener(
                'change',
                applyFilters
            );


        document
            .getElementById('filterTo')
            .addEventListener(
                'change',
                applyFilters
            );


        /* ========================================================
           INITIALIZE
        ========================================================= */

        applyFilters();

    </script>

</body>

</html>