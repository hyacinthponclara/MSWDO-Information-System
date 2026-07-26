<?php

    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    require 'auth.php';
    requireRole(['Admin']);
    require 'db_connect.php';

    // This page never renders anything — it only processes a POST from
    // budgetmanagement.php, then redirects back with a flash message
    // (?msg=...&type=success|error) that the page picks up and shows as a toast.

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: budgetmanagement.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'] ?? '';

    function redirectBack(string $msg, string $type = 'success'): void
    {
        header('Location: budgetmanagement.php?msg=' . urlencode($msg) . '&type=' . $type);
        exit;
    }

    // AUGMENT (add funds from an external source, or transfer from another
    // program's remaining budget)
    if ($action === 'augment') {

        $targetId = (int) ($_POST['target_program_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);
        $source = trim($_POST['source'] ?? '');
        $reason = trim($_POST['reason'] ?? '');

        if ($targetId <= 0 || $amount <= 0 || $source === '') {
            redirectBack('Please fill in all required augmentation fields.', 'error');
        }

        try {
            $pdo->beginTransaction();

            // Lock the target row so two admins can't augment the same program
            // at the same time and corrupt the running total.
            $stmt = $pdo->prepare("SELECT program_name FROM program WHERE program_id = ? FOR UPDATE");
            $stmt->execute([$targetId]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$target) {
                throw new Exception('Target program not found.');
            }

            $sourceLabel = $source;
            $logAction = 'Augment';

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
                        p.program_name,
                        p.prog_annual_budget,
                        COALESCE(SUM(a.av_amount),0) AS spent
                    FROM program p
                    LEFT JOIN availment a
                        ON a.program_id = p.program_id
                    AND a.av_status IN ('Approved','Released')
                    WHERE p.program_id = ?
                    GROUP BY
                        p.program_id,
                        p.program_name,
                        p.prog_annual_budget
                    FOR UPDATE
                ");


                $donorStmt->execute([$donorId]);
                $donor = $donorStmt->fetch(PDO::FETCH_ASSOC);

                if (!$donor) {
                    throw new Exception('Donor program not found.');
                }

                $donorRemaining =
                    (float) $donor['prog_annual_budget']
                    -
                    (float) $donor['spent'];
                if ($amount > $donorRemaining) {
                    throw new Exception(
                        'Insufficient funds. Donor only has ₱' .
                        number_format($donorRemaining, 2) .
                        ' remaining.'
                    );
                }
                // Real transfer: the amount leaves the donor's budget entirely
                // (not logged as "spent" — spent is reserved for actual client availments).
                $pdo->prepare("
                    UPDATE program
                    SET prog_annual_budget = prog_annual_budget - ?
                    WHERE program_id = ?
                ")->execute([$amount, $donorId]);

                $pdo->prepare("
                    INSERT INTO budget_log (program_id, user_id, action_type, amount, source, reason)
                    VALUES (?, ?, 'Transfer Out', ?, ?, ?)
                ")->execute([$donorId, $user_id, $amount, 'To ' . $target['program_name'], $reason]);

                $sourceLabel = 'Transfer from ' . $donor['program_name'];
                $logAction = 'Transfer In';

            } elseif ($source === 'Other') {
                $other = trim($_POST['other_source'] ?? '');
                if ($other === '') {
                    throw new Exception('Please specify the source.');
                }
                $sourceLabel = $other;
            }

            // Add the amount to the target program's budget
            $pdo->prepare("
                UPDATE program
                SET prog_annual_budget = prog_annual_budget + ?
                WHERE program_id = ?
            ")->execute([$amount, $targetId]);


            $pdo->prepare("
                INSERT INTO budget_log (program_id, user_id, action_type, amount, source, reason)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$targetId, $user_id, $logAction, $amount, $sourceLabel, $reason]);

            $pdo->commit();

            redirectBack(
                "Budget for {$target['program_name']} augmented by ₱" . number_format($amount, 2) . " from {$sourceLabel}."
            );

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            redirectBack($e->getMessage(), 'error');
        }

        // ─────────────────────────────────────────────────────────────────────────
    // END PERIOD EARLY (unused remaining budget is considered returned to the LGU)
    // ─────────────────────────────────────────────────────────────────────────
    } elseif ($action === 'end_period') {

        $programId = (int) ($_POST['program_id'] ?? 0);
        if ($programId <= 0) {
            redirectBack('Invalid program.', 'error');
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT
                    p.program_name,
                    p.prog_annual_budget,
                    COALESCE(SUM(a.av_amount),0) AS spent
                FROM program p
                LEFT JOIN availment a
                    ON a.program_id = p.program_id
                AND a.av_status IN ('Approved','Released')
                WHERE p.program_id = ?
                GROUP BY
                    p.program_id,
                    p.program_name,
                    p.prog_annual_budget
                FOR UPDATE
                ");

            $stmt->execute([$programId]);
            $program = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$program) {
                throw new Exception('Program not found.');
            }

            $remaining = (float) $program['prog_annual_budget'] - (float) $program['spent'];

            // Guard: if spent somehow exceeds the annual budget (bad data, late-posted
            // availments, manual edits, etc.), $remaining goes negative. Subtracting a
            // negative number from prog_annual_budget would silently INCREASE the budget,
            // which is the opposite of what "end period" should ever do. Treat this as
            // a data problem that needs a human to look at it, rather than clamping to 0
            // and quietly hiding the overspend.
            if ($remaining < 0) {
                throw new Exception(
                    "{$program['program_name']} shows spending of ₱" . number_format((float) $program['spent'], 2) .
                    " against a budget of ₱" . number_format((float) $program['prog_annual_budget'], 2) .
                    " (overspent by ₱" . number_format(abs($remaining), 2) . "). " .
                    "Cannot end period until this is reconciled."
                );
            }

            // Idempotency guard: if a program's remaining is already 0 (e.g. a
            // double-submitted request, or the period was already ended), there's
            // nothing left to return. Avoid writing a spurious ₱0.00 log entry.
            if ($remaining == 0) {
                $pdo->commit();
                redirectBack("{$program['program_name']} has no remaining budget to return; nothing to do.");
            }

            // The annual budget shrinks down to exactly what's been spent so far;
            // the unused remainder is what gets "returned to the LGU."
            $pdo->prepare("
                UPDATE program
                SET prog_annual_budget = prog_annual_budget - ?
                WHERE program_id = ?
            ")->execute([$remaining, $programId]);

            $pdo->prepare("
                INSERT INTO budget_log (program_id, user_id, action_type, amount, source, reason)
                VALUES (?, ?, 'End Period Early', ?, 'Returned to LGU', NULL)
            ")->execute([$programId, $user_id, $remaining]);

            $pdo->commit();

            redirectBack(
                "{$program['program_name']} period ended early. ₱" . number_format($remaining, 2) . " returned to LGU."
            );

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            redirectBack($e->getMessage(), 'error');
        }

    } else {
        redirectBack('Unknown action.', 'error');
    }