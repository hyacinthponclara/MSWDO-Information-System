<?php
require 'auth.php';
requireRole(['Social Worker']);
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['availment_id'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];
    $availment_id = (int) $_POST['availment_id'];

    if ($action !== 'release' || $availment_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT av_status, av_amount, program_id FROM AVAILMENT WHERE availment_id = ? FOR UPDATE");
        $stmt->execute([$availment_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception('Application not found.');
        }

        if ($row['av_status'] !== 'Approved') {
            throw new Exception('Only approved applications can be released.');
        }
        $progStmt = $pdo->prepare("
            SELECT
                p.prog_annual_budget,
                COALESCE(SUM(CASE WHEN a.av_status = 'Released' THEN a.av_amount ELSE 0 END), 0) AS released
            FROM PROGRAM p
            LEFT JOIN AVAILMENT a
                ON a.program_id = p.program_id
                AND YEAR(a.av_date_applied) = YEAR(CURDATE())
            WHERE p.program_id = ?
            GROUP BY p.program_id
        ");
        $progStmt->execute([$row['program_id']]);
        $prog = $progStmt->fetch(PDO::FETCH_ASSOC);
        $available = (float) ($prog['prog_annual_budget'] ?? 0) - (float) ($prog['released'] ?? 0);

        if ((float) $row['av_amount'] > $available) {
            throw new Exception('Insufficient budget remaining to release this amount.');
        }

        $upd = $pdo->prepare("UPDATE AVAILMENT SET av_status = 'Released', av_date_released = CURDATE() WHERE availment_id = ?");
        $upd->execute([$availment_id]);
        $message = 'Funds released and deducted from the budget.';

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => $message]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

function getBudgetSummary(PDO $pdo, string $programName): array
{
    $stmt = $pdo->prepare("
        SELECT
            p.prog_annual_budget,
            COALESCE(SUM(CASE WHEN a.av_status = 'Approved' THEN a.av_amount ELSE 0 END), 0) AS approved,
            COALESCE(SUM(CASE WHEN a.av_status = 'Released' THEN a.av_amount ELSE 0 END), 0) AS released
        FROM PROGRAM p
        LEFT JOIN AVAILMENT a
            ON a.program_id = p.program_id
            AND YEAR(a.av_date_applied) = YEAR(CURDATE())
        WHERE p.program_name = ?
        GROUP BY p.program_id
        LIMIT 1
    ");
    $stmt->execute([$programName]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $total = (float) ($row['prog_annual_budget'] ?? 0);
    $approved = (float) ($row['approved'] ?? 0);
    $released = (float) ($row['released'] ?? 0);
    $available = $total - $released;
    $pctUsed = $total > 0 ? round(($released / $total) * 100, 1) : 0;

    return compact('total', 'approved', 'released', 'available', 'pctUsed');
}

$fbmlBudget = getBudgetSummary($pdo, 'AICS FBML');
$eduBudget = getBudgetSummary($pdo, 'AICS Educational');

$pendingStmt = $pdo->prepare("
    SELECT
        a.availment_id,
        a.av_amount,
        a.av_date_applied,
        a.av_status,
        CONCAT(c.cl_firstname, ' ', c.cl_lastname) AS beneficiary_name,
        p.program_name,
        CASE
            WHEN p.program_name = 'AICS FBML' THEN
                CASE
                    WHEN med.aics_medical_id    IS NOT NULL THEN 'Medical'
                    WHEN fin.aics_financial_id   IS NOT NULL THEN 'Financial'
                    WHEN bur.aics_burial_id      IS NOT NULL THEN 'Burial'
                    WHEN liv.aics_livelihood_id  IS NOT NULL THEN 'Livelihood'
                    ELSE 'Other'
                END
            WHEN p.program_name = 'AICS Educational' THEN 'Educational'
            ELSE p.program_name
        END AS assistance_type
    FROM AVAILMENT a
    JOIN CLIENT c  ON a.client_id = c.client_id
    JOIN PROGRAM p ON a.program_id = p.program_id
    LEFT JOIN AICS_MEDICAL    med ON med.availment_id = a.availment_id
    LEFT JOIN AICS_FINANCIAL  fin ON fin.availment_id = a.availment_id
    LEFT JOIN AICS_BURIAL     bur ON bur.availment_id = a.availment_id
    LEFT JOIN AICS_LIVELIHOOD liv ON liv.availment_id = a.availment_id
    WHERE a.av_status = 'Approved'
      AND p.program_name IN ('AICS FBML', 'AICS Educational')
    ORDER BY a.av_date_applied ASC
");
$pendingStmt->execute();
$pendingApplications = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AICS Release Queue – MSWDO San Enrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['DM Sans', 'sans-serif'],
                        serif: ['DM Serif Display', 'serif'],
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
                            400: '#C49A2A',
                        },
                        slate2: '#F4F7FC',
                    },
                    keyframes: {
                        fadeUp: { '0%': { opacity: '0', transform: 'translateY(12px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.4s ease both',
                        'fade-up-1': 'fadeUp 0.4s ease 0.05s both',
                        'fade-up-2': 'fadeUp 0.4s ease 0.1s both',
                        'fade-up-3': 'fadeUp 0.4s ease 0.15s both',
                        'fade-up-4': 'fadeUp 0.4s ease 0.2s both',
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

        .badge-approved {
            background: #D1FAE5;
            color: #059669;
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

<body class="bg-slate2 min-h-screen flex">

    <?php require 'sidebar.php'; ?>

    <div class="ml-64 flex-1 flex flex-col min-h-screen">

        <!-- Top Bar -->
        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <span class="text-green-600 font-semibold">AICS Release Queue</span>
            </div>
            <div class="flex items-center gap-2">
            </div>
        </header>

        <main class="flex-1 p-6 space-y-5 overflow-y-auto">

            <!-- Page Title with Quick Actions -->
            <div class="flex flex-wrap items-center justify-between gap-3 animate-fade-up">
                <div>
                    <h1 class="text-xl font-serif text-green-600">AICS Applications Awaiting Release</h1>
                </div>
            </div>

            <!-- Budget Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 animate-fade-up-1">
                <!-- AICS FBML Budget -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[13px] font-semibold text-green-600"><i
                                class="fas fa-pills mr-1.5 text-green-400"></i>AICS FBML</h3>
                        <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">Budget</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total</p>
                            <p class="text-xl font-bold text-green-600">₱<?= number_format($fbmlBudget['total']) ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider"
                                title="Approved but not yet deducted">Awaiting Release</p>
                            <p class="text-xl font-bold text-amber-600">₱<?= number_format($fbmlBudget['approved']) ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider" title="Actually paid out">
                                Released</p>
                            <p class="text-xl font-bold text-emerald-600">₱<?= number_format($fbmlBudget['released']) ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Available</p>
                            <p class="text-xl font-bold text-blue-600">₱<?= number_format($fbmlBudget['available']) ?>
                            </p>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2">Awaiting release:
                        ₱<?= number_format($fbmlBudget['approved']) ?></p>
                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <div class="flex justify-between text-[10px] text-slate-400">
                            <span>Released: <?= $fbmlBudget['pctUsed'] ?>%</span>
                            <span>Remaining: <?= max(0, round(100 - $fbmlBudget['pctUsed'], 1)) ?>%</span>
                        </div>
                        <div class="bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                            <div class="h-1.5 rounded-full bg-green-500"
                                style="width:<?= min(100, $fbmlBudget['pctUsed']) ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- AICS Educational Budget -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[13px] font-semibold text-green-600"><i
                                class="fas fa-graduation-cap mr-1.5 text-green-400"></i>AICS Educational</h3>
                        <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">Budget</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total</p>
                            <p class="text-xl font-bold text-green-600">₱<?= number_format($eduBudget['total']) ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider"
                                title="Approved but not yet deducted">Awaiting Release</p>
                            <p class="text-xl font-bold text-amber-600">₱<?= number_format($eduBudget['approved']) ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider" title="Actually paid out">
                                Released</p>
                            <p class="text-xl font-bold text-emerald-600">₱<?= number_format($eduBudget['released']) ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Available</p>
                            <p class="text-xl font-bold text-blue-600">₱<?= number_format($eduBudget['available']) ?>
                            </p>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2">Awaiting release:
                        ₱<?= number_format($eduBudget['approved']) ?></p>
                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <div class="flex justify-between text-[10px] text-slate-400">
                            <span>Released: <?= $eduBudget['pctUsed'] ?>%</span>
                            <span>Remaining: <?= max(0, round(100 - $eduBudget['pctUsed'], 1)) ?>%</span>
                        </div>
                        <div class="bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                            <div class="h-1.5 rounded-full bg-green-500"
                                style="width:<?= min(100, $eduBudget['pctUsed']) ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter & Search - Filter by Availment Type -->
            <div class="flex flex-wrap items-center justify-between gap-3 animate-fade-up-2">
                <div class="flex flex-wrap items-center gap-3">
                    <select id="filterType"
                        class="text-[12px] border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none"
                        onchange="filterTable()">
                        <option value="all">All AICS</option>
                        <option value="Educational">Educational</option>
                        <option value="Financial">Financial</option>
                        <option value="Burial">Burial</option>
                        <option value="Medical">Medical</option>
                        <option value="Livelihood">Livelihood</option>
                    </select>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" placeholder="Search beneficiary..."
                            class="text-[12px] pl-8 pr-3 py-1.5 border border-slate-200 rounded-lg bg-white focus:border-green-400 focus:ring-1 focus:ring-green-400 outline-none w-48" />
                    </div>
                </div>
                <span class="text-[11px] text-slate-400" id="rowCount">Showing <?= count($pendingApplications) ?>
                    applications awaiting release</span>
            </div>

            <!-- Applications Awaiting Release Table -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-up-3">
                <div class="overflow-x-auto">
                    <table class="w-full text-[12px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Beneficiary</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Budget Source</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Type</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Amount</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Date Applied</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Status</th>
                                <th
                                    class="text-left px-5 py-3 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="pendingTableBody">
                            <?php if (empty($pendingApplications)): ?>
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-slate-400">No applications awaiting
                                        release right now.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pendingApplications as $app): ?>
                                    <?php
                                    $isFbml = $app['program_name'] === 'AICS FBML';
                                    $sourceLabel = $isFbml ? 'AICS FBML' : 'AICS Educational';
                                    $sourceBadgeCls = $isFbml
                                        ? 'bg-blue-100 text-blue-700'
                                        : 'bg-purple-100 text-purple-700';
                                    $budgetType = $isFbml ? 'fbml' : 'edu';
                                    $safeName = htmlspecialchars($app['beneficiary_name'], ENT_QUOTES);
                                    ?>
                                    <tr class="table-row" data-type="<?= htmlspecialchars($app['assistance_type']) ?>"
                                        id="row-<?= (int) $app['availment_id'] ?>">
                                        <td class="px-5 py-3 font-medium text-green-700"><?= $safeName ?></td>
                                        <td class="px-5 py-3"><span
                                                class="<?= $sourceBadgeCls ?> px-2 py-0.5 rounded text-[10px] font-semibold"><?= $sourceLabel ?></span>
                                        </td>
                                        <td class="px-5 py-3 text-slate-600"><?= htmlspecialchars($app['assistance_type']) ?>
                                        </td>
                                        <td class="px-5 py-3 font-semibold text-slate-700">
                                            ₱<?= number_format((float) $app['av_amount']) ?></td>
                                        <td class="px-5 py-3 text-slate-400">
                                            <?= date('M j, Y', strtotime($app['av_date_applied'])) ?>
                                        </td>
                                        <td class="px-5 py-3"><span
                                                class="badge-approved px-2.5 py-0.5 rounded-full text-[10px] font-semibold"><?= htmlspecialchars($app['av_status']) ?></span>
                                        </td>
                                        <td class="px-5 py-3">
                                            <div class="flex items-center gap-1.5">
                                                <button
                                                    onclick="handleRelease(<?= (int) $app['availment_id'] ?>, '<?= addslashes($safeName) ?>', <?= (float) $app['av_amount'] ?>, '<?= $budgetType ?>')"
                                                    class="text-[11px] font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg px-2.5 py-1 hover:bg-blue-100 transition-colors">
                                                    <i class="fas fa-hand-holding-dollar mr-1"></i> Release
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="flex items-center justify-between px-5 py-3 border-t border-slate-100">
                    <span class="text-[11px] text-slate-400">Showing <span
                            id="visibleCount"><?= count($pendingApplications) ?></span> of <span
                            id="totalCount"><?= count($pendingApplications) ?></span> applications awaiting
                        release</span>
                    <div class="flex items-center gap-1">
                        <button
                            class="text-[11px] text-slate-400 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors">Previous</button>
                        <button class="text-[11px] font-medium text-white bg-green-600 rounded-lg px-3 py-1">1</button>
                        <button
                            class="text-[11px] text-slate-600 border border-slate-200 rounded-lg px-3 py-1 hover:bg-slate-50 transition-colors">Next</button>
                    </div>
                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer
            class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <!-- Toast Notification -->
    <div id="toast"
        class="fixed bottom-6 right-6 bg-green-700 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-green-300"></i>
        <span id="toastMsg">Action completed!</span>
    </div>

    <script>
        function filterTable() {
            const filterValue = document.getElementById('filterType').value.toLowerCase();
            const rows = document.querySelectorAll('#pendingTableBody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                const type = row.getAttribute('data-type');
                if (filterValue === 'all' || type.toLowerCase() === filterValue) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('visibleCount').textContent = visibleCount;
            document.getElementById('totalCount').textContent = rows.length;

            const countSpan = document.querySelector('.text-slate-400:not(.flex-1)');
            if (countSpan) {
                countSpan.textContent = `Showing ${visibleCount} applications awaiting release`;
            }
        }

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

        async function submitDecision(availmentId, action, name, amount, budgetType) {
            const params = new URLSearchParams({ action, availment_id: availmentId });

            try {
                const res = await fetch(window.location.pathname, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                });
                const data = await res.json();

                if (!data.success) {
                    showToast(data.message || 'Something went wrong.', 'error');
                    return;
                }

                const budgetName = budgetType === 'fbml' ? 'AICS FBML' : 'AICS Educational';
                showToast(`${name}'s ${budgetName} funds released! ₱${amount.toLocaleString()} deducted from the budget.`);

                const row = document.getElementById('row-' + availmentId);
                if (row) row.remove();

                const remainingRows = document.querySelectorAll('#pendingTableBody tr[data-type]').length;
                document.getElementById('visibleCount').textContent = remainingRows;
                document.getElementById('totalCount').textContent = remainingRows;
                document.getElementById('rowCount').textContent = `Showing ${remainingRows} applications awaiting release`;

                setTimeout(() => window.location.reload(), 1200);
            } catch (err) {
                showToast('Network error — please try again.', 'error');
            }
        }

        function handleRelease(availmentId, name, amount, budgetType) {
            const budgetName = budgetType === 'fbml' ? 'AICS FBML' : 'AICS Educational';
            const msg = `Release ₱${amount.toLocaleString()} to ${name}? This will deduct the amount from the ${budgetName} budget.`;
            if (confirm(msg)) {
                submitDecision(availmentId, 'release', name, amount, budgetType);
            }
        }
    </script>

</body>

</html>