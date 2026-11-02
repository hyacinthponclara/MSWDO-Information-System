<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';

$proposalId = filter_var(
    $_GET['id'] ?? $_POST['proposal_id'] ?? 0,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$proposalId) {
    header('Location: fund_request_reports.php');
    exit;
}

$proposalStmt = $pdo->prepare("
    SELECT pp.*, p.program_name, p.prog_annual_budget
    FROM project_proposal pp
    JOIN program p ON p.program_id = pp.program_id
    WHERE pp.proposal_id = ?
    LIMIT 1
");
$proposalStmt->execute([$proposalId]);
$proposal = $proposalStmt->fetch(PDO::FETCH_ASSOC);

if (!$proposal) {
    http_response_code(404);
    exit('Fund request not found.');
}

if (empty($_SESSION['fund_request_update_token'])) {
    $_SESSION['fund_request_update_token'] = bin2hex(random_bytes(32));
}

$escape = static fn($value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8'
);

$values = [
    'pp_title' => $proposal['pp_title'],
    'pp_date_from' => $proposal['pp_date_from'],
    'pp_date_to' => $proposal['pp_date_to'],
    'pp_venue' => $proposal['pp_venue'],
    'pp_num_participants' => $proposal['pp_num_participants'],
    'pp_participant_desc' => $proposal['pp_participant_desc'],
    'pp_budget' => number_format((float) $proposal['pp_budget'], 2, '.', ''),
    'pp_fund_source' => $proposal['pp_fund_source'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (
        !is_string($submittedToken)
        || !hash_equals($_SESSION['fund_request_update_token'], $submittedToken)
    ) {
        $errors[] = 'Your form session expired. Reload this page and submit your changes again.';
    }

    foreach ($values as $field => $unused) {
        $values[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    if ($values['pp_title'] === '') {
        $errors[] = 'Project title is required.';
    }
    if ($values['pp_venue'] === '') {
        $errors[] = 'Venue is required.';
    }
    if ($values['pp_participant_desc'] === '') {
        $errors[] = 'Description of participants is required.';
    }
    if ($values['pp_fund_source'] === '') {
        $errors[] = 'Source of fund is required.';
    }

    $dateFrom = DateTimeImmutable::createFromFormat('!Y-m-d', $values['pp_date_from']);
    $dateTo = DateTimeImmutable::createFromFormat('!Y-m-d', $values['pp_date_to']);
    if (
        !$dateFrom
        || $dateFrom->format('Y-m-d') !== $values['pp_date_from']
        || !$dateTo
        || $dateTo->format('Y-m-d') !== $values['pp_date_to']
    ) {
        $errors[] = 'Enter valid start and end dates.';
    } elseif ($dateTo < $dateFrom) {
        $errors[] = '"To" date cannot be before "From" date.';
    }

    $participantCount = filter_var(
        $values['pp_num_participants'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    if ($participantCount === false) {
        $errors[] = 'Enter a valid number of participants.';
    }

    $budget = is_numeric($values['pp_budget']) ? (float) $values['pp_budget'] : 0;
    if (!is_finite($budget) || $budget <= 0) {
        $errors[] = 'Enter a valid budgetary requirement.';
    }

    $documentUpload = $_FILES['pp_document'] ?? null;
    if (
        $documentUpload
        && $documentUpload['error'] !== UPLOAD_ERR_NO_FILE
        && $documentUpload['error'] !== UPLOAD_ERR_OK
    ) {
        $errors[] = 'The uploaded file could not be processed. Please try again.';
    }

    $newDocumentPath = null;
    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $lockStmt = $pdo->prepare("
                SELECT pp.program_id, pp.pp_status, pp.pp_date_released, pp.pp_document,
                       p.prog_annual_budget
                FROM project_proposal pp
                JOIN program p ON p.program_id = pp.program_id
                WHERE pp.proposal_id = ?
                FOR UPDATE
            ");
            $lockStmt->execute([$proposalId]);
            $lockedProposal = $lockStmt->fetch(PDO::FETCH_ASSOC);

            if (!$lockedProposal) {
                throw new RuntimeException('This fund request no longer exists.');
            }

            $programId = (int) $lockedProposal['program_id'];
            $programLock = $pdo->prepare('SELECT program_id FROM program WHERE program_id = ? FOR UPDATE');
            $programLock->execute([$programId]);

            $releasedThisYear = $lockedProposal['pp_status'] === 'Released'
                && !empty($lockedProposal['pp_date_released'])
                && (new DateTimeImmutable($lockedProposal['pp_date_released']))->format('Y') === date('Y');

            if ($releasedThisYear) {
                $availmentStmt = $pdo->prepare("
                    SELECT COALESCE(SUM(av_amount), 0)
                    FROM availment
                    WHERE program_id = ?
                      AND av_status = 'Released'
                      AND av_date_released IS NOT NULL
                      AND YEAR(av_date_released) = YEAR(CURDATE())
                ");
                $availmentStmt->execute([$programId]);
                $releasedAvailments = (float) $availmentStmt->fetchColumn();

                $proposalSpendStmt = $pdo->prepare("
                    SELECT COALESCE(SUM(pp_budget), 0)
                    FROM project_proposal
                    WHERE program_id = ?
                      AND pp_status = 'Released'
                      AND pp_date_released IS NOT NULL
                      AND YEAR(pp_date_released) = YEAR(CURDATE())
                      AND proposal_id <> ?
                ");
                $proposalSpendStmt->execute([$programId, $proposalId]);
                $otherReleasedProposals = (float) $proposalSpendStmt->fetchColumn();

                $availableBudget = (float) $lockedProposal['prog_annual_budget']
                    - $releasedAvailments
                    - $otherReleasedProposals;

                if ($budget > $availableBudget) {
                    throw new RuntimeException(
                        'The updated budget exceeds the remaining program budget of ₱'
                        . number_format(max(0, $availableBudget), 2) . '.'
                    );
                }
            }

            $documentName = $lockedProposal['pp_document'];
            if ($documentUpload && $documentUpload['error'] === UPLOAD_ERR_OK) {
                if ((int) $documentUpload['size'] > 10 * 1024 * 1024) {
                    throw new RuntimeException('The project proposal document must not exceed 10 MB.');
                }

                $extension = strtolower(pathinfo(basename($documentUpload['name']), PATHINFO_EXTENSION));
                $allowedMimes = [
                    'pdf' => ['application/pdf'],
                    'doc' => ['application/msword'],
                    'docx' => [
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/zip',
                    ],
                    'jpg' => ['image/jpeg'],
                    'jpeg' => ['image/jpeg'],
                    'png' => ['image/png'],
                ];

                if (!isset($allowedMimes[$extension])) {
                    throw new RuntimeException('Upload a PDF, DOC, DOCX, JPG, JPEG, or PNG file.');
                }

                $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $fileInfo->file($documentUpload['tmp_name']);
                if (!in_array($mime, $allowedMimes[$extension], true)) {
                    throw new RuntimeException('The uploaded file content does not match its file type.');
                }

                $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads'
                    . DIRECTORY_SEPARATOR . 'project_proposals';
                if (
                    !is_dir($uploadDirectory)
                    && !mkdir($uploadDirectory, 0755, true)
                    && !is_dir($uploadDirectory)
                ) {
                    throw new RuntimeException('The upload folder could not be created.');
                }

                $originalName = preg_replace(
                    '/[^a-zA-Z0-9._-]/',
                    '_',
                    basename($documentUpload['name'])
                );
                $documentName = bin2hex(random_bytes(12)) . '_' . trim($originalName, '._-');
                $newDocumentPath = $uploadDirectory . DIRECTORY_SEPARATOR . $documentName;

                if (!move_uploaded_file($documentUpload['tmp_name'], $newDocumentPath)) {
                    $newDocumentPath = null;
                    throw new RuntimeException('The uploaded document could not be saved.');
                }
            }

            $updateStmt = $pdo->prepare("
                UPDATE project_proposal
                SET pp_title = ?,
                    pp_date_from = ?,
                    pp_date_to = ?,
                    pp_venue = ?,
                    pp_num_participants = ?,
                    pp_participant_desc = ?,
                    pp_budget = ?,
                    pp_fund_source = ?,
                    pp_document = ?
                WHERE proposal_id = ?
            ");
            $updateStmt->execute([
                $values['pp_title'],
                $values['pp_date_from'],
                $values['pp_date_to'],
                $values['pp_venue'],
                $participantCount,
                $values['pp_participant_desc'],
                $budget,
                $values['pp_fund_source'],
                $documentName,
                $proposalId,
            ]);

            $pdo->commit();

            if ($newDocumentPath && !empty($lockedProposal['pp_document'])) {
                $oldDocumentPath = __DIR__ . DIRECTORY_SEPARATOR . 'uploads'
                    . DIRECTORY_SEPARATOR . 'project_proposals'
                    . DIRECTORY_SEPARATOR . basename($lockedProposal['pp_document']);
                if (is_file($oldDocumentPath) && !unlink($oldDocumentPath)) {
                    error_log('Could not remove replaced proposal document: ' . $oldDocumentPath);
                }
            }

            header('Location: project_proposal_view.php?id=' . $proposalId . '&updated=1');
            exit;
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($newDocumentPath && is_file($newDocumentPath) && !unlink($newDocumentPath)) {
                error_log('Could not remove an uncommitted proposal document: ' . $newDocumentPath);
            }
            $errors[] = $e->getMessage();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($newDocumentPath && is_file($newDocumentPath) && !unlink($newDocumentPath)) {
                error_log('Could not remove an uncommitted proposal document: ' . $newDocumentPath);
            }
            error_log('Fund request update failed: ' . $e->getMessage());
            $errors[] = 'The fund request could not be updated. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Update Fund Request – MSWDO San Enrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>

<body class="min-h-screen bg-slate-50 text-slate-800">
    <?php require 'sidebar.php'; ?>
    <main class="ml-64 p-6">
        <div class="mx-auto max-w-3xl space-y-5">
            <header>
                <a href="project_proposal_view.php?id=<?= (int) $proposalId ?>"
                    class="text-sm text-green-700 hover:underline">
                    <i class="fas fa-arrow-left mr-1"></i> Back to request
                </a>
                <h1 class="mt-3 text-2xl font-semibold text-green-800">Update Fund Request</h1>
                <p class="mt-1 text-sm text-slate-500">
                    <?= $escape($proposal['program_name']) ?> · Request #<?= (int) $proposalId ?>
                </p>
            </header>

            <?php if ($errors): ?>
                <div class="space-y-1 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><i class="fas fa-exclamation-circle mr-1"></i><?= $escape($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data"
                class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <input type="hidden" name="proposal_id" value="<?= (int) $proposalId ?>">
                <input type="hidden" name="csrf_token"
                    value="<?= $escape($_SESSION['fund_request_update_token']) ?>">

                <div>
                    <label for="pp_title" class="mb-1 block text-sm font-medium">Title / Name of Project</label>
                    <input id="pp_title" name="pp_title" required maxlength="255"
                        value="<?= $escape($values['pp_title']) ?>"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="pp_date_from" class="mb-1 block text-sm font-medium">Duration — From</label>
                        <input id="pp_date_from" name="pp_date_from" type="date" required
                            value="<?= $escape($values['pp_date_from']) ?>"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none">
                    </div>
                    <div>
                        <label for="pp_date_to" class="mb-1 block text-sm font-medium">Duration — To</label>
                        <input id="pp_date_to" name="pp_date_to" type="date" required
                            value="<?= $escape($values['pp_date_to']) ?>"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label for="pp_venue" class="mb-1 block text-sm font-medium">Venue</label>
                    <input id="pp_venue" name="pp_venue" required
                        value="<?= $escape($values['pp_venue']) ?>"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="pp_num_participants" class="mb-1 block text-sm font-medium">Number of Participants</label>
                        <input id="pp_num_participants" name="pp_num_participants" type="number" min="1" required
                            value="<?= $escape($values['pp_num_participants']) ?>"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none">
                    </div>
                    <div>
                        <label for="pp_participant_desc" class="mb-1 block text-sm font-medium">Participant Description</label>
                        <input id="pp_participant_desc" name="pp_participant_desc" required
                            value="<?= $escape($values['pp_participant_desc']) ?>"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="pp_budget" class="mb-1 block text-sm font-medium">Budgetary Requirement (₱)</label>
                        <input id="pp_budget" name="pp_budget" type="number" min="0.01" step="0.01" required
                            value="<?= $escape($values['pp_budget']) ?>"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none">
                    </div>
                    <div>
                        <label for="pp_fund_source" class="mb-1 block text-sm font-medium">Source of Fund</label>
                        <input id="pp_fund_source" name="pp_fund_source" required
                            value="<?= $escape($values['pp_fund_source']) ?>"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none">
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4">
                    <label for="pp_document" class="mb-1 block text-sm font-medium">Replace Project Proposal Document (optional)</label>
                    <?php if (!empty($proposal['pp_document'])): ?>
                        <p class="mb-2 text-sm text-slate-500">
                            Current file:
                            <a class="text-green-700 underline"
                                href="uploads/project_proposals/<?= rawurlencode(basename($proposal['pp_document'])) ?>"
                                target="_blank" rel="noopener">
                                <?= $escape(basename($proposal['pp_document'])) ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    <input id="pp_document" name="pp_document" type="file"
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                        class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-green-50 file:px-4 file:py-2 file:font-medium file:text-green-700">
                    <p class="mt-1 text-xs text-slate-400">PDF, DOC, DOCX, JPG, JPEG, or PNG · Maximum 10 MB</p>
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-4">
                    <a href="project_proposal_view.php?id=<?= (int) $proposalId ?>"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">
                        Cancel
                    </a>
                    <button type="submit"
                        class="rounded-lg bg-green-700 px-5 py-2 text-sm font-semibold text-white hover:bg-green-800">
                        <i class="fas fa-save mr-1"></i> Submit Changes
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>

</html>
