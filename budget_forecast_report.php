<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';

function peso(float $n): string
{
    return '₱' . number_format($n, 2);
}

function divisor(string $period): int
{
    return $period === 'Quarterly' ? 4 : ($period === 'Half-Year' ? 2 : 1);
}

function periodLabel(string $period, int $currentPeriod): string
{
    if ($period === 'Quarterly') {
        return 'Q' . max(1, min(4, $currentPeriod));
    }

    if ($period === 'Half-Year') {
        return $currentPeriod === 2 ? '2nd Half' : '1st Half';
    }

    return 'Annual';
}

/*
 * The percentage comes directly from the Forecast modal.
 * 0% = no increase.
 * There is no automatic 15% increase.
 */
$increasePct = isset($_GET['increase_pct'])
    ? (float) $_GET['increase_pct']
    : 0.0;

$increasePct = max(0.0, min(100.0, $increasePct));

$stmt = $pdo->query("
    SELECT
        p.program_id,
        p.program_name,
        p.prog_period,
        p.prog_annual_budget,
        COALESCE(p.prog_current_period, 1) AS current_period,

        /*
         * MATCH BUDGET MANAGEMENT:
         * Include all legitimate historical RELEASED transactions.
         * Do not filter by prog_period_started_at because older
         * transactions existed before the new period tracking.
         */
        COALESCE((
            SELECT SUM(a.av_amount)
            FROM availment a
            WHERE a.program_id = p.program_id
              AND a.av_status = 'Released'
              AND a.av_date_released IS NOT NULL
        ), 0)
        +
        COALESCE((
            SELECT SUM(pp.pp_budget)
            FROM project_proposal pp
            WHERE pp.program_id = p.program_id
              AND pp.pp_status = 'Released'
              AND pp.pp_date_released IS NOT NULL
        ), 0) AS spent,

        /*
         * MATCH BUDGET MANAGEMENT BENEFICIARY LOGIC:
         * Approved/Released availments count distinct clients.
         * Approved/Released proposals use their participant count.
         */
        COALESCE((
            SELECT COUNT(DISTINCT a2.client_id)
            FROM availment a2
            WHERE a2.program_id = p.program_id
              AND a2.av_status IN ('Approved', 'Released')
        ), 0)
        +
        COALESCE((
            SELECT SUM(pp2.pp_num_participants)
            FROM project_proposal pp2
            WHERE pp2.program_id = p.program_id
              AND pp2.pp_status IN ('Approved', 'Released')
        ), 0) AS beneficiaries

    FROM program p
    ORDER BY p.program_id
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$consider = 0;
$noIncrease = 0;
$review = 0;

$totalBeneficiaries = 0;
$totalPeriodBudget = 0.0;
$totalSpent = 0.0;

/*
 * IMPORTANT:
 * This must match the Forecast modal.
 *
 * Only programs with:
 *   utilization >= 70%
 *   AND beneficiaries > 0
 * are eligible for the custom increase.
 *
 * Programs marked "No Increase Needed" or "Review" do not
 * receive the custom percentage.
 */
$eligibleBaseBudget = 0.0;
$totalIncreaseAmount = 0.0;
$totalRecommended = 0.0;

foreach ($rows as &$r) {
    $annual = (float) $r['prog_annual_budget'];
    $periodBudget = $annual / divisor($r['prog_period']);
    $spent = (float) $r['spent'];
    $beneficiaries = (int) $r['beneficiaries'];

    $utilization = $periodBudget > 0
        ? ($spent / $periodBudget) * 100
        : 0.0;

    if ($utilization <= 30) {
        $recommendation = 'No Increase Needed';
        $noIncrease++;
    } elseif ($utilization >= 70 && $beneficiaries > 0) {
        $recommendation = 'Consider Increase';
        $consider++;
    } else {
        $recommendation = 'Review';
        $review++;
    }

    $increaseAmount = 0.0;
    $recommendedBudget = 0.0;

    if ($recommendation === 'Consider Increase') {
        $increaseAmount = $periodBudget * ($increasePct / 100);
        $recommendedBudget = $periodBudget + $increaseAmount;

        $eligibleBaseBudget += $periodBudget;
        $totalIncreaseAmount += $increaseAmount;
        $totalRecommended += $recommendedBudget;
    }

    $r['period_budget'] = $periodBudget;
    $r['spent_value'] = $spent;
    $r['beneficiaries_value'] = $beneficiaries;
    $r['utilization'] = $utilization;
    $r['recommendation'] = $recommendation;
    $r['increase_amount'] = $increaseAmount;
    $r['recommended_budget'] = $recommendedBudget;

    $totalBeneficiaries += $beneficiaries;
    $totalPeriodBudget += $periodBudget;
    $totalSpent += $spent;
}
unset($r);

$generatedAt = date('F d, Y h:i:s A');

$generatedBy = trim(
    ($_SESSION['user_firstname'] ?? '') . ' ' .
    ($_SESSION['user_lastname'] ?? '')
);

if ($generatedBy === '') {
    $generatedBy = 'Authorized Admin';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Budget Forecast Report - MSWDO San Enrique</title>

<style>
@page {
    size: legal landscape;
    margin: 10mm 12mm 12mm 12mm;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    color: #111;
    background: #fff;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10pt;
    line-height: 1.3;
}

.report-page {
    width: 100%;
}

.header {
    border-bottom: 2px solid #000;
    padding-bottom: 8px;
    margin-bottom: 12px;
}

.header-table {
    width: 100%;
    border-collapse: collapse;
}

.header-table td {
    border: none;
    padding: 0;
    vertical-align: middle;
}

.header-center {
    text-align: center;
}

.header-center div {
    margin: 1px 0;
}

.gov {
    font-size: 9pt;
}

.province {
    font-size: 10pt;
}

.municipality {
    font-size: 10pt;
    font-weight: bold;
}

.office {
    font-size: 10pt;
    font-weight: bold;
}

.generated {
    width: 180px;
    text-align: right;
    font-size: 8pt;
    vertical-align: bottom !important;
}

.report-title {
    text-align: center;
    font-size: 15pt;
    font-weight: bold;
    margin-top: 3px;
}

.report-subtitle {
    text-align: center;
    font-size: 9pt;
    color: #444;
    margin-bottom: 10px;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
}

.info-table td {
    border: 1px solid #777;
    padding: 5px 7px;
}

.info-label {
    font-weight: bold;
    background: #f2f2f2;
    width: 14%;
}

.section-title {
    font-size: 10pt;
    font-weight: bold;
    margin: 10px 0 5px;
    text-transform: uppercase;
}

.report {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.report th,
.report td {
    border: 1px solid #777;
    padding: 5px 6px;
    vertical-align: middle;
}

.report th {
    background: #eef6f0;
    text-align: center;
    font-weight: bold;
}

.report th:nth-child(1) { width: 18%; }
.report th:nth-child(2) { width: 10%; }
.report th:nth-child(3) { width: 9%; }
.report th:nth-child(4) { width: 13%; }
.report th:nth-child(5) { width: 12%; }
.report th:nth-child(6) { width: 9%; }
.report th:nth-child(7) { width: 29%; }

.center {
    text-align: center;
}

.right {
    text-align: right;
}

.rec-increase {
    font-weight: bold;
}

.rec-no {
    font-weight: bold;
}

.rec-review {
    font-weight: bold;
}

.total-row td {
    font-weight: bold;
    background: #f5f5f5;
}

.summary {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
}

.summary td {
    border: 1px solid #777;
    padding: 6px 8px;
}

.summary-label {
    width: 24%;
    font-weight: bold;
    background: #f2f2f2;
}

.calculation {
    border: 1px solid #777;
    padding: 9px 11px;
    margin-top: 12px;
}

.calculation-title {
    font-weight: bold;
    font-size: 10pt;
    margin-bottom: 5px;
}

.calculation-line {
    margin: 2px 0;
}

.methodology {
    border: 1px solid #aaa;
    padding: 9px 11px;
    margin-top: 10px;
    font-size: 9pt;
}

.methodology strong {
    font-size: 9.5pt;
}

.signatures {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
}

.signatures td {
    border: none;
    width: 50%;
    text-align: center;
    padding: 0 35px;
}

.sign-line {
    border-top: 1px solid #000;
    margin-bottom: 4px;
}

.footer {
    border-top: 1px solid #888;
    margin-top: 15px;
    padding-top: 6px;
    text-align: center;
    font-size: 7.5pt;
    color: #444;
}

.no-print {
    text-align: center;
    margin-top: 15px;
}

.no-print button {
    border: 0;
    background: #1A5C3A;
    color: #fff;
    padding: 9px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
}

@media print {
    .no-print {
        display: none !important;
    }
}
</style>
</head>

<body>

<div class="report-page">

    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-center">
                    <div class="gov">REPUBLIC OF THE PHILIPPINES</div>
                    <div class="province">PROVINCE OF NEGROS OCCIDENTAL</div>
                    <div class="municipality">MUNICIPALITY OF SAN ENRIQUE</div>
                    <div class="office">MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICE</div>
                </td>

                <td class="generated">
                    Generated:<br>
                    <?= htmlspecialchars($generatedAt) ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="report-title">BUDGET FORECAST REPORT</div>

    <div class="report-subtitle">
        Projected budget recommendation based on current utilization and demand.
    </div>

    <table class="info-table">
        <tr>
            <td class="info-label">Forecast Basis</td>
            <td>Current budget utilization and beneficiary demand</td>

            <td class="info-label">Custom Increase</td>
            <td><?= number_format($increasePct, 1) ?>%</td>
        </tr>

        <tr>
            <td class="info-label">Eligible Programs</td>
            <td><?= number_format($consider) ?> program<?= $consider === 1 ? '' : 's' ?></td>

            <td class="info-label">Generated By</td>
            <td><?= htmlspecialchars($generatedBy) ?></td>
        </tr>
    </table>

    <div class="section-title">Per-Program Forecast</div>

    <table class="report">
        <thead>
            <tr>
                <th>Program</th>
                <th>Period</th>
                <th>Beneficiaries</th>
                <th>Period Budget</th>
                <th>Spent</th>
                <th>Utilization</th>
                <th>Recommendation / Forecasted Budget</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['program_name']) ?></td>

                <td class="center">
                    <?= htmlspecialchars($r['prog_period']) ?><br>
                    <span style="font-size:8pt;">
                        <?= htmlspecialchars(periodLabel($r['prog_period'], (int)$r['current_period'])) ?>
                    </span>
                </td>

                <td class="center">
                    <?= number_format($r['beneficiaries_value']) ?>
                </td>

                <td class="right">
                    <?= peso($r['period_budget']) ?>
                </td>

                <td class="right">
                    <?= peso($r['spent_value']) ?>
                </td>

                <td class="center">
                    <?= number_format($r['utilization'], 1) ?>%
                </td>

                <td>
                    <?php if ($r['recommendation'] === 'Consider Increase'): ?>

                        <span class="rec-increase">
                            Consider Increase
                        </span>

                        <br>
                        Base Budget:
                        <?= peso($r['period_budget']) ?>

                        <br>
                        Increase:
                        <?= peso($r['increase_amount']) ?>

                        <br>
                        <strong>
                            Recommended Budget:
                            <?= peso($r['recommended_budget']) ?>
                        </strong>

                    <?php elseif ($r['recommendation'] === 'Review'): ?>

                        <span class="rec-review">Review</span>
                        <br>
                        No automatic increase applied.

                    <?php else: ?>

                        <span class="rec-no">
                            No Increase Needed
                        </span>
                        <br>
                        No automatic increase applied.

                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>

            <tr class="total-row">
                <td colspan="2" class="right">TOTAL</td>

                <td class="center">
                    <?= number_format($totalBeneficiaries) ?>
                </td>

                <td class="right">
                    <?= peso($totalPeriodBudget) ?>
                </td>

                <td class="right">
                    <?= peso($totalSpent) ?>
                </td>

                <td class="center">
                    <?= $totalPeriodBudget > 0
                        ? number_format(($totalSpent / $totalPeriodBudget) * 100, 1)
                        : '0.0' ?>%
                </td>

                <td class="right">
                    <?= peso($totalRecommended) ?>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Forecast Calculation</div>

    <div class="calculation">
        <div class="calculation-title">
            Custom Increase Calculation
        </div>

        <div class="calculation-line">
            <strong>Eligible Base Budget:</strong>
            <?= peso($eligibleBaseBudget) ?>
        </div>

        <div class="calculation-line">
            <strong>Custom Increase:</strong>
            <?= number_format($increasePct, 1) ?>%
        </div>

        <div class="calculation-line">
            <strong>Increase Amount:</strong>
            <?= peso($totalIncreaseAmount) ?>
        </div>

        <div class="calculation-line" style="margin-top:6px;">
            <strong>Recommended Budget:</strong>
            <?= peso($totalRecommended) ?>
        </div>

        <div style="margin-top:6px; font-size:8.5pt; color:#444;">
            Formula:
            <?= peso($eligibleBaseBudget) ?>
            × <?= number_format($increasePct, 1) ?>%
            =
            <?= peso($totalIncreaseAmount) ?>
            increase
            <br>

            <?= peso($eligibleBaseBudget) ?>
            +
            <?= peso($totalIncreaseAmount) ?>
            =
            <strong><?= peso($totalRecommended) ?></strong>
        </div>
    </div>

    <div class="section-title">Forecast Methodology</div>

    <div class="methodology">
        <strong>How the forecast works</strong><br>

        Beneficiary count is used only as a program-demand indicator.
        The system does not assign a fixed peso amount per beneficiary.

        Financial utilization and beneficiary volume are considered together.

        A program is eligible for a budget increase when its utilization is
        at least 70% and it has beneficiary volume.

        Programs with utilization of 30% or below are classified as
        <strong>No Increase Needed</strong>. Other programs are classified as
        <strong>Review</strong>.

        The custom percentage is applied only to programs classified as
        <strong>Consider Increase</strong>.

        There is no automatic 15% increase.
        A 0% custom increase means no increase is applied.
    </div>

    <table class="summary">
        <tr>
            <td class="summary-label">Programs for Possible Increase</td>
            <td><?= number_format($consider) ?></td>

            <td class="summary-label">Programs for Review</td>
            <td><?= number_format($review) ?></td>
        </tr>

        <tr>
            <td class="summary-label">Programs with No Increase Needed</td>
            <td><?= number_format($noIncrease) ?></td>

            <td class="summary-label">Custom Increase Applied</td>
            <td><?= number_format($increasePct, 1) ?>%</td>
        </tr>

        <tr>
            <td class="summary-label">Total Increase Amount</td>
            <td><?= peso($totalIncreaseAmount) ?></td>

            <td class="summary-label">Total Recommended Budget</td>
            <td><strong><?= peso($totalRecommended) ?></strong></td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div class="sign-line"></div>
                <strong>Prepared by</strong><br>
                <span style="font-size:8.5pt;">MSWDO Personnel</span>
            </td>

            <td>
                <div class="sign-line"></div>
                <strong>Reviewed by</strong><br>
                <span style="font-size:8.5pt;">
                    Municipal Social Welfare and Development Officer
                </span>
            </td>
        </tr>
    </table>

    <div class="footer">
        MSWDO San Enrique Information System<br>
        Computer-generated Budget Forecast Report • <?= htmlspecialchars($generatedAt) ?>
    </div>

    <div class="no-print">
        <button onclick="window.print()">Print / Save as PDF</button>
    </div>

</div>

<script>
window.addEventListener('load', function () {
    setTimeout(function () {
        window.print();
    }, 400);
});
</script>

</body>
</html>
