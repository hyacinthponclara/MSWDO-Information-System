<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';

function fiscalYearJsonResponse(bool $success, string $message = '', array $extra = []): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        array_merge(['success' => $success, 'message' => $message], $extra),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fy_action'])) {
    $action = trim($_POST['fy_action']);
    $actorUserId = (int)($_SESSION['user_id'] ?? 0);

    try {
        if ($action === 'save_budgets') {
            $fyId = (int)($_POST['fiscal_year_id'] ?? 0);
            $budgets = json_decode($_POST['budgets'] ?? '[]', true);

            if ($fyId <= 0 || !is_array($budgets)) {
                fiscalYearJsonResponse(false, 'Invalid fiscal year budget data.');
            }

            $fyStmt = $pdo->prepare("SELECT fiscal_year_id, fy_year, fy_status FROM fiscal_year WHERE fiscal_year_id = ? FOR UPDATE");
            $fyStmt->execute([$fyId]);
            $fy = $fyStmt->fetch(PDO::FETCH_ASSOC);

            if (!$fy) {
                fiscalYearJsonResponse(false, 'Fiscal year not found.');
            }

            if ($fy['fy_status'] === 'Archived') {
                fiscalYearJsonResponse(false, 'Archived fiscal years are read-only and cannot be edited.');
            }

            $pdo->beginTransaction();

            $updateFyBudget = $pdo->prepare("UPDATE fiscal_year_budget SET annual_budget = ? WHERE fiscal_year_id = ? AND program_id = ?");
            $updateProgram = $pdo->prepare("UPDATE program SET prog_annual_budget = ?, prog_remaining_budget = ? WHERE program_id = ?");

            foreach ($budgets as $item) {
                $programId = (int)($item['program_id'] ?? 0);
                $budget = round((float)($item['budget'] ?? 0), 2);

                if ($programId <= 0 || $budget < 0) {
                    throw new RuntimeException('One or more budget entries are invalid.');
                }

                $updateFyBudget->execute([$budget, $fyId, $programId]);

                if ($updateFyBudget->rowCount() === 0) {
                    $check = $pdo->prepare("SELECT COUNT(*) FROM fiscal_year_budget WHERE fiscal_year_id = ? AND program_id = ?");
                    $check->execute([$fyId, $programId]);
                    if ((int)$check->fetchColumn() === 0) {
                        $insert = $pdo->prepare("INSERT INTO fiscal_year_budget (fiscal_year_id, program_id, annual_budget) VALUES (?, ?, ?)");
                        $insert->execute([$fyId, $programId, $budget]);
                    }
                }

                /* Keep the live Budget Management page synchronized only for the active FY. */
                if ($fy['fy_status'] === 'Active') {
                    $spentStmt = $pdo->prepare("SELECT
                        COALESCE((SELECT SUM(av_amount) FROM availment
                            WHERE program_id = ? AND av_status = 'Released'
                              AND av_date_released IS NOT NULL
                              AND YEAR(av_date_released) = ?), 0)
                        +
                        COALESCE((SELECT SUM(pp_budget) FROM project_proposal
                            WHERE program_id = ? AND pp_status = 'Released'
                              AND pp_date_released IS NOT NULL
                              AND YEAR(pp_date_released) = ?), 0)");
                    $spentStmt->execute([$programId, (int)$fy['fy_year'], $programId, (int)$fy['fy_year']]);
                    $spent = (float)$spentStmt->fetchColumn();
                    $remaining = max(0, $budget - $spent);
                    $updateProgram->execute([$budget, $remaining, $programId]);
                }
            }

            $pdo->commit();
            fiscalYearJsonResponse(true, 'Fiscal year budgets saved successfully.');
        }

        if ($action === 'initialize_fy') {
            $year = (int)($_POST['year'] ?? 0);
            $start = trim($_POST['start'] ?? '');
            $end = trim($_POST['end'] ?? '');
            $budgets = json_decode($_POST['budgets'] ?? '[]', true);

            $currentCalendarYear = (int)date('Y');
            if ($year < $currentCalendarYear + 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
                fiscalYearJsonResponse(false, 'Invalid fiscal year or dates.');
            }
            if ($start >= $end) {
                fiscalYearJsonResponse(false, 'End date must be after start date.');
            }
            if (!is_array($budgets) || count($budgets) === 0) {
                fiscalYearJsonResponse(false, 'No program budgets were supplied.');
            }

            $exists = $pdo->prepare("SELECT fiscal_year_id FROM fiscal_year WHERE fy_year = ? LIMIT 1");
            $exists->execute([$year]);
            if ($exists->fetchColumn()) {
                fiscalYearJsonResponse(false, "FY {$year} already exists.");
            }

            $pdo->beginTransaction();

            /* Future FY is Planning. The current active FY is NOT archived prematurely. */
            $insertFy = $pdo->prepare("INSERT INTO fiscal_year (fy_year, fy_start_date, fy_end_date, fy_status, created_by) VALUES (?, ?, ?, 'Planning', ?)");
            $insertFy->execute([$year, $start, $end, $actorUserId > 0 ? $actorUserId : null]);
            $fyId = (int)$pdo->lastInsertId();

            $insertBudget = $pdo->prepare("INSERT INTO fiscal_year_budget (fiscal_year_id, program_id, annual_budget) VALUES (?, ?, ?)");
            $totalBudget = 0;
            $fundedPrograms = 0;

            foreach ($budgets as $item) {
                $programId = (int)($item['program_id'] ?? 0);
                $budget = round((float)($item['budget'] ?? 0), 2);
                if ($programId <= 0 || $budget < 0) {
                    throw new RuntimeException('One or more new fiscal-year budgets are invalid.');
                }
                $insertBudget->execute([$fyId, $programId, $budget]);
                $totalBudget += $budget;
                if ($budget > 0) $fundedPrograms++;
            }

            $pdo->commit();

            fiscalYearJsonResponse(true, "FY {$year} initialized as Planning.", [
                'fiscal_year_id' => $fyId,
                'year' => $year,
                'total_budget' => $totalBudget,
                'funded_programs' => $fundedPrograms,
                'status' => 'Planning'
            ]);
        }


        if ($action === 'activate_fy') {
            $fyId = (int)($_POST['fiscal_year_id'] ?? 0);

            if ($fyId <= 0) {
                fiscalYearJsonResponse(false, 'Invalid fiscal year.');
            }

            $pdo->beginTransaction();

            $fyStmt = $pdo->prepare("
                SELECT fiscal_year_id, fy_year, fy_start_date, fy_end_date, fy_status
                FROM fiscal_year
                WHERE fiscal_year_id = ?
                FOR UPDATE
            ");
            $fyStmt->execute([$fyId]);
            $targetFY = $fyStmt->fetch(PDO::FETCH_ASSOC);

            if (!$targetFY) {
                throw new RuntimeException('Fiscal year not found.');
            }

            if ($targetFY['fy_status'] !== 'Planning') {
                throw new RuntimeException('Only a Planning fiscal year can be activated.');
            }

            $today = date('Y-m-d');

            if ($today < $targetFY['fy_start_date']) {
                throw new RuntimeException(
                    'FY ' . $targetFY['fy_year'] .
                    ' cannot be activated yet. It starts on ' .
                    $targetFY['fy_start_date'] . '.'
                );
            }

            /*
             * There must be exactly one active FY after activation.
             * The current Active FY is archived only when the new FY
             * is actually activated.
             */
            $pdo->exec("
                UPDATE fiscal_year
                SET fy_status = 'Archived'
                WHERE fy_status = 'Active'
            ");

            $activate = $pdo->prepare("
                UPDATE fiscal_year
                SET fy_status = 'Active'
                WHERE fiscal_year_id = ?
            ");
            $activate->execute([$fyId]);

            /*
             * Synchronize the newly active FY budgets to PROGRAM so
             * Budget Management uses the newly active annual allocations.
             *
             * Spending is calculated only from RELEASED transactions
             * inside the activated fiscal-year dates.
             */
            $budgetStmt = $pdo->prepare("
                SELECT
                    fb.program_id,
                    fb.annual_budget,
                    COALESCE((
                        SELECT SUM(a.av_amount)
                        FROM availment a
                        WHERE a.program_id = fb.program_id
                          AND a.av_status = 'Released'
                          AND a.av_date_released IS NOT NULL
                          AND a.av_date_released >= ?
                          AND a.av_date_released < DATE_ADD(?, INTERVAL 1 DAY)
                    ), 0)
                    +
                    COALESCE((
                        SELECT SUM(pp.pp_budget)
                        FROM project_proposal pp
                        WHERE pp.program_id = fb.program_id
                          AND pp.pp_status = 'Released'
                          AND pp.pp_date_released IS NOT NULL
                          AND pp.pp_date_released >= ?
                          AND pp.pp_date_released < DATE_ADD(?, INTERVAL 1 DAY)
                    ), 0) AS spent
                FROM fiscal_year_budget fb
                WHERE fb.fiscal_year_id = ?
            ");

            $budgetStmt->execute([
                $targetFY['fy_start_date'],
                $targetFY['fy_end_date'],
                $targetFY['fy_start_date'],
                $targetFY['fy_end_date'],
                $fyId
            ]);

            $updateProgram = $pdo->prepare("
                UPDATE program
                SET
                    prog_annual_budget = ?,
                    prog_remaining_budget = ?,
                    prog_current_period = 1,
                    prog_early_end_count = 0,
                    prog_period_started_at = ?
                WHERE program_id = ?
            ");

            foreach ($budgetStmt->fetchAll(PDO::FETCH_ASSOC) as $budgetRow) {
                $annualBudget = round((float)$budgetRow['annual_budget'], 2);
                $spent = round((float)$budgetRow['spent'], 2);
                $remaining = max(0, $annualBudget - $spent);

                $updateProgram->execute([
                    $annualBudget,
                    $remaining,
                    $targetFY['fy_start_date'] . ' 00:00:00',
                    (int)$budgetRow['program_id']
                ]);
            }

            $pdo->commit();

            fiscalYearJsonResponse(
                true,
                "FY {$targetFY['fy_year']} is now Active. The previous active fiscal year was archived.",
                [
                    'fiscal_year_id' => $fyId,
                    'year' => (int)$targetFY['fy_year'],
                    'status' => 'Active'
                ]
            );
        }

        fiscalYearJsonResponse(false, 'Unknown fiscal-year action.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        fiscalYearJsonResponse(false, $e->getMessage());
    }
}

/* Load all FY/program budget rows. Spending is calculated from actual released transactions. */
$fiscalYearStmt = $pdo->query(" 
    SELECT
        fy.fiscal_year_id,
        fy.fy_year,
        fy.fy_start_date,
        fy.fy_end_date,
        fy.fy_status,
        fb.fy_budget_id,
        p.program_id,
        p.program_name,
        p.prog_funding_source,
        p.prog_period,
        fb.annual_budget,
        COALESCE((
            SELECT SUM(a.av_amount)
            FROM availment a
            WHERE a.program_id = p.program_id
              AND a.av_status = 'Released'
              AND a.av_date_released IS NOT NULL
              AND a.av_date_released >= fy.fy_start_date
              AND a.av_date_released < DATE_ADD(fy.fy_end_date, INTERVAL 1 DAY)
        ), 0)
        + COALESCE((
            SELECT SUM(pp.pp_budget)
            FROM project_proposal pp
            WHERE pp.program_id = p.program_id
              AND pp.pp_status = 'Released'
              AND pp.pp_date_released IS NOT NULL
              AND pp.pp_date_released >= fy.fy_start_date
              AND pp.pp_date_released < DATE_ADD(fy.fy_end_date, INTERVAL 1 DAY)
        ), 0) AS spent
    FROM fiscal_year_budget fb
    INNER JOIN fiscal_year fy ON fy.fiscal_year_id = fb.fiscal_year_id
    INNER JOIN program p ON p.program_id = fb.program_id
    ORDER BY fy.fy_year DESC, p.program_id ASC
");

$fiscalYearRows = $fiscalYearStmt->fetchAll(PDO::FETCH_ASSOC);
$fiscalYearsPhp = [];

foreach ($fiscalYearRows as $row) {
    $fyId = (int)$row['fiscal_year_id'];
    if (!isset($fiscalYearsPhp[$fyId])) {
        $fiscalYearsPhp[$fyId] = [
            'id' => $fyId,
            'year' => (int)$row['fy_year'],
            'start' => $row['fy_start_date'],
            'end' => $row['fy_end_date'],
            'status' => $row['fy_status'],
            'totalBudget' => 0.0,
            'totalSpent' => 0.0,
            'programs' => []
        ];
    }

    $budget = (float)$row['annual_budget'];
    $spent = (float)$row['spent'];

    $fiscalYearsPhp[$fyId]['programs'][] = [
        'programId' => (int)$row['program_id'],
        'program' => $row['program_name'],
        'source' => $row['prog_funding_source'] ?: 'LGU',
        'period' => $row['prog_period'],
        'budget' => $budget,
        'spent' => $spent
    ];
    $fiscalYearsPhp[$fyId]['totalBudget'] += $budget;
    $fiscalYearsPhp[$fyId]['totalSpent'] += $spent;
}

$fiscalYearsPhp = array_values($fiscalYearsPhp);
$activeFyIndex = 0;
foreach ($fiscalYearsPhp as $i => $fy) {
    if ($fy['status'] === 'Active') {
        $activeFyIndex = $i;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fiscal Year Management – MSWDO San Enrique</title>
    <script src="https://cdn.tailwindcss.com">
    </script>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js">
    </script>
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
                        slate2: '#F4F7FC',
                    },
                    keyframes: {
                        fadeUp: {
                            '0%': { opacity: '0', transform: 'translateY(12px)' }, '100%': {
                                opacity: '1',
                                transform: 'translateY(0)'
                            }
                        },
                        modalIn: {
                            '0%': { opacity: '0', transform: 'scale(0.95) translateY(10px)' },
                            '100%': { opacity: '1', transform: 'scale(1) translateY(0)' }
                        },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.4s ease both',
                        'fade-up-1': 'fadeUp 0.4s ease 0.05s both',
                        'fade-up-2': 'fadeUp 0.4s ease 0.1s both',
                        'modal-in': 'modalIn 0.3s ease both',
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
            transition: all .15s ease;
        }

        .sidebar-item:hover {
            background: rgba(255, 255, 255, .07);
            color: rgba(255, 255, 255, .95);
        }

        .sidebar-item.active {
            background: rgba(26, 92, 58, .25);
            border-left-color: #C49A2A;
            color: #fff;
        }

        @media (max-width: 768px) {
            .ml-64 {
                margin-left: 0 !important;
            }

            .desktop-sidebar {
                display: none !important;
            }
        }

        @media (min-width: 769px) {
            .desktop-sidebar {
                display: block !important;
            }
        }

        .stat-card {
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(26, 92, 58, .1);
        }

        .btn-action {
            transition: all .15s ease;
        }

        .btn-action:hover {
            transform: translateY(-1px);
        }

        .table-row {
            transition: background .12s;
        }

        .table-row:hover {
            background: #EEF6F0;
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
            min-height: 60px;
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

        .modal-backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(26, 92, 58, .2);
            border-radius: 2px;
        }

        .fy-card {
            transition: all .2s ease;
            cursor: pointer;
        }

        .fy-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(26, 92, 58, .1);
        }

        .fy-card.active-fy {
            border-color: #1A5C3A;
            background: #EEF6F0;
        }

        .badge-active {
            background: #D1FAE5;
            color: #059669;
        }

        .badge-archived {
            background: #E2E8F0;
            color: #475569;
        }

        .upload-zone {
            border: 2px dashed #D4E8DC;
            border-radius: 1rem;
            padding: 1rem;
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

        .upload-zone input[type=file] {
            display: none;
        }

        input[type=number]::-webkit-outer-spin-button,
        input[type=number]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
            appearance: textfield;
        }

        .budget-input {
            font-feature-settings: "tnum";
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border: 1px solid #D4E8DC;
            border-radius: 0.75rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            z-index: 10;
            min-width: 160px;
            overflow: hidden;
            margin-top: 4px;
        }

        .dropdown-menu.open {
            display: block;
        }

        .dropdown-item {
            display: block;
            padding: 8px 16px;
            font-size: 12px;
            color: #1e293b;
            cursor: pointer;
            transition: background .15s;
        }

        .dropdown-item:hover {
            background: #EEF6F0;
        }

        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            border: 2px solid #D4E8DC;
            color: #94A3B8;
            transition: all .3s;
        }

        .step-circle.active {
            border-color: #1A5C3A;
            background: #1A5C3A;
            color: #fff;
        }

        .step-circle.done {
            border-color: #1A5C3A;
            background: #EEF6F0;
            color: #1A5C3A;
        }

        .step-line {
            flex: 1;
            height: 2px;
            background: #D4E8DC;
            transition: background .3s;
        }

        .step-line.active {
            background: #1A5C3A;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
                font-family: Arial, sans-serif !important;
                font-size: 11pt !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .print-header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                text-align: center;
                font-size: 10pt;
                font-weight: 700;
                color: #1A5C3A;
                border-bottom: 2px solid #1A5C3A;
                padding-bottom: 6px;
                margin: 0 0.5in;
                background: #fff;
                z-index: 9999;
                line-height: 1.4;
            }

            .print-header .sub {
                font-size: 9pt;
                font-weight: 400;
                color: #555;
            }

            .print-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                text-align: center;
                font-size: 9pt;
                color: #888;
                border-top: 1px solid #ccc;
                padding-top: 6px;
                margin: 0 0.5in;
                background: #fff;
                z-index: 9999;
            }

            .print-doc {
                padding: 0.7in 0.5in 0.5in 0.5in !important;
                margin-top: 0 !important;
            }

            .print-doc h1 {
                font-size: 14pt !important;
                font-weight: 700 !important;
                text-align: center;
                margin-bottom: 4px;
            }

            .print-doc .subtitle {
                text-align: center;
                font-size: 11pt;
                margin-bottom: 16px;
            }

            .print-doc table {
                font-size: 10pt !important;
                width: 100%;
                border-collapse: collapse;
            }

            .print-doc table th,
            .print-doc table td {
                border: 1px solid #000;
                padding: 6px 8px;
                text-align: left;
            }

            .print-doc table th {
                background: #f0f0f0;
                font-weight: 700;
            }

            .print-doc .text-right {
                text-align: right;
            }

            .print-doc .header-text {
                font-size: 11pt !important;
            }

            @page {
                size: letter;
                margin: 0.6in 0.5in 0.6in 0.5in;
            }
        }

        @media screen {
            .print-header {
                display: none;
            }

            .print-footer {
                display: none;
            }
        }
    </style>
</head>

<body class="bg-slate2 min-h-screen flex">

    <!-- ══════════════════════════ SIDEBAR ══════════════════════════ -->
    <?php require 'sidebar.php'; ?>

    <!-- ══════════════════════════ MAIN CONTENT ══════════════════════════ -->
    <div class="ml-64 flex-1 flex flex-col min-h-screen" id="mainContent">

        <!-- Print Header -->
        <div class="print-header">
            Republic of the Philippines – Province of Negros Occidental<br />
            <span class="sub">Municipal Social Welfare and Development Office – San Enrique<br />Fiscal Year Budget
                Report</span>
        </div>

        <!-- Print Footer -->
        <div class="print-footer">
            Generated on: <span id="printDate"></span> &nbsp;|&nbsp; Page <span id="printPage"></span>
            <br /><span style="font-size:8pt;color:#aaa;">This is a computer-generated document. No signature
                required.</span>
        </div>

        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-4 md:px-6 sticky top-0 z-20 no-print">
            <div class="flex items-center gap-3">
                <span class="text-green-600 font-semibold text-[13px] md:text-[15px]">Fiscal Year Management</span>
                <span class="text-slate-400 text-xs hidden sm:block" id="currentDate"></span>
            </div>
            <div class="flex items-center gap-2">
            </div>
        </header>

        <main class="flex-1 p-4 md:p-6 space-y-5 overflow-y-auto print-doc">

            <!-- Page Title -->
            <div class="flex flex-wrap items-center justify-between gap-3 animate-fade-up no-print">
                <div>
                    <h1 class="text-xl font-serif text-green-600">Fiscal Year Management</h1>
                    <p class="text-[13px] text-slate-500 mt-0.5">Manage budget cycles. Fiscal year runs from
                        <strong>January 1 to December 31</strong>.
                    </p>
                </div>
                <button onclick="openNewFYModal()"
                    class="btn-action text-[12px] font-semibold text-white bg-green-600 rounded-lg px-4 py-2 hover:bg-green-700 transition-all flex items-center gap-2">
                    <i class="fas fa-plus"></i> Initialize New Fiscal Year
                </button>
            </div>

            <!-- Active Fiscal Year Card -->
            <div class="animate-fade-up-1 no-print">
                <div class="bg-white rounded-2xl border-2 border-green-500 p-4 md:p-5 relative overflow-hidden">
                    <div
                        class="absolute top-0 right-0 bg-green-500 text-white text-[10px] font-bold px-3 py-1 rounded-bl-lg">
                         ACTIVE
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Active Fiscal Year</p>
                            <h2 class="text-xl md:text-2xl font-bold text-green-700" id="activeFYLabel">FY 2026</h2>
                            <p class="text-[12px] text-slate-500 mt-1" id="activeFYPeriod">—</p>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 w-full md:w-auto">
                            <div class="text-center">
                                <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total Budget</p>
                                <p class="text-lg md:text-xl font-bold text-green-600" id="activeTotalBudget">₱0.00</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total Spent</p>
                                <p class="text-lg md:text-xl font-bold text-amber-600" id="activeTotalSpent">₱0.00</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] text-slate-400 uppercase tracking-wider">Remaining</p>
                                <p class="text-lg md:text-xl font-bold text-blue-600" id="activeTotalRemaining">₱0.00</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] text-slate-400 uppercase tracking-wider">Utilization</p>
                                <p class="text-lg md:text-xl font-bold text-green-600" id="activeUtilization">0%</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <div class="bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div id="activeUtilizationBar" class="h-2 rounded-full bg-green-500" style="width:0%"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                            <span>0%</span>
                            <span id="activeUtilizationLabel">0% utilized</span>
                            <span>100%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fiscal Year Cards (sorted recent first) -->
            <div class="animate-fade-up-2 no-print">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-[13px] font-semibold text-green-600">Fiscal Year History</h2>
                    <span class="text-[11px] text-slate-400">Click any year to view its budget breakdown</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4" id="fyCards">
                    <!-- Injected by JS -->
                </div>
            </div>

            <!-- Budget Table for Selected FY -->
            <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div
                    class="flex flex-wrap items-center justify-between gap-2 px-4 md:px-5 py-4 border-b border-slate-100 no-print">
                    <div class="flex items-center gap-2">
                        <h2 class="text-[13px] font-semibold text-green-600" id="selectedFYTitle">Fiscal Year Budget Breakdown</h2>
                        <span
                            class="bg-green-100 text-green-700 text-[10px] font-semibold px-2 py-0.5 rounded-full">Active</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Export Dropdown -->
                        <div class="relative">
                            <button onclick="toggleExportDropdown()"
                                class="text-[11px] font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg px-3 py-1 hover:bg-blue-100 transition-all flex items-center gap-1">
                                <i class="fas fa-file-export mr-1"></i> Export
                                <i class="fas fa-chevron-down text-[8px] ml-1"></i>
                            </button>
                            <div id="exportDropdown" class="dropdown-menu">
                                <span class="dropdown-item" onclick="exportBudget('pdf')"><i
                                        class="fas fa-file-pdf mr-2 text-red-500"></i> PDF (Download)</span>
                                <span class="dropdown-item" onclick="exportBudget('doc')"><i
                                        class="fas fa-file-word mr-2 text-blue-600"></i> DOC (Download)</span>
                                <span class="dropdown-item" onclick="exportBudget('xlsx')"><i
                                        class="fas fa-file-excel mr-2 text-green-600"></i> XLSX (Download)</span>
                            </div>
                        </div>
                        <button id="editSelectedFYButton" onclick="openEditBudgetModal()"
                            class="hidden text-[11px] font-semibold text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-1 hover:bg-green-100 transition-all items-center gap-1">
                            <i class="fas fa-pen-to-square"></i> Edit Budgets
                        </button>
                    </div>
                </div>
                <!-- Print-only title -->
                <div class="hidden print:block" style="display:none;">
                    <h1 id="printFYTitle" style="text-align:center;font-size:14pt;font-weight:700;margin-bottom:4px;">Fiscal Year Budget Report</h1>
                    <p class="subtitle" style="text-align:center;font-size:11pt;margin-bottom:16px;">
                        <span id="printFYPeriod"></span> | Status: <span id="printFYStatus"></span>
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-[12px]" id="budgetTable">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th
                                    class="text-left px-4 md:px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Program</th>
                                <th
                                    class="text-left px-4 md:px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold hidden sm:table-cell">
                                    Funding Source</th>
                                <th
                                    class="text-left px-4 md:px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Annual Budget</th>
                                <th
                                    class="text-left px-4 md:px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold hidden md:table-cell">
                                    Spent</th>
                                <th
                                    class="text-left px-4 md:px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Remaining</th>
                                <th
                                    class="text-left px-4 md:px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold hidden lg:table-cell">
                                    Utilization</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="budgetTableBody">
                            <!-- Rows injected by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

        </main>

        <footer
            class="border-t border-slate-200 bg-white px-4 md:px-6 py-3 flex items-center justify-between text-[11px] text-slate-400 no-print">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <!-- ══════════════════════════ NEW FY MODAL ══════════════════════════ -->
    <div id="newFYModal" class="fixed inset-0 z-50 flex items-center justify-center modal-backdrop hidden">
        <div
            class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto animate-modal-in">
            <div
                class="sticky top-0 bg-white z-10 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-[16px] font-semibold text-green-600">Initialize New Fiscal Year</h2>
                <button onclick="closeNewFYModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <!-- Step indicator -->
                <div class="flex items-center gap-2 mb-4">
                    <div class="step-circle active" id="step1Circle">1</div>
                    <div class="step-line active" id="step1Line"></div>
                    <div class="step-circle" id="step2Circle">2</div>
                    <div class="step-line" id="step2Line"></div>
                    <div class="step-circle" id="step3Circle">3</div>
                </div>

                <!-- Step 1: Select FY -->
                <div id="step1Content">
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
                        <p class="text-[12px] text-slate-600">
                            <i class="fas fa-info-circle text-blue-500 mr-1.5"></i>
                            Step 1: Select the new fiscal year and its duration.
                        </p>
                    </div>
                    <form id="step1Form" onsubmit="return false;">
                        <div>
                            <label class="field-label req">Fiscal Year</label>
                            <input type="number" id="fyYearInput" class="field" placeholder="e.g. 2027" min="2027"
                                required />
                            <p class="text-[10px] text-slate-400 mt-1">Only future years are allowed (current year + 1
                                and up)</p>
                        </div>
                        <div>
                            <label class="field-label req">Start Date</label>
                            <input type="date" id="fyStartDate" class="field" required />
                            <p class="text-[10px] text-slate-400 mt-1">Typically January 1 of the selected year</p>
                        </div>
                        <div>
                            <label class="field-label req">End Date</label>
                            <input type="date" id="fyEndDate" class="field" required />
                            <p class="text-[10px] text-slate-400 mt-1">Typically December 31 of the selected year</p>
                        </div>
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <p class="text-[11px] text-amber-700 flex items-start gap-2">
                                <i class="fas fa-triangle-exclamation text-amber-500 mt-0.5"></i>
                                <span>FY <span id="currentFYLabel">2026</span> will remain active. The new fiscal year will be created as
                                    <strong>Planning</strong>, so its budgets can be reviewed and edited before the new
                                    fiscal year officially begins.</span>
                            </p>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                            <button type="button" onclick="closeNewFYModal()"
                                class="text-[13px] font-medium text-slate-600 border border-slate-200 rounded-xl px-5 py-2 hover:border-green-400 hover:text-green-600 transition-all">
                                Cancel
                            </button>
                            <button type="button" onclick="goToStep2()"
                                class="text-[13px] font-semibold text-white bg-green-600 rounded-xl px-6 py-2 hover:bg-green-500 transition-all">
                                Next <i class="fas fa-arrow-right ml-1.5"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 2: Edit Program Budgets -->
                <div id="step2Content" style="display:none;">
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
                        <p class="text-[12px] text-slate-600">
                            <i class="fas fa-info-circle text-blue-500 mr-1.5"></i>
                            Step 2: Edit Program Budgets — FY <span id="step2FYLabel"></span>
                        </p>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
                        <p class="text-[12px] text-amber-700 flex items-start gap-2">
                            <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5"></i>
                            <span><strong>Important:</strong> Enter the annual budget for each program. Changes to
                                program budgets will affect financial reports and utilization calculations.</span>
                        </p>
                    </div>

                    <!-- Table Header -->
                    <div
                        class="hidden sm:grid grid-cols-12 gap-2 px-3 py-2 bg-slate-50 rounded-lg text-[10px] uppercase tracking-wider text-slate-500 font-semibold">
                        <div class="col-span-5">Program</div>
                        <div class="col-span-5">New Budget</div>
                        <div class="col-span-2">Previous</div>
                    </div>

                    <form id="step2BudgetForm" onsubmit="return false;">
                        <div class="space-y-2 max-h-[300px] overflow-y-auto" id="step2BudgetRows">
                            <!-- Injected by JS -->
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mt-4">
                            <p class="text-[11px] text-blue-700 flex items-start gap-2">
                                <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                                <span>Enter the budget amounts for each program. Leave as 0 if no budget is allocated.
                                    Previous year's budget is shown for reference.</span>
                            </p>
                        </div>
                        <div class="flex justify-between gap-3 pt-4 border-t border-slate-200">
                            <button type="button" onclick="goToStep1()"
                                class="text-[13px] font-medium text-slate-600 border border-slate-200 rounded-xl px-5 py-2 hover:border-green-400 hover:text-green-600 transition-all">
                                <i class="fas fa-arrow-left mr-1.5"></i> Back
                            </button>
                            <button type="button" onclick="goToStep3()"
                                class="text-[13px] font-semibold text-white bg-green-600 rounded-xl px-6 py-2 hover:bg-green-500 transition-all">
                                Next <i class="fas fa-arrow-right ml-1.5"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 3: Final Budget Table & Verification -->
                <div id="step3Content" style="display:none;">
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-4">
                        <p class="text-[12px] text-emerald-700 flex items-start gap-2">
                            <i class="fas fa-check-circle text-emerald-500 mt-0.5"></i>
                            <span>Step 3: Review the final budget before initializing the new fiscal year.</span>
                        </p>
                    </div>

                    <div class="mb-4">
                        <p class="text-[13px] font-semibold text-green-700 mb-2">Fiscal Year: <span
                                id="confirmFY">—</span></p>
                        <p class="text-[12px] text-slate-500 mb-3"><span id="confirmStart">—</span> – <span
                                id="confirmEnd">—</span></p>
                    </div>

                    <!-- Budget Summary Table -->
                    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-[12px]">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th
                                        class="text-left px-4 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        Program</th>
                                    <th
                                        class="text-right px-4 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                        Budget</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100" id="step3BudgetTable">
                                <!-- Injected by JS -->
                            </tbody>
                            <tfoot>
                                <tr class="bg-green-50 border-t border-green-200">
                                    <td class="px-4 py-3 font-semibold text-green-700">TOTAL BUDGET</td>
                                    <td class="px-4 py-3 text-right font-bold text-green-700" id="step3TotalBudget">
                                        ₱0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Verification Checkbox -->
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mt-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" id="verifyBudgetCheckbox"
                                class="mt-0.5 w-4 h-4 text-green-600 rounded border-slate-300 focus:ring-green-500"
                                onchange="updateVerifyButton()" />
                            <span class="text-[12px] text-slate-700">
                                <strong>I verify that the budget amounts above are correct.</strong> By checking this
                                box, I confirm that I have reviewed all program budgets and they are accurate for the
                                new fiscal year.
                            </span>
                        </label>
                    </div>

                    <div class="flex justify-between gap-3 pt-4 border-t border-slate-200">
                        <button type="button" onclick="goToStep2_from3()"
                            class="text-[13px] font-medium text-slate-600 border border-slate-200 rounded-xl px-5 py-2 hover:border-green-400 hover:text-green-600 transition-all">
                            <i class="fas fa-arrow-left mr-1.5"></i> Back
                        </button>
                        <button id="finalizeButton" onclick="finalizeFY()" disabled
                            class="text-[13px] font-semibold text-white bg-green-600 rounded-xl px-6 py-2 hover:bg-green-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-green-600">
                            <i class="fas fa-check mr-1.5"></i> Finalize & Initialize FY
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════ SUCCESS MODAL ══════════════════════════ -->
    <div id="successModal" class="fixed inset-0 z-50 flex items-center justify-center modal-backdrop hidden">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 animate-modal-in">
            <div class="p-6 text-center">
                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-4xl text-green-600"></i>
                </div>
                <h2 class="text-[20px] font-bold text-green-700 mb-2">All Done!</h2>
                <p class="text-[13px] text-slate-600 mb-4">The new fiscal year has been saved successfully as a planning cycle. The current active fiscal year remains active until it is officially changed.</p>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-left text-[12px] space-y-2">
                    <p><strong>New Fiscal Year:</strong> <span id="successFY">2027</span></p>
                    <p><strong>Total Budget:</strong> <span id="successTotalBudget">₱0.00</span></p>
                    <p><strong>Programs Funded:</strong> <span id="successProgramCount">0</span></p>
                    <p><strong>Date Initialized:</strong> <span id="successDate">—</span></p>
                </div>
                <button onclick="closeSuccessModal()"
                    class="mt-4 text-[13px] font-semibold text-white bg-green-600 rounded-xl px-6 py-2.5 hover:bg-green-500 transition-all">
                    <i class="fas fa-check mr-1.5"></i> Close
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════ EDIT BUDGET MODAL (Active or Planning FY) ══════════════════════════ -->
    <div id="editBudgetModal" class="fixed inset-0 z-50 flex items-center justify-center modal-backdrop hidden">
        <div
            class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto animate-modal-in">
            <div
                class="sticky top-0 bg-white z-10 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-[16px] font-semibold text-green-600">Edit Program Budgets — <span id="editFYLabel">FY
                        2027</span></h2>
                <button onclick="closeEditBudgetModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <p class="text-[12px] text-amber-700 flex items-start gap-2">
                        <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5"></i>
                        <span><strong>Important:</strong> Planning fiscal years can be edited before activation. Changes to an
                            Active fiscal year affect current budget figures and utilization calculations. Archived
                            fiscal years are read-only.</span>
                    </p>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <p class="text-[12px] text-slate-600">
                        <i class="fas fa-info-circle text-blue-500 mr-1.5"></i>
                        Enter the annual budget for each program. The table below shows the difference from the previous
                        year's budget with increase/decrease indicators and percentages.
                    </p>
                </div>

                <!-- Table Header -->
                <div
                    class="hidden sm:grid grid-cols-12 gap-2 px-3 py-2 bg-slate-50 rounded-lg text-[10px] uppercase tracking-wider text-slate-500 font-semibold">
                    <div class="col-span-4">Program</div>
                    <div class="col-span-3">New Budget</div>
                    <div class="col-span-3">Change from Previous Year</div>
                    <div class="col-span-2">% Change</div>
                </div>

                <form id="editBudgetForm" onsubmit="return false;">
                    <div class="space-y-2" id="budgetEditRows">
                        <!-- Injected by JS -->
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 mt-4">
                        <button type="button" onclick="confirmCancelEdit()"
                            class="text-[13px] font-medium text-slate-600 border border-slate-200 rounded-xl px-5 py-2 hover:border-red-400 hover:text-red-600 transition-all">
                            <i class="fas fa-times mr-1.5"></i> Cancel
                        </button>
                        <button type="button" onclick="saveBudgetEdits()"
                            class="text-[13px] font-semibold text-white bg-green-600 rounded-xl px-6 py-2 hover:bg-green-500 transition-all">
                            <i class="fas fa-save mr-1.5"></i> Save All Budgets
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirmation Dialog for Cancel -->
    <div id="cancelConfirmDialog" class="fixed inset-0 z-[60] flex items-center justify-center modal-backdrop hidden">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 animate-modal-in">
            <div class="p-6 text-center">
                <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl text-amber-500"></i>
                </div>
                <h2 class="text-[18px] font-bold text-slate-700 mb-2">Discard Changes?</h2>
                <p class="text-[13px] text-slate-600 mb-6">You have unsaved changes to the program budgets. Are you sure
                    you want to cancel? All modifications will be lost.</p>
                <div class="flex gap-3 justify-center">
                    <button onclick="closeCancelDialog()"
                        class="text-[13px] font-medium text-slate-600 border border-slate-200 rounded-xl px-5 py-2 hover:border-green-400 hover:text-green-600 transition-all">
                        No, Continue Editing
                    </button>
                    <button onclick="forceCloseEditBudget()"
                        class="text-[13px] font-semibold text-white bg-red-600 rounded-xl px-5 py-2 hover:bg-red-500 transition-all">
                        Yes, Discard Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast"
        class="fixed bottom-6 right-6 bg-green-700 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-green-300"></i>
        <span id="toastMsg">Action completed!</span>
    </div>

    <script>
        // ── Toggle Export Dropdown ──
        function toggleExportDropdown() {
            const dropdown = document.getElementById('exportDropdown');
            dropdown.classList.toggle('open');
        }
        document.addEventListener('click', function (e) {
            const dropdown = document.getElementById('exportDropdown');
            const btn = e.target.closest('.relative');
            if (!btn && dropdown) dropdown.classList.remove('open');
        });

        // ── Database data supplied by PHP ──
        const fiscalYears = <?= json_encode($fiscalYearsPhp, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        let selectedFY = <?= (int)$activeFyIndex ?>;
        let currentYear = fiscalYears[selectedFY] ? fiscalYears[selectedFY].year : new Date().getFullYear();
        let stepData = {};
        let step2Budgets = {};


        // ── Render FY Cards ──
        function renderFYCards() {
            const container = document.getElementById('fyCards');
            container.innerHTML = '';
            const sorted = [...fiscalYears].sort((a, b) => b.year - a.year);
            sorted.forEach((fy) => {
                const index = fiscalYears.indexOf(fy);
                const isActive = fy.status === 'Active';
                const isSelected = index === selectedFY;
                const pct = fy.totalBudget > 0 ? Math.round((fy.totalSpent / fy.totalBudget) * 100) : 0;
                const isPlanning = fy.status === 'Planning';
                const isArchived = fy.status === 'Archived';
                const statusColor = isActive
                    ? 'badge-active'
                    : isPlanning
                        ? 'bg-amber-100 text-amber-700'
                        : 'badge-archived';

                const startDate = new Date(fy.start + 'T00:00:00');
                const todayDate = new Date();
                todayDate.setHours(0, 0, 0, 0);
                const canActivate = isPlanning && todayDate >= startDate;

                const activationButton = isPlanning
                    ? `
                        <button
                            type="button"
                            onclick="event.stopPropagation(); activateFY(${index})"
                            ${canActivate ? '' : 'disabled'}
                            class="mt-3 w-full text-[10px] font-semibold rounded-lg px-2.5 py-1.5 border transition-all
                                ${canActivate
                                    ? 'text-green-700 bg-green-50 border-green-200 hover:bg-green-100'
                                    : 'text-slate-400 bg-slate-50 border-slate-200 cursor-not-allowed'}">
                            <i class="fas fa-power-off mr-1"></i>
                            ${canActivate ? 'Activate Fiscal Year' : `Starts ${fy.start}`}
                        </button>
                    `
                    : '';

                container.innerHTML += `
                    <div onclick="selectFY(${index})" class="fy-card ${isSelected ? 'active-fy' : ''} bg-white border-2 ${isSelected ? 'border-green-500' : 'border-slate-200'} rounded-xl p-3 md:p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-[13px] md:text-[15px] font-bold text-green-700">FY ${fy.year}</h3>
                            <span class="${statusColor} px-2 py-0.5 rounded-full text-[10px] font-semibold">${fy.status}</span>
                        </div>
                        <p class="text-[9px] md:text-[10px] text-slate-400">${fy.start} – ${fy.end}</p>
                        <div class="mt-3 flex justify-between text-[11px]">
                            <span class="text-slate-500">Total Budget</span>
                            <span class="font-semibold text-green-600">₱${fy.totalBudget.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</span>
                        </div>
                        <div class="flex justify-between text-[11px]">
                            <span class="text-slate-500">Utilization</span>
                            <span class="font-semibold ${pct > 80 ? 'text-red-500' : pct > 60 ? 'text-amber-500' : 'text-green-600'}">${pct}%</span>
                        </div>
                        <div class="mt-2 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="h-1.5 rounded-full ${pct > 80 ? 'bg-red-500' : pct > 60 ? 'bg-amber-400' : 'bg-green-500'}" style="width:${Math.min(pct, 100)}%"></div>
                        </div>
                        ${isActive ? '<div class="mt-2 text-[10px] text-green-600 font-semibold flex items-center gap-1"><i class="fas fa-circle text-[6px]"></i> Current FY</div>' : ''}
                        ${isPlanning ? activationButton : ''}
                    </div>
                `;
            });
        }

        // ── Activate Planning Fiscal Year ──
        async function activateFY(index) {
            const fy = fiscalYears[index];

            if (!fy || fy.status !== 'Planning') {
                showToast('Only a Planning fiscal year can be activated.', 'error');
                return;
            }

            const startDate = new Date(fy.start + 'T00:00:00');
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (today < startDate) {
                showToast(`FY ${fy.year} cannot be activated until ${fy.start}.`, 'error');
                return;
            }

            const confirmed = confirm(
                `Activate FY ${fy.year}?\n\n` +
                `FY ${fy.year} will become Active and the current Active fiscal year will be Archived.\n\n` +
                `The FY ${fy.year} program budgets will become the live budgets used by Budget Management.`
            );

            if (!confirmed) return;

            try {
                const body = new URLSearchParams();
                body.set('fy_action', 'activate_fy');
                body.set('fiscal_year_id', String(fy.id));

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: body.toString()
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Unable to activate fiscal year.');
                }

                showToast(result.message || `FY ${fy.year} is now Active.`);

                setTimeout(() => {
                    window.location.reload();
                }, 700);

            } catch (error) {
                showToast(error.message || 'Unable to activate fiscal year.', 'error');
            }
        }

        // ── Select FY ──
        function selectFY(index) {
            selectedFY = index;
            renderFYCards();
            renderBudgetTable();
            updateSelectedFYTitle();
            updatePrintInfo();
        }

        function updateSelectedFYTitle() {
            const fy = fiscalYears[selectedFY];
            document.getElementById('selectedFYTitle').textContent = `FY ${fy.year} Budget Breakdown`;
            const badge = document.querySelector('#selectedFYTitle + span');
            if (badge) {
                badge.textContent = fy.status;
                badge.className =
                    `text-[10px] font-semibold px-2 py-0.5 rounded-full ${
                        fy.status === 'Active'
                            ? 'bg-green-100 text-green-700'
                            : fy.status === 'Planning'
                                ? 'bg-amber-100 text-amber-700'
                                : 'bg-slate-100 text-slate-600'
                    }`;
            }
            document.getElementById('editFYLabel').textContent = `FY ${fy.year}`;

            const editButton = document.getElementById('editSelectedFYButton');
            if (editButton) {
                const editable = fy.status === 'Active' || fy.status === 'Planning';
                editButton.classList.toggle('hidden', !editable);
                editButton.classList.toggle('flex', editable);
                editButton.title = fy.status === 'Planning'
                    ? `Edit FY ${fy.year} planning budget`
                    : `Edit FY ${fy.year} active budget`;
            }
        }

        function updatePrintInfo() {
            const fy = fiscalYears[selectedFY];
            const printFYTitle = document.getElementById('printFYTitle');
            const printFYPeriod = document.getElementById('printFYPeriod');
            const printFYStatus = document.getElementById('printFYStatus');
            if (printFYTitle) printFYTitle.textContent = `FY ${fy.year} Budget Report`;
            if (printFYPeriod) printFYPeriod.textContent = `${fy.start} – ${fy.end}`;
            if (printFYStatus) printFYStatus.textContent = fy.status;
        }

        function renderBudgetTable() {
            const tbody = document.getElementById('budgetTableBody');
            const fy = fiscalYears[selectedFY];
            tbody.innerHTML = '';
            fy.programs.forEach(p => {
                const remaining = p.budget - p.spent;
                const pct = p.budget > 0 ? Math.round((p.spent / p.budget) * 100) : 0;
                const barColor = pct > 80 ? 'bg-red-500' : pct > 60 ? 'bg-amber-400' : 'bg-emerald-500';
                const textColor = pct > 80 ? 'text-red-500' : pct > 60 ? 'text-amber-500' : 'text-emerald-600';
                tbody.innerHTML += `
                    <tr class="table-row">
                        <td class="px-4 md:px-5 py-3 font-medium text-green-700">${p.program}</td>
                        <td class="px-4 md:px-5 py-3 text-slate-600 hidden sm:table-cell">${p.source}</td>
                        <td class="px-4 md:px-5 py-3 font-semibold text-slate-700">₱${p.budget.toLocaleString()}</td>
                        <td class="px-4 md:px-5 py-3 text-slate-600 hidden md:table-cell">₱${p.spent.toLocaleString()}</td>
                        <td class="px-4 md:px-5 py-3 font-semibold ${remaining < (p.budget * 0.15) ? 'text-red-500' : remaining < (p.budget * 0.30) ? 'text-amber-500' : 'text-green-600'}">₱${remaining.toLocaleString()}</td>
                        <td class="px-4 md:px-5 py-3 hidden lg:table-cell">
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-semibold ${textColor}">${pct}%</span>
                                <div class="flex-1 bg-slate-100 rounded-full h-1.5 overflow-hidden max-w-20">
                                    <div class="h-1.5 rounded-full ${barColor}" style="width:${Math.min(pct, 100)}%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            });
            const activeFY = fiscalYears.find(f => f.status === 'Active');
            if (activeFY) {
                const totalBudget = activeFY.totalBudget;
                const totalSpent = activeFY.totalSpent;
                const remaining = totalBudget - totalSpent;
                const pct = totalBudget > 0 ? Math.round((totalSpent / totalBudget) * 100) : 0;
                document.getElementById('activeTotalBudget').textContent = '₱' + totalBudget.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('activeTotalSpent').textContent = '₱' + totalSpent.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('activeTotalRemaining').textContent = '₱' + remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('activeUtilization').textContent = pct + '%';
                document.getElementById('activeFYLabel').textContent = 'FY ' + activeFY.year;
                const periodEl = document.getElementById('activeFYPeriod');
                if (periodEl) periodEl.textContent = `${activeFY.start} – ${activeFY.end}`;
                const barEl = document.getElementById('activeUtilizationBar');
                if (barEl) barEl.style.width = Math.min(pct, 100) + '%';
                const labelEl = document.getElementById('activeUtilizationLabel');
                if (labelEl) labelEl.textContent = pct + '% utilized';
            }
            updatePrintInfo();
        }

        // ── Step navigation functions ──
        function goToStep2() {
            const yearEl = document.getElementById('fyYearInput');
            const startEl = document.getElementById('fyStartDate');
            const endEl = document.getElementById('fyEndDate');
            const year = yearEl.value.trim();
            const start = startEl.value;
            const end = endEl.value;

            if (!year || year.length !== 4 || !/^\d{4}$/.test(year)) {
                showToast('Please enter a valid 4-digit year.', 'error');
                yearEl.focus();
                return;
            }
            if (parseInt(year) <= new Date().getFullYear()) {
                showToast('Please enter a future year (current year + 1 and up).', 'error');
                yearEl.focus();
                return;
            }
            if (isFYExists(year)) {
                showToast(`FY ${year} already exists. Please enter a different year.`, 'error');
                yearEl.focus();
                return;
            }
            if (!start) {
                showToast('Please select a start date.', 'error');
                startEl.focus();
                return;
            }
            if (!end) {
                showToast('Please select an end date.', 'error');
                endEl.focus();
                return;
            }
            if (new Date(end) <= new Date(start)) {
                showToast('End date must be after start date.', 'error');
                endEl.focus();
                return;
            }

            stepData.year = year;
            stepData.start = start;
            stepData.end = end;

            // Populate step 2 budget form
            document.getElementById('step2FYLabel').textContent = year;
            populateStep2BudgetRows();

            document.getElementById('step1Content').style.display = 'none';
            document.getElementById('step2Content').style.display = 'block';
            document.getElementById('step3Content').style.display = 'none';

            document.getElementById('step1Circle').className = 'step-circle done';
            document.getElementById('step1Line').className = 'step-line active';
            document.getElementById('step2Circle').className = 'step-circle active';
            document.getElementById('step2Line').className = 'step-line';
            document.getElementById('step3Circle').className = 'step-circle';
        }

        function populateStep2BudgetRows() {
            const container = document.getElementById('step2BudgetRows');
            const activeFY = fiscalYears.find(f => f.status === 'Active');
            const programs = activeFY ? activeFY.programs : fiscalYears[selectedFY].programs;

            container.innerHTML = '';
            programs.forEach((p, index) => {
                const prevBudget = activeFY ? p.budget : 0;
                container.innerHTML += `
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center p-3 bg-slate-50 rounded-lg border border-slate-100">
            <div class="col-span-5">
                <span class="text-[13px] font-medium text-green-700">${p.program}</span>
                <span class="text-[10px] text-slate-400 block">${p.source}</span>
            </div>
            <div class="col-span-5 relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium text-[12px]">₱</span>
                <input type="text" class="field pl-7 text-[13px] py-2 w-full step2-budget" 
                       data-index="${index}" 
                       data-program-id="${p.programId}"
                       data-program="${p.program}"
                       data-source="${p.source}"
                       value="${formatCurrency(prevBudget)}" 
                       placeholder="0.00"
                       onfocus="this.select()"
                       onblur="formatStep2BudgetOnBlur(this)" />
            </div>
            <div class="col-span-2">
                <span class="text-[11px] text-slate-400">Prev: ₱${prevBudget.toLocaleString()}</span>
            </div>
        </div>
        `;
            });
        }

        function formatStep2BudgetOnBlur(input) {
            let value = input.value.replace(/[^0-9.]/g, '');

            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }

            const num = parseFloat(value);
            if (!isNaN(num) && value !== '') {
                input.value = num.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            } else if (value === '' || value === '.') {
                input.value = '0.00';
            }
        }

        function goToStep1() {
            document.getElementById('step1Content').style.display = 'block';
            document.getElementById('step2Content').style.display = 'none';
            document.getElementById('step3Content').style.display = 'none';
            document.getElementById('step1Circle').className = 'step-circle active';
            document.getElementById('step1Line').className = 'step-line active';
            document.getElementById('step2Circle').className = 'step-circle';
            document.getElementById('step2Line').className = 'step-line';
            document.getElementById('step3Circle').className = 'step-circle';
        }

        function goToStep2_from3() {
            document.getElementById('step2Content').style.display = 'block';
            document.getElementById('step3Content').style.display = 'none';
            document.getElementById('step2Circle').className = 'step-circle active';
            document.getElementById('step2Line').className = 'step-line';
            document.getElementById('step3Circle').className = 'step-circle';
        }

        function goToStep3() {
            // Collect budgets from step 2
            const budgetInputs = document.querySelectorAll('.step2-budget');
            let hasValidBudget = false;
            step2Budgets = {};

            budgetInputs.forEach(input => {
                const index = parseInt(input.dataset.index);
                const program = input.dataset.program;
                const source = input.dataset.source;
                const raw = input.value.replace(/[^0-9.]/g, '');
                const value = parseFloat(raw) || 0;

                step2Budgets[index] = {
                    programId: parseInt(input.dataset.programId || '0'),
                    program: program,
                    source: source,
                    budget: value
                };

                if (value > 0) hasValidBudget = true;
            });

            // Populate step 3 table
            const tableBody = document.getElementById('step3BudgetTable');
            let totalBudget = 0;
            tableBody.innerHTML = '';

            Object.values(step2Budgets).forEach(item => {
                totalBudget += item.budget;
                tableBody.innerHTML += `
        <tr class="table-row">
            <td class="px-4 py-3 font-medium text-green-700">
                ${item.program}
                <span class="text-[10px] text-slate-400 block">${item.source}</span>
            </td>
            <td class="px-4 py-3 text-right font-semibold text-slate-700">₱${item.budget.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
        </tr>
        `;
            });

            document.getElementById('step3TotalBudget').textContent = '₱' + totalBudget.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('confirmFY').textContent = stepData.year;
            document.getElementById('confirmStart').textContent = stepData.start;
            document.getElementById('confirmEnd').textContent = stepData.end;
            document.getElementById('verifyBudgetCheckbox').checked = false;
            document.getElementById('finalizeButton').disabled = true;

            document.getElementById('step1Content').style.display = 'none';
            document.getElementById('step2Content').style.display = 'none';
            document.getElementById('step3Content').style.display = 'block';
            document.getElementById('step2Circle').className = 'step-circle done';
            document.getElementById('step2Line').className = 'step-line active';
            document.getElementById('step3Circle').className = 'step-circle active';
        }

        function updateVerifyButton() {
            const checkbox = document.getElementById('verifyBudgetCheckbox');
            const button = document.getElementById('finalizeButton');
            button.disabled = !checkbox.checked;
        }

        function isFYExists(year) {
            return fiscalYears.some(fy => fy.year === parseInt(year));
        }

        // ── Open New FY Modal ──
        function openNewFYModal() {
            const activeFY = fiscalYears.find(f => f.status === 'Active');
            if (activeFY) document.getElementById('currentFYLabel').textContent = activeFY.year;
            const currentYearVal = new Date().getFullYear();
            const minYear = currentYearVal + 1;
            document.getElementById('fyYearInput').min = minYear;
            document.getElementById('fyYearInput').placeholder = `e.g. ${minYear}`;
            document.getElementById('fyYearInput').value = '';
            document.getElementById('fyStartDate').value = '';
            document.getElementById('fyEndDate').value = '';
            document.getElementById('step1Content').style.display = 'block';
            document.getElementById('step2Content').style.display = 'none';
            document.getElementById('step3Content').style.display = 'none';
            document.getElementById('step1Circle').className = 'step-circle active';
            document.getElementById('step1Line').className = 'step-line active';
            document.getElementById('step2Circle').className = 'step-circle';
            document.getElementById('step2Line').className = 'step-line';
            document.getElementById('step3Circle').className = 'step-circle';
            document.getElementById('newFYModal').classList.remove('hidden');
            document.getElementById('newFYModal').style.display = 'flex';
            stepData = {};
            step2Budgets = {};
        }

        function closeNewFYModal() {
            document.getElementById('newFYModal').classList.add('hidden');
            document.getElementById('newFYModal').style.display = 'none';
        }

        // ── Auto-update dates ──
        document.getElementById('fyYearInput').addEventListener('input', function () {
            const year = this.value.trim();
            if (year.length === 4 && /^\d{4}$/.test(year)) {
                document.getElementById('fyStartDate').value = `${year}-01-01`;
                document.getElementById('fyEndDate').value = `${year}-12-31`;
            }
        });

        // ── Finalize / Initialize FY (database-backed) ──
        async function finalizeFY() {
            const verify = document.getElementById('verifyBudgetCheckbox');
            if (!verify || !verify.checked) {
                showToast('Please verify the budget amounts first.', 'error');
                return;
            }

            const budgets = Object.values(step2Budgets).map(item => ({
                program_id: Number(item.programId),
                budget: Number(item.budget || 0)
            }));

            const totalBudget = budgets.reduce((sum, item) => sum + item.budget, 0);
            const button = document.getElementById('finalizeButton');
            if (button) button.disabled = true;

            try {
                const body = new URLSearchParams();
                body.set('fy_action', 'initialize_fy');
                body.set('year', stepData.year);
                body.set('start', stepData.start);
                body.set('end', stepData.end);
                body.set('budgets', JSON.stringify(budgets));

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString()
                });
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Unable to initialize fiscal year.');
                }

                closeNewFYModal();

                document.getElementById('successFY').textContent = stepData.year;
                document.getElementById('successTotalBudget').textContent = '₱' + totalBudget.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                document.getElementById('successProgramCount').textContent = budgets.filter(x => x.budget > 0).length + ' of ' + budgets.length;
                document.getElementById('successDate').textContent = new Date().toLocaleDateString('en-PH', {
                    year: 'numeric', month: 'long', day: 'numeric'
                });

                document.getElementById('successModal').classList.remove('hidden');
                document.getElementById('successModal').style.display = 'flex';

                showToast(`FY ${stepData.year} saved successfully as Planning.`);

                // Reload from MySQL so the new FY immediately appears in history.
                setTimeout(() => window.location.reload(), 700);
            } catch (error) {
                console.error('Fiscal year initialization error:', error);
                showToast(error.message || 'Unable to initialize fiscal year.', 'error');
                if (button) button.disabled = false;
            }
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
            document.getElementById('successModal').style.display = 'none';
        }

        // ── Format currency ──
        function formatCurrency(value) {
            if (value === '' || value === undefined || value === null) return '';
            const num = parseFloat(value);
            if (isNaN(num)) return '';
            return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // ── Open Edit Budget Modal ──
        function openEditBudgetModal() {
            const fy = fiscalYears[selectedFY];

            if (!fy) {
                showToast('Fiscal year not found.', 'error');
                return;
            }

            if (fy.status === 'Archived') {
                showToast('Archived fiscal years are read-only.', 'error');
                return;
            }

            if (fy.status !== 'Active' && fy.status !== 'Planning') {
                showToast('Only Active or Planning fiscal years can be edited.', 'error');
                return;
            }

            const container = document.getElementById('budgetEditRows');
            container.innerHTML = '';

            const prevFY = fiscalYears.find(f => f.year === fy.year - 1);

            fy.programs.forEach((p, index) => {
                let prevBudget = 0;
                let changeAmount = 0;
                let changePercent = 0;
                let changeText = 'N/A';
                let changeColor = 'text-slate-400';
                let bgColor = 'bg-slate-50';

                if (prevFY) {
                    const prevProgram = prevFY.programs.find(pp => pp.program === p.program);
                    if (prevProgram) {
                        prevBudget = prevProgram.budget;
                        changeAmount = p.budget - prevBudget;

                        if (p.budget === 0 && prevBudget === 0) {
                            changeText = 'No change';
                            changeColor = 'text-slate-400';
                            bgColor = 'bg-slate-50';
                        } else if (changeAmount > 0) {
                            changePercent = prevBudget > 0 ? Math.round((changeAmount / prevBudget) * 100) : 100;
                            changeText = `+₱${formatCurrency(changeAmount)}`;
                            changeColor = 'text-emerald-600';
                            bgColor = 'bg-emerald-50';
                        } else if (changeAmount < 0) {
                            changePercent = prevBudget > 0 ? Math.round((Math.abs(changeAmount) / prevBudget) * 100) : 100;
                            changeText = `-₱${formatCurrency(Math.abs(changeAmount))}`;
                            changeColor = 'text-red-600';
                            bgColor = 'bg-red-50';
                        } else {
                            changeText = '₱0.00';
                            changeColor = 'text-slate-400';
                            bgColor = 'bg-slate-50';
                        }
                    }
                }

                container.innerHTML += `
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center p-3 ${bgColor} rounded-lg border border-slate-100 hover:border-green-200 transition-all">
                    <div class="col-span-4">
                        <span class="text-[13px] font-medium text-green-700">${p.program}</span>
                        <span class="text-[10px] text-slate-400 block sm:hidden">${p.source}</span>
                    </div>
                    <div class="col-span-3 relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium text-[12px]">₱</span>
                        <input type="text" class="field pl-7 text-[13px] py-2 budget-input w-full" 
                               data-index="${index}" 
                               data-program-id="${p.programId}"
                               data-prev-budget="${prevBudget}"
                               value="${formatCurrency(p.budget)}" 
                               placeholder="0.00"
                               onfocus="this.select()"
                               oninput="updateChangeIndicator(this)" />
                    </div>
                    <div class="col-span-3">
                        <span class="text-[13px] font-semibold ${changeColor} change-amount" id="change-amount-${index}">
                            ${changeText}
                        </span>
                    </div>
                    <div class="col-span-2">
                        <span class="text-[12px] font-semibold ${changeColor} change-percent" id="change-percent-${index}">
                            ${prevFY ? (changeAmount > 0 ? '+' : changeAmount < 0 ? '-' : '') + changePercent + '%' : 'N/A'}
                        </span>
                    </div>
                </div>
            `;
            });

            document.getElementById('editFYLabel').textContent = `FY ${fy.year}`;
            document.getElementById('editBudgetModal').classList.remove('hidden');
            document.getElementById('editBudgetModal').style.display = 'flex';
        }

        // ── Update Change Indicator on Input ──
        function updateChangeIndicator(input) {
            const index = parseInt(input.dataset.index);
            const prevBudget = parseFloat(input.dataset.prevBudget) || 0;
            const raw = input.value.replace(/[^0-9.]/g, '');
            const newBudget = parseFloat(raw) || 0;

            const changeAmountEl = document.getElementById(`change-amount-${index}`);
            const changePercentEl = document.getElementById(`change-percent-${index}`);

            if (!changeAmountEl || !changePercentEl) return;

            const changeAmount = newBudget - prevBudget;

            if (newBudget === 0 && prevBudget === 0) {
                changeAmountEl.textContent = 'No change';
                changeAmountEl.className = 'text-[13px] font-semibold text-slate-400 change-amount';
                changePercentEl.textContent = '0%';
                changePercentEl.className = 'text-[12px] font-semibold text-slate-400 change-percent';
            } else if (changeAmount > 0) {
                const percent = prevBudget > 0 ? Math.round((changeAmount / prevBudget) * 100) : 100;
                changeAmountEl.textContent = `+₱${formatCurrency(changeAmount)}`;
                changeAmountEl.className = 'text-[13px] font-semibold text-emerald-600 change-amount';
                changePercentEl.textContent = `+${percent}%`;
                changePercentEl.className = 'text-[12px] font-semibold text-emerald-600 change-percent';
            } else if (changeAmount < 0) {
                const percent = prevBudget > 0 ? Math.round((Math.abs(changeAmount) / prevBudget) * 100) : 100;
                changeAmountEl.textContent = `-₱${formatCurrency(Math.abs(changeAmount))}`;
                changeAmountEl.className = 'text-[13px] font-semibold text-red-600 change-amount';
                changePercentEl.textContent = `-${percent}%`;
                changePercentEl.className = 'text-[12px] font-semibold text-red-600 change-percent';
            } else {
                changeAmountEl.textContent = '₱0.00';
                changeAmountEl.className = 'text-[13px] font-semibold text-slate-400 change-amount';
                changePercentEl.textContent = '0%';
                changePercentEl.className = 'text-[12px] font-semibold text-slate-400 change-percent';
            }
        }

        // ── Confirmation Dialog Functions ──
        function confirmCancelEdit() {
            document.getElementById('cancelConfirmDialog').classList.remove('hidden');
            document.getElementById('cancelConfirmDialog').style.display = 'flex';
        }

        function closeCancelDialog() {
            document.getElementById('cancelConfirmDialog').classList.add('hidden');
            document.getElementById('cancelConfirmDialog').style.display = 'none';
        }

        function forceCloseEditBudget() {
            document.getElementById('cancelConfirmDialog').classList.add('hidden');
            document.getElementById('cancelConfirmDialog').style.display = 'none';
            document.getElementById('editBudgetModal').classList.add('hidden');
            document.getElementById('editBudgetModal').style.display = 'none';
            showToast('Changes discarded.', 'warning');
        }

        function closeEditBudgetModal() {
            // Check if there are unsaved changes
            const inputs = document.querySelectorAll('.budget-input');
            let hasChanges = false;

            inputs.forEach(input => {
                const index = parseInt(input.dataset.index);
                const prevBudget = parseFloat(input.dataset.prevBudget) || 0;
                const raw = input.value.replace(/[^0-9.]/g, '');
                const newBudget = parseFloat(raw) || 0;

                if (Math.abs(newBudget - prevBudget) > 0.01) {
                    hasChanges = true;
                }
            });

            if (hasChanges) {
                confirmCancelEdit();
            } else {
                document.getElementById('editBudgetModal').classList.add('hidden');
                document.getElementById('editBudgetModal').style.display = 'none';
            }
        }

        // ── Format on blur ──
        document.addEventListener('focusout', function (e) {
            if (e.target.classList.contains('budget-input')) {
                const value = e.target.value.replace(/[^0-9.]/g, '');
                const num = parseFloat(value);
                if (!isNaN(num) && value !== '') {
                    e.target.value = num.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            }
            if (e.target.classList.contains('step2-budget')) {
                formatStep2Budget(e.target);
            }
        });

        // ── Save Budget Edits (database-backed) ──
        async function saveBudgetEdits() {
            const fy = fiscalYears.find(f => f.status === 'Active');
            if (!fy) {
                showToast('No active FY found.', 'error');
                return;
            }

            const budgets = [];
            document.querySelectorAll('.budget-input').forEach(input => {
                budgets.push({
                    program_id: Number(input.dataset.programId),
                    budget: Number(input.value.replace(/[^0-9.]/g, '')) || 0
                });
            });

            try {
                const body = new URLSearchParams();
                body.set('fy_action', 'save_budgets');
                body.set('fiscal_year_id', String(fy.id));
                body.set('budgets', JSON.stringify(budgets));

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString()
                });
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Unable to save budgets.');
                }

                closeEditBudgetModal();
                showToast(`FY ${fy.year} budgets saved successfully.`);

                // Re-read all figures from MySQL, including actual released spending.
                setTimeout(() => window.location.reload(), 500);
            } catch (error) {
                console.error('Budget save error:', error);
                showToast(error.message || 'Unable to save budgets.', 'error');
            }
        }

        // ── Export Functions ──
        function exportBudget(format) {
            const dropdown = document.getElementById('exportDropdown');
            if (dropdown) dropdown.classList.remove('open');

            if (format === 'pdf') {
                exportPDF();
                return;
            }

            if (format === 'xlsx') {
                exportXLSX();
                return;
            }

            // ── DOC Export ──
            const fy = fiscalYears[selectedFY];
            const now = new Date().toLocaleString('en-PH', { timeZone: 'Asia/Manila' });

            let html = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office"
                  xmlns:w="urn:schemas-microsoft-com:office:word"
                  xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                <!--[if gte mso 9]><xml>
                    <w:WordDocument>
                        <w:View>Print</w:View>
                        <w:Zoom>100</w:Zoom>
                        <w:DoNotOptimizeForBrowser/>
                    </w:WordDocument>
                </xml><![endif]-->
                <style>
                    @page {
                        size: letter;
                        margin: 0.8in 0.7in 0.8in 0.7in;
                        mso-header-margin: 0.5in;
                        mso-footer-margin: 0.5in;
                    }
                    body { font-family: Arial, sans-serif; font-size: 11pt; }
                    .header-table { width: 100%; border-bottom: 2px solid #1A5C3A; margin-bottom: 16px; padding-bottom: 8px; }
                    .header-table td { vertical-align: top; }
                    .header-title { font-size: 13pt; font-weight: 700; color: #1A5C3A; }
                    .header-sub { font-size: 10pt; color: #555; }
                    h1 { font-size: 14pt; font-weight: 700; text-align: center; margin-bottom: 4px; color: #1A5C3A; }
                    .subtitle { text-align: center; font-size: 11pt; margin-bottom: 16px; color: #555; }
                    table.data-table { width: 100%; border-collapse: collapse; font-size: 10pt; margin-top: 12px; }
                    table.data-table th, table.data-table td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
                    table.data-table th { background: #E8F0EC; font-weight: 700; color: #1A5C3A; }
                    .text-right { text-align: right; }
                    .footer-text { text-align: center; font-size: 9pt; color: #888; margin-top: 20px; border-top: 1px solid #ccc; padding-top: 12px; }
                    .footer-text .gen { font-size: 8pt; color: #aaa; }
                </style>
            </head>
            <body>
                <!-- HEADER -->
                <table class="header-table">
                    <tr>
                        <td style="width:70%;">
                            <div class="header-title">Republic of the Philippines</div>
                            <div class="header-sub">Province of Negros Occidental</div>
                            <div class="header-sub"><strong>Municipal Social Welfare and Development Office</strong></div>
                            <div class="header-sub">San Enrique, Negros Occidental</div>
                        </td>
                        <td style="width:30%;text-align:right;">
                            <div style="font-size:9pt;color:#888;">Generated:<br>${now}</div>
                        </td>
                    </tr>
                </table>

                <!-- TITLE -->
                <h1>Fiscal Year Budget Report</h1>
                <p class="subtitle">FY ${fy.year} &nbsp;|&nbsp; ${fy.start} – ${fy.end} &nbsp;|&nbsp; Status: <strong>${fy.status}</strong></p>

                <!-- DATA TABLE -->
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Funding Source</th>
                            <th class="text-right">Annual Budget</th>
                            <th class="text-right">Spent</th>
                            <th class="text-right">Remaining</th>
                            <th class="text-right">Utilization</th>
                        </tr>
                    </thead>
                    <tbody>`;

            fy.programs.forEach(p => {
                const remaining = p.budget - p.spent;
                const pct = p.budget > 0 ? Math.round((p.spent / p.budget) * 100) : 0;
                html += `
                <tr>
                    <td>${p.program}</td>
                    <td>${p.source}</td>
                    <td class="text-right">₱${p.budget.toLocaleString()}</td>
                    <td class="text-right">₱${p.spent.toLocaleString()}</td>
                    <td class="text-right">₱${remaining.toLocaleString()}</td>
                    <td class="text-right">${pct}%</td>
                </tr>`;
            });

            html += `
                    </tbody>
                    <tfoot>
                        <tr style="font-weight:700;background:#f5f5f5;">
                            <td colspan="2" class="text-right"><strong>TOTAL</strong></td>
                            <td class="text-right"><strong>₱${fy.totalBudget.toLocaleString()}</strong></td>
                            <td class="text-right"><strong>₱${fy.totalSpent.toLocaleString()}</strong></td>
                            <td class="text-right"><strong>₱${(fy.totalBudget - fy.totalSpent).toLocaleString()}</strong></td>
                            <td class="text-right"><strong>${fy.totalBudget > 0 ? Math.round((fy.totalSpent / fy.totalBudget) * 100) : 0}%</strong></td>
                        </tr>
                    </tfoot>
                </table>

                <!-- FOOTER -->
                <p class="footer-text">
                    Generated on: ${now}<br>
                    <span class="gen">This is a computer-generated document. No signature required.</span>
                </p>
            </body>
            </html>`;

            const blob = new Blob([html], { type: 'application/msword' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `FY_${fy.year}_Budget_Report.doc`;
            a.click();
            URL.revokeObjectURL(url);
            showToast('DOC exported successfully!');
        }

        // ── PDF Export ──
        function exportPDF() {

            const fy = fiscalYears[selectedFY];

            const now = new Date().toLocaleString("en-PH", {
                timeZone: "Asia/Manila"
            });

            const element = document.createElement("div");

            element.innerHTML = `

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

.pdf-page{

    width:100%;
    font-family:Arial,sans-serif;
    color:#000;
    font-size:11pt;
    line-height:1.35;

}

.header{

    width:100%;
    border-bottom:2px solid #000;
    padding-bottom:8px;
    margin-bottom:15px;

}

.header-table{

    width:100%;
    border-collapse:collapse;

}

.header-table td{

    border:none;
    vertical-align:middle;

}

.header-center{

    text-align:center;

}

.header-center p{

    margin:1px 0;

}

.gov{

    font-size:10pt;

}

.office{

    font-size:11pt;
    font-weight:bold;

}

.municipality{

    font-size:10pt;

}

.generated{

    text-align:right;
    font-size:8.5pt;
    width:170px;

}

.report-title{

    text-align:center;
    font-size:16pt;
    font-weight:bold;
    margin-bottom:5px;
    text-transform:uppercase;

}

.report-subtitle{

    text-align:center;
    font-size:11pt;
    margin-bottom:18px;

}

.info-table{

    width:100%;
    border-collapse:collapse;
    margin-bottom:18px;
    font-size:10pt;

}

.info-table td{

    border:1px solid #000;
    padding:6px 8px;

}

.report{

    width:100%;
    border-collapse:collapse;
    table-layout:fixed;
    font-size:9.5pt;

}

.report th{

    border:1px solid #000;
    background:#EDEDED;
    font-weight:bold;
    text-align:center;
    padding:7px;

}

.report td{

    border:1px solid #000;
    padding:6px;

}

.report th:nth-child(1){

    width:26%;

}

.report th:nth-child(2){

    width:18%;

}

.report th:nth-child(3){

    width:16%;

}

.report th:nth-child(4){

    width:16%;

}

.report th:nth-child(5){

    width:16%;

}

.report th:nth-child(6){

    width:8%;

}

.right{

    text-align:right;

}

.center{

    text-align:center;

}

tfoot td{

    background:#F5F5F5;
    font-weight:bold;

}

.signature{

    width:100%;
    margin-top:35px;
    border-collapse:collapse;

}

.signature td{

    width:50%;
    border:none;
    text-align:center;
    vertical-align:top;

}

.sign-line{

    width:220px;
    border-top:1px solid #000;
    margin:38px auto 5px auto;

}

.footer{

    margin-top:25px;
    border-top:1px solid #000;
    padding-top:8px;
    text-align:center;
    font-size:9pt;

}

</style>

<div class="pdf-page">

<div class="header">

<table class="header-table">

<tr>

<td style="width:170px;"></td>

<td class="header-center">

<p class="gov"><strong>Republic of the Philippines</strong></p>

<p class="gov">Province of Negros Occidental</p>

<p class="office">Municipal Social Welfare and Development Office</p>

<p class="municipality">Municipality of San Enrique</p>

</td>

<td class="generated">

Generated:<br>

${now}

</td>

</tr>

</table>

</div>

<div class="report-title">

FISCAL YEAR BUDGET REPORT

</div>

<div class="report-subtitle">

Fiscal Year ${fy.year}

</div>

<table class="info-table">

<tr>

<td><strong>Fiscal Year</strong></td>

<td>${fy.year}</td>

<td><strong>Status</strong></td>

<td>${fy.status}</td>

</tr>

<tr>

<td><strong>Reporting Period</strong></td>

<td>${fy.start} – ${fy.end}</td>

<td><strong>Report Type</strong></td>

<td>Budget Summary</td>

</tr>

</table>

<table class="report">

<thead>

<tr>

<th>Program</th>

<th>Funding Source</th>

<th>Annual Budget</th>

<th>Amount Spent</th>

<th>Remaining Budget</th>

<th>Utilization</th>

</tr>

</thead>

<tbody>

${fy.programs.map(p => {

                const remaining = p.budget - p.spent;

                const percent = p.budget > 0
                    ? Math.round((p.spent / p.budget) * 100)
                    : 0;

                return `

<tr>

<td>${p.program}</td>

<td>${p.source}</td>

<td class="right">₱${p.budget.toLocaleString()}</td>

<td class="right">₱${p.spent.toLocaleString()}</td>

<td class="right">₱${remaining.toLocaleString()}</td>

<td class="center">${percent}%</td>

</tr>

`;

            }).join("")}

</tbody>

<tfoot>

<tr>

<td colspan="2" class="right">
TOTAL
</td>

<td class="right">
<strong>₱${fy.totalBudget.toLocaleString()}</strong>
</td>

<td class="right">
<strong>₱${fy.totalSpent.toLocaleString()}</strong>
</td>

<td class="right">
<strong>₱${(fy.totalBudget - fy.totalSpent).toLocaleString()}</strong>
</td>

<td class="center">
<strong>
${fy.totalBudget > 0
                    ? Math.round((fy.totalSpent / fy.totalBudget) * 100)
                    : 0}%
</strong>
</td>

</tr>

</tfoot>

</table>

<table class="signature">

<tr>

<td>

<div class="sign-line"></div>

<strong>Prepared by</strong>

<br>

<span style="font-size:9pt;">
MSWDO Personnel
</span>

</td>

<td>

<div class="sign-line"></div>

<strong>Reviewed by</strong>

<br>

<span style="font-size:9pt;">
Municipal Social Welfare and Development Officer
</span>

</td>

</tr>

</table>

<div class="footer">

Municipal Social Welfare and Development Office Information System

<br>

San Enrique, Negros Occidental

<br><br>

This is a computer-generated report. No signature is required unless otherwise specified.

</div>

</div>

`;

            const options = {

                margin: [0.35, 0.40, 0.35, 0.40],

                filename: `FY_${fy.year}_Budget_Report.pdf`,

                image: {
                    type: "jpeg",
                    quality: 1
                },

                html2canvas: {

                    scale: 3,

                    useCORS: true,

                    scrollY: 0,

                    letterRendering: true

                },

                jsPDF: {

                    unit: "in",

                    format: "letter",

                    orientation: "portrait"

                },

                pagebreak: {

                    mode: ["avoid-all", "css", "legacy"]

                }

            };

            html2pdf()

                .set(options)

                .from(element)

                .save()

                .then(() => {

                    showToast("PDF exported successfully!");

                });

        }

        // ── Multi-sheet Excel export using SheetJS ──
        function exportXLSX() {
            if (typeof XLSX === 'undefined') {
                showToast('Excel library not loaded. Falling back to single-sheet export.', 'error');
                exportBudgetFallbackXLS();
                return;
            }

            try {
                const wb = XLSX.utils.book_new();
                const sorted = [...fiscalYears].sort((a, b) => b.year - a.year);

                sorted.forEach(fy => {
                    const data = [
                        ['Program', 'Funding Source', 'Annual Budget', 'Spent', 'Remaining', 'Utilization']
                    ];

                    fy.programs.forEach(p => {
                        const remaining = p.budget - p.spent;
                        const pct = p.budget > 0 ? Math.round((p.spent / p.budget) * 100) : 0;
                        data.push([
                            p.program,
                            p.source,
                            p.budget,
                            p.spent,
                            remaining,
                            pct + '%'
                        ]);
                    });

                    data.push([
                        'TOTAL',
                        '',
                        fy.totalBudget,
                        fy.totalSpent,
                        fy.totalBudget - fy.totalSpent,
                        fy.totalBudget > 0 ? Math.round((fy.totalSpent / fy.totalBudget) * 100) + '%' : '0%'
                    ]);

                    const ws = XLSX.utils.aoa_to_sheet(data);
                    ws['!cols'] = [
                        { wch: 22 },
                        { wch: 16 },
                        { wch: 16 },
                        { wch: 14 },
                        { wch: 14 },
                        { wch: 12 }
                    ];

                    const sheetName = `FY ${fy.year}`;
                    XLSX.utils.book_append_sheet(wb, ws, sheetName);
                });

                const now = new Date();
                const dateStr = now.toISOString().slice(0, 10);
                XLSX.writeFile(wb, `MSWDO_Fiscal_Years_${dateStr}.xlsx`);
                showToast('XLSX exported with ' + sorted.length + ' sheets (one per Fiscal Year)!');
            } catch (err) {
                console.error('XLSX export error:', err);
                showToast('Excel export failed. Trying fallback...', 'error');
                exportBudgetFallbackXLS();
            }
        }

        // Fallback: single HTML-based XLS for the selected FY
        function exportBudgetFallbackXLS() {
            const fy = fiscalYears[selectedFY];
            const now = new Date().toLocaleString('en-PH', { timeZone: 'Asia/Manila' });

            let html = `
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; font-size: 11pt; }
                    table { width: 100%; border-collapse: collapse; font-size: 10pt; }
                    th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
                    th { background: #f0f0f0; font-weight: 700; }
                    .text-right { text-align: right; }
                </style>
            </head>
            <body>
                <h2>MSWDO San Enrique – FY ${fy.year} Budget Report</h2>
                <p>Period: ${fy.start} – ${fy.end} | Status: ${fy.status}</p>
                <table>
                    <thead><tr><th>Program</th><th>Funding Source</th><th class="text-right">Budget</th><th class="text-right">Spent</th><th class="text-right">Remaining</th><th class="text-right">Utilization</th></tr></thead>
                    <tbody>`;

            fy.programs.forEach(p => {
                const remaining = p.budget - p.spent;
                const pct = p.budget > 0 ? Math.round((p.spent / p.budget) * 100) : 0;
                html += `<tr><td>${p.program}</td><td>${p.source}</td><td class="text-right">₱${p.budget.toLocaleString()}</td><td class="text-right">₱${p.spent.toLocaleString()}</td><td class="text-right">₱${remaining.toLocaleString()}</td><td class="text-right">${pct}%</td></tr>`;
            });

            html += `
                    </tbody>
                </table>
                <p style="text-align:center;font-size:9pt;color:#888;margin-top:16px;">Generated: ${now}</p>
            </body>
            </html>`;

            const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `FY_${fy.year}_Budget_Report.xls`;
            a.click();
            URL.revokeObjectURL(url);
            showToast('XLS exported (single sheet fallback).');
        }

        // ── Toast ──
        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            if (!t) return;
            document.getElementById('toastMsg').textContent = msg;
            const icon = t.querySelector('i');
            if (icon) {
                if (type === 'error') {
                    icon.className = 'fas fa-exclamation-circle text-red-300';
                    t.className = t.className.replace(/bg-\w+-\d+/, 'bg-red-700');
                } else if (type === 'warning') {
                    icon.className = 'fas fa-exclamation-triangle text-amber-300';
                    t.className = t.className.replace(/bg-\w+-\d+/, 'bg-amber-700');
                } else {
                    icon.className = 'fas fa-check-circle text-green-300';
                    t.className = t.className.replace(/bg-\w+-\d+/, 'bg-green-700');
                }
            }
            t.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
            t.classList.add('opacity-100', 'translate-y-0');
            clearTimeout(t._timeout);
            t._timeout = setTimeout(() => {
                t.classList.add('opacity-0', 'translate-y-4');
                t.classList.remove('opacity-100', 'translate-y-0');
                // Reset toast color after hiding
                setTimeout(() => {
                    t.className = t.className.replace(/bg-\w+-\d+/, 'bg-green-700');
                }, 300);
            }, 3500);
        }

        // ── Current Date ──
        document.getElementById('currentDate').textContent = new Date().toLocaleDateString('en-PH', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        document.getElementById('printDate').textContent = new Date().toLocaleString('en-PH', { timeZone: 'Asia/Manila' });

        // ── Init ──
        renderFYCards();
        renderBudgetTable();
        updateSelectedFYTitle();
        updatePrintInfo();

        // Close modals on backdrop click
        document.getElementById('newFYModal').addEventListener('click', function (e) {
            if (e.target === this) closeNewFYModal();
        });
        document.getElementById('editBudgetModal').addEventListener('click', function (e) {
            if (e.target === this) closeEditBudgetModal();
        });
        document.getElementById('successModal').addEventListener('click', function (e) {
            if (e.target === this) {
                document.getElementById('successModal').classList.add('hidden');
                document.getElementById('successModal').style.display = 'none';
            }
        });
        document.getElementById('cancelConfirmDialog').addEventListener('click', function (e) {
            if (e.target === this) closeCancelDialog();
        });

        // ── Keyboard: ESC to close modals ──
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const newFYModal = document.getElementById('newFYModal');
                const editBudgetModal = document.getElementById('editBudgetModal');
                const successModal = document.getElementById('successModal');
                const cancelDialog = document.getElementById('cancelConfirmDialog');

                if (cancelDialog && !cancelDialog.classList.contains('hidden')) {
                    closeCancelDialog();
                } else if (newFYModal && !newFYModal.classList.contains('hidden')) {
                    closeNewFYModal();
                } else if (editBudgetModal && !editBudgetModal.classList.contains('hidden')) {
                    closeEditBudgetModal();
                } else if (successModal && !successModal.classList.contains('hidden')) {
                    successModal.classList.add('hidden');
                    successModal.style.display = 'none';
                }
            }
        });

        console.log(' Fiscal Year Management initialized – all enhancements applied.');
        console.log('   - Step 1: Select fiscal year duration');
        console.log('   - Step 2: Edit program budgets');
        console.log('   - Step 3: Final budget table with verification checkbox');
        console.log('   - Success: "All Done!" message with summary');
    </script>
</body>

</html>