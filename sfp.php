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
    <title>SFP Availment – MSWDO San Enrique</title>
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

        .nutri-opt {
            transition: all .15s;
            cursor: pointer;
        }

        .nutri-opt:hover {
            border-color: #94A3B8;
        }

        .nutri-opt.n-sel {
            border-color: #0B2545;
            background: #E8EDF5;
        }

        .safety-check {
            transition: all .15s;
            cursor: pointer;
        }

        .safety-check:has(input:checked) {
            border-color: #0B2545;
            background: #E8EDF5;
        }

        .copy-badge {
            display: inline-flex;
            padding: 1px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            background: #FEF3C7;
            color: #92400E;
            margin-left: 6px;
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

    <!-- ═══════════════════ SIDEBAR ═══════════════════ -->
    <?php require 'sidebar.php'; ?>

    <!-- Main -->
    <div class="ml-56 flex-1 flex flex-col min-h-screen">
        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <a href="#" class="text-slate-400 hover:text-navy-600">Clients</a>
                <span class="text-slate-300">/</span>
                <a href="#" class="text-slate-400 hover:text-navy-600">Program Avaiment</a>
                <span class="text-slate-300">/</span>
                <span class="text-navy-600 font-semibold">SFP Availment</span>
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
          <div class="flex items-center gap-2 mb-1">
            <span class="text-slate-300">·</span>
            <span class="text-[12px] text-slate-400">Supplementary Feeding Program</span>
          </div>
          <h1 class="text-xl font-serif text-navy-600">SFP Availment Form</h1>
          <p class="text-[13px] text-slate-500 mt-1">Enroll a child in the feeding program and record nutrition status
            per feeding cycle.</p>
        </div>

        <!-- Info banner -->
        <div class="animate-fade-up-1 bg-navy-50 border border-navy-100 rounded-xl px-4 py-3 flex items-start gap-3">
          <i class="fas fa-info-circle text-navy-500 text-lg mt-0.5"></i>
          <p class="text-[12px] text-navy-700">
            SFP runs for <strong class="font-semibold">180 days (6 months)</strong>.
            Record the child's baseline measurement at enrolment, then update
            <strong>monthly weight & height</strong> to monitor progress.
            Submit a separate record for each child.
          </p>
        </div>

        <!-- Transaction Details -->
        <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
          <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
            <div
              class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
              <i class="fas fa-child"></i>
            </div>
            <div>
              <h2 class="text-[14px] font-semibold text-navy-600">Transaction Details</h2>
              <p class="text-[11px] text-slate-400">Child information and feeding cycle dates</p>
            </div>
          </div>
          <div class="p-6 space-y-4">
            <div class="grid grid-cols-3 gap-4">
              <div class="col-span-2"><label class="field-label req">Child's Full Name</label><input type="text"
                  class="field" placeholder="Full name of child" id="childName"></div>
              <div><label class="field-label req">Age</label><input type="number" min="0" max="12" class="field"
                  placeholder="Auto-calculated" id="childAge" readonly></div>
            </div>
            <div class="grid grid-cols-3 gap-4">
              <div><label class="field-label">Birthdate</label><input type="date" class="field" id="birthdate"></div>
              <div><label class="field-label req">Sex</label>
                <div class="flex gap-2 mt-0.5">
                  <label
                    class="flex-1 flex items-center justify-center gap-1.5 border-2 border-slate-200 rounded-xl py-2.5 cursor-pointer text-[12px] font-medium text-slate-600 hover:border-navy-400 has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50 has-[:checked]:text-navy-700">
                    <input type="radio" name="childSex" value="Male" class="hidden"><i class="fas fa-mars"></i> Male
                  </label>
                  <label
                    class="flex-1 flex items-center justify-center gap-1.5 border-2 border-slate-200 rounded-xl py-2.5 cursor-pointer text-[12px] font-medium text-slate-600 hover:border-navy-400 has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50 has-[:checked]:text-navy-700">
                    <input type="radio" name="childSex" value="Female" class="hidden"><i class="fas fa-venus"></i>
                    Female
                  </label>
                </div>
              </div>
              
            </div>
            <div class="grid grid-cols-3 gap-4">
              <div><label class="field-label req">Day Care Center</label><select class="field" id="daycareCenter">
                  <option value="">Select</option>
                  <option>Brgy. Bagonawa Day Care</option>
                  <option>Brgy. Baliwagan Day Care</option>
                  <option>Brgy. Batuan Day Care</option>
                  <option>Brgy. Guintorilan Day Care</option>
                  <option>Brgy. Nayon Day Care</option>
                  <option>Brgy. Poblacion Day Care</option>
                  <option>Brgy. Sibucao Day Care</option>
                  <option>Brgy. Tabao Baybay Day Care</option>
                  <option>Brgy. Tabao Rizal Day Care</option>
                  <option>Brgy. Tibsoc Day Care</option>
                </select></div>
              <div><label class="field-label req">Feeding Start Date</label><input type="date" class="field"
                  id="feedingStartDate"></div>
              <div><label class="field-label">Feeding End Date</label><input type="date" class="field"
                  id="feedingEndDate"></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div><label class="field-label req">Feeding Days Completed</label><input type="number" min="0"
                  class="field" placeholder="Days attended" id="feedingDays"></div>
              <div><label class="field-label">Total Feeding Days</label>
                <input type="number" min="0" class="field bg-slate-100 text-slate-700 cursor-not-allowed" value="180"
                  readonly>
              </div>
            </div>
          </div>
        </div>

        <!-- Nutrition Progress – Baseline + Monthly Updates -->
        <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
          <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
            <div
              class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
              <i class="fas fa-weight-scale"></i>
            </div>
            <div>
              <h2 class="text-[14px] font-semibold text-navy-600">Nutrition Progress – Baseline &amp; Monthly Updates
              </h2>
              <p class="text-[11px] text-slate-400">Baseline before feeding, then monthly weight, height &amp; nutrition
                status for 6 months (180 days)</p>
            </div>
          </div>
          <div class="p-6 space-y-5">

            <!-- WHO reference note -->
            <div class="bg-navy-50 border border-navy-100 rounded-xl px-4 py-3 flex items-start gap-3">
              <i class="fas fa-book-open text-navy-500 text-lg mt-0.5"></i>
              <p class="text-[12px] text-navy-700">
                <strong>Classify nutritional status</strong> using the WHO Child Growth Standards table (boys/girls
                24–60 months).<br>
                <span class="text-[11px] text-navy-500">Refer to the official NNC/DOH tables for weight‑for‑height
                  cut‑offs.</span>
              </p>
            </div>

            <!-- Baseline (before feeding) -->
            <div>
              <h3 class="text-[13px] font-semibold text-navy-600 mb-3 flex items-center gap-2">
                <i class="fas fa-clock text-navy-400"></i> Baseline (Before Feeding Starts)
              </h3>
              <div class="grid grid-cols-3 gap-4">
                <div>
                  <label class="field-label req">Weight (kg)</label>
                  <input type="number" step="0.1" min="0" class="field" placeholder="e.g. 14.5" id="baseWeight">
                </div>
                <div>
                  <label class="field-label req">Height (cm)</label>
                  <input type="number" step="0.1" min="0" class="field" placeholder="e.g. 95.0" id="baseHeight">
                </div>
                <div>
                  <label class="field-label req">Date Measured</label>
                  <input type="date" class="field" id="baseDate">
                </div>
              </div>
            </div>

            <!-- Monthly progress (Month 1 to 6) -->
            <div>
              <h3 class="text-[13px] font-semibold text-navy-600 mb-3 flex items-center gap-2">
                <i class="fas fa-chart-line text-navy-400"></i> Monthly Progress (6 months / 180 days)
              </h3>
              <div class="overflow-x-auto">
                <table class="w-full text-[12px] border-collapse">
                  <thead>
                    <tr class="bg-slate-50 text-slate-500 text-[10px] uppercase tracking-wide">
                      <th class="px-3 py-2 text-left font-semibold">Month</th>
                      <th class="px-3 py-2 text-left font-semibold">Weight (kg)</th>
                      <th class="px-3 py-2 text-left font-semibold">Height (cm)</th>
                      <th class="px-3 py-2 text-left font-semibold">Date</th>
                      <th class="px-3 py-2 text-left font-semibold">Nutrition Status</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100" id="monthlyBody">
                    <!-- Month 1 -->
                    <tr>
                      <td class="px-3 py-2 font-medium text-navy-600">Month 1</td>
                      <td class="px-3 py-2"><input type="number" step="0.1" min="0"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400"
                          placeholder="kg"></td>
                      <td class="px-3 py-2"><input type="number" step="0.1" min="0"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400"
                          placeholder="cm"></td>
                      <td class="px-3 py-2"><input type="date"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400">
                      </td>
                      <td class="px-3 py-2">
                        <select
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400 bg-white">
                          <option value="">Select</option>
                          <option>Severely Wasted</option>
                          <option>Wasted</option>
                          <option>Normal</option>
                          <option>Overweight</option>
                          <option>Obese</option>
                        </select>
                      </td>
                    </tr>
                    <!-- Month 2 -->
                    <tr>
                      <td class="px-3 py-2 font-medium text-navy-600">Month 2</td>
                      <td class="px-3 py-2"><input type="number" step="0.1" min="0"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400"
                          placeholder="kg"></td>
                      <td class="px-3 py-2"><input type="number" step="0.1" min="0"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400"
                          placeholder="cm"></td>
                      <td class="px-3 py-2"><input type="date"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400">
                      </td>
                      <td class="px-3 py-2">
                        <select
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400 bg-white">
                          <option value="">Select</option>
                          <option>Severely Wasted</option>
                          <option>Wasted</option>
                          <option>Normal</option>
                          <option>Overweight</option>
                          <option>Obese</option>
                        </select>
                      </td>
                    </tr>
                    <!-- Month 3 -->
                    <tr>
                      <td class="px-3 py-2 font-medium text-navy-600">Month 3</td>
                      <td class="px-3 py-2"><input type="number" step="0.1" min="0"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400"
                          placeholder="kg"></td>
                      <td class="px-3 py-2"><input type="number" step="0.1" min="0"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400"
                          placeholder="cm"></td>
                      <td class="px-3 py-2"><input type="date"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400">
                      </td>
                      <td class="px-3 py-2">
                        <select
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400 bg-white">
                          <option value="">Select</option>
                          <option>Severely Wasted</option>
                          <option>Wasted</option>
                          <option>Normal</option>
                          <option>Overweight</option>
                          <option>Obese</option>
                        </select>
                      </td>
                    </tr>
                    <!-- Month 4 -->
                    <tr>
                      <td class="px-3 py-2 font-medium text-navy-600">Month 4</td>
                      <td class="px-3 py-2"><input type="number" step="0.1" min="0"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400"
                          placeholder="kg"></td>
                      <td class="px-3 py-2"><input type="number" step="0.1" min="0"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400"
                          placeholder="cm"></td>
                      <td class="px-3 py-2"><input type="date"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400">
                      </td>
                      <td class="px-3 py-2">
                        <select
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400 bg-white">
                          <option value="">Select</option>
                          <option>Severely Wasted</option>
                          <option>Wasted</option>
                          <option>Normal</option>
                          <option>Overweight</option>
                          <option>Obese</option>
                        </select>
                      </td>
                    </tr>
                    <!-- Month 5 -->
                    <tr>
                      <td class="px-3 py-2 font-medium text-navy-600">Month 5</td>
                      <td class="px-3 py-2"><input type="number" step="0.1" min="0"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400"
                          placeholder="kg"></td>
                      <td class="px-3 py-2"><input type="number" step="0.1" min="0"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400"
                          placeholder="cm"></td>
                      <td class="px-3 py-2"><input type="date"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400">
                      </td>
                      <td class="px-3 py-2">
                        <select
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400 bg-white">
                          <option value="">Select</option>
                          <option>Severely Wasted</option>
                          <option>Wasted</option>
                          <option>Normal</option>
                          <option>Overweight</option>
                          <option>Obese</option>
                        </select>
                      </td>
                    </tr>
                    <!-- Month 6 -->
                    <tr>
                      <td class="px-3 py-2 font-medium text-navy-600">Month 6</td>
                      <td class="px-3 py-2"><input type="number" step="0.1" min="0"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400"
                          placeholder="kg"></td>
                      <td class="px-3 py-2"><input type="number" step="0.1" min="0"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400"
                          placeholder="cm"></td>
                      <td class="px-3 py-2"><input type="date"
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400">
                      </td>
                      <td class="px-3 py-2">
                        <select
                          class="w-full py-1 px-2 border border-slate-200 rounded-lg text-[12px] focus:outline-none focus:border-navy-400 bg-white">
                          <option value="">Select</option>
                          <option>Severely Wasted</option>
                          <option>Wasted</option>
                          <option>Normal</option>
                          <option>Overweight</option>
                          <option>Obese</option>
                        </select>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Overall nutrition classification (after 6 months) -->
            <div class="pt-3 border-t border-slate-100">
              <p class="text-[12px] font-semibold text-navy-600 mb-3">Overall Nutrition Status (after 6 months)</p>
              <div class="grid grid-cols-5 gap-3" id="nutriSelector">
                <div onclick="setNutri(this,'Severely Wasted')"
                  class="nutri-opt border-2 border-slate-200 rounded-2xl p-3 text-center">
                  <i class="fas fa-exclamation-circle text-navy-400 text-2xl mb-1"></i>
                  <p class="text-[11px] font-semibold text-slate-700">Severely Wasted</p>
                </div>
                <div onclick="setNutri(this,'Wasted')"
                  class="nutri-opt border-2 border-slate-200 rounded-2xl p-3 text-center">
                  <i class="fas fa-exclamation-triangle text-navy-500 text-2xl mb-1"></i>
                  <p class="text-[11px] font-semibold text-slate-700">Wasted</p>
                </div>
                <div onclick="setNutri(this,'Normal')"
                  class="nutri-opt border-2 border-slate-200 rounded-2xl p-3 text-center">
                  <i class="fas fa-check-circle text-navy-600 text-2xl mb-1"></i>
                  <p class="text-[11px] font-semibold text-slate-700">Normal</p>
                </div>
                <div onclick="setNutri(this,'Overweight')"
                  class="nutri-opt border-2 border-slate-200 rounded-2xl p-3 text-center">
                  <i class="fas fa-chart-bar text-navy-600 text-2xl mb-1"></i>
                  <p class="text-[11px] font-semibold text-slate-700">Overweight</p>
                </div>
                <div onclick="setNutri(this,'Obese')"
                  class="nutri-opt border-2 border-slate-200 rounded-2xl p-3 text-center">
                  <i class="fas fa-weight-scale text-navy-600 text-2xl mb-1"></i>
                  <p class="text-[11px] font-semibold text-slate-700">Obese</p>
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="flex justify-end gap-3">
          <button onclick="saveComplete()"
            class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500">Submit SFP
            Record</button>
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
    // Auto-calculate age from birthdate and feeding start date
    function calculateAge() {
      const birthdate = document.getElementById('birthdate').value;
      const startDate = document.getElementById('feedingStartDate').value;
      const ageField = document.getElementById('childAge');

      if (birthdate && startDate) {
        const birth = new Date(birthdate);
        const start = new Date(startDate);
        if (start < birth) {
          ageField.value = '';
          showToast('Feeding start date cannot be before birthdate.', 'error');
          return;
        }
        let age = start.getFullYear() - birth.getFullYear();
        const monthDiff = start.getMonth() - birth.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && start.getDate() < birth.getDate())) {
          age--;
        }
        if (age < 0) age = 0;
        ageField.value = age;
      } else {
        ageField.value = '';
      }
    }

    // Enforce feeding end date after start date
    function updateEndDateMin() {
      const startDate = document.getElementById('feedingStartDate').value;
      const endDateInput = document.getElementById('feedingEndDate');
      if (startDate) {
        endDateInput.min = startDate;
        // If current end date is before start, clear it
        if (endDateInput.value && endDateInput.value < startDate) {
          endDateInput.value = '';
          showToast('Feeding end date cannot be before start date. Cleared.', 'error');
        }
      } else {
        endDateInput.min = '';
      }
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function () {
      const birthdateEl = document.getElementById('birthdate');
      const startDateEl = document.getElementById('feedingStartDate');

      birthdateEl.addEventListener('change', calculateAge);
      startDateEl.addEventListener('change', function () {
        calculateAge();
        updateEndDateMin();
      });

      // Initial min for end date if start date pre-filled
      updateEndDateMin();
    });

    // Nutrition selector
    function setNutri(el, label) {
      document.querySelectorAll('#nutriSelector .nutri-opt').forEach(e => e.classList.remove('n-sel'));
      el.classList.add('n-sel');
    }

    // Toast
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

    // Validate and submit
    function saveComplete() {
      const errors = [];

      // Child's Full Name
      const childName = document.getElementById('childName');
      if (!childName.value.trim()) {
        errors.push({ field: childName, msg: 'Please enter the child\'s full name.' });
      }

      // Age (auto-calculated, must not be empty)
      const ageField = document.getElementById('childAge');
      if (!ageField.value) {
        errors.push({ field: document.getElementById('birthdate'), msg: 'Please provide birthdate and feeding start date to calculate age.' });
      } else if (parseInt(ageField.value) > 12) {
        errors.push({ field: ageField, msg: 'Child age must be 0–12 years for SFP.' });
      }

      // Sex
      const sexSelected = document.querySelector('input[name="childSex"]:checked');
      if (!sexSelected) {
        errors.push({ field: document.querySelector('input[name="childSex"]'), msg: 'Please select the child\'s sex.' });
      }

      // Day Care Center
      const daycare = document.getElementById('daycareCenter');
      if (!daycare.value) {
        errors.push({ field: daycare, msg: 'Please select a Day Care Center.' });
      }

      // Feeding Start Date
      const startDate = document.getElementById('feedingStartDate');
      if (!startDate.value) {
        errors.push({ field: startDate, msg: 'Please select Feeding Start Date.' });
      }

      // Check if end date is before start date (should not happen due to min, but extra safety)
      const endDate = document.getElementById('feedingEndDate').value;
      if (endDate && startDate.value && endDate < startDate.value) {
        errors.push({ field: document.getElementById('feedingEndDate'), msg: 'Feeding End Date cannot be before Start Date.' });
      }

      // Baseline weight
      const baseWeight = document.getElementById('baseWeight');
      if (!baseWeight.value || parseFloat(baseWeight.value) <= 0) {
        errors.push({ field: baseWeight, msg: 'Please enter baseline weight.' });
      }

      // Baseline height
      const baseHeight = document.getElementById('baseHeight');
      if (!baseHeight.value || parseFloat(baseHeight.value) <= 0) {
        errors.push({ field: baseHeight, msg: 'Please enter baseline height.' });
      }

      // Baseline date
      const baseDate = document.getElementById('baseDate');
      if (!baseDate.value) {
        errors.push({ field: baseDate, msg: 'Please select baseline measurement date.' });
      }

      if (errors.length > 0) {
        const first = errors[0];
        showToast(first.msg, 'error');
        first.field.focus();
        first.field.style.borderColor = '#EF4444';
        setTimeout(() => { first.field.style.borderColor = ''; }, 2000);
        return;
      }

      showToast('SFP record submitted successfully!');
    }

    function saveDraft() { showToast('Draft saved!'); }
  </script>
</body>

</html>