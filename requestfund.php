<?php
require 'auth.php';
requireRole(['Admin', 'Social Worker']);
require 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Program Budget Request – MSWDO San Enrique</title>
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
                        hunter: {
                            DEFAULT: '#1E4D2B',
                            50: '#F0F7F2',
                            100: '#DCE8E0',
                            200: '#B8D0C0',
                            300: '#8FB8A0',
                            400: '#4A7A5A',
                            500: '#1E4D2B',
                            600: '#1A4024',
                            700: '#15331D',
                            800: '#0F2616',
                            900: '#0A1A0F'
                        },
                        sage: '#E8F0EA',
                        cream: '#F8FAF9',
                        gold: { DEFAULT: '#C49A2A', 400: '#C49A2A' },
                    },
                    keyframes: {
                        fadeUp: { '0%': { opacity: '0', transform: 'translateY(12px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.4s ease both',
                        'fade-up-1': 'fadeUp 0.4s 0.08s ease both',
                        'fade-up-2': 'fadeUp 0.4s 0.16s ease both',
                        'fade-up-3': 'fadeUp 0.4s 0.24s ease both',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'DM Sans', sans-serif;
            background: #F0F7F2;
        }

        .sidebar-item {
            transition: all .15s;
        }
        .sidebar-item:hover {
            background: rgba(30, 77, 43, 0.08);
            color: #1E4D2B;
        }
        .sidebar-item.active {
            background: rgba(30, 77, 43, 0.12);
            border-left-color: #C49A2A;
            color: #1E4D2B;
        }

        .prog-card {
            transition: all .25s ease;
            cursor: pointer;
            background: #FFFFFF;
            border: 1.5px solid #E8F0EA;
            border-radius: 16px;
            padding: 1.5rem 1rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(30, 77, 43, 0.04);
        }
        .prog-card:hover {
            transform: translateY(-4px);
            border-color: #B8D0C0;
            box-shadow: 0 12px 32px rgba(30, 77, 43, 0.08);
        }
        .prog-card .card-icon {
            transition: transform .25s ease;
            width: 56px;
            height: 56px;
            margin: 0 auto 12px;
            border-radius: 14px;
            background: #F0F7F2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #1E4D2B;
        }
        .prog-card:hover .card-icon {
            transform: scale(1.05);
            background: #DCE8E0;
        }

        ::-webkit-scrollbar {
            width: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(30, 77, 43, 0.2);
            border-radius: 2px;
        }
    </style>
</head>

<body class="min-h-screen flex">

    <?php require 'sidebar.php'; ?>

    <div class="ml-64 flex-1 flex flex-col min-h-screen bg-sage">
        <header class="bg-white/80 backdrop-blur-sm border-b border-sage h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <span class="text-hunter-600 font-semibold">Request Budget</span>
            </div>
        </header>

        <main class="flex-1 p-6 overflow-y-auto">
            <div class="max-w-5xl mx-auto space-y-6">
                <div class="animate-fade-up">
                    <h1 class="text-2xl font-serif text-hunter-600">Request Budget Allocation</h1>
                    <p class="text-[14px] text-slate-500 mt-1">
                        Select a program to request a budget
                    </p>
                </div>

                <div class="animate-fade-up-1 grid grid-cols-2 sm:grid-cols-4 gap-4">

                    <!-- 4Ps -->
                    <a href="projectproposal.php?program_id=3" class="prog-card">
                        <div class="card-icon"><i class="fas fa-home"></i></div>
                        <p class="text-[14px] font-semibold text-hunter-600">4Ps</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Pantawid Pamilyang Pilipino Program</p>
                    </a>

                    <!-- SLP -->
                    <a href="projectproposal.php?program_id=4" class="prog-card">
                        <div class="card-icon"><i class="fas fa-hand-holding-usd"></i></div>
                        <p class="text-[14px] font-semibold text-hunter-600">SLP</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Sustainable Livelihood Program</p>
                    </a>

                    <!-- SFP -->
                    <a href="projectproposal.php?program_id=5" class="prog-card">
                        <div class="card-icon"><i class="fas fa-utensils"></i></div>
                        <p class="text-[14px] font-semibold text-hunter-600">SFP</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Supplementary Feeding Program</p>
                    </a>

                    <!-- Day Care -->
                    <a href="projectproposal.php?program_id=6" class="prog-card">
                        <div class="card-icon"><i class="fas fa-school"></i></div>
                        <p class="text-[14px] font-semibold text-hunter-600">Day Care</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Day Care Program</p>
                    </a>

                    <!-- Senior Citizen -->
                    <a href="projectproposal.php?program_id=8" class="prog-card">
                        <div class="card-icon"><i class="fas fa-user-friends"></i></div>
                        <p class="text-[14px] font-semibold text-hunter-600">Senior Citizens Program</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Pension & ID</p>
                    </a>

                    <!-- PWD -->
                    <a href="projectproposal.php?program_id=9" class="prog-card">
                        <div class="card-icon"><i class="fas fa-wheelchair"></i></div>
                        <p class="text-[14px] font-semibold text-hunter-600">PWD</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Persons with Disabilities Program</p>
                    </a>

                    <!-- Solo Parent -->
                    <a href="projectproposal.php?program_id=10" class="prog-card">
                        <div class="card-icon"><i class="fas fa-user"></i></div>
                        <p class="text-[14px] font-semibold text-hunter-600">Solo Parents Program</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">ID & Assistance</p>
                    </a>

                    <!-- Women & Child Protection -->
                    <a href="projectproposal.php?program_id=7" class="prog-card">
                        <div class="card-icon"><i class="fas fa-shield-alt"></i></div>
                        <p class="text-[14px] font-semibold text-hunter-600">Women & Child Protection</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Women & Child Protection Program</p>
                    </a>

                </div>

            </div>
        </main>

        <footer class="border-t border-sage bg-white/60 backdrop-blur-sm px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

</body>
</html>