<?php
require 'auth.php';
requireRole(['Admin', 'Social Worker', 'Staff']);
require 'db_connect.php';

$client_id = intval($_GET['client_id'] ?? 0);
if ($client_id === 0) {
    header('Location: clientslist.php');
    exit;
}


$stmt = $pdo->prepare("
    SELECT
        c.client_id,
        c.cl_firstname, c.cl_middlename, c.cl_lastname,
        c.cl_age, c.cl_sex,
        c.cl_is_4ps, c.cl_is_pwd, c.cl_is_senior,
        c.cl_is_soloparent, c.cl_is_indigent,
        c.cl_date_registered,
        b.barangay_name
    FROM CLIENT c
    LEFT JOIN BARANGAY b ON c.brgy_id = b.barangay_id
    WHERE c.client_id = :id
    LIMIT 1
");
$stmt->execute([':id' => $client_id]);
$client = $stmt->fetch();

if (!$client) {
    header('Location: clientslist.php');
    exit;
}

// build display name and initials
$fullName = $client['cl_firstname'];
if (!empty($client['cl_middlename'])) {
    $fullName .= ' ' . $client['cl_middlename'][0] . '.';
}
$fullName   .= ' ' . $client['cl_lastname'];
$initials    = strtoupper(substr($client['cl_firstname'], 0, 1) . substr($client['cl_lastname'], 0, 1));
$regYear     = date('Y', strtotime($client['cl_date_registered'] ?? 'now'));
$clientIdStr = 'CLT-' . $regYear . '-' . str_pad($client['client_id'], 5, '0', STR_PAD_LEFT);

// ELIGIBILITY CHECKS

$thisYear    = date('Y');
$thisQuarter = ceil(date('n') / 3); // 1-4

// AICS FBML: max 1/quarter, max 4/year
// we count availments linked to the AICS FBML program only
// AICS Educational has its own separate limit (max 2/year)
$aicsStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_year,
        SUM(CASE
            WHEN QUARTER(av_date_applied) = :quarter
             AND YEAR(av_date_applied) = :year
            THEN 1 ELSE 0
        END) AS total_quarter
    FROM AVAILMENT a
    JOIN PROGRAM p ON a.program_id = p.program_id
    WHERE a.client_id  = :client_id
      AND p.program_name = 'AICS FBML'
      AND YEAR(av_date_applied) = :year2
");
$aicsStmt->execute([
    ':client_id' => $client_id,
    ':quarter'   => $thisQuarter,
    ':year'      => $thisYear,
    ':year2'     => $thisYear,
]);
$aicsCount       = $aicsStmt->fetch();
$aicsThisQuarter = intval($aicsCount['total_quarter'] ?? 0);
$aicsThisYear    = intval($aicsCount['total_year']    ?? 0);

// AICS Educational: max 2/year, separate budget
$aicsEdStmt = $pdo->prepare("
    SELECT COUNT(*) AS total_year
    FROM AVAILMENT a
    JOIN PROGRAM p ON a.program_id = p.program_id
    WHERE a.client_id  = :client_id
      AND p.program_name = 'AICS Educational'
      AND YEAR(av_date_applied) = :year
");
$aicsEdStmt->execute([':client_id' => $client_id, ':year' => $thisYear]);
$aicsEdThisYear = intval($aicsEdStmt->fetch()['total_year'] ?? 0);

// SLP: only 1 allowed until prior one succeeds
$slpStmt = $pdo->prepare("
    SELECT COUNT(*) AS slp_count
    FROM AVAILMENT a
    JOIN PROGRAM p ON a.program_id = p.program_id
    WHERE a.client_id  = :id
      AND p.program_name = 'SLP'
");
$slpStmt->execute([':id' => $client_id]);
$slpCount = intval($slpStmt->fetch()['slp_count'] ?? 0);

// Last case study date
$csStmt = $pdo->prepare("
    SELECT interview_date FROM CASE_STUDY
    WHERE client_id = :id
    ORDER BY interview_date DESC
    LIMIT 1
");
$csStmt->execute([':id' => $client_id]);
$lastCaseStudy = $csStmt->fetch();
$lastCsDate    = $lastCaseStudy ? date('M j, Y', strtotime($lastCaseStudy['interview_date'])) : 'Never';
$caseStudyOld  = $lastCaseStudy && strtotime($lastCaseStudy['interview_date']) < strtotime('-6 months');

// FETCH PROGRAMS FROM DB 

$dbPrograms = $pdo->query("
    SELECT program_id, program_name, prog_category, prog_description,
           prog_annual_budget, prog_remaining_budget,
           prog_max_per_quarter, prog_max_per_year,
           prog_max_amount, prog_min_amount, prog_funding_source
    FROM PROGRAM
    ORDER BY FIELD(program_name,
        'AICS FBML','AICS Educational',
        '4Ps','SLP','SFP','Day Care Center Program',
        'Senior Citizen Program','PWD Program',
        'Solo Parent Program','Women and Child Protection'
    )
")->fetchAll();

// index by program_name for easy lookup
$progByName = [];
foreach ($dbPrograms as $p) {
    $progByName[$p['program_name']] = $p;
}

// helper: compute pct used and bar color for a program row
function budgetMeta(array $p): array {
    $annual    = floatval($p['prog_annual_budget']    ?? 0);
    $remaining = floatval($p['prog_remaining_budget'] ?? 0);
    $pct       = ($annual > 0) ? round((($annual - $remaining) / $annual) * 100) : 0;
    $remPct    = 100 - $pct;
    return [
        'pct'       => $pct,
        'rem_pct'   => $remPct,
        'bar_color' => $remPct < 15 ? 'bg-red-400' : ($remPct < 30 ? 'bg-amber-400' : 'bg-emerald-400'),
        'txt_color' => $remPct < 15 ? 'text-red-500': ($remPct < 30 ? 'text-amber-500': 'text-emerald-600'),
    ];
}

//   AICS FBML row - split into 4 virtual cards (Financial, Burial, Medical, Livelihood)
//                   They all share the same program_id and budget from AICS FBML
//   AICS Educational - its own card, its own program_id, its own budget

$cards = [];

foreach ($dbPrograms as $prog) {

    if ($prog['program_name'] === 'AICS FBML') {
        // We pass subtype= in the URL so the availment form knows which sub-form to show
        $bm  = budgetMeta($prog);
        $remaining = floatval($prog['prog_remaining_budget'] ?? 0);

        $subtypes = [
            [
                'subtype'   => 'Financial',
                'label'     => 'AICS Financial',
                'desc'      => 'Financial assistance for individuals and families in crisis situations.',
                'icon_bg'   => 'bg-green-50',
                'icon_text' => 'text-green-600',
                'border'    => 'border-green-200',
                'hover'     => 'hover:border-green-400',
                'tag_bg'    => 'bg-green-100 text-green-700',
            ],
            [
                'subtype'   => 'Burial',
                'label'     => 'AICS Burial',
                'desc'      => 'Burial assistance for indigent families who lost a member.',
                'border'    => 'border-slate-200',
                'hover'     => 'hover:border-slate-400',
                'tag_bg'    => 'bg-slate-100 text-slate-600',
            ],
            [
                'subtype'   => 'Medical',
                'label'     => 'AICS Medical',
                'desc'      => 'Medical assistance for hospitalization, medicines, and lab exams.',
                'border'    => 'border-red-200',
                'hover'     => 'hover:border-red-400',
                'tag_bg'    => 'bg-red-100 text-red-700',
            ],
            [
                'subtype'   => 'Livelihood',
                'label'     => 'AICS Livelihood',
                'desc'      => 'Livelihood assistance for small businesses and income-generating projects.',
                'border'    => 'border-amber-200',
                'hover'     => 'hover:border-amber-400',
                'tag_bg'    => 'bg-amber-100 text-amber-700',
            ],
        ];

        foreach ($subtypes as $sub) {
            $cards[] = [
                'type'        => 'aics_fbml_sub',
                'program_id'  => $prog['program_id'],
                'subtype'     => $sub['subtype'],
                'label'       => $sub['label'],
                'desc'        => $sub['desc'],
                'border'      => $sub['border'],
                'hover'       => $sub['hover'],
                'tag_bg'      => $sub['tag_bg'],
                // shared budget — all 4 show the same number
                'remaining'   => $remaining,
                'pct'         => $bm['pct'],
                'bar_color'   => $bm['bar_color'],
                'txt_color'   => $bm['txt_color'],
                'budget_note' => 'Shared AICS FBML budget',
                // limits from AICS FBML 
                'max_quarter' => $prog['prog_max_per_quarter'],
                'max_year'    => $prog['prog_max_per_year'],
                'max_amount'  => $prog['prog_max_amount'],
                'min_amount'  => $prog['prog_min_amount'],
                // eligibility for the eligibility check labels
                'eligible'    => ($aicsThisQuarter < 1 && $aicsThisYear < 4),
                'restricted'  => false,
            ];
        }
        continue;
    }

    $bm        = budgetMeta($prog);
    $remaining = floatval($prog['prog_remaining_budget'] ?? 0);

    // design per program
    $design = match($prog['program_name']) {
        'AICS Educational'          => ['border'=>'border-blue-200','hover'=>'hover:border-blue-400'],
        '4Ps'                       => ['border'=>'border-purple-200','hover'=>'hover:border-purple-400'],
        'SLP'                       => ['border'=>'border-orange-200','hover'=>'hover:border-orange-400'],
        'SFP'                       => ['border'=>'border-lime-200',  'hover'=>'hover:border-lime-400'],
        'Day Care Center Program'   => ['border'=>'border-yellow-200','hover'=>'hover:border-yellow-400'],
        'Senior Citizen Program'    => ['border'=>'border-amber-200','hover'=>'hover:border-amber-400'],
        'PWD Program'               => ['border'=>'border-indigo-200','hover'=>'hover:border-indigo-400'],
        'Solo Parent Program'       => ['border'=>'border-teal-200','hover'=>'hover:border-teal-400'],
        'Women and Child Protection'=> ['border'=>'border-violet-200','hover'=>'hover:border-violet-400'],
        default                     => ['border'=>'border-slate-200','hover'=>'hover:border-navy-400'],
    };

    // Women and Child Protection is restricted to Social Worker / MSWDO Head
    $restricted = ($prog['program_name'] === 'Women and Child Protection')
                  && !in_array($_SESSION['user_role'] ?? '', ['Admin', 'Social Worker']);

    $cards[] = [
        'type'        => 'regular',
        'program_id'  => $prog['program_id'],
        'subtype'     => null,
        'label'       => $prog['program_name'],
        'desc'        => $prog['prog_description'],
        'border'      => $design['border'],
        'hover'       => $design['hover'],
        'remaining'   => $remaining,
        'pct'         => $bm['pct'],
        'bar_color'   => $bm['bar_color'],
        'txt_color'   => $bm['txt_color'],
        'budget_note' => null,
        'max_quarter' => $prog['prog_max_per_quarter'],
        'max_year'    => $prog['prog_max_per_year'],
        'max_amount'  => $prog['prog_max_amount'],
        'min_amount'  => $prog['prog_min_amount'],
        'eligible'    => true,
        'restricted'  => $restricted,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Program Availment – MSWDO San Enrique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet" />
  
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['DM Sans','sans-serif'], serif: ['DM Serif Display','serif'] },
          colors: {
            navy: { DEFAULT:'#0B2545', 50:'#E8EDF5', 100:'#C5D1E6', 400:'#3A5F93', 500:'#163566', 600:'#0B2545', 700:'#091D38' },
            gold: { DEFAULT:'#C49A2A', 400:'#C49A2A' },
            slate2: '#F4F7FC',
          },
          keyframes: {
            fadeUp:  { '0%':{ opacity:'0', transform:'translateY(10px)' }, '100%':{ opacity:'1', transform:'translateY(0)' } },
            scaleIn: { '0%':{ opacity:'0', transform:'scale(.96)' },       '100%':{ opacity:'1', transform:'scale(1)' } },
          },
          animation: {
            'fade-up':   'fadeUp 0.35s ease both',
            'fade-up-1': 'fadeUp 0.35s 0.04s ease both',
            'fade-up-2': 'fadeUp 0.35s 0.08s ease both',
            'fade-up-3': 'fadeUp 0.35s 0.12s ease both',
            'scale-in':  'scaleIn 0.25s ease both',
          }
        }
      }
    }
  </script>
  <style>
    body { font-family: 'DM Sans', sans-serif; }
    .sidebar-item { transition: all .15s ease; }
    .sidebar-item:hover { background: rgba(255,255,255,.07); color: rgba(255,255,255,.95); }
    .sidebar-item.active { background: rgba(29,111,164,.28); border-left-color: #C49A2A; color: #fff; }

    .prog-card { transition: all .2s cubic-bezier(.4,0,.2,1); cursor: pointer; }
    .prog-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(11,37,69,.12); }
    .prog-card:hover .prog-arrow { opacity:1; transform:translateX(0); }
    .prog-card:active { transform: translateY(-1px) scale(.99); }
    .prog-card.restricted { opacity:.65; cursor:not-allowed; }
    .prog-card.restricted:hover { transform:none; box-shadow:none; }

    .prog-arrow { opacity:0; transform:translateX(-4px); transition:all .2s ease; }
    .elig-row   { transition: background .1s; }
    .elig-row:hover { background: #F8FAFC; }
    .prog-bar-fill  { transition: width 1s cubic-bezier(.4,0,.2,1); }

    /* AICS section divider label */
    .section-label {
      font-size: 10px; font-weight: 700;
      text-transform: uppercase; letter-spacing: .1em;
      color: #64748b;
      padding: 0 2px 8px;
      display: flex; align-items: center; gap: 8px;
    }
    .section-label::after {
      content: ''; flex: 1; height: 1px; background: #e2e8f0;
    }

    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 2px; }
    @keyframes fadeUp  { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
    @keyframes scaleIn { from { opacity:0; transform:scale(.96); }       to { opacity:1; transform:scale(1); } }
  </style>
</head>

<body class="bg-slate2 min-h-screen flex">
  <?php require 'sidebar.php'; ?>

  <div class="ml-64 flex-1 flex flex-col min-h-screen">

    <header class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
      <div class="flex items-center gap-2 text-[13px]">
        <a href="clientslist.php" class="text-slate-400 hover:text-navy-600 transition-colors">Clients</a>
        <span class="text-slate-300">/</span>
        <a href="clientprofile.php?id=<?= $client_id ?>" class="text-slate-400 hover:text-navy-600 transition-colors">
          <?= htmlspecialchars($fullName) ?>
        </a>
        <span class="text-slate-300">/</span>
        <span class="text-navy-600 font-semibold">Select Program</span>
      </div>
      <a href="clientprofile.php?id=<?= $client_id ?>"
        class="text-[12px] text-slate-500 hover:text-navy-600 flex items-center gap-1.5 transition-colors">
        ← Back to Profile
      </a>
    </header>

    <main class="flex-1 p-6 max-w-6xl w-full mx-auto">

      <!-- Client summary card -->
      <div class="animate-fade-up bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-4 mb-6">
        <div class="w-12 h-12 rounded-xl bg-navy-600 flex items-center justify-center text-white font-serif text-lg flex-shrink-0">
          <?= htmlspecialchars($initials) ?>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[15px] font-semibold text-navy-600"><?= htmlspecialchars($fullName) ?></p>
          <p class="text-[12px] text-slate-400 mt-0.5">
            <?= htmlspecialchars($clientIdStr) ?> &nbsp;·&nbsp;
            <?= htmlspecialchars($client['barangay_name'] ?? '—') ?> &nbsp;·&nbsp;
            <?= $client['cl_age'] ?> yrs, <?= htmlspecialchars($client['cl_sex']) ?>
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <?php if ($client['cl_is_4ps']): ?><span class="bg-purple-100 text-purple-700 text-[10px] font-semibold px-2.5 py-1 rounded-full"> 4Ps</span><?php endif; ?>
          <?php if ($client['cl_is_senior']): ?><span class="bg-amber-100 text-amber-700 text-[10px] font-semibold px-2.5 py-1 rounded-full"> Senior</span><?php endif; ?>
          <?php if ($client['cl_is_pwd']): ?><span class="bg-blue-100 text-blue-700 text-[10px] font-semibold px-2.5 py-1 rounded-full"> PWD</span><?php endif; ?>
          <?php if ($client['cl_is_soloparent']): ?><span class="bg-teal-100 text-teal-700 text-[10px] font-semibold px-2.5 py-1 rounded-full"> Solo Parent</span><?php endif; ?>
          <?php if ($client['cl_is_indigent']): ?><span class="bg-emerald-100 text-emerald-700 text-[10px] font-semibold px-2.5 py-1 rounded-full"> Indigent</span><?php endif; ?>
        </div>
      </div>

      <!-- Eligibility check panel -->
      <div class="animate-fade-up-1 bg-white rounded-2xl border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-2">
          
          <h2 class="text-[13px] font-semibold text-navy-600">Eligibility &amp; Limit Check — This Client</h2>
          <span class="ml-auto text-[11px] text-slate-400">Live data from database</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-5 divide-x divide-slate-100">

          <div class="elig-row px-5 py-3.5">
            <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wide mb-1">AICS FBML / Quarter</p>
            <p class="text-[14px] font-bold <?= $aicsThisQuarter >= 1 ? 'text-red-500' : 'text-emerald-600' ?>">
              <?= $aicsThisQuarter ?> / 1
            </p>
            <p class="text-[10px] mt-0.5 font-medium <?= $aicsThisQuarter >= 1 ? 'text-red-500' : 'text-emerald-600' ?>">
              <?= $aicsThisQuarter >= 1 ? '✕ Limit reached' : '✓ Eligible' ?>
            </p>
          </div>

          <div class="elig-row px-5 py-3.5">
            <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wide mb-1">AICS FBML / Year</p>
            <p class="text-[14px] font-bold <?= $aicsThisYear >= 4 ? 'text-red-500' : 'text-emerald-600' ?>">
              <?= $aicsThisYear ?> / 4
            </p>
            <p class="text-[10px] mt-0.5 font-medium <?= $aicsThisYear >= 4 ? 'text-red-500' : 'text-emerald-600' ?>">
              <?= $aicsThisYear >= 4 ? '✕ Yearly limit' : '✓ ' . (4 - $aicsThisYear) . ' remaining' ?>
            </p>
          </div>

          <div class="elig-row px-5 py-3.5">
            <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wide mb-1">AICS Educational / Year</p>
            <p class="text-[14px] font-bold <?= $aicsEdThisYear >= 2 ? 'text-red-500' : 'text-emerald-600' ?>">
              <?= $aicsEdThisYear ?> / 2
            </p>
            <p class="text-[10px] mt-0.5 font-medium <?= $aicsEdThisYear >= 2 ? 'text-red-500' : 'text-emerald-600' ?>">
              <?= $aicsEdThisYear >= 2 ? '✕ Limit reached' : '✓ ' . (2 - $aicsEdThisYear) . ' remaining' ?>
            </p>
          </div>

          <div class="elig-row px-5 py-3.5">
            <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wide mb-1">SLP Status</p>
            <p class="text-[14px] font-bold <?= $slpCount > 0 ? 'text-amber-500' : 'text-emerald-600' ?>">
              <?= $slpCount > 0 ? 'Has record' : 'Not availed' ?>
            </p>
            <p class="text-[10px] mt-0.5 font-medium <?= $slpCount > 0 ? 'text-amber-500' : 'text-emerald-600' ?>">
              <?= $slpCount > 0 ? '⚠ Check prior availment' : '✓ Eligible' ?>
            </p>
          </div>

          <div class="elig-row px-5 py-3.5">
            <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wide mb-1">Last Case Study</p>
            <p class="text-[14px] font-bold text-navy-600"><?= $lastCsDate ?></p>
            <p class="text-[10px] mt-0.5 font-medium <?= $caseStudyOld ? 'text-amber-500' : ($lastCaseStudy ? 'text-emerald-600' : 'text-red-500') ?>">
              <?= $caseStudyOld ? '⚠ Update recommended' : ($lastCaseStudy ? '✓ Recent' : '✕ No record yet') ?>
            </p>
          </div>

        </div>
      </div>

      <div class="animate-fade-up-2 mb-5">
        <h1 class="text-[18px] font-serif text-navy-600">Select a Program</h1>
        <p class="text-[13px] text-slate-500 mt-1">Choose the program to begin a new availment for this client.</p>
      </div>

      <!-- ── AICS GROUP ──────────────────────────────────────────────────── -->
      <div class="mb-6">
        <p class="section-label mb-3">
          
          AICS — Assistance to Individuals in Crisis Situation
        </p>

        <?php
          $fbml = $progByName['AICS FBML'] ?? null;
          if ($fbml):
            $fbmlRemaining = floatval($fbml['prog_remaining_budget'] ?? 0);
            $fbmlBm        = budgetMeta($fbml);
        ?>
        <?php endif; ?>

        <!-- 4 AICS FBML cards + 1 AICS Educational card -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
          <?php foreach ($cards as $card):
            if ($card['type'] !== 'aics_fbml_sub' && $card['label'] !== 'AICS Educational') continue;
            $isEd = ($card['label'] === 'AICS Educational');
          ?>
            <div
              class="prog-card animate-fade-up bg-white rounded-2xl border-2 <?= $card['border'] ?> <?= $card['hover'] ?> p-4 flex flex-col gap-2.5 relative overflow-hidden <?= (!$card['eligible'] && !$isEd) ? 'opacity-60' : '' ?>"
              onclick="<?= (!$card['eligible'] && !$isEd) ? 'showIneligible()' : 'openModal(' . $card['program_id'] . ', \'' . addslashes($card['subtype'] ?? '') . '\', \'' . addslashes($card['label']) . '\')' ?>"
            >
              <!-- ineligible overlay badge -->
              <?php if (!$card['eligible'] && !$isEd): ?>
                <div class="absolute top-2 right-2 bg-red-100 text-red-600 text-[9px] font-bold px-2 py-0.5 rounded-full">Limit reached</div>
              <?php endif; ?>
              <?php if ($isEd): ?>
                <div class="absolute top-2 right-2 bg-blue-100 text-blue-600 text-[9px] font-bold px-2 py-0.5 rounded-full">Own budget</div>
              <?php endif; ?>

              <div class="flex items-center gap-2.5">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-1">
                    <p class="text-[13px] font-semibold text-navy-600 truncate"><?= htmlspecialchars($card['label']) ?></p>
                    <span class="prog-arrow text-navy-400 text-xs">→</span>
                  </div>
                </div>
              </div>

              <p class="text-[11px] text-slate-400 leading-relaxed line-clamp-2"><?= htmlspecialchars($card['desc']) ?></p>

              <!-- budget bar -->
              <?php if ($card['pct'] > 0 || $card['remaining'] > 0): ?>
                <div>
                  <div class="flex justify-between text-[10px] mb-0.5">
                    <span class="text-slate-400"><?= $isEd ? 'Own budget' : 'Shared budget' ?></span>
                    <span class="font-semibold <?= $card['txt_color'] ?>"><?= $card['pct'] ?>%</span>
                  </div>
                  <div class="bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="prog-bar-fill h-1.5 rounded-full <?= $card['bar_color'] ?>"
                         style="width:0%" data-target="<?= $card['pct'] ?>%"></div>
                  </div>
                  <p class="text-[10px] <?= $card['txt_color'] ?> mt-0.5 font-medium">
                    ₱<?= number_format($card['remaining']) ?> left
                  </p>
                </div>
              <?php endif; ?>

              <!-- limits -->
              <div class="pt-1.5 border-t border-slate-100 text-[10px] text-slate-400">
                <?php
                  $limParts = [];
                  if ($card['max_quarter']) $limParts[] = 'Max ' . $card['max_quarter'] . '/qtr';
                  if ($card['max_year'])    $limParts[] = 'Max ' . $card['max_year'] . '/yr';
                  if ($card['max_amount'])  $limParts[] = 'Up to ₱' . number_format($card['max_amount']);
                  echo $limParts ? implode(' · ', $limParts) : 'No preset limit';
                ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- ── ALL OTHER PROGRAMS ─────────────────────────────────────────── -->
      <div>
        <p class="section-label mb-3">
          
          Sectoral &amp; Core Programs
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <?php foreach ($cards as $card):
            // skip AICS cards - already shown above
            if ($card['type'] === 'aics_fbml_sub') continue;
            if ($card['label'] === 'AICS Educational') continue;

            $remaining = $card['remaining'];
            $budgetStr = $remaining > 0 ? '₱' . number_format($remaining) . ' remaining' : 'Budget not set';
          ?>
            <div
              class="prog-card animate-fade-up bg-white rounded-2xl border-2 <?= $card['border'] ?> <?= $card['restricted'] ? '' : $card['hover'] ?> p-5 flex flex-col gap-3 relative overflow-hidden <?= $card['restricted'] ? 'restricted' : '' ?>"
              onclick="<?= $card['restricted'] ? 'showRestricted()' : 'openModal(' . $card['program_id'] . ', null, \'' . addslashes($card['label']) . '\')' ?>"
            >
              <!-- restricted badge -->
              <?php if ($card['restricted']): ?>
                <div class="absolute top-3 right-3 bg-violet-100 text-violet-600 text-[9px] font-bold px-2 py-0.5 rounded-full">
                   Restricted
                </div>
              <?php endif; ?>

              <div class="flex items-start gap-3">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-1.5">
                    <p class="text-[14px] font-semibold text-navy-600"><?= htmlspecialchars($card['label']) ?></p>
                    <?php if (!$card['restricted']): ?>
                      <span class="prog-arrow text-navy-400 text-sm">→</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <?php if (!empty($card['desc'])): ?>
                <p class="text-[11px] text-slate-500 leading-relaxed line-clamp-2">
                  <?= htmlspecialchars($card['desc']) ?>
                </p>
              <?php endif; ?>

              <?php if ($card['pct'] > 0 || $remaining > 0): ?>
                <div class="pt-1">
                  <div class="flex justify-between text-[10px] mb-1">
                    <span class="text-slate-400">Budget utilization</span>
                    <span class="font-semibold <?= $card['txt_color'] ?>"><?= $card['pct'] ?>% used</span>
                  </div>
                  <div class="bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="prog-bar-fill h-1.5 rounded-full <?= $card['bar_color'] ?>"
                         style="width:0%" data-target="<?= $card['pct'] ?>%"></div>
                  </div>
                </div>
              <?php endif; ?>

              <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                <span class="text-[10px] text-slate-400 border border-slate-100 px-2 py-0.5 rounded-full">
                  <?php
                    $limParts = [];
                    if ($card['max_quarter']) $limParts[] = 'Max ' . $card['max_quarter'] . '/qtr';
                    if ($card['max_year'])    $limParts[] = 'Max ' . $card['max_year'] . '/yr';
                    if ($card['max_amount'])  $limParts[] = 'Up to ₱' . number_format($card['max_amount']);
                    echo $limParts ? implode(' · ', $limParts) : 'No preset limit';
                  ?>
                </span>
                <span class="text-[11px] font-semibold <?= $card['txt_color'] ?>">
                  <?= $card['restricted'] ? 'Restricted access' : $budgetStr ?>
                </span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </main>

    <footer class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
      <span>MSWDO San Enrique Information System</span>
    </footer>
  </div>

  <!-- ── CONFIRM MODAL ──────────────────────────────────────────────────── -->
  <div id="modal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-6 hidden">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 animate-scale-in">
      <div class="flex items-start justify-between mb-4">
        <div class="flex items-center gap-3">
          <div>
            <h3 id="mTitle" class="text-[16px] font-semibold text-navy-600"></h3>
            <p id="mSub"   class="text-[12px] text-slate-400 mt-0.5"></p>
          </div>
        </div>
        <button onclick="closeModal()" class="text-slate-300 hover:text-slate-500 text-xl leading-none mt-0.5">✕</button>
      </div>

      <!-- budget info -->
      <div id="mBudget" class="bg-slate-50 border border-slate-100 rounded-xl p-3 mb-4 text-[12px]"></div>

      <!-- shared budget note - only shown for AICS FBML subtypes -->
      <div id="mSharedNote" class="hidden bg-blue-50 border border-blue-100 rounded-xl px-3 py-2.5 mb-4 text-[11px] text-blue-700 flex items-center gap-2">
        
        <span>This availment shares the combined AICS FBML budget with Financial, Burial, Medical, and Livelihood types.</span>
      </div>

      <!-- limits -->
      <div id="mLimits" class="text-[12px] text-slate-500 mb-5"></div>

      <div class="flex gap-3">
        <button onclick="closeModal()"
          class="flex-1 py-2.5 border border-slate-200 rounded-xl text-[13px] font-medium text-slate-600 hover:border-navy-400 transition-all">
          Cancel
        </button>
        <a id="proceedBtn" href="#"
          class="flex-1 py-2.5 bg-navy-600 rounded-xl text-[13px] font-semibold text-white hover:bg-navy-500 transition-all text-center">
          Proceed →
        </a>
      </div>
    </div>
  </div>

  <script>
    const CARDS = <?= json_encode($cards, JSON_HEX_TAG | JSON_HEX_APOS) ?>;

    // for AICS FBML subtypes: key is "programId|Financial", "programId|Burial", etc.
    const CARD_MAP = {};
    CARDS.forEach(c => {
      const key = c.subtype ? `${c.program_id}|${c.subtype}` : `${c.program_id}|`;
      CARD_MAP[key] = c;
    });

    function openModal(programId, subtype, label) {
      const key  = `${programId}|${subtype || ''}`;
      const card = CARD_MAP[key];
      if (!card) return;

      document.getElementById('mTitle').textContent = card.label;
      document.getElementById('mSub').textContent   = subtype
        ? 'AICS FBML — ' + subtype + ' Assistance'
        : card.label;

      // budget section
      const remaining = parseFloat(card.remaining || 0);
      const pct       = card.pct || 0;
      document.getElementById('mBudget').innerHTML = remaining > 0 ? `
        <div class="flex gap-4 flex-wrap">
          <div class="flex-1 min-w-0">
            <p class="text-slate-400 mb-1">${card.budget_note ? card.budget_note : 'Remaining Budget'}</p>
            <p class="font-semibold text-[13px] ${card.txt_color}">₱${remaining.toLocaleString()}</p>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-slate-400 mb-1">Budget used</p>
            <div class="flex items-center gap-2">
              <div class="flex-1 bg-slate-200 rounded-full h-1.5 overflow-hidden">
                <div class="h-1.5 rounded-full ${card.bar_color}" style="width:${pct}%"></div>
              </div>
              <span class="text-[11px] font-semibold ${card.txt_color}">${pct}%</span>
            </div>
          </div>
        </div>` : `<p class="text-slate-400">Budget not configured for this program.</p>`;

      // show shared note only for AICS FBML subtypes
      const sharedNote = document.getElementById('mSharedNote');
      if (card.type === 'aics_fbml_sub') {
        sharedNote.classList.remove('hidden');
      } else {
        sharedNote.classList.add('hidden');
      }

      // limits
      let lims = [];
      if (card.max_quarter) lims.push(`Max per quarter: <strong>${card.max_quarter}</strong>`);
      if (card.max_year)    lims.push(`Max per year: <strong>${card.max_year}</strong>`);
      if (card.max_amount)  lims.push(`Max amount: <strong>₱${parseFloat(card.max_amount).toLocaleString()}</strong>`);
      if (card.min_amount)  lims.push(`Min amount: <strong>₱${parseFloat(card.min_amount).toLocaleString()}</strong>`);
      document.getElementById('mLimits').innerHTML = lims.length
        ? lims.join(' &nbsp;·&nbsp; ')
        : 'No preset limits for this program.';

      // proceed button URL
      // for AICS FBML subtypes, also passes subtype= so the form knows which sub-form to show
      let url = `programavailmentselection.php?client_id=<?= $client_id ?>&program_id=${programId}`;
      if (subtype) url += `&subtype=${encodeURIComponent(subtype)}`;
      document.getElementById('proceedBtn').href = url;

      document.getElementById('modal').classList.remove('hidden');
    }

    function closeModal() {
      document.getElementById('modal').classList.add('hidden');
    }

    function showIneligible() {
      alert('This client has reached the AICS FBML limit for this quarter or year. Please check the eligibility panel above.');
    }

    function showRestricted() {
      alert('This program is restricted to Social Workers and Administrators only.');
    }

    // close on backdrop click
    document.getElementById('modal').addEventListener('click', function(e) {
      if (e.target === this) closeModal();
    });

    // animate budget bars after load
    requestAnimationFrame(() => {
      setTimeout(() => {
        document.querySelectorAll('.prog-bar-fill').forEach(el => {
          el.style.width = el.dataset.target;
        });
      }, 300);
    });
  </script>
</body>
</html>