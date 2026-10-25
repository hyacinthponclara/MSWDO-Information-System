<?php

require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action !== 'release') {

        echo json_encode([
            'success' => false,
            'message' => 'Invalid request.'
        ]);

        exit;
    }


    $releaseType = $_POST['release_type'] ?? '';



    if ($releaseType === 'availment') {

        $availmentId = (int) ($_POST['availment_id'] ?? 0);

        if ($availmentId <= 0) {

            echo json_encode([
                'success' => false,
                'message' => 'Invalid availment.'
            ]);

            exit;
        }


        try {

            $pdo->beginTransaction();



            $stmt = $pdo->prepare("
                SELECT
                    availment_id,
                    av_amount,
                    av_status,
                    program_id
                FROM availment
                WHERE availment_id = ?
                FOR UPDATE
            ");

            $stmt->execute([$availmentId]);

            $availment = $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$availment) {

                throw new Exception(
                    'Application not found.'
                );
            }


            if ($availment['av_status'] !== 'Approved') {

                throw new Exception(
                    'Only approved applications can be released.'
                );
            }


            $programId = (int) $availment['program_id'];
            $amount = (float) $availment['av_amount'];



            $programStmt = $pdo->prepare("
                SELECT
                    program_id,
                    program_name,
                    prog_annual_budget
                FROM program
                WHERE program_id = ?
                FOR UPDATE
            ");

            $programStmt->execute([$programId]);

            $program = $programStmt->fetch(PDO::FETCH_ASSOC);


            if (!$program) {

                throw new Exception(
                    'Program not found.'
                );
            }


            $annualBudget =
                (float) $program['prog_annual_budget'];



            $aicsSpentStmt = $pdo->prepare("
                SELECT
                    COALESCE(SUM(av_amount), 0)
                FROM availment
                WHERE program_id = ?
                  AND av_status = 'Released'
                  AND av_date_released IS NOT NULL
                  AND YEAR(av_date_released) = YEAR(CURDATE())
            ");

            $aicsSpentStmt->execute([$programId]);

            $releasedAics =
                (float) $aicsSpentStmt->fetchColumn();



            $proposalSpentStmt = $pdo->prepare("
                SELECT
                    COALESCE(SUM(pp_budget), 0)
                FROM project_proposal
                WHERE program_id = ?
                  AND pp_status = 'Released'
                  AND pp_date_released IS NOT NULL
                  AND YEAR(pp_date_released) = YEAR(CURDATE())
            ");

            $proposalSpentStmt->execute([$programId]);

            $releasedProposals =
                (float) $proposalSpentStmt->fetchColumn();


            $available =
                $annualBudget
                - $releasedAics
                - $releasedProposals;


            if ($amount > $available) {

                throw new Exception(
                    'Insufficient budget remaining to release this amount.'
                );
            }



            $update = $pdo->prepare("
                UPDATE availment
                SET
                    av_status = 'Released',
                    av_date_released = NOW()
                WHERE availment_id = ?
                  AND av_status = 'Approved'
            ");

            $update->execute([$availmentId]);


            if ($update->rowCount() !== 1) {

                throw new Exception(
                    'The application could not be released.'
                );
            }


            $pdo->commit();


            echo json_encode([
                'success' => true,
                'message' =>
                    'Funds released and deducted from the budget.',
                'release_type' => 'availment'
            ]);

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }


            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }


        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | RELEASE PROJECT PROPOSAL
    |--------------------------------------------------------------------------
    */

    if ($releaseType === 'proposal') {

        $proposalId =
            (int) ($_POST['proposal_id'] ?? 0);


        if ($proposalId <= 0) {

            echo json_encode([
                'success' => false,
                'message' => 'Invalid project proposal.'
            ]);

            exit;
        }


        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Get and lock proposal
            |--------------------------------------------------------------------------
            */

            $proposalStmt = $pdo->prepare("
                SELECT
                    proposal_id,
                    pp_title,
                    pp_budget,
                    pp_status,
                    pp_date_approved,
                    pp_date_released,
                    program_id
                FROM project_proposal
                WHERE proposal_id = ?
                FOR UPDATE
            ");

            $proposalStmt->execute([$proposalId]);

            $proposal =
                $proposalStmt->fetch(PDO::FETCH_ASSOC);


            if (!$proposal) {

                throw new Exception(
                    'Project proposal not found.'
                );
            }


            if ($proposal['pp_status'] !== 'Approved') {

                throw new Exception(
                    'Only approved project proposals can be released.'
                );
            }


            $programId =
                (int) $proposal['program_id'];

            $amount =
                (float) $proposal['pp_budget'];



            $programStmt = $pdo->prepare("
                SELECT
                    program_id,
                    program_name,
                    prog_annual_budget
                FROM program
                WHERE program_id = ?
                FOR UPDATE
            ");

            $programStmt->execute([$programId]);

            $program =
                $programStmt->fetch(PDO::FETCH_ASSOC);


            if (!$program) {

                throw new Exception(
                    'Program assigned to this proposal was not found.'
                );
            }


            $annualBudget =
                (float) $program['prog_annual_budget'];



            $aicsSpentStmt = $pdo->prepare("
                SELECT
                    COALESCE(SUM(av_amount), 0)
                FROM availment
                WHERE program_id = ?
                  AND av_status = 'Released'
                  AND av_date_released IS NOT NULL
                  AND YEAR(av_date_released) = YEAR(CURDATE())
            ");

            $aicsSpentStmt->execute([$programId]);

            $releasedAics =
                (float) $aicsSpentStmt->fetchColumn();



            $proposalSpentStmt = $pdo->prepare("
                SELECT
                    COALESCE(SUM(pp_budget), 0)
                FROM project_proposal
                WHERE program_id = ?
                  AND pp_status = 'Released'
                  AND pp_date_released IS NOT NULL
                  AND YEAR(pp_date_released) = YEAR(CURDATE())
            ");

            $proposalSpentStmt->execute([$programId]);

            $releasedProposals =
                (float) $proposalSpentStmt->fetchColumn();



            $available =
                $annualBudget
                - $releasedAics
                - $releasedProposals;


            if ($amount > $available) {

                throw new Exception(
                    'Insufficient budget remaining to release this project proposal.'
                );
            }



            $updateProposal = $pdo->prepare("
                UPDATE project_proposal
                SET
                    pp_status = 'Released',
                    pp_date_released = NOW()
                WHERE proposal_id = ?
                  AND pp_status = 'Approved'
            ");

            $updateProposal->execute([$proposalId]);


            if ($updateProposal->rowCount() !== 1) {

                throw new Exception(
                    'The project proposal could not be released.'
                );
            }


            $pdo->commit();


            echo json_encode([
                'success' => true,
                'message' =>
                    'Project proposal released and deducted from the budget.',
                'release_type' => 'proposal'
            ]);

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }


            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }


        exit;
    }


    echo json_encode([
        'success' => false,
        'message' => 'Invalid release type.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| GET ALL PROGRAM BUDGETS
|--------------------------------------------------------------------------
|
| For each program:
|
| Total          = annual budget
| Awaiting       = Approved AICS + Approved proposals
| Released       = Released AICS + Released proposals
| Available      = Total - Released
|
|--------------------------------------------------------------------------
*/

$budgetStmt = $pdo->query("
    SELECT
        p.program_id,
        p.program_name,
        p.prog_annual_budget,

        COALESCE(av.approved_availments, 0)
            AS approved_availments,

        COALESCE(av.released_availments, 0)
            AS released_availments,

        COALESCE(pp.approved_proposals, 0)
            AS approved_proposals,

        COALESCE(pp.released_proposals, 0)
            AS released_proposals

    FROM program p

    LEFT JOIN (

        SELECT
            program_id,

            SUM(
                CASE
                    WHEN av_status = 'Approved'
                    THEN av_amount
                    ELSE 0
                END
            ) AS approved_availments,

            SUM(
                CASE
                    WHEN av_status = 'Released'
                    THEN av_amount
                    ELSE 0
                END
            ) AS released_availments

        FROM availment

        WHERE
            YEAR(av_date_applied) = YEAR(CURDATE())

        GROUP BY program_id

    ) av

        ON av.program_id = p.program_id


    LEFT JOIN (

        SELECT
            program_id,

            SUM(
                CASE
                    WHEN pp_status = 'Approved'
                    THEN pp_budget
                    ELSE 0
                END
            ) AS approved_proposals,

            SUM(
                CASE
                    WHEN pp_status = 'Released'
                    THEN pp_budget
                    ELSE 0
                END
            ) AS released_proposals

        FROM project_proposal

        WHERE
            YEAR(pp_date_submitted) = YEAR(CURDATE())

        GROUP BY program_id

    ) pp

        ON pp.program_id = p.program_id


    ORDER BY p.program_id ASC
");

$programBudgets = [];

while ($row = $budgetStmt->fetch(PDO::FETCH_ASSOC)) {

    $total =
        (float) $row['prog_annual_budget'];


    $approvedAvailments =
        (float) $row['approved_availments'];


    $releasedAvailments =
        (float) $row['released_availments'];


    $approvedProposals =
        (float) $row['approved_proposals'];


    $releasedProposals =
        (float) $row['released_proposals'];


    $awaitingRelease =
        $approvedAvailments
        + $approvedProposals;


    $released =
        $releasedAvailments
        + $releasedProposals;


    $available =
        max(
            0,
            $total - $released
        );


    $pctUsed =
        $total > 0
            ? ($released / $total) * 100
            : 0;


    $programBudgets[] = [

        'program_id' =>
            (int) $row['program_id'],

        'program_name' =>
            $row['program_name'],

        'total' =>
            $total,

        'approved_availments' =>
            $approvedAvailments,

        'approved_proposals' =>
            $approvedProposals,

        'awaiting_release' =>
            $awaitingRelease,

        'released_availments' =>
            $releasedAvailments,

        'released_proposals' =>
            $releasedProposals,

        'released' =>
            $released,

        'available' =>
            $available,

        'pct_used' =>
            round($pctUsed, 1)
    ];
}



$programIcons = [

    'AICS FBML' =>
        'fa-hand-holding-heart',

    'AICS Educational' =>
        'fa-graduation-cap',

    '4Ps' =>
        'fa-house',

    'SLP' =>
        'fa-seedling',

    'SFP' =>
        'fa-utensils',

    'Day Care Center Program' =>
        'fa-child',

    'Senior Citizen Program' =>
        'fa-user-group',

    'PWD Program' =>
        'fa-wheelchair',

    'Solo Parent Program' =>
        'fa-user-shield',

    'Women and Child Protection' =>
        'fa-people-roof'
];



$pendingStmt = $pdo->prepare("
    SELECT
        a.availment_id,
        a.av_amount,
        a.av_date_applied,
        a.av_status,

        CONCAT(
            c.cl_firstname,
            ' ',
            c.cl_lastname
        ) AS beneficiary_name,

        p.program_name,

        CASE

            WHEN p.program_name = 'AICS FBML'
            THEN

                CASE

                    WHEN med.aics_medical_id IS NOT NULL
                        THEN 'Medical'

                    WHEN fin.aics_financial_id IS NOT NULL
                        THEN 'Financial'

                    WHEN bur.aics_burial_id IS NOT NULL
                        THEN 'Burial'

                    WHEN liv.aics_livelihood_id IS NOT NULL
                        THEN 'Livelihood'

                    ELSE 'Other'

                END

            WHEN p.program_name = 'AICS Educational'
                THEN 'Educational'

            ELSE p.program_name

        END AS assistance_type

    FROM availment a

    JOIN client c
        ON a.client_id = c.client_id

    JOIN program p
        ON a.program_id = p.program_id

    LEFT JOIN AICS_MEDICAL med
        ON med.availment_id = a.availment_id

    LEFT JOIN AICS_FINANCIAL fin
        ON fin.availment_id = a.availment_id

    LEFT JOIN AICS_BURIAL bur
        ON bur.availment_id = a.availment_id

    LEFT JOIN AICS_LIVELIHOOD liv
        ON liv.availment_id = a.availment_id

    WHERE a.av_status = 'Approved'

      AND p.program_name IN (
          'AICS FBML',
          'AICS Educational'
      )

    ORDER BY a.av_date_applied ASC
");

$pendingStmt->execute();

$pendingApplications =
    $pendingStmt->fetchAll(PDO::FETCH_ASSOC);


$proposalStmt = $pdo->prepare("
    SELECT
        pp.proposal_id,
        pp.pp_title,
        pp.pp_budget,
        pp.pp_date_submitted,
        pp.pp_date_approved,
        pp.pp_status,
        p.program_name

    FROM project_proposal pp

    JOIN program p
        ON pp.program_id = p.program_id

    WHERE pp.pp_status = 'Approved'

    ORDER BY
        COALESCE(
            pp.pp_date_approved,
            pp.pp_date_submitted
        ) ASC
");

$proposalStmt->execute();

$pendingProposals =
    $proposalStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Release Queue – MSWDO San Enrique
    </title>


    <script src="https://cdn.tailwindcss.com"></script>


    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
    >


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


    <style>

        body {
            font-family: 'DM Sans', sans-serif;
        }

        .sidebar-item {
            transition: all .15s ease;
        }

        .sidebar-item:hover {
            background: rgba(255,255,255,.07);
            color: rgba(255,255,255,.95);
        }

        .sidebar-item.active {
            background: rgba(26,92,58,.25);
            border-left-color: #C49A2A;
            color: #fff;
        }

        .table-row {
            transition: background .12s;
        }

        .table-row:hover {
            background: #EEF6F0;
        }

        .badge-approved {
            background: #D1FAE5;
            color: #059669;
        }

        .badge-proposal {
            background: #EDE9FE;
            color: #6D28D9;
        }

        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(26,92,58,.2);
            border-radius: 2px;
        }

    </style>

</head>


<body class="bg-slate2 min-h-screen flex">


<?php require 'sidebar.php'; ?>


<div class="ml-64 flex-1 flex flex-col min-h-screen">


    <!-- HEADER -->

    <header
        class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20"
    >

        <div class="flex items-center gap-2 text-[13px]">

            <span class="text-green-600 font-semibold">
                Release Queue
            </span>

        </div>

    </header>


    <main class="flex-1 p-6 space-y-5 overflow-y-auto">


        <!-- TITLE -->

        <div
            class="flex flex-wrap items-center justify-between gap-3"
        >

            <div>

                <h1 class="text-xl font-serif text-green-600">
                    Applications Awaiting Release
                </h1>

                <p class="text-[11px] text-slate-400 mt-1">
                    Approved applications and project proposals waiting for release
                </p>

            </div>

        </div>


        <!-- =========================================================
             ALL PROGRAM BUDGETS
        ========================================================== -->

        <div>

            <div
                class="flex items-center justify-between mb-3"
            >

                <div>

                    <h2 class="text-[13px] font-semibold text-green-600">
                        Program Budget Status
                    </h2>

                    <p class="text-[10px] text-slate-400">
                        Only released amounts are deducted from the budget.
                    </p>

                </div>

                <span
                    class="text-[10px] text-slate-400"
                >
                    FY <?= date('Y') ?>
                </span>

            </div>


            <div
                class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"
            >

                <?php foreach ($programBudgets as $budget): ?>

                    <?php

                    $programName =
                        $budget['program_name'];

                    $icon =
                        $programIcons[$programName]
                        ?? 'fa-folder';


                    $pct =
                        min(
                            100,
                            max(
                                0,
                                (float) $budget['pct_used']
                            )
                        );


                    if ($pct >= 80) {

                        $barColor =
                            'bg-red-500';

                        $pctColor =
                            'text-red-600';

                    } elseif ($pct >= 60) {

                        $barColor =
                            'bg-amber-400';

                        $pctColor =
                            'text-amber-600';

                    } else {

                        $barColor =
                            'bg-emerald-500';

                        $pctColor =
                            'text-emerald-600';
                    }

                    ?>


                    <div
                        class="bg-white rounded-2xl border border-slate-200 p-5"
                    >

                        <!-- Program -->

                        <div
                            class="flex items-center justify-between mb-4"
                        >

                            <div class="flex items-center gap-2">

                                <div
                                    class="w-8 h-8 rounded-lg bg-green-50 text-green-600 flex items-center justify-center"
                                >

                                    <i
                                        class="fas <?= htmlspecialchars(
                                            $icon,
                                            ENT_QUOTES
                                        ) ?> text-sm"
                                    ></i>

                                </div>


                                <div>

                                    <h3
                                        class="text-[12px] font-semibold text-green-700"
                                    >
                                        <?= htmlspecialchars(
                                            $programName,
                                            ENT_QUOTES
                                        ) ?>
                                    </h3>

                                    <p
                                        class="text-[9px] text-slate-400"
                                    >
                                        Program Budget
                                    </p>

                                </div>

                            </div>


                            <span
                                class="text-[9px] font-semibold px-2 py-1 rounded-full <?= $pctColor ?> bg-slate-50"
                            >
                                <?= number_format(
                                    $pct,
                                    1
                                ) ?>% used
                            </span>

                        </div>


                        <!-- Total -->

                        <div class="mb-3">

                            <p
                                class="text-[9px] uppercase tracking-wider text-slate-400"
                            >
                                Annual Budget
                            </p>

                            <p
                                class="text-xl font-bold text-green-600"
                            >
                                ₱<?= number_format(
                                    $budget['total']
                                ) ?>
                            </p>

                        </div>


                        <!-- Details -->

                        <div
                            class="grid grid-cols-3 gap-2"
                        >

                            <div
                                class="bg-amber-50 rounded-lg p-2.5"
                            >

                                <p
                                    class="text-[8px] uppercase tracking-wide text-amber-600"
                                >
                                    Awaiting
                                </p>

                                <p
                                    class="text-[12px] font-bold text-amber-700 mt-0.5"
                                >
                                    ₱<?= number_format(
                                        $budget['awaiting_release']
                                    ) ?>
                                </p>

                            </div>


                            <div
                                class="bg-emerald-50 rounded-lg p-2.5"
                            >

                                <p
                                    class="text-[8px] uppercase tracking-wide text-emerald-600"
                                >
                                    Released
                                </p>

                                <p
                                    class="text-[12px] font-bold text-emerald-700 mt-0.5"
                                >
                                    ₱<?= number_format(
                                        $budget['released']
                                    ) ?>
                                </p>

                            </div>


                            <div
                                class="bg-blue-50 rounded-lg p-2.5"
                            >

                                <p
                                    class="text-[8px] uppercase tracking-wide text-blue-600"
                                >
                                    Available
                                </p>

                                <p
                                    class="text-[12px] font-bold text-blue-700 mt-0.5"
                                >
                                    ₱<?= number_format(
                                        $budget['available']
                                    ) ?>
                                </p>

                            </div>

                        </div>


                        <!-- Progress -->

                        <div class="mt-4">

                            <div
                                class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden"
                            >

                                <div
                                    class="h-1.5 rounded-full <?= $barColor ?>"
                                    style="width: <?= $pct ?>%"
                                ></div>

                            </div>


                            <div
                                class="flex justify-between mt-1.5"
                            >

                                <span
                                    class="text-[9px] text-slate-400"
                                >
                                    Released spending
                                </span>

                                <span
                                    class="text-[9px] font-semibold <?= $pctColor ?>"
                                >
                                    ₱<?= number_format(
                                        $budget['released']
                                    ) ?>
                                </span>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>


        <!-- =========================================================
             PROJECT PROPOSALS
        ========================================================== -->

        <div
            class="bg-white rounded-2xl border border-slate-200 overflow-hidden"
        >

            <div
                class="px-5 py-4 border-b border-slate-100 flex items-center justify-between"
            >

                <div>

                    <h2
                        class="text-[13px] font-semibold text-green-600"
                    >
                        Project Proposals Awaiting Release
                    </h2>

                    <p
                        class="text-[10px] text-slate-400 mt-0.5"
                    >
                        Approved proposals are not deducted until released.
                    </p>

                </div>


                <span
                    class="text-[10px] font-semibold px-2.5 py-1 rounded-full bg-purple-50 text-purple-700"
                >
                    <?= count($pendingProposals) ?>
                    awaiting
                </span>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-[12px]">

                    <thead>

                        <tr
                            class="bg-slate-50 border-b border-slate-100"
                        >

                            <th
                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                            >
                                Proposal
                            </th>

                            <th
                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                            >
                                Program
                            </th>

                            <th
                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                            >
                                Amount
                            </th>

                            <th
                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                            >
                                Approved
                            </th>

                            <th
                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                            >
                                Status
                            </th>

                            <th
                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                            >
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody
                        class="divide-y divide-slate-100"
                    >

                        <?php if (empty($pendingProposals)): ?>

                            <tr>

                                <td
                                    colspan="6"
                                    class="px-5 py-8 text-center text-slate-400"
                                >

                                    No project proposals awaiting release.

                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($pendingProposals as $proposal): ?>

                                <?php

                                $approvedDate =
                                    $proposal['pp_date_approved']
                                    ?: $proposal['pp_date_submitted'];

                                ?>


                                <tr class="table-row">

                                    <td class="px-5 py-3">

                                        <div
                                            class="font-medium text-green-700"
                                        >

                                            <?= htmlspecialchars(
                                                $proposal['pp_title'],
                                                ENT_QUOTES
                                            ) ?>

                                        </div>


                                        <div
                                            class="text-[10px] text-slate-400 mt-0.5"
                                        >

                                            Proposal #
                                            <?= (int) $proposal['proposal_id'] ?>

                                        </div>

                                    </td>


                                    <td class="px-5 py-3">

                                        <span
                                            class="badge-proposal px-2 py-0.5 rounded text-[10px] font-semibold"
                                        >

                                            <?= htmlspecialchars(
                                                $proposal['program_name'],
                                                ENT_QUOTES
                                            ) ?>

                                        </span>

                                    </td>


                                    <td
                                        class="px-5 py-3 font-semibold text-slate-700"
                                    >

                                        ₱<?= number_format(
                                            (float) $proposal['pp_budget']
                                        ) ?>

                                    </td>


                                    <td
                                        class="px-5 py-3 text-slate-400"
                                    >

                                        <?= date(
                                            'M j, Y',
                                            strtotime($approvedDate)
                                        ) ?>

                                    </td>


                                    <td class="px-5 py-3">

                                        <span
                                            class="badge-approved px-2.5 py-0.5 rounded-full text-[10px] font-semibold"
                                        >

                                            <?= htmlspecialchars(
                                                $proposal['pp_status'],
                                                ENT_QUOTES
                                            ) ?>

                                        </span>

                                    </td>


                                    <td class="px-5 py-3">

                                        <button
                                            onclick="handleProposalRelease(
                                                <?= (int) $proposal['proposal_id'] ?>,
                                                <?= htmlspecialchars(
                                                    json_encode(
                                                        $proposal['pp_title'],
                                                        JSON_HEX_TAG |
                                                        JSON_HEX_APOS |
                                                        JSON_HEX_QUOT |
                                                        JSON_HEX_AMP
                                                    ),
                                                    ENT_QUOTES
                                                ) ?>,
                                                <?= (float) $proposal['pp_budget'] ?>
                                            )"
                                            class="text-[11px] font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg px-2.5 py-1 hover:bg-blue-100 transition-colors"
                                        >

                                            <i
                                                class="fas fa-hand-holding-dollar mr-1"
                                            ></i>

                                            Release

                                        </button>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


        <!-- =========================================================
             AICS FILTER
        ========================================================== -->

        <div
            class="flex flex-wrap items-center justify-between gap-3"
        >

            <div
                class="flex flex-wrap items-center gap-3"
            >

                <select
                    id="filterType"
                    class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                    onchange="filterTable()"
                >

                    <option value="all">
                        All AICS
                    </option>

                    <option value="Educational">
                        Educational
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

                </select>


                <div class="relative">

                    <i
                        class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"
                    ></i>

                    <input
                        id="searchBeneficiary"
                        type="text"
                        placeholder="Search beneficiary..."
                        class="text-[12px] pl-8 pr-3 py-1.5 border border-slate-200 rounded-lg bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none w-48"
                        oninput="filterTable()"
                    >

                </div>

            </div>


            <span
                class="text-[11px] text-slate-400"
                id="rowCount"
            >
                Showing <?= count($pendingApplications) ?>
                AICS applications awaiting release
            </span>

        </div>


        <!-- =========================================================
             AICS TABLE
        ========================================================== -->

        <div
            class="bg-white rounded-2xl border border-slate-200 overflow-hidden"
        >

            <div class="overflow-x-auto">

                <table class="w-full text-[12px]">

                    <thead>

                        <tr
                            class="bg-slate-50 border-b border-slate-100"
                        >

                            <th
                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                            >
                                Beneficiary
                            </th>

                            <th
                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                            >
                                Budget Source
                            </th>

                            <th
                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                            >
                                Type
                            </th>

                            <th
                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                            >
                                Amount
                            </th>

                            <th
                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                            >
                                Date Applied
                            </th>

                            <th
                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                            >
                                Status
                            </th>

                            <th
                                class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold"
                            >
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody
                        class="divide-y divide-slate-100"
                        id="pendingTableBody"
                    >

                        <?php if (empty($pendingApplications)): ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="px-5 py-8 text-center text-slate-400"
                                >

                                    No AICS applications awaiting release right now.

                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($pendingApplications as $app): ?>

                                <?php

                                $isFbml =
                                    $app['program_name']
                                    === 'AICS FBML';


                                $sourceLabel =
                                    $isFbml
                                        ? 'AICS FBML'
                                        : 'AICS Educational';


                                $sourceBadgeCls =
                                    $isFbml
                                        ? 'bg-blue-100 text-blue-700'
                                        : 'bg-purple-100 text-purple-700';


                                $budgetType =
                                    $isFbml
                                        ? 'fbml'
                                        : 'edu';


                                ?>


                                <tr
                                    class="table-row"
                                    data-type="<?= htmlspecialchars(
                                        $app['assistance_type'],
                                        ENT_QUOTES
                                    ) ?>"
                                    data-search="<?= htmlspecialchars(
                                        $app['beneficiary_name'],
                                        ENT_QUOTES
                                    ) ?>"
                                    id="row-<?= (int) $app['availment_id'] ?>"
                                >

                                    <td
                                        class="px-5 py-3 font-medium text-green-700"
                                    >

                                        <?= htmlspecialchars(
                                            $app['beneficiary_name'],
                                            ENT_QUOTES
                                        ) ?>

                                    </td>


                                    <td class="px-5 py-3">

                                        <span
                                            class="<?= $sourceBadgeCls ?> px-2 py-0.5 rounded text-[10px] font-semibold"
                                        >

                                            <?= $sourceLabel ?>

                                        </span>

                                    </td>


                                    <td
                                        class="px-5 py-3 text-slate-600"
                                    >

                                        <?= htmlspecialchars(
                                            $app['assistance_type'],
                                            ENT_QUOTES
                                        ) ?>

                                    </td>


                                    <td
                                        class="px-5 py-3 font-semibold text-slate-700"
                                    >

                                        ₱<?= number_format(
                                            (float) $app['av_amount']
                                        ) ?>

                                    </td>


                                    <td
                                        class="px-5 py-3 text-slate-400"
                                    >

                                        <?= date(
                                            'M j, Y',
                                            strtotime(
                                                $app['av_date_applied']
                                            )
                                        ) ?>

                                    </td>


                                    <td class="px-5 py-3">

                                        <span
                                            class="badge-approved px-2.5 py-0.5 rounded-full text-[10px] font-semibold"
                                        >

                                            <?= htmlspecialchars(
                                                $app['av_status']
                                            ) ?>

                                        </span>

                                    </td>


                                    <td class="px-5 py-3">

                                        <button
                                            onclick="handleRelease(
                                                <?= (int) $app['availment_id'] ?>,
                                                <?= htmlspecialchars(
                                                    json_encode(
                                                        $app['beneficiary_name'],
                                                        JSON_HEX_TAG |
                                                        JSON_HEX_APOS |
                                                        JSON_HEX_QUOT |
                                                        JSON_HEX_AMP
                                                    ),
                                                    ENT_QUOTES
                                                ) ?>,
                                                <?= (float) $app['av_amount'] ?>,
                                                <?= htmlspecialchars(
                                                    json_encode(
                                                        $budgetType
                                                    ),
                                                    ENT_QUOTES
                                                ) ?>
                                            )"
                                            class="text-[11px] font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg px-2.5 py-1 hover:bg-blue-100 transition-colors"
                                        >

                                            <i
                                                class="fas fa-hand-holding-dollar mr-1"
                                            ></i>

                                            Release

                                        </button>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <div
                class="flex items-center justify-between px-5 py-3 border-t border-slate-100"
            >

                <span
                    class="text-[11px] text-slate-400"
                >

                    Showing
                    <span id="visibleCount">
                        <?= count($pendingApplications) ?>
                    </span>

                    of

                    <span id="totalCount">
                        <?= count($pendingApplications) ?>
                    </span>

                    applications awaiting release

                </span>

            </div>

        </div>


    </main>


    <footer
        class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400"
    >

        <span>
            MSWDO San Enrique Information System
        </span>

        <span>
            FY <?= date('Y') ?>
        </span>

    </footer>


</div>


<!-- =========================================================
     TOAST
========================================================== -->

<div
    id="toast"
    class="fixed bottom-6 right-6 bg-green-700 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50"
>

    <i
        id="toastIcon"
        class="fas fa-check-circle text-green-300"
    ></i>

    <span id="toastMsg">
        Action completed!
    </span>

</div>


<script>

/*
|--------------------------------------------------------------------------
| AICS FILTER
|--------------------------------------------------------------------------
*/

function filterTable() {

    const filterValue =
        document
            .getElementById('filterType')
            .value
            .toLowerCase();


    const searchValue =
        document
            .getElementById('searchBeneficiary')
            .value
            .toLowerCase()
            .trim();


    const rows =
        document.querySelectorAll(
            '#pendingTableBody tr[data-type]'
        );


    let visibleCount = 0;


    rows.forEach(row => {

        const type =
            (
                row.getAttribute('data-type')
                || ''
            ).toLowerCase();


        const search =
            (
                row.getAttribute('data-search')
                || ''
            ).toLowerCase();


        const typeMatch =
            filterValue === 'all'
            || type === filterValue;


        const searchMatch =
            search === ''
            || search.includes(searchValue);


        if (typeMatch && searchMatch) {

            row.style.display = '';

            visibleCount++;

        } else {

            row.style.display = 'none';

        }

    });


    document.getElementById(
        'visibleCount'
    ).textContent = visibleCount;


    document.getElementById(
        'totalCount'
    ).textContent = rows.length;


    document.getElementById(
        'rowCount'
    ).textContent =
        `Showing ${visibleCount} AICS applications awaiting release`;
}


/*
|--------------------------------------------------------------------------
| TOAST
|--------------------------------------------------------------------------
*/

function showToast(
    message,
    type = 'success'
) {

    const toast =
        document.getElementById('toast');


    const msg =
        document.getElementById('toastMsg');


    const icon =
        document.getElementById('toastIcon');


    msg.textContent = message;


    if (type === 'error') {

        icon.className =
            'fas fa-exclamation-circle text-red-300';

    } else {

        icon.className =
            'fas fa-check-circle text-green-300';

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


/*
|--------------------------------------------------------------------------
| RELEASE AICS
|--------------------------------------------------------------------------
*/

async function submitAicsRelease(
    availmentId,
    name,
    amount,
    budgetType
) {

    const params =
        new URLSearchParams({

            action: 'release',

            release_type: 'availment',

            availment_id: availmentId

        });


    try {

        const response =
            await fetch(
                window.location.pathname,
                {

                    method: 'POST',

                    headers: {
                        'Content-Type':
                            'application/x-www-form-urlencoded'
                    },

                    body:
                        params.toString()

                }
            );


        const data =
            await response.json();


        if (!data.success) {

            showToast(
                data.message ||
                'Something went wrong.',
                'error'
            );

            return;
        }


        const budgetName =
            budgetType === 'fbml'
                ? 'AICS FBML'
                : 'AICS Educational';


        showToast(
            `${name}'s ${budgetName} funds released! ₱${amount.toLocaleString()} deducted from the budget.`
        );


        setTimeout(
            () => window.location.reload(),
            1000
        );


    } catch (error) {

        console.error(error);

        showToast(
            'Network error — please try again.',
            'error'
        );

    }
}


/*
|--------------------------------------------------------------------------
| AICS RELEASE CONFIRMATION
|--------------------------------------------------------------------------
*/

function handleRelease(
    availmentId,
    name,
    amount,
    budgetType
) {

    const budgetName =
        budgetType === 'fbml'
            ? 'AICS FBML'
            : 'AICS Educational';


    const confirmed =
        confirm(
            `Release ₱${amount.toLocaleString()} to ${name}? ` +
            `This will deduct the amount from the ${budgetName} budget.`
        );


    if (confirmed) {

        submitAicsRelease(
            availmentId,
            name,
            amount,
            budgetType
        );

    }
}


/*
|--------------------------------------------------------------------------
| RELEASE PROJECT PROPOSAL
|--------------------------------------------------------------------------
*/

async function submitProposalRelease(
    proposalId,
    title,
    amount
) {

    const params =
        new URLSearchParams({

            action: 'release',

            release_type: 'proposal',

            proposal_id: proposalId

        });


    try {

        const response =
            await fetch(
                window.location.pathname,
                {

                    method: 'POST',

                    headers: {
                        'Content-Type':
                            'application/x-www-form-urlencoded'
                    },

                    body:
                        params.toString()

                }
            );


        const data =
            await response.json();


        if (!data.success) {

            showToast(
                data.message ||
                'Something went wrong.',
                'error'
            );

            return;
        }


        showToast(
            `Project proposal "${title}" released! ₱${amount.toLocaleString()} deducted from the budget.`
        );


        setTimeout(
            () => window.location.reload(),
            1000
        );


    } catch (error) {

        console.error(error);

        showToast(
            'Network error — please try again.',
            'error'
        );

    }
}


/*
|--------------------------------------------------------------------------
| PROJECT PROPOSAL RELEASE CONFIRMATION
|--------------------------------------------------------------------------
*/

function handleProposalRelease(
    proposalId,
    title,
    amount
) {

    const confirmed =
        confirm(
            `Release project proposal "${title}" for ₱${amount.toLocaleString()}? ` +
            `This amount will be deducted from the program budget.`
        );


    if (confirmed) {

        submitProposalRelease(
            proposalId,
            title,
            amount
        );

    }
}

</script>


</body>

</html>