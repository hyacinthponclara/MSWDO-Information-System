<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';

function peso(float $n): string { return '₱' . number_format($n, 2); }

$stmt = $pdo->query("
    SELECT
        p.program_name,
        p.prog_period,
        p.prog_annual_budget,
        COALESCE(p.prog_current_period, 1) AS current_period,
        COALESCE(av.spent, 0) + COALESCE(pp.spent, 0) AS spent
    FROM program p
    LEFT JOIN (
        SELECT program_id, SUM(av_amount) spent
        FROM availment
        WHERE av_status = 'Released'
        GROUP BY program_id
    ) av ON av.program_id = p.program_id
    LEFT JOIN (
        SELECT program_id, SUM(pp_budget) spent
        FROM project_proposal
        WHERE pp_status = 'Released'
        GROUP BY program_id
    ) pp ON pp.program_id = p.program_id
    ORDER BY p.program_id
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function divisor(string $period): int {
    return $period === 'Quarterly' ? 4 : ($period === 'Half-Year' ? 2 : 1);
}
function statusFor(float $remaining, float $periodBudget): string {
    if ($periodBudget <= 0) return 'N/A';
    $pct = ($remaining / $periodBudget) * 100;
    return $pct <= 15 ? 'Critical' : ($pct <= 30 ? 'Warning' : 'OK');
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Budget Analysis Report - MSWDO San Enrique</title>
<style>
@page { size: legal; margin: 16mm 15mm 18mm 15mm; }
* { box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size:11pt; color:#222; margin:0; }
.header { text-align:center; line-height:1.25; }
.header img { max-height:55px; max-width:70px; margin:0 8px; vertical-align:middle; }
.header .small { font-size:10pt; }
.title { text-align:center; font-weight:bold; font-size:14pt; margin:22px 0 4px; }
.meta { text-align:center; font-size:10pt; margin-bottom:18px; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
th,td { border:1px solid #777; padding:7px 6px; vertical-align:middle; }
th { background:#eef6f0; text-align:center; font-weight:bold; }
.num { text-align:right; white-space:nowrap; }
.center { text-align:center; }
.section { font-weight:bold; margin-top:20px; margin-bottom:6px; }
.summary { width:55%; margin-left:auto; }
.footer { margin-top:30px; border-top:1px solid #aaa; padding-top:8px; font-size:9pt; text-align:center; }
.signatures { margin-top:45px; display:flex; justify-content:space-between; gap:50px; }
.sig { width:45%; text-align:center; }
.line { border-top:1px solid #222; margin-top:35px; padding-top:5px; }
@media print { .no-print { display:none!important; } }
</style>
</head>
<body>
<div class="header">
    <div><strong>REPUBLIC OF THE PHILIPPINES</strong></div>
    <div>PROVINCE OF NEGROS OCCIDENTAL</div>
    <div>MUNICIPALITY OF SAN ENRIQUE</div>
    <div class="small"><strong>MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICE</strong></div>
</div>

<div class="title">BUDGET ANALYSIS REPORT</div>
<div class="meta">Generated: <?= date('F d, Y h:i:s A') ?></div>

<div class="section">PROGRAM BUDGET ANALYSIS</div>
<table>
<thead><tr>
<th>Program</th><th>Period</th><th>Total Budget</th><th>Total Period Budget</th>
<th>Spent</th><th>Remaining</th><th>Utilization</th><th>Status</th>
</tr></thead>
<tbody>
<?php
$total=0; $spentTotal=0; $remainingTotal=0;
foreach ($rows as $r):
    $periodBudget=(float)$r['prog_annual_budget']/divisor($r['prog_period']);
    $spent=(float)$r['spent'];
    $remaining=max(0,$periodBudget-$spent);
    $util=$periodBudget>0 ? ($spent/$periodBudget)*100 : 0;
    $total+=(float)$r['prog_annual_budget'];
    $spentTotal+=$spent; $remainingTotal+=$remaining;
?>
<tr>
<td><?= htmlspecialchars($r['program_name']) ?></td>
<td class="center"><?= htmlspecialchars($r['prog_period']) ?></td>
<td class="num"><?= peso((float)$r['prog_annual_budget']) ?></td>
<td class="num"><?= peso($periodBudget) ?></td>
<td class="num"><?= peso($spent) ?></td>
<td class="num"><?= peso($remaining) ?></td>
<td class="center"><?= number_format($util,1) ?>%</td>
<td class="center"><?= statusFor($remaining,$periodBudget) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="section">SUMMARY</div>
<table class="summary">
<tr><th>Total Annual Budget</th><td class="num"><?= peso($total) ?></td></tr>
<tr><th>Total Current-Period Spending</th><td class="num"><?= peso($spentTotal) ?></td></tr>
<tr><th>Total Current-Period Remaining</th><td class="num"><?= peso($remainingTotal) ?></td></tr>
</table>

<div class="section">ANALYSIS / RECOMMENDATION</div>
<p>
The report evaluates the current period budget using actual released transactions.
Approved but unreleased requests are not treated as spending. Programs with critical
or warning remaining balances should be reviewed for possible management action.
Programs with low utilization should not automatically receive additional funds.
</p>

<div class="signatures">
<div class="sig"><div class="line"><strong>Prepared by</strong><br>MSWDO Staff</div></div>
<div class="sig"><div class="line"><strong>Reviewed by</strong><br>Municipal Social Welfare and Development Officer</div></div>
</div>

<div class="footer">
MSWDO San Enrique Information System<br>
Computer-generated Budget Analysis Report • <?= date('F d, Y h:i:s A') ?>
</div>

<div class="no-print" style="text-align:center;margin-top:20px">
<button onclick="window.print()">Print / Save as PDF</button>
</div>
<script>
window.addEventListener('load', () => setTimeout(() => window.print(), 400));
</script>
</body>
</html>
