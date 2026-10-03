<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'auth.php';
requireRole(['Admin', 'Social Worker']);
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: budgetmanagement.php');
    exit;
}

$user_id = (int) ($_SESSION['user_id'] ?? 0);
$action = $_POST['action'] ?? '';

function redirectBack(string $msg, string $type = 'success'): void
{
    header('Location: budgetmanagement.php?msg=' . urlencode($msg) . '&type=' . $type);
    exit;
}

function periodCount(string $period): int
{
    return $period === 'Quarterly' ? 4 : ($period === 'Half-Year' ? 2 : 1);
}

/*
 * AUGMENT
 * - External augmentation increases the annual budget.
 * - Transfer from another program moves budget between programs.
 * - Both sides are logged.
 */
if ($action === 'augment') {

    $targetId = (int) ($_POST['target_program_id'] ?? 0);
    $amount   = (float) ($_POST['amount'] ?? 0);
    $source   = trim($_POST['source'] ?? '');
    $reason   = trim($_POST['reason'] ?? '');

    if ($targetId <= 0 || $amount <= 0 || $source === '') {
        redirectBack('Please fill in all required augmentation fields.', 'error');
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT program_id, program_name, prog_annual_budget
            FROM program
            WHERE program_id = ?
            FOR UPDATE
        ");
        $stmt->execute([$targetId]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$target) {
            throw new Exception('Target program not found.');
        }

        $sourceLabel = $source;

        if ($source === 'From another program') {

            $donorId = (int) ($_POST['donor_program_id'] ?? 0);

            if ($donorId <= 0) {
                throw new Exception('Please select the program to transfer from.');
            }

            if ($donorId === $targetId) {
                throw new Exception('Cannot transfer from the same program.');
            }

            $donorStmt = $pdo->prepare("
                SELECT
                    p.program_id,
                    p.program_name,
                    p.prog_annual_budget,
                    COALESCE(av.released, 0) + COALESCE(pp.released, 0) AS spent
                FROM program p
                LEFT JOIN (
                    SELECT program_id, SUM(av_amount) released
                    FROM availment
                    WHERE av_status = 'Released'
                    GROUP BY program_id
                ) av ON av.program_id = p.program_id
                LEFT JOIN (
                    SELECT program_id, SUM(pp_budget) released
                    FROM project_proposal
                    WHERE pp_status = 'Released'
                    GROUP BY program_id
                ) pp ON pp.program_id = p.program_id
                WHERE p.program_id = ?
                FOR UPDATE
            ");
            $donorStmt->execute([$donorId]);
            $donor = $donorStmt->fetch(PDO::FETCH_ASSOC);

            if (!$donor) {
                throw new Exception('Donor program not found.');
            }

            $donorRemaining = max(
                0,
                (float) $donor['prog_annual_budget'] - (float) $donor['spent']
            );

            if ($amount > $donorRemaining) {
                throw new Exception(
                    'Insufficient funds. Donor only has ₱' .
                    number_format($donorRemaining, 2) . ' remaining.'
                );
            }

            $pdo->prepare("
                UPDATE program
                SET prog_annual_budget = prog_annual_budget - ?
                WHERE program_id = ?
            ")->execute([$amount, $donorId]);

            $pdo->prepare("
                INSERT INTO budget_log
                    (program_id, user_id, action_type, amount, source, reason, reference_no)
                VALUES
                    (?, ?, 'Transfer Out', ?, ?, ?, ?)
            ")->execute([
                $donorId,
                $user_id,
                $amount,
                'To ' . $target['program_name'],
                $reason,
                'TRF-' . date('YmdHis') . '-' . $donorId . '-' . $targetId
            ]);

            $sourceLabel = 'Transfer from ' . $donor['program_name'];

            $pdo->prepare("
                UPDATE program
                SET prog_annual_budget = prog_annual_budget + ?
                WHERE program_id = ?
            ")->execute([$amount, $targetId]);

            $pdo->prepare("
                INSERT INTO budget_log
                    (program_id, user_id, action_type, amount, source, reason, reference_no)
                VALUES
                    (?, ?, 'Transfer In', ?, ?, ?, ?)
            ")->execute([
                $targetId,
                $user_id,
                $amount,
                $sourceLabel,
                $reason,
                'TRF-' . date('YmdHis') . '-' . $donorId . '-' . $targetId
            ]);

        } else {

            if ($source === 'Other') {
                $other = trim($_POST['other_source'] ?? '');
                if ($other === '') {
                    throw new Exception('Please specify the source.');
                }
                $sourceLabel = $other;
            }

            $pdo->prepare("
                UPDATE program
                SET prog_annual_budget = prog_annual_budget + ?
                WHERE program_id = ?
            ")->execute([$amount, $targetId]);

            $pdo->prepare("
                INSERT INTO budget_log
                    (program_id, user_id, action_type, amount, source, reason, reference_no)
                VALUES
                    (?, ?, 'Augment', ?, ?, ?, ?)
            ")->execute([
                $targetId,
                $user_id,
                $amount,
                $sourceLabel,
                $reason,
                'AUG-' . date('YmdHis') . '-' . $targetId
            ]);
        }

        $pdo->commit();

        redirectBack(
            "Budget for {$target['program_name']} augmented by ₱" .
            number_format($amount, 2) . " from {$sourceLabel}."
        );

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        redirectBack($e->getMessage(), 'error');
    }
}

/*
 * END PERIOD EARLY
 *
 * Important:
 * - DO NOT reduce prog_annual_budget.
 * - Record the unused current-period amount as returned to Accounting Office.
 * - Advance Quarterly Q1→Q2→Q3→Q4 or Half-Year 1→2.
 * - Annual programs cannot use End Early.
 * - Maximum of 3 early advances per program/year.
 * - New period starts immediately when this action is completed.
 */
elseif ($action === 'end_period') {

    $programId = (int) ($_POST['program_id'] ?? 0);

    if ($programId <= 0) {
        redirectBack('Invalid program.', 'error');
    }

    try {

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT
                p.program_id,
                p.program_name,
                p.prog_period,
                p.prog_annual_budget,
                COALESCE(p.prog_current_period, 1) AS current_period,
                COALESCE(p.prog_early_end_count, 0) AS early_end_count,
                p.prog_period_started_at
            FROM program p
            WHERE p.program_id = ?
            FOR UPDATE
        ");
        $stmt->execute([$programId]);

        $program = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$program) {
            throw new Exception('Program not found.');
        }

        $period = $program['prog_period'];
        $maxPeriods = periodCount($period);
        $currentPeriod = (int) $program['current_period'];
        $earlyEndCount = (int) $program['early_end_count'];

        if ($period === 'Annually') {
            throw new Exception('Annual programs do not have an End Early action.');
        }

        if ($currentPeriod >= $maxPeriods) {
            throw new Exception('This is already the final period. There is no next period to advance to.');
        }

        // End Early limits are derived from the configured period:
        // Quarterly = 3 advances (Q1→Q2→Q3→Q4)
        // Half-Year  = 1 advance (1st Half→2nd Half)
        // Annual     = not applicable (handled above)
        $maxEarlyAdvances = max(0, $maxPeriods - 1);

        if ($earlyEndCount >= $maxEarlyAdvances) {
            throw new Exception(
                "The maximum of {$maxEarlyAdvances} early period advance" .
                ($maxEarlyAdvances === 1 ? '' : 's') .
                " has already been used for this program."
            );
        }

        /*
         * Ensure the current period has a start timestamp.
         * For a newly migrated record, use the current timestamp.
         */
        $periodStartedAt = $program['prog_period_started_at'];

        if (empty($periodStartedAt)) {
            $periodStartedAt = date('Y-m-d H:i:s');

            $pdo->prepare("
                UPDATE program
                SET prog_period_started_at = ?
                WHERE program_id = ?
            ")->execute([$periodStartedAt, $programId]);
        }

        /*
         * Only RELEASED transactions count as spending.
         * The current period begins at prog_period_started_at.
         */
        $avStmt = $pdo->prepare("
            SELECT COALESCE(SUM(av_amount), 0)
            FROM availment
            WHERE program_id = ?
              AND av_status = 'Released'
              AND av_date_released IS NOT NULL
              AND av_date_released >= DATE(?)
        ");
        $avStmt->execute([$programId, $periodStartedAt]);
        $releasedAvailments = (float) $avStmt->fetchColumn();

        $ppStmt = $pdo->prepare("
            SELECT COALESCE(SUM(pp_budget), 0)
            FROM project_proposal
            WHERE program_id = ?
              AND pp_status = 'Released'
              AND pp_date_released IS NOT NULL
              AND pp_date_released >= DATE(?)
        ");
        $ppStmt->execute([$programId, $periodStartedAt]);
        $releasedProposals = (float) $ppStmt->fetchColumn();

        $spent = $releasedAvailments + $releasedProposals;

        $periodBudget =
            (float) $program['prog_annual_budget'] /
            $maxPeriods;

        $remaining = $periodBudget - $spent;

        if ($remaining < -0.01) {
            throw new Exception(
                "{$program['program_name']} is overspent in the current period by ₱" .
                number_format(abs($remaining), 2) .
                ". Reconcile the period before ending it early."
            );
        }

        $remaining = max(0, $remaining);

        /*
         * Advance to the next period.
         */
        $nextPeriod = $currentPeriod + 1;
        $nextEarlyCount = $earlyEndCount + 1;
        $newPeriodStartedAt = date('Y-m-d H:i:s');

        $pdo->prepare("
            UPDATE program
            SET
                prog_current_period = ?,
                prog_early_end_count = ?,
                prog_period_started_at = ?
            WHERE program_id = ?
        ")->execute([
            $nextPeriod,
            $nextEarlyCount,
            $newPeriodStartedAt,
            $programId
        ]);

        /*
         * Keep an accounting-proof record.
         * The annual budget itself is NOT reduced.
         */
        $reason = 'Current period ended early. Unused period budget returned to Accounting Office.';

        $pdo->prepare("
            INSERT INTO budget_log
                (program_id, user_id, action_type, period_number, next_period, amount,
                 returned_amount, source, reason, reference_no)
            VALUES
                (?, ?, 'End Period Early', ?, ?, ?, ?, 'Returned to Accounting Office', ?, ?)
        ")->execute([
            $programId,
            $user_id,
            $currentPeriod,
            $nextPeriod,
            $remaining,
            $remaining,
            $reason,
            'RET-' . date('YmdHis') . '-' . $programId . '-P' . $currentPeriod
        ]);

        $pdo->commit();

        $periodName = $period === 'Quarterly'
            ? 'Q' . $currentPeriod
            : 'Half-Year ' . $currentPeriod;

        $nextName = $period === 'Quarterly'
            ? 'Q' . $nextPeriod
            : 'Half-Year ' . $nextPeriod;

        redirectBack(
            "{$program['program_name']} {$periodName} ended early. " .
            "₱" . number_format($remaining, 2) .
            " returned to the Accounting Office. " .
            "The program is now on {$nextName}."
        );

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        redirectBack($e->getMessage(), 'error');
    }

} else {
    redirectBack('Unknown action.', 'error');
}