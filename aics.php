<?php
require 'auth.php';
requireRole(['Admin','Staff']);
require 'db_connect.php';


$client_id = (int) ($_GET['client_id'] ?? 0);
if ($client_id <= 0) {
    header("Location: clientslist.php");
    exit;
}

$stmt = $pdo->prepare("SELECT cl_firstname, cl_lastname FROM client WHERE client_id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$client) {
    header("Location: clientslist.php");
    exit;
}
$client_name = htmlspecialchars($client['cl_firstname'] . ' ' . $client['cl_lastname']);


function saveFile($field, $folder)
{
    if (!isset($_FILES[$field])) {
        return null; // if wala uploaded store NULL in database
    }
    $file = $_FILES[$field];

    // Multi-file input (e.g. name="edu_doc_card[]" with multiple) - PHP gives arrays here.
    if (is_array($file['name'])) {
        $saved = [];
        foreach ($file['name'] as $i => $original) {
            if (($file['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue; // skip empty/failed slots, don't bail on the whole field
            }
            $safe_name = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($original));
            if (!is_dir($folder)) {
                mkdir($folder, 0755, true); // Create upload folder if missing
            }
            if (move_uploaded_file($file['tmp_name'][$i], $folder . $safe_name)) {
                $saved[] = $safe_name;
            }
        }
        return $saved ? implode(',', $saved) : null; // store as comma-separated list in the single DB column
    }

    // Single-file input
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $original = basename($file['name']);
    $safe_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $original);
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true); // Create upload folder if missing
    }
    if (move_uploaded_file($file['tmp_name'], $folder . $safe_name)) {
        return $safe_name; // 
    }
    return null;
}


$errors = [];
$savedFiles = [];

/**
 * Return true when at least one successfully uploaded file exists for a field.
 * Works for both normal inputs and [] multi-file inputs.
 */
function hasUploadedFile(string $field): bool
{
    if (!isset($_FILES[$field])) {
        return false;
    }

    $file = $_FILES[$field];

    if (is_array($file['name'])) {
        foreach ($file['name'] as $i => $name) {
            if (($file['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
                && trim((string) $name) !== '') {
                return true;
            }
        }
        return false;
    }

    return ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
        && trim((string) ($file['name'] ?? '')) !== '';
}

/**
 * Validate the required upload fields on the server.
 * Browser required="required" is not sufficient because it can be bypassed.
 */
function requireUploadedFiles(array &$errors, array $fields): void
{
    foreach ($fields as $field => $label) {
        if (!hasUploadedFile($field)) {
            $errors[] = $label . ' is required.';
        }
    }
}

/**
 * Save a file and remember its path so it can be removed if the DB transaction
 * is rolled back. This prevents orphaned files when an INSERT fails.
 */
function saveFileTracked(string $field, string $folder, array &$savedFiles): ?string
{
    if (!isset($_FILES[$field])) {
        return null;
    }

    $file = $_FILES[$field];
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $maxBytes = 10 * 1024 * 1024; // 10 MB per uploaded file

    if (is_array($file['name'])) {
        $saved = [];

        foreach ($file['name'] as $i => $original) {
            $error = $file['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                throw new RuntimeException('One of the files for ' . $field . ' could not be uploaded.');
            }

            $tmp = $file['tmp_name'][$i] ?? '';
            $size = (int) ($file['size'][$i] ?? 0);
            $ext = strtolower(pathinfo((string) $original, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions, true)) {
                throw new RuntimeException('Invalid file type for ' . $field . '. Only PDF, JPG, JPEG, and PNG files are allowed.');
            }
            if ($size <= 0 || $size > $maxBytes) {
                throw new RuntimeException('A file for ' . $field . ' exceeds the 10 MB size limit or is empty.');
            }

            if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
                throw new RuntimeException('Unable to create the upload folder.');
            }

            $safeName = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '_' .
                preg_replace('/[^a-zA-Z0-9._-]/', '_', basename((string) $original));
            $path = $folder . $safeName;

            if (!move_uploaded_file($tmp, $path)) {
                throw new RuntimeException('Unable to save an uploaded file for ' . $field . '.');
            }

            $savedFiles[] = $path;
            $saved[] = $safeName;
        }

        return $saved ? implode(',', $saved) : null;
    }

    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The file for ' . $field . ' could not be uploaded.');
    }

    $original = basename((string) $file['name']);
    $size = (int) ($file['size'] ?? 0);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExtensions, true)) {
        throw new RuntimeException('Invalid file type for ' . $field . '. Only PDF, JPG, JPEG, and PNG files are allowed.');
    }
    if ($size <= 0 || $size > $maxBytes) {
        throw new RuntimeException('The file for ' . $field . ' exceeds the 10 MB size limit or is empty.');
    }

    if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
        throw new RuntimeException('Unable to create the upload folder.');
    }

    $safeName = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '_' .
        preg_replace('/[^a-zA-Z0-9._-]/', '_', $original);
    $path = $folder . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new RuntimeException('Unable to save the uploaded file for ' . $field . '.');
    }

    $savedFiles[] = $path;
    return $safeName;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id = (int) ($_SESSION['user_id'] ?? 0);
    $aics_type = trim($_POST['aics_type'] ?? '');
    $amount = (float) ($_POST['amount'] ?? 0);
    $date_applied = trim($_POST['date_applied'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '') ?: null;

    $patientDifferent = (int) ($_POST['patient_different'] ?? 0) === 1;
    $patientName = trim($_POST['patient_name'] ?? '');
    $patientAgeRaw = trim($_POST['patient_age'] ?? '');
    $patientRelationship = trim($_POST['patient_relationship'] ?? '');

    // -------------------------
    // Basic server-side validation
    // -------------------------
    $allowedTypes = ['medical', 'financial', 'educational', 'livelihood', 'burial'];

    if (!in_array($aics_type, $allowedTypes, true)) {
        $errors[] = 'Please select a valid assistance type.';
    }
    if ($amount < 500 || $amount > 5000) {
        $errors[] = 'Amount must be between ₱500 and ₱5,000.';
    }
    if ($date_applied === '') {
        $errors[] = 'Date Applied is required.';
    } else {
        $appliedDate = DateTime::createFromFormat('Y-m-d', $date_applied);
        $dateErrors = DateTime::getLastErrors();
        if (!$appliedDate || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
            $errors[] = 'Date Applied is invalid.';
        } elseif ($date_applied > date('Y-m-d')) {
            $errors[] = 'Date Applied cannot be a future date.';
        }
    }

    if ($user_id <= 0) {
        $errors[] = 'Your user session is invalid. Please log in again.';
    }

    if ($aics_type === 'medical') {
        requireUploadedFiles($errors, [
            'doc_medcert' => 'Medical Certificate / Abstract',
            'doc_labresults' => 'Laboratory Results / Resita',
            'doc_validid' => 'Valid ID',
            'doc_indigency' => 'Barangay Indigency Certificate',
        ]);

        if ($patientDifferent) {
            if ($patientName === '') {
                $errors[] = 'Patient Name is required when Different patient is selected.';
            }
            if ($patientAgeRaw !== '' && (!ctype_digit($patientAgeRaw) || (int) $patientAgeRaw > 150)) {
                $errors[] = 'Patient Age must be a valid age.';
            }
            if ($patientRelationship === '') {
                $errors[] = 'Relationship to Client is required when Different patient is selected.';
            }
        }
    } elseif ($aics_type === 'financial') {
        requireUploadedFiles($errors, [
            'fin_doc_mayors' => "Mayor's Approval",
            'fin_doc_id' => 'Valid ID',
            'fin_doc_indigency' => 'Barangay Indigency Certificate',
        ]);
    } elseif ($aics_type === 'educational') {
        requireUploadedFiles($errors, [
            'edu_doc_card' => 'Grades / Report Card',
            'edu_doc_enroll' => 'Certificate of Enrollment',
            'edu_doc_indigency' => 'Certificate of Indigency',
            'edu_doc_residency' => 'Certificate of Residency',
            'edu_doc_studentid' => 'Student ID',
            'edu_doc_claimantid' => "Claimant's Valid ID",
        ]);

        $eduLevel = trim($_POST['edu_level'] ?? '');
        $eduPurpose = trim($_POST['edu_purpose'] ?? '');
        $eduSchool = trim($_POST['edu_school'] ?? '');
        $eduYear = trim($_POST['edu_school_year'] ?? '');
        $eduSemester = trim($_POST['edu_semester'] ?? '');

        if (!in_array($eduLevel, ['K-12', 'College', 'Vocational', 'Graduate'], true)) {
            $errors[] = 'Educational Level is required.';
        }
        if ($eduSchool === '') {
            $errors[] = 'School Name is required.';
        }
        if ($eduPurpose === '') {
            $errors[] = 'Purpose is required.';
        }
        if ($eduPurpose === 'Other' && trim($_POST['edu_purpose_other'] ?? '') === '') {
            $errors[] = 'Please specify the educational purpose.';
        }
        if ($eduYear === '') {
            $errors[] = 'School Year is required.';
        }
        if ($eduSemester === '') {
            $errors[] = 'Semester / Term is required.';
        }
    } elseif ($aics_type === 'livelihood') {
        requireUploadedFiles($errors, [
            'liv_doc_intent' => 'Letter of Intent',
            'liv_doc_proposal' => 'Business Proposal',
            'liv_doc_validid' => 'Valid ID',
            'liv_doc_indigency' => 'Certificate of Indigency',
            'liv_doc_residency' => 'Certificate of Residency',
        ]);

        if (trim($_POST['biz_name'] ?? '') === '') {
            $errors[] = 'Business Name is required.';
        }
        if (trim($_POST['biz_type'] ?? '') === '') {
            $errors[] = 'Business Type is required.';
        }
        if (!isset($_POST['biz_cost']) || $_POST['biz_cost'] === '' || (float) $_POST['biz_cost'] < 0) {
            $errors[] = 'Start-up Cost is required and cannot be negative.';
        }
    } elseif ($aics_type === 'burial') {
        requireUploadedFiles($errors, [
            'bur_doc_death' => 'Death Certificate',
            'bur_doc_contract' => 'Funeral Contract',
            'bur_doc_validid' => 'Valid ID',
            'bur_doc_indigency' => 'Barangay Indigency Certificate',
        ]);

        $ab_deceased_name = trim($_POST['deceased_name'] ?? '');
        $ab_date_of_death = trim($_POST['date_of_death'] ?? '');
        $ab_relationship_to_claimant = trim($_POST['relationship_to_claimant'] ?? '');
        $ab_funeral_home = trim($_POST['funeral_home'] ?? '');
        $ab_funeral_cost_raw = trim($_POST['funeral_cost'] ?? '');

        if ($ab_deceased_name === '') {
            $errors[] = 'Name of Deceased is required.';
        }

        if ($ab_date_of_death === '') {
            $errors[] = 'Date of Death is required.';
        } else {
            $deathDate = DateTime::createFromFormat('Y-m-d', $ab_date_of_death);
            $deathDateErrors = DateTime::getLastErrors();
            if (
                !$deathDate ||
                ($deathDateErrors !== false && ($deathDateErrors['warning_count'] > 0 || $deathDateErrors['error_count'] > 0))
            ) {
                $errors[] = 'Date of Death is invalid.';
            } elseif ($deathDate > new DateTime('today')) {
                $errors[] = 'Date of Death cannot be in the future.';
            }
        }

        if ($ab_relationship_to_claimant === '') {
            $errors[] = 'Relationship to Claimant is required.';
        }

        if ($ab_funeral_cost_raw === '' || !is_numeric($ab_funeral_cost_raw) || (float)$ab_funeral_cost_raw < 0) {
            $errors[] = 'Funeral Contract Amount is required and cannot be negative.';
        }

        $ab_funeral_cost = (float) $ab_funeral_cost_raw;
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Resolve the correct AICS program.
            $programName = $aics_type === 'educational' ? 'AICS Educational' : 'AICS FBML';
            $progStmt = $pdo->prepare("
                SELECT program_id, prog_annual_budget
                FROM program
                WHERE program_name = ?
                LIMIT 1
                FOR UPDATE
            ");
            $progStmt->execute([$programName]);
            $program = $progStmt->fetch(PDO::FETCH_ASSOC);

            if (!$program) {
                throw new RuntimeException('The AICS program budget record could not be found.');
            }

            $program_id = (int) $program['program_id'];
            $totalBudget = (float) ($program['prog_annual_budget'] ?? 0);

            // Only Released amounts are considered spent/deducted.
            $spentStmt = $pdo->prepare("
                SELECT COALESCE(SUM(av_amount), 0)
                FROM availment
                WHERE program_id = ?
                  AND av_status = 'Released'
                  AND av_date_released IS NOT NULL
                  AND YEAR(av_date_released) = YEAR(CURDATE())
            ");
            $spentStmt->execute([$program_id]);
            $spentBudget = (float) $spentStmt->fetchColumn();
            $remainingBudget = $totalBudget - $spentBudget;

            if ($amount > $remainingBudget) {
                throw new RuntimeException('Insufficient remaining budget for this program.');
            }

            // Re-check AICS limits on the server, using the actual submitted date.
            // These checks prevent JavaScript from being the only enforcement layer.
            $year = (int) date('Y', strtotime($date_applied));
            $applied = new DateTimeImmutable($date_applied);
            $windowStart = $applied->modify('-2 months');
            $windowEnd = $applied->modify('+3 months');

            if ($aics_type === 'educational') {
                $countStmt = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM availment
                    WHERE client_id = ?
                      AND program_id = ?
                      AND av_status != 'Denied'
                      AND YEAR(av_date_applied) = ?
                ");
                $countStmt->execute([$client_id, $program_id, $year]);
                if ((int) $countStmt->fetchColumn() >= 2) {
                    throw new RuntimeException('Educational assistance year limit reached. Maximum is 2 per year.');
                }

                $qStmt = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM availment
                    WHERE client_id = ?
                      AND program_id = ?
                      AND av_status != 'Denied'
                      AND av_date_applied >= ?
                      AND av_date_applied < ?
                ");
                $qStmt->execute([$client_id, $program_id, $windowStart->format('Y-m-d'), $windowEnd->format('Y-m-d')]);
                if ((int) $qStmt->fetchColumn() >= 1) {
                    throw new RuntimeException('Educational assistance quarter limit reached. Maximum is 1 in the current 3-month window.');
                }
            } else {
                $tableMap = [
                    'medical' => 'aics_medical',
                    'financial' => 'aics_financial',
                    'burial' => 'aics_burial',
                    'livelihood' => 'aics_livelihood',
                ];
                $table = $tableMap[$aics_type];

                $yearStmt = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM availment a
                    INNER JOIN {$table} s ON s.availment_id = a.availment_id
                    WHERE a.client_id = ?
                      AND a.program_id = ?
                      AND a.av_status != 'Denied'
                      AND YEAR(a.av_date_applied) = ?
                ");
                $yearStmt->execute([$client_id, $program_id, $year]);
                if ((int) $yearStmt->fetchColumn() >= 4) {
                    throw new RuntimeException(ucfirst($aics_type) . ' assistance year limit reached. Maximum is 4 per year.');
                }

                // Find the latest prior transaction for this subtype and apply the same
                // rolling 3-month rule used by the screen. The submitted date is the
                // reference date for this request.
                $qStmt = $pdo->prepare("
                    SELECT a.av_date_applied
                    FROM availment a
                    INNER JOIN {$table} s ON s.availment_id = a.availment_id
                    WHERE a.client_id = ?
                      AND a.program_id = ?
                      AND a.av_status != 'Denied'
                      AND a.av_date_applied <= ?
                    ORDER BY a.av_date_applied DESC
                    LIMIT 1
                ");
                $qStmt->execute([$client_id, $program_id, $date_applied]);
                $lastDate = $qStmt->fetchColumn();
                if ($lastDate) {
                    $last = new DateTimeImmutable($lastDate);
                    if ($applied < $last->modify('+3 months')) {
                        throw new RuntimeException(ucfirst($aics_type) . ' assistance quarter limit reached. Maximum is 1 in the current 3-month window.');
                    }
                }
            }

            // -------------------------
            // Insert the main transaction first, still inside the transaction.
            // It remains Approved and unreleased; Release Queue changes it to Released.
            // -------------------------
            $stmt = $pdo->prepare("
                INSERT INTO availment (
                    client_id,
                    program_id,
                    user_id,
                    av_date_applied,
                    av_date_approved,
                    av_amount,
                    av_status,
                    av_date_released,
                    av_remarks
                ) VALUES (?, ?, ?, ?, Now(), ?, 'Approved', NULL, ?)
            ");
            $stmt->execute([
                $client_id,
                $program_id,
                $user_id,
                $date_applied,
                $amount,
                $remarks
            ]);
            $availment_id = (int) $pdo->lastInsertId();

            // -------------------------
            // Save subtype data and documents.
            // If anything fails, the catch block rolls back the DB and deletes files.
            // -------------------------
            if ($aics_type === 'medical') {
                $folder = 'uploads/aics/medical/';
                $amed_med_cert = saveFileTracked('doc_medcert', $folder, $savedFiles);
                $amed_lab_result = saveFileTracked('doc_labresults', $folder, $savedFiles);
                $amed_valid_id = saveFileTracked('doc_validid', $folder, $savedFiles);
                $amed_cert_indigency = saveFileTracked('doc_indigency', $folder, $savedFiles);
                $amed_hospital_bill = saveFileTracked('doc_hospitalbill', $folder, $savedFiles);
                $amed_discharge_summary = saveFileTracked('doc_discharge', $folder, $savedFiles);
                $amed_med_quotation = saveFileTracked('doc_dialysis', $folder, $savedFiles);
                $amed_chemo_protocol = saveFileTracked('doc_chemo', $folder, $savedFiles);
                $amed_mayors_approval = saveFileTracked('doc_mayors', $folder, $savedFiles);

                $patientNameToSave = $patientDifferent ? $patientName : trim($client['cl_firstname'] . ' ' . $client['cl_lastname']);
                $patientAgeToSave = $patientAgeRaw !== '' ? (int) $patientAgeRaw : null;
                $patientRelationshipToSave = $patientDifferent ? $patientRelationship : 'Self';

                $stmt = $pdo->prepare("
                    INSERT INTO aics_medical (
                        availment_id,
                        amed_patient_name,
                        amed_patient_age,
                        amed_patient_relationship,
                        amed_med_cert,
                        amed_valid_id,
                        amed_cert_indigency,
                        amed_lab_result,
                        amed_hospital_bill,
                        amed_discharge_summary,
                        amed_med_quotation,
                        amed_chemo_protocol,
                        amed_mayors_approval
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $availment_id,
                    $patientNameToSave,
                    $patientAgeToSave,
                    $patientRelationshipToSave,
                    $amed_med_cert,
                    $amed_valid_id,
                    $amed_cert_indigency,
                    $amed_lab_result,
                    $amed_hospital_bill,
                    $amed_discharge_summary,
                    $amed_med_quotation,
                    $amed_chemo_protocol,
                    $amed_mayors_approval
                ]);

            } elseif ($aics_type === 'financial') {
                $folder = 'uploads/aics/financial/';
                $afin_approval = saveFileTracked('fin_doc_mayors', $folder, $savedFiles);
                $afin_valid_id = saveFileTracked('fin_doc_id', $folder, $savedFiles);
                $afin_supporting_docs = saveFileTracked('fin_doc_indigency', $folder, $savedFiles);
                $afin_supporting_docs_2 = saveFileTracked('fin_doc_support', $folder, $savedFiles);

                $stmt = $pdo->prepare("
                    INSERT INTO aics_financial (
                        availment_id,
                        afin_approval,
                        afin_valid_id,
                        afin_supporting_docs,
                        afin_supporting_docs_2
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $availment_id,
                    $afin_approval,
                    $afin_valid_id,
                    $afin_supporting_docs,
                    $afin_supporting_docs_2
                ]);

            } elseif ($aics_type === 'educational') {
                $aed_school_name = trim($_POST['edu_school'] ?? '') ?: null;
                $aed_educational_level = trim($_POST['edu_level'] ?? '') ?: null;
                $aed_purpose = trim($_POST['edu_purpose'] ?? '') ?: null;
                if ($aed_purpose === 'Other') {
                    $aed_purpose_other = trim($_POST['edu_purpose_other'] ?? '');
                    if ($aed_purpose_other !== '') {
                        $aed_purpose = $aed_purpose_other;
                    }
                }
                $aed_school_year = trim($_POST['edu_school_year'] ?? '') ?: null;
                $aed_semester = trim($_POST['edu_semester'] ?? '') ?: null;

                $folder = 'uploads/aics/educational/';
                $aed_grades = saveFileTracked('edu_doc_card', $folder, $savedFiles);
                $aed_cert_enrollment = saveFileTracked('edu_doc_enroll', $folder, $savedFiles);
                $aed_cert_indigency = saveFileTracked('edu_doc_indigency', $folder, $savedFiles);
                $aed_cert_residency = saveFileTracked('edu_doc_residency', $folder, $savedFiles);
                $aed_student_id = saveFileTracked('edu_doc_studentid', $folder, $savedFiles);
                $aed_claimant_id = saveFileTracked('edu_doc_claimantid', $folder, $savedFiles);

                $stmt = $pdo->prepare("
                    INSERT INTO aics_educational (
                        availment_id,
                        aed_grades,
                        aed_cert_enrollment,
                        aed_cert_indigency,
                        aed_cert_residency,
                        aed_student_id,
                        aed_claimant_id,
                        aed_school_name,
                        aed_educational_level,
                        aed_purpose,
                        aed_school_year,
                        aed_semester
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $availment_id,
                    $aed_grades,
                    $aed_cert_enrollment,
                    $aed_cert_indigency,
                    $aed_cert_residency,
                    $aed_student_id,
                    $aed_claimant_id,
                    $aed_school_name,
                    $aed_educational_level,
                    $aed_purpose,
                    $aed_school_year,
                    $aed_semester
                ]);

            } elseif ($aics_type === 'livelihood') {
                $aliv_business_name = trim($_POST['biz_name'] ?? '') ?: null;
                $aliv_business_type = trim($_POST['biz_type'] ?? '') ?: null;
                $aliv_business_location = trim($_POST['biz_location'] ?? '') ?: null;
                $aliv_target_start_date = trim($_POST['biz_start_date'] ?? '') ?: null;
                $aliv_start_up_cost = (float) ($_POST['biz_cost'] ?? 0);

                $folder = 'uploads/aics/livelihood/';
                $aliv_letter_intent = saveFileTracked('liv_doc_intent', $folder, $savedFiles);
                $aliv_livelihood_proposal = saveFileTracked('liv_doc_proposal', $folder, $savedFiles);
                $aliv_valid_id = saveFileTracked('liv_doc_validid', $folder, $savedFiles);
                $aliv_cert_indigency = saveFileTracked('liv_doc_indigency', $folder, $savedFiles);
                $aliv_cert_residency = saveFileTracked('liv_doc_residency', $folder, $savedFiles);
                $aliv_training_certificate = saveFileTracked('liv_doc_training', $folder, $savedFiles);

                $stmt = $pdo->prepare("
                    INSERT INTO aics_livelihood (
                        availment_id,
                        aliv_letter_intent,
                        aliv_livelihood_proposal,
                        aliv_valid_id,
                        aliv_cert_indigency,
                        aliv_cert_residency,
                        aliv_business_name,
                        aliv_business_type,
                        aliv_business_location,
                        aliv_target_start_date,
                        aliv_start_up_cost,
                        aliv_training_certificate
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $availment_id,
                    $aliv_letter_intent,
                    $aliv_livelihood_proposal,
                    $aliv_valid_id,
                    $aliv_cert_indigency,
                    $aliv_cert_residency,
                    $aliv_business_name,
                    $aliv_business_type,
                    $aliv_business_location,
                    $aliv_target_start_date,
                    $aliv_start_up_cost,
                    $aliv_training_certificate
                ]);

            } elseif ($aics_type === 'burial') {
                $folder = 'uploads/aics/burial/';
                $ab_death_cert = saveFileTracked('bur_doc_death', $folder, $savedFiles);
                $ab_funeral_contract = saveFileTracked('bur_doc_contract', $folder, $savedFiles);
                $ab_valid_id = saveFileTracked('bur_doc_validid', $folder, $savedFiles);
                $ab_brgy_indigency = saveFileTracked('bur_doc_indigency', $folder, $savedFiles);
                $ab_mayors_approval = saveFileTracked('bur_doc_mayors', $folder, $savedFiles);

                $stmt = $pdo->prepare("
                    INSERT INTO aics_burial (
                        availment_id,
                        ab_deceased_name,
                        ab_date_of_death,
                        ab_relationship_to_claimant,
                        ab_funeral_home,
                        ab_funeral_cost,
                        ab_death_cert,
                        ab_funeral_contract,
                        ab_valid_id,
                        ab_brgy_indigency,
                        ab_mayors_approval
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $availment_id,
                    $ab_deceased_name,
                    $ab_date_of_death,
                    $ab_relationship_to_claimant,
                    $ab_funeral_home !== '' ? $ab_funeral_home : null,
                    $ab_funeral_cost,
                    $ab_death_cert,
                    $ab_funeral_contract,
                    $ab_valid_id,
                    $ab_brgy_indigency,
                    $ab_mayors_approval
                ]);
            }

            $pdo->commit();

            header("Location: clientprofile.php?id={$client_id}&saved=aics");
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            foreach ($savedFiles as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            $errors[] = 'Unable to save the AICS availment: ' . $e->getMessage();
        }
    }
}


$stmt = $pdo->prepare("
    SELECT
        p.prog_annual_budget,
        COALESCE(SUM(CASE WHEN a.av_status = 'Released' THEN a.av_amount ELSE 0 END), 0) AS spent
    FROM program p
    LEFT JOIN availment a
        ON a.program_id = p.program_id
        AND YEAR(a.av_date_applied) = YEAR(CURDATE())
    WHERE p.program_name = 'AICS FBML'
    GROUP BY p.program_id
    LIMIT 1
");
$stmt->execute();
$budget = $stmt->fetch(PDO::FETCH_ASSOC);
$annual = (float) ($budget['prog_annual_budget'] ?? 0);
$spent = (float) ($budget['spent'] ?? 0);
$remaining = $annual - $spent;
$pct_used = $annual > 0 ? round(($spent / $annual) * 100, 1) : 0;

// Budget badge — what to show based on $pct_used
if ($pct_used >= 90) {
    $badge_cls = 'text-red-500 bg-red-50 border-red-200';
    $badge_icon = 'fa-exclamation-triangle';
    $badge_text = 'Critical — ' . round(100 - $pct_used, 1) . '% remaining';
    $bar_color = 'bg-red-400';
} elseif ($pct_used >= 70) {
    $badge_cls = 'text-amber-600 bg-amber-50 border-amber-200';
    $badge_icon = 'fa-exclamation-circle';
    $badge_text = 'Moderate — ' . round(100 - $pct_used, 1) . '% remaining';
    $bar_color = 'bg-amber-400';
} else {
    $badge_cls = 'text-emerald-600 bg-emerald-50 border-emerald-200';
    $badge_icon = 'fa-check-circle';
    $badge_text = 'Healthy — ' . round(100 - $pct_used, 1) . '% remaining';
    $bar_color = 'bg-emerald-400';
}


//  AICS Educational budget (separate budget) 
$stmt = $pdo->prepare("
    SELECT
        p.prog_annual_budget,
        COALESCE(SUM(CASE WHEN a.av_status = 'Released' THEN a.av_amount ELSE 0 END), 0) AS spent
    FROM program p
    LEFT JOIN availment a
        ON a.program_id = p.program_id
        AND YEAR(a.av_date_applied) = YEAR(CURDATE())
    WHERE p.program_name = 'AICS Educational'
    GROUP BY p.program_id
    LIMIT 1
");
$stmt->execute();
$edu_budget = $stmt->fetch(PDO::FETCH_ASSOC);
$edu_annual = (float) ($edu_budget['prog_annual_budget'] ?? 0);
$edu_spent = (float) ($edu_budget['spent'] ?? 0);
$edu_remaining = $edu_annual - $edu_spent;
$edu_pct_used = $edu_annual > 0 ? round(($edu_spent / $edu_annual) * 100, 1) : 0;

if ($edu_pct_used >= 90) {
    $edu_badge_cls = 'text-red-500 bg-red-50 border-red-200';
    $edu_badge_icon = 'fa-exclamation-triangle';
    $edu_badge_text = 'Critical — ' . round(100 - $edu_pct_used, 1) . '% remaining';
    $edu_bar_color = 'bg-red-400';
} elseif ($edu_pct_used >= 70) {
    $edu_badge_cls = 'text-amber-600 bg-amber-50 border-amber-200';
    $edu_badge_icon = 'fa-exclamation-circle';
    $edu_badge_text = 'Moderate — ' . round(100 - $edu_pct_used, 1) . '% remaining';
    $edu_bar_color = 'bg-amber-400';
} else {
    $edu_badge_cls = 'text-emerald-600 bg-emerald-50 border-emerald-200';
    $edu_badge_icon = 'fa-check-circle';
    $edu_badge_text = 'Healthy — ' . round(100 - $edu_pct_used, 1) . '% remaining';
    $edu_bar_color = 'bg-emerald-400';
}

$edu_budget_ok = $edu_remaining > 0;

function getSubtypeDates(PDO $pdo, int $client_id, int $program_id, string $join_table): array
{
    $stmt = $pdo->prepare("
        SELECT a.av_date_applied
        FROM availment a
        JOIN {$join_table} s ON s.availment_id = a.availment_id
        WHERE a.client_id  = ?
          AND a.program_id = ?
          AND a.av_status != 'Denied'
        ORDER BY a.av_date_applied ASC
    ");
    $stmt->execute([$client_id, $program_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getRollingWindowCount(array $dates): array
{
    if (empty($dates)) {
        return ['count' => 0, 'window_start' => null, 'window_end' => null];
    }
    $today = new DateTime();
    $q_count = 0;
    $current_window_start = null;
    $current_window_end = null;

    $window_start = new DateTime($dates[0]);
    $window_end = (clone $window_start)->modify('+3 months');
    $window_avs = [];

    foreach ($dates as $d) {
        $dt = new DateTime($d);
        if ($dt < $window_end) {
            $window_avs[] = $d;
        } else {
            $window_start = $dt;
            $window_end = (clone $window_start)->modify('+3 months');
            $window_avs = [$d];
        }
        if ($today >= $window_start && $today < $window_end) {
            $current_window_start = clone $window_start;
            $current_window_end = clone $window_end;
            $q_count = count($window_avs);
        }
    }
    return [
        'count' => $q_count,
        'window_start' => $current_window_start,
        'window_end' => $current_window_end,
    ];
}

$fbml_pid_stmt = $pdo->prepare("SELECT program_id FROM program WHERE program_name = 'AICS FBML' LIMIT 1");
$fbml_pid_stmt->execute();
$fbml_program_id = (int) ($fbml_pid_stmt->fetchColumn() ?? 0);

$med_q = getRollingWindowCount(getSubtypeDates($pdo, $client_id, $fbml_program_id, 'aics_medical'));
$fin_q = getRollingWindowCount(getSubtypeDates($pdo, $client_id, $fbml_program_id, 'aics_financial'));
$bur_q = getRollingWindowCount(getSubtypeDates($pdo, $client_id, $fbml_program_id, 'aics_burial'));
$liv_q = getRollingWindowCount(getSubtypeDates($pdo, $client_id, $fbml_program_id, 'aics_livelihood'));

//  Per-subtype year counts 
$med_y_count = count(array_filter(getSubtypeDates($pdo, $client_id, $fbml_program_id, 'aics_medical'), fn($d) => date('Y', strtotime($d)) == date('Y')));
$fin_y_count = count(array_filter(getSubtypeDates($pdo, $client_id, $fbml_program_id, 'aics_financial'), fn($d) => date('Y', strtotime($d)) == date('Y')));
$bur_y_count = count(array_filter(getSubtypeDates($pdo, $client_id, $fbml_program_id, 'aics_burial'), fn($d) => date('Y', strtotime($d)) == date('Y')));
$liv_y_count = count(array_filter(getSubtypeDates($pdo, $client_id, $fbml_program_id, 'aics_livelihood'), fn($d) => date('Y', strtotime($d)) == date('Y')));

//  AICS Educational: separate 2×/year limit 
$edu_pid_stmt = $pdo->prepare("SELECT program_id FROM program WHERE program_name = 'AICS Educational' LIMIT 1");
$edu_pid_stmt->execute();
$edu_program_id = (int) ($edu_pid_stmt->fetchColumn() ?? 0);

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM availment
    WHERE client_id  = ?
      AND program_id = ?
      AND av_status != 'Denied'
      AND YEAR(av_date_applied) = YEAR(CURDATE())
");
$stmt->execute([$client_id, $edu_program_id]);
$edu_y_count = (int) $stmt->fetchColumn();

//  AICS Educational: rolling quarter-window count (same pattern as FBML subtypes) 
$edu_q = getRollingWindowCount(getSubtypeDates($pdo, $client_id, $edu_program_id, 'aics_educational'));

// $quarter_ok = true means AT LEAST ONE subtype is still within its quarter limit.
// The Proceed button is only fully hard-blocked when every FBML subtype is blocked.
$quarter_ok = ($med_q['count'] < 1)
    || ($fin_q['count'] < 1)
    || ($bur_q['count'] < 1)
    || ($liv_q['count'] < 1);

$edu_year_ok = $edu_y_count < 2;
$budget_ok = $remaining > 0;
$edu_year_left = max(0, 2 - $edu_y_count);

// Per-subtype year remaining
$med_left = max(0, 4 - $med_y_count);
$fin_left = max(0, 4 - $fin_y_count);
$bur_left = max(0, 4 - $bur_y_count);
$liv_left = max(0, 4 - $liv_y_count);

// Soonest window reset date — shown in the block banner when all subtypes are blocked
$soonest_reset = null;
foreach ([$med_q, $fin_q, $bur_q, $liv_q] as $qdata) {
    if ($qdata['window_end'] !== null) {
        if ($soonest_reset === null || $qdata['window_end'] < $soonest_reset) {
            $soonest_reset = $qdata['window_end'];
        }
    }
}

$window_reset_text = $soonest_reset ? $soonest_reset->format('F j, Y') : '';

$post_errors = $errors ?? [];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AICS Availment Forms – MSWDO San Enrique</title>
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
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.35s ease both',
                        'fade-up-1': 'fadeUp 0.35s 0.05s ease both',
                        'fade-up-2': 'fadeUp 0.35s 0.10s ease both',
                        'fade-up-3': 'fadeUp 0.35s 0.15s ease both',
                        'fade-up-4': 'fadeUp 0.35s 0.20s ease both',
                        'fade-in': 'fadeIn 0.25s ease both',
                        'slide-up': 'slideUp 0.3s ease both',
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
            display: none;
        }

        .screen-panel.active {
            display: block;
            animation: fadeUp 0.3s ease both;
        }

        .sub-nav {
            transition: all .18s ease;
        }

        .sub-nav:hover {
            background: #F1F5F9;
        }

        .sub-nav.active {
            background: #0B2545;
            color: #fff;
        }

        .sub-nav.active .sub-icon {
            background: rgba(255, 255, 255, .15);
        }

        .sub-nav.active .sub-check {
            opacity: 1;
        }

        .sub-check {
            opacity: 0;
            transition: opacity .15s;
        }

        .type-card {
            transition: all .2s ease;
            cursor: pointer;
        }

        .type-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .type-card.active-card {
            border-color: #0B2545 !important;
            background: #F8FAFE !important;
            box-shadow: 0 4px 16px rgba(11, 37, 69, 0.12);
        }

        .type-card .card-icon {
            transition: transform .2s ease;
        }

        .type-card:hover .card-icon {
            transform: scale(1.1);
        }

        .field {
            display: block;
            width: 100%;
            border-radius: 0.75rem;
            border: 1.5px solid #E2E8F0;
            background: #F8FAFC;
            padding: 0.625rem 0.875rem;
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
            letter-spacing: 0.05em;
            color: #64748B;
            margin-bottom: 6px;
        }

        .req::after {
            content: '*';
            color: #EF4444;
            margin-left: 2px;
        }

        .upload-zone {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 130px;
            border: 2px dashed #CBD5E1;
            border-radius: 0.875rem;
            padding: 1.25rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            background: #F8FAFC;
            width: 100%;
            box-sizing: border-box;
        }

        .upload-zone:hover {
            border-color: #3A5F93;
            background: #EBF4FB;
        }

        .upload-zone.has-file {
            border-color: #0B2545;
            background: #E8EDF5;
            border-style: solid;
        }

        .upload-zone input[type=file] {
            display: none;
        }

        .upload-zone .upload-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            gap: 0.25rem;
        }

        .upload-zone .upload-icon {
            font-size: 1.75rem;
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .upload-zone .upload-title {
            font-size: 12px;
            font-weight: 500;
            color: #475569;
            line-height: 1.3;
        }

        .upload-zone .upload-hint {
            font-size: 11px;
            color: #94A3B8;
            line-height: 1.3;
        }

        .upload-zone.has-file .upload-icon {
            font-size: 1.5rem;
        }

        .upload-zone.has-file .upload-title {
            color: #0B2545;
            font-weight: 600;
            font-size: 12px;
            word-break: break-all;
            padding: 0 4px;
        }

        .upload-zone.has-file .upload-hint {
            color: #3A5F93;
            font-size: 10px;
        }

        .copy-badge {
            display: inline-flex;
            align-items: center;
            padding: 1px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            background: #FEF3C7;
            color: #92400E;
            margin-left: 6px;
        }

        .opt-badge {
            display: inline-flex;
            align-items: center;
            padding: 1px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 500;
            background: #F1F5F9;
            color: #64748B;
            margin-left: 6px;
        }

        .budget-bar-fill {
            transition: width 1s cubic-bezier(.4, 0, .2, 1);
        }

        .limit-row {
            transition: background .1s;
        }

        .limit-row:hover {
            background: #F8FAFC;
        }

        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, .15);
            border-radius: 2px;
        }

        #toast {
            transition: all .3s ease;
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
    </style>
</head>

<body class="bg-slate2 min-h-screen flex">

    <?php require 'sidebar.php'; ?>

    <div class="ml-64 flex-1 flex flex-col min-h-screen">
        <header
            class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
            <div class="flex items-center gap-2 text-[13px]">
                <a href="clientslist.php" class="text-slate-400 hover:text-navy-600">Clients</a>
                <span class="text-slate-300">/</span>
                <a href="clientprofile.php?id=<?= $client_id ?>"
                    class="text-slate-400 hover:text-navy-600"><?= $client_name ?></a>
                <span class="text-slate-300">/</span>
                <a href="programavailmentselection.php?client_id=<?= $client_id ?>"
                    class="text-slate-400 hover:text-navy-600">Program Selection</a>
                <span class="text-slate-300">/</span>
                <span class="text-navy-600 font-semibold" id="breadcrumbLast">AICS Availment</span>
            </div>
        </header>

        <div class="flex flex-1">
            <main class="flex-1 p-6 overflow-y-auto">

                <?php if (!empty($post_errors)): ?>
                    <div
                        class="max-w-3xl mx-auto mb-4 bg-red-50 border border-red-200 rounded-xl px-5 py-3.5 flex items-start gap-3">
                        <i class="fas fa-exclamation-circle text-red-400 text-lg mt-0.5"></i>
                        <div>
                            <p class="text-[13px] font-semibold text-red-700 mb-1">Please fix the following:</p>
                            <ul class="list-disc list-inside text-[12px] text-red-600 space-y-0.5">
                                <?php foreach ($post_errors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="aics.php?client_id=<?= $client_id ?>" enctype="multipart/form-data"
                    id="aicsForm">

                    <input type="hidden" name="aics_type" id="hiddenType" value="">
                    <input type="hidden" name="amount" id="hiddenAmount" value="">
                    <input type="hidden" name="date_applied" id="hiddenApplied" value="">
                    <input type="hidden" name="remarks" id="hiddenRemarks" value="">
                    <input type="hidden" name="patient_different" id="patientDifferent" value="0">

                    <!-- MAIN FORM PANEL -->
                    <div class="screen-panel active" id="panel-main">
                        <div class="max-w-3xl mx-auto space-y-5">
                            <div class="animate-fade-up">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-slate-300">·</span>
                                    <span class="text-[12px] text-slate-400">Common fields for all AICS subtypes</span>
                                </div>
                                <h1 class="text-xl font-serif text-navy-600">AICS Availment — Main Form</h1>
                                <p class="text-[13px] text-slate-500 mt-1">Complete the transaction details, then
                                    proceed to the subtype-specific requirements form.</p>
                            </div>

                            <div class="animate-fade-up-1 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                                <div class="px-5 py-4 border-b border-slate-100 flex items-center">
                                    <h2 class="text-[13px] font-semibold text-navy-600" id="budgetTitle">AICS Budget
                                        Status — <span id="budgetProgramLabel">AICS FBML</span></h2>
                                </div>
                                <div class="px-5 py-4 grid grid-cols-3 gap-4">
                                    <div>
                                        <p
                                            class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold mb-1">
                                            Annual Budget</p>
                                        <p class="text-[18px] font-bold text-navy-600" id="budgetAnnual">
                                            ₱<?= number_format($annual) ?></p>
                                    </div>
                                    <div>
                                        <p
                                            class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold mb-1">
                                            Spent This Year</p>
                                        <p class="text-[18px] font-bold text-slate-700" id="budgetSpent">
                                            ₱<?= number_format($spent) ?></p>
                                    </div>
                                    <div>
                                        <p
                                            class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold mb-1">
                                            Remaining</p>
                                        <p class="text-[18px] font-bold" id="budgetRemaining">
                                            ₱<?= number_format($remaining) ?></p>
                                    </div>
                                </div>
                                <div class="px-5 pb-4">
                                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="budget-bar-fill h-2 rounded-full" id="budgetBar" style="width:0%"
                                            data-target="<?= $pct_used ?>%"></div>
                                    </div>
                                    <div class="flex justify-between text-[10px] text-slate-400 mt-1.5">
                                        <span>0%</span>
                                        <span class="font-semibold" id="budgetPct"><?= $pct_used ?>% utilized</span>
                                        <span>100%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Transaction Details -->
                            <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                                <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                    <div
                                        class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold">
                                        1</div>
                                    <div>
                                        <h2 class="text-[14px] font-semibold text-navy-600">Transaction Details</h2>
                                        <p class="text-[11px] text-slate-400">Amount, dates, and limit verification</p>
                                    </div>
                                </div>
                                <div class="p-6 space-y-5">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="field-label req">Amount (₱)</label>
                                            <div class="relative">
                                                <span
                                                    class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-medium text-[13px]">₱</span>
                                                <input type="number" min="500" max="5000" class="field pl-7"
                                                    placeholder="500 – 5,000" oninput="checkAmount(this)"
                                                    id="amountField">
                                            </div>
                                            <p class="text-[10px] text-slate-400 mt-1.5">Min ₱500 · Max ₱5,000</p>
                                        </div>
                                        <div><label class="field-label req">Date Applied</label><input type="date"
                                                class="field" id="dateApplied" max="<?= date('Y-m-d') ?>"></div>
                                    </div>

                                    <div class="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden"
                                        id="limitPanel">
                                        <div
                                            class="px-4 py-2.5 bg-slate-100/80 border-b border-slate-200 flex items-center justify-between">
                                            <p class="text-[11px] font-semibold text-navy-600">Automatic Limit Check —
                                                <?= $client_name ?> · AICS
                                            </p>
                                        </div>
                                        <div id="limitRows" class="divide-y divide-slate-100">

                                            <!-- Quarter row (shared format for FBML subtypes and Educational) -->
                                            <div id="row-quarter"
                                                class="limit-row flex items-center justify-between px-4 py-2.5">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-calendar-alt text-slate-500 text-sm"></i>
                                                    <div>
                                                        <span class="text-[12px] text-slate-600">Availments this
                                                            quarter</span>
                                                        <span class="ml-2 text-[10px] text-slate-400"
                                                            id="quarter-window-label"></span>
                                                    </div>
                                                </div>
                                                <span class="text-[12px] font-semibold text-slate-400"
                                                    id="quarter-status-text">— Select assistance type below</span>
                                            </div>

                                            <!-- Year row (shared format for FBML subtypes and Educational) -->
                                            <div id="row-year"
                                                class="limit-row flex items-center justify-between px-4 py-2.5">
                                                <div class="flex items-center gap-2"><i
                                                        class="fas fa-calendar-week text-slate-500 text-sm"></i><span
                                                        class="text-[12px] text-slate-600">Availments this year</span>
                                                </div>
                                                <span class="text-[12px] font-semibold text-slate-400"
                                                    id="fbml-year-text">—</span>
                                            </div>

                                            <div class="limit-row flex items-center justify-between px-4 py-2.5"
                                                id="row-budget">
                                                <div class="flex items-center gap-2"><i
                                                        class="fas fa-chart-line text-slate-500 text-sm"></i><span
                                                        class="text-[12px] text-slate-600">Budget sufficient</span>
                                                </div>
                                                <span
                                                    class="text-[12px] font-semibold <?= $budget_ok ? 'text-emerald-600' : 'text-red-500' ?>"
                                                    id="budgetSufficientText">
                                                    <?= $budget_ok ? '✓ ₱' . number_format($remaining) . ' available' : '✗ No budget remaining' ?>
                                                </span>
                                            </div>
                                            <div class="limit-row flex items-center justify-between px-4 py-2.5"
                                                id="amountCheck">
                                                <div class="flex items-center gap-2"><i
                                                        class="fas fa-dollar-sign text-slate-500 text-sm"></i><span
                                                        class="text-[12px] text-slate-600">Amount within range</span>
                                                </div>
                                                <span class="text-[12px] font-semibold text-slate-400">— Enter amount
                                                    above</span>
                                            </div>
                                        </div>

                                        <!-- Block banner — only shown when the SELECTED subtype is at its quarter limit -->
                                        <div id="quarterBlockBanner"
                                            class="hidden mx-4 mb-4 mt-1 bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-start gap-3">
                                            <i class="fas fa-ban text-red-500 text-base mt-0.5 flex-shrink-0"></i>
                                            <div>
                                                <p class="text-[12px] font-semibold text-red-700">Quarter limit reached
                                                    for this type — Select a different type or wait until the quarter
                                                    resets</p>
                                                <p class="text-[11px] text-red-600 mt-0.5" id="quarterBlockMsg"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div><label class="field-label">Remarks</label><textarea class="field resize-none"
                                            rows="3" id="remarksInput"
                                            placeholder="Optional notes about this transaction..."></textarea></div>
                                </div>
                            </div>

                            <!-- Assistance Type Selection Cards -->
                            <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 p-6">
                                <h2 class="text-[14px] font-semibold text-navy-600 mb-1">Select Assistance Type</h2>
                                <p class="text-[12px] text-slate-400 mb-5">Choose the AICS program subtype to proceed
                                    with the corresponding requirements.</p>
                                <div class="grid grid-cols-5 gap-3" id="typeSelector">
                                    <div onclick="selectType(this,'medical')"
                                        class="type-card bg-white border-2 border-slate-200 rounded-2xl p-4 text-center hover:border-navy-600 group">
                                        <div
                                            class="card-icon w-12 h-12 mx-auto mb-3 rounded-xl bg-slate-100 flex items-center justify-center text-2xl">
                                            <i class="fas fa-capsules text-navy-500"></i>
                                        </div>
                                        <p class="text-[13px] font-semibold text-slate-700">Medical</p>
                                        <p class="text-[10px] text-slate-400 mt-1">9 documents</p>
                                        <?php if ($med_q['count'] >= 1): ?>
                                            <p class="text-[10px] text-red-400 font-semibold mt-1">Quarter full</p>
                                        <?php endif; ?>
                                    </div>
                                    <div onclick="selectType(this,'financial')"
                                        class="type-card bg-white border-2 border-slate-200 rounded-2xl p-4 text-center hover:border-navy-600 group">
                                        <div
                                            class="card-icon w-12 h-12 mx-auto mb-3 rounded-xl bg-slate-100 flex items-center justify-center text-2xl">
                                            <i class="fas fa-coins text-navy-600"></i>
                                        </div>
                                        <p class="text-[13px] font-semibold text-slate-700">Financial</p>
                                        <p class="text-[10px] text-slate-400 mt-1">4 documents</p>
                                        <?php if ($fin_q['count'] >= 1): ?>
                                            <p class="text-[10px] text-red-400 font-semibold mt-1">Quarter full</p>
                                        <?php endif; ?>
                                    </div>
                                    <div onclick="selectType(this,'educational')"
                                        class="type-card bg-white border-2 border-slate-200 rounded-2xl p-4 text-center hover:border-navy-600 group">
                                        <div
                                            class="card-icon w-12 h-12 mx-auto mb-3 rounded-xl bg-slate-100 flex items-center justify-center text-2xl">
                                            <i class="fas fa-graduation-cap text-navy-600"></i>
                                        </div>
                                        <p class="text-[13px] font-semibold text-slate-700">Educational</p>
                                        <p class="text-[10px] text-slate-400 mt-1">6 documents</p>
                                        <?php if ($edu_q['count'] >= 1): ?>
                                            <p class="text-[10px] text-red-400 font-semibold mt-1">Quarter full</p>
                                        <?php elseif (!$edu_year_ok): ?>
                                            <p class="text-[10px] text-red-400 font-semibold mt-1">Year limit reached</p>
                                        <?php endif; ?>
                                    </div>
                                    <div onclick="selectType(this,'livelihood')"
                                        class="type-card bg-white border-2 border-slate-200 rounded-2xl p-4 text-center hover:border-navy-600 group">
                                        <div
                                            class="card-icon w-12 h-12 mx-auto mb-3 rounded-xl bg-slate-100 flex items-center justify-center text-2xl">
                                            <i class="fas fa-briefcase text-navy-600"></i>
                                        </div>
                                        <p class="text-[13px] font-semibold text-slate-700">Livelihood</p>
                                        <p class="text-[10px] text-slate-400 mt-1">6 documents</p>
                                        <?php if ($liv_q['count'] >= 1): ?>
                                            <p class="text-[10px] text-red-400 font-semibold mt-1">Quarter full</p>
                                        <?php endif; ?>
                                    </div>
                                    <div onclick="selectType(this,'burial')"
                                        class="type-card bg-white border-2 border-slate-200 rounded-2xl p-4 text-center hover:border-navy-600 group">
                                        <div
                                            class="card-icon w-12 h-12 mx-auto mb-3 rounded-xl bg-slate-100 flex items-center justify-center text-2xl">
                                            <i class="fas fa-dove text-navy-600"></i>
                                        </div>
                                        <p class="text-[13px] font-semibold text-slate-700">Burial</p>
                                        <p class="text-[10px] text-slate-400 mt-1">5 documents</p>
                                        <?php if ($bur_q['count'] >= 1): ?>
                                            <p class="text-[10px] text-red-400 font-semibold mt-1">Quarter full</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="animate-fade-up-4 flex justify-end gap-3">
                                <button type="button" id="proceedToSubtype"
                                    class="flex items-center gap-2 text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-3 hover:bg-navy-500 transition-all">
                                    Proceed to Requirements →
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- MEDICAL -->
                    <div class="screen-panel" id="panel-medical">
                        <div class="max-w-3xl mx-auto space-y-5">
                            <div class="animate-fade-up">
                                <div class="flex items-center gap-2 mb-1"><span class="text-[12px] text-slate-400">AICS
                                        Medical Requirements</span></div>
                                <h1 class="text-xl font-serif text-navy-600">Medical Assistance — Requirements</h1>
                                <p class="text-[13px] text-slate-500 mt-1">Upload all required documents. Copy counts
                                    follow DSWD guidelines.</p>
                            </div>
                            <div
                                class="animate-fade-up-1 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 flex items-start gap-3">
                                <i class="fas fa-info-circle text-blue-400 text-lg mt-0.5"></i>
                                <div class="text-[12px] text-blue-800"><strong class="font-semibold">DSWD Document
                                        Standard:</strong> All required documents must be submitted as <strong>1
                                        original + 2 photocopies</strong> each, unless otherwise noted.</div>
                            </div>
                            <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                                <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                    <div
                                        class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                        1</div>
                                    <div>
                                        <h2 class="text-[14px] font-semibold text-navy-600">Patient Information</h2>
                                        <p class="text-[11px] text-slate-400">Fill in only if the patient is different
                                            from the client/claimant</p>
                                    </div>
                                    <label class="ml-auto flex items-center gap-2 cursor-pointer">
                                        <span class="text-[12px] text-slate-500">Different patient</span>
                                        <div class="relative w-9 h-5 bg-slate-200 rounded-full transition-colors"
                                            id="patientToggleTrack" onclick="togglePatient()">
                                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform"
                                                id="patientToggleThumb"></div>
                                        </div>
                                    </label>
                                </div>
                                <div id="patientFields" class="p-6 hidden">
                                    <div class="grid grid-cols-3 gap-4">
                                        <div><label class="field-label req">Patient Name</label><input type="text"
                                                class="field" name="patient_name" placeholder="Full name of patient"></div>
                                        <div><label class="field-label">Age</label><input type="number" class="field" name="patient_age"
                                                placeholder="Age" min="0" max="150"></div>
                                        <div><label class="field-label">Relationship to Client</label><input type="text"
                                                class="field" name="patient_relationship" placeholder="e.g. Child, Spouse"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                                <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                    <div
                                        class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                        2</div>
                                    <div>
                                        <h2 class="text-[14px] font-semibold text-navy-600">Required Documents</h2>
                                        <p class="text-[11px] text-slate-400">Upload scanned copies or photos of each
                                            document</p>
                                    </div>
                                </div>
                                <div class="p-6 grid grid-cols-2 gap-4">
                                    <!--
                                -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Medical Certificate /
                                            Abstract <span class="copy-badge">1 orig + 2 copies</span></div>
                                        <label class="upload-zone" id="uz-medcert">
                                            <input type="file" name="doc_medcert[]" accept=".pdf,.jpg,.jpeg,.png"
                                                multiple required onchange="fileSelected(this,'uz-medcert')">
                                            <div class="upload-content"><i
                                                    class="fas fa-paperclip upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="doc_labresults" → DB: amed_lab_result -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Laboratory Results /
                                            Resita <span class="copy-badge">1 orig + 2 copies</span></div>
                                        <label class="upload-zone" id="uz-labresults">
                                            <input type="file" name="doc_labresults[]" accept=".pdf,.jpg,.jpeg,.png"
                                                multiple required onchange="fileSelected(this,'uz-labresults')">
                                            <div class="upload-content"><i class="fas fa-flask upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="doc_validid" → DB: amed_valid_id -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Valid ID <span
                                                class="copy-badge">1 orig + 2 copies</span></div>
                                        <label class="upload-zone" id="uz-validid">
                                            <input type="file" name="doc_validid[]" accept=".pdf,.jpg,.jpeg,.png"
                                                multiple required onchange="fileSelected(this,'uz-validid')">
                                            <div class="upload-content"><i class="fas fa-id-card upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="doc_indigency" → DB: amed_cert_indigency -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Barangay Indigency
                                            Certificate <span class="copy-badge">1 orig + 2 copies</span></div>
                                        <label class="upload-zone" id="uz-indigency">
                                            <input type="file" name="doc_indigency[]" accept=".pdf,.jpg,.jpeg,.png"
                                                multiple required onchange="fileSelected(this,'uz-indigency')">
                                            <div class="upload-content"><i class="fas fa-file-alt upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="doc_hospitalbill" → DB: amed_hospital_bill -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Hospital Bill <span
                                                class="opt-badge">Optional — if admitted</span></div>
                                        <label class="upload-zone" id="uz-hospitalbill">
                                            <input type="file" name="doc_hospitalbill" accept=".pdf,.jpg,.jpeg,.png"
                                                onchange="fileSelected(this,'uz-hospitalbill')">
                                            <div class="upload-content"><i
                                                    class="fas fa-hospital-user upload-icon"></i><span
                                                    class="upload-title">Click to upload</span><span
                                                    class="upload-hint">PDF, JPG, PNG</span></div>
                                        </label>
                                    </div>
                                    <!-- name="doc_discharge" → DB: amed_discharge_summary -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Discharge Summary
                                            <span class="opt-badge">Optional</span>
                                        </div>
                                        <label class="upload-zone" id="uz-discharge">
                                            <input type="file" name="doc_discharge" accept=".pdf,.jpg,.jpeg,.png"
                                                onchange="fileSelected(this,'uz-discharge')">
                                            <div class="upload-content"><i
                                                    class="fas fa-clipboard-list upload-icon"></i><span
                                                    class="upload-title">Click to upload</span><span
                                                    class="upload-hint">PDF, JPG, PNG</span></div>
                                        </label>
                                    </div>
                                    <!-- name="doc_dialysis" → DB: amed_med_quotation -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Medical Quotation
                                            (Dialysis) <span class="opt-badge">Optional</span></div>
                                        <label class="upload-zone" id="uz-dialysis">
                                            <input type="file" name="doc_dialysis" accept=".pdf,.jpg,.jpeg,.png"
                                                onchange="fileSelected(this,'uz-dialysis')">
                                            <div class="upload-content"><i class="fas fa-syringe upload-icon"></i><span
                                                    class="upload-title">Click to upload</span><span
                                                    class="upload-hint">PDF, JPG, PNG</span></div>
                                        </label>
                                    </div>
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Medical Protocol
                                            (Chemo) <span class="opt-badge">Optional</span></div>
                                        <label class="upload-zone" id="uz-chemo">
                                            <input type="file" name="doc_chemo" accept=".pdf,.jpg,.jpeg,.png"
                                                onchange="fileSelected(this,'uz-chemo')">
                                            <div class="upload-content"><i class="fas fa-flask upload-icon"></i><span
                                                    class="upload-title">Click to upload</span><span
                                                    class="upload-hint">PDF, JPG, PNG</span></div>
                                        </label>
                                    </div>
                                    <!-- name="doc_mayors" → DB: amed_mayors_approval -->
                                    <div class="col-span-2">
                                        <div class="field-label flex items-center flex-wrap gap-1">Mayor's Approval
                                            <span class="opt-badge">LGU AICS only</span>
                                        </div>
                                        <label class="upload-zone" id="uz-mayors">
                                            <input type="file" name="doc_mayors" accept=".pdf,.jpg,.jpeg,.png"
                                                onchange="fileSelected(this,'uz-mayors')">
                                            <div class="upload-content"><i class="fas fa-landmark upload-icon"></i><span
                                                    class="upload-title">Click to upload Mayor's Approval</span><span
                                                    class="upload-hint">PDF, JPG, PNG</span></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <button type="button" onclick="switchSub('main')"
                                    class="text-[13px] font-medium text-slate-500 border border-slate-200 rounded-xl px-5 py-2.5 hover:border-navy-400 hover:text-navy-600 transition-all">←
                                    Back to Main Form</button>
                                <!-- saveComplete() fills hidden inputs then submits the form -->
                                <button type="button" onclick="saveComplete()"
                                    class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500 transition-all">Save
                                    & Complete ✓</button>
                            </div>
                        </div>
                    </div>

                    <!-- FINANCIAL -->
                    <div class="screen-panel" id="panel-financial">
                        <div class="max-w-3xl mx-auto space-y-5">
                            <div class="animate-fade-up">
                                <div class="flex items-center gap-2 mb-1"><span class="text-[12px] text-slate-400">AICS
                                        Financial Requirements</span></div>
                                <h1 class="text-xl font-serif text-navy-600">Financial Assistance — Requirements</h1>
                                <p class="text-[13px] text-slate-500 mt-1">Upload the Mayor's approval and any
                                    supporting documents for this financial assistance request.</p>
                            </div>
                            <div
                                class="animate-fade-up-1 bg-navy-50 border border-navy-100 rounded-xl px-4 py-3 flex items-start gap-3">
                                <i class="fas fa-exclamation-triangle text-navy-400 text-lg mt-0.5"></i>
                                <p class="text-[12px] text-navy-800">Financial assistance requires <strong
                                        class="font-semibold">Mayor's approval</strong> before release. Ensure the
                                    approval letter is signed and dated before submission.</p>
                            </div>
                            <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                                <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                    <div
                                        class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                        1</div>
                                    <div>
                                        <h2 class="text-[14px] font-semibold text-navy-600">Required Documents</h2>
                                    </div>
                                </div>
                                <div class="p-6 grid grid-cols-2 gap-4">
                                    <!-- name="fin_doc_mayors" → saveFile('fin_doc_mayors',...) → DB: afin_approval -->
                                    <div class="col-span-2">
                                        <div class="field-label flex items-center flex-wrap gap-1">Mayor's Approval
                                            <span class="copy-badge">1 original required</span>
                                        </div>
                                        <label class="upload-zone" id="uz-fin-mayors">
                                            <input type="file" name="fin_doc_mayors" accept=".pdf,.jpg,.jpeg,.png"
                                                required onchange="fileSelected(this,'uz-fin-mayors')">
                                            <div class="upload-content"><i class="fas fa-landmark upload-icon"></i><span
                                                    class="upload-title">Click to upload Mayor's Approval</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="fin_doc_id" → (stored with afin_supporting_docs) -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Valid ID <span
                                                class="copy-badge">1 orig + 2 copies</span></div>
                                        <label class="upload-zone" id="uz-fin-id">
                                            <input type="file" name="fin_doc_id[]" accept=".pdf,.jpg,.jpeg,.png"
                                                multiple required onchange="fileSelected(this,'uz-fin-id')">
                                            <div class="upload-content"><i class="fas fa-id-card upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="fin_doc_indigency" → DB: afin_supporting_docs -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Barangay Indigency
                                            <span class="copy-badge">1 orig + 2 copies</span>
                                        </div>
                                        <label class="upload-zone" id="uz-fin-indigency">
                                            <input type="file" name="fin_doc_indigency[]" accept=".pdf,.jpg,.jpeg,.png"
                                                multiple required onchange="fileSelected(this,'uz-fin-indigency')">
                                            <div class="upload-content"><i class="fas fa-file-alt upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="fin_doc_support" → DB: afin_supporting_docs_2 -->
                                    <div class="col-span-2">
                                        <div class="field-label flex items-center flex-wrap gap-1">Supporting Documents
                                            <span class="opt-badge">Multiple files allowed</span>
                                        </div>
                                        <label class="upload-zone" id="uz-fin-support">
                                            <input type="file" name="fin_doc_support" accept=".pdf,.jpg,.jpeg,.png"
                                                multiple onchange="fileSelected(this,'uz-fin-support')">
                                            <div class="upload-content"><i
                                                    class="fas fa-folder-open upload-icon"></i><span
                                                    class="upload-title">Click to upload (multiple files
                                                    accepted)</span><span class="upload-hint">PDF, JPG, PNG</span></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                                <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                    <div
                                        class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                        2</div>
                                    <div>
                                        <h2 class="text-[14px] font-semibold text-navy-600">Additional Notes</h2>
                                    </div>
                                </div>
                                <div class="p-6"><label class="field-label">Remarks</label><textarea
                                        class="field resize-none" rows="3"
                                        placeholder="Describe the financial need and purpose of assistance..."></textarea>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <button type="button" onclick="switchSub('main')"
                                    class="text-[13px] font-medium text-slate-500 border border-slate-200 rounded-xl px-5 py-2.5 hover:border-navy-400 hover:text-navy-600 transition-all">←
                                    Back to Main Form</button>
                                <button type="button" onclick="saveComplete()"
                                    class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500 transition-all">Save
                                    & Complete ✓</button>
                            </div>
                        </div>
                    </div>

                    <!-- EDUCATIONAL -->
                    <div class="screen-panel" id="panel-educational">
                        <div class="max-w-3xl mx-auto space-y-5">
                            <div class="animate-fade-up">
                                <div class="flex items-center gap-2 mb-1"><span class="text-[12px] text-slate-400">AICS
                                        Educational Requirements</span></div>
                                <h1 class="text-xl font-serif text-navy-600">Educational Assistance — Requirements</h1>
                                <p class="text-[13px] text-slate-500 mt-1">Maximum twice per school year · Max ₱20,000
                                    per year total.</p>
                            </div>
                            <div
                                class="animate-fade-up-1 bg-navy-50 border border-navy-100 rounded-xl px-4 py-3 flex items-start gap-3">
                                <i class="fas fa-info-circle text-navy-400 text-lg mt-0.5"></i>
                                <p class="text-[12px] text-navy-800">Educational assistance is limited to <strong>2
                                        times per school year</strong> with a maximum of <strong>₱20,000/year</strong>.
                                </p>
                            </div>
                            <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                                <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                    <div
                                        class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                        1</div>
                                    <div>
                                        <h2 class="text-[14px] font-semibold text-navy-600">Education Details</h2>
                                    </div>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <label class="field-label req">Educational Level</label>
                                            <!-- name="edu_level" → $_POST['edu_level'] → $aed_educational_level in PHP -->
                                            <select class="field" name="edu_level">
                                                <option value="">Select</option>
                                                <option value="K-12">K-12</option>
                                                <option value="College">College</option>
                                                <option value="Vocational">Vocational / Technical</option>
                                                <option value="Graduate">Graduate Studies</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="field-label req">Purpose</label>
                                            <!-- name="edu_purpose" → $_POST['edu_purpose'] → $aed_purpose -->
                                            <select class="field" name="edu_purpose" id="eduPurposeSelect"
                                                onchange="toggleEduPurposeOther(this.value)">
                                                <option value="">Select</option>
                                                <option>Tuition Fee</option>
                                                <option>Field Trip</option>
                                                <option>Diploma Processing</option>
                                                <option>School Supplies</option>
                                                <option>Miscellaneous Fees</option>
                                                <option>Other</option>
                                            </select>
                                            <!-- Shown only when "Other" is selected above -->
                                            <input type="text" class="field mt-2 hidden" name="edu_purpose_other"
                                                id="eduPurposeOther" placeholder="Please specify purpose">
                                        </div>
                                        <div>
                                            <label class="field-label req">School / Institution Name</label>
                                            <!-- name="edu_school" → $_POST['edu_school'] → $aed_school_name -->
                                            <input type="text" class="field" name="edu_school"
                                                placeholder="Name of school or university">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div><label class="field-label">School Year</label><select class="field"
                                                name="edu_school_year">
                                                <?php
                                                // Generate a rolling range of school years instead of hardcoding
                                                // just two: 1 year ahead through 5 years back, most recent first.
                                                $sy_start = (int) date('Y') + 1;
                                                for ($sy = $sy_start; $sy >= $sy_start - 6; $sy--) {
                                                    echo '<option>' . $sy . '–' . ($sy + 1) . '</option>';
                                                }
                                                ?>
                                            </select></div>
                                        <div><label class="field-label">Semester / Term</label><select class="field"
                                                name="edu_semester">
                                                <option>1st Semester</option>
                                                <option>2nd Semester</option>
                                                <option>Summer</option>
                                            </select></div>
                                    </div>
                                </div>
                            </div>
                            <div class="animate-fade-up-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                                <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                    <div
                                        class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                        2</div>
                                    <div>
                                        <h2 class="text-[14px] font-semibold text-navy-600">Required Documents</h2>
                                    </div>
                                </div>
                                <div class="p-6 grid grid-cols-2 gap-4">
                                    <!-- name="edu_doc_card" → saveFile('edu_doc_card',...) → DB: aed_grades -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Report Card / Grades
                                            <span class="copy-badge">1 orig + 2 copies</span>
                                        </div>
                                        <label class="upload-zone" id="uz-edu-card">
                                            <input type="file" name="edu_doc_card[]" multiple required
                                                onchange="fileSelected(this,'uz-edu-card')">
                                            <div class="upload-content"><i
                                                    class="fas fa-chart-line upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="edu_doc_enroll" → DB: aed_cert_enrollment -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Certificate of
                                            Enrollment <span class="copy-badge">1 orig + 2 copies</span></div>
                                        <label class="upload-zone" id="uz-edu-enroll">
                                            <input type="file" name="edu_doc_enroll[]" multiple required
                                                onchange="fileSelected(this,'uz-edu-enroll')">
                                            <div class="upload-content"><i
                                                    class="fas fa-graduation-cap upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="edu_doc_indigency" → DB: aed_cert_indigency -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Certificate of
                                            Indigency <span class="copy-badge">1 orig + 2 copies</span></div>
                                        <label class="upload-zone" id="uz-edu-indigency">
                                            <input type="file" name="edu_doc_indigency[]" multiple required
                                                onchange="fileSelected(this,'uz-edu-indigency')">
                                            <div class="upload-content"><i class="fas fa-file-alt upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="edu_doc_residency" → DB: aed_cert_residency -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Certificate of
                                            Residency <span class="copy-badge">1 orig + 2 copies</span></div>
                                        <label class="upload-zone" id="uz-edu-residency">
                                            <input type="file" name="edu_doc_residency[]" multiple required
                                                onchange="fileSelected(this,'uz-edu-residency')">
                                            <div class="upload-content"><i class="fas fa-home upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="edu_doc_studentid" → DB: aed_student_id -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Student ID <span
                                                class="copy-badge">1 orig + 2 copies</span></div>
                                        <label class="upload-zone" id="uz-edu-studentid">
                                            <input type="file" name="edu_doc_studentid[]" multiple required
                                                onchange="fileSelected(this,'uz-edu-studentid')">
                                            <div class="upload-content"><i class="fas fa-id-card upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="edu_doc_claimantid" → DB: aed_claimant_id -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Claimant's Valid ID
                                            <span class="copy-badge">1 orig + 2 copies</span>
                                        </div>
                                        <label class="upload-zone" id="uz-edu-claimantid">
                                            <input type="file" name="edu_doc_claimantid[]" multiple required
                                                onchange="fileSelected(this,'uz-edu-claimantid')">
                                            <div class="upload-content"><i
                                                    class="fas fa-user-check upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <button type="button" onclick="switchSub('main')"
                                    class="text-[13px] font-medium text-slate-500 border border-slate-200 rounded-xl px-5 py-2.5 hover:border-navy-400 hover:text-navy-600 transition-all">←
                                    Back to Main Form</button>
                                <button type="button" onclick="saveComplete()"
                                    class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500 transition-all">Save
                                    & Complete ✓</button>
                            </div>
                        </div>
                    </div>

                    <!-- LIVELIHOOD -->
                    <div class="screen-panel" id="panel-livelihood">
                        <div class="max-w-3xl mx-auto space-y-5">
                            <div class="animate-fade-up">
                                <div class="flex items-center gap-2 mb-1"><span class="text-[12px] text-slate-400">AICS
                                        Livelihood Requirements</span></div>
                                <h1 class="text-xl font-serif text-navy-600">Livelihood Assistance — Requirements</h1>
                                <p class="text-[13px] text-slate-500 mt-1">Provide business details and upload all
                                    required documents for livelihood assistance.</p>
                            </div>
                            <div class="animate-fade-up-1 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                                <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                    <div
                                        class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                        1</div>
                                    <div>
                                        <h2 class="text-[14px] font-semibold text-navy-600">Business Information</h2>
                                    </div>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="grid grid-cols-3 gap-4">
                                        <!-- name="biz_name" → $_POST['biz_name'] → $aliv_business_name -->
                                        <div><label class="field-label req">Business Name</label><input type="text"
                                                class="field" name="biz_name" placeholder="Proposed business name">
                                        </div>
                                        <div>
                                            <label class="field-label req">Business Type</label>
                                            <!-- name="biz_type" → $_POST['biz_type'] → $aliv_business_type -->
                                            <select class="field" name="biz_type">
                                                <option value="">Select type</option>
                                                <option>Sari-sari Store</option>
                                                <option>Rice Retailing</option>
                                                <option>Frozen Goods</option>
                                                <option>Food Vending / Catering</option>
                                                <option>Farming / Gardening</option>
                                                <option>Livestock Raising</option>
                                                <option>Handicrafts</option>
                                                <option>Other</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="field-label req">Start-up Cost (₱)</label>
                                            <div class="relative">
                                                <span
                                                    class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-medium text-[13px]">₱</span>
                                                <!-- name="biz_cost" → $_POST['biz_cost'] → $aliv_start_up_cost -->
                                                <input type="number" min="0" class="field pl-7" name="biz_cost"
                                                    placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div><label class="field-label">Business Location</label><input type="text" name="biz_location"
                                                class="field" placeholder="Street, barangay, or stall location"></div>
                                        <div><label class="field-label">Target Start Date</label><input type="date" name="biz_start_date"
                                                class="field"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                                <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                    <div
                                        class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                        2</div>
                                    <div>
                                        <h2 class="text-[14px] font-semibold text-navy-600">Required Documents</h2>
                                    </div>
                                </div>
                                <div class="p-6 grid grid-cols-2 gap-4">
                                    <!-- name="liv_doc_intent" → DB: aliv_letter_intent -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Letter of Intent
                                            <span class="copy-badge">1 orig + 2 copies</span>
                                        </div>
                                        <label class="upload-zone" id="uz-liv-intent">
                                            <input type="file" name="liv_doc_intent[]" multiple required
                                                onchange="fileSelected(this,'uz-liv-intent')">
                                            <div class="upload-content"><i class="fas fa-pen-alt upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="liv_doc_proposal" → DB: aliv_livelihood_proposal -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Business Proposal
                                            <span class="copy-badge">1 orig + 2 copies</span>
                                        </div>
                                        <label class="upload-zone" id="uz-liv-proposal">
                                            <input type="file" name="liv_doc_proposal[]" multiple required
                                                onchange="fileSelected(this,'uz-liv-proposal')">
                                            <div class="upload-content"><i
                                                    class="fas fa-chart-pie upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="liv_doc_validid" → DB: aliv_valid_id -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Valid ID <span
                                                class="copy-badge">1 orig + 2 copies</span></div>
                                        <label class="upload-zone" id="uz-liv-id">
                                            <input type="file" name="liv_doc_validid[]" multiple required
                                                onchange="fileSelected(this,'uz-liv-id')">
                                            <div class="upload-content"><i class="fas fa-id-card upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="liv_doc_indigency" → DB: aliv_cert_indigency -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Certificate of
                                            Indigency <span class="copy-badge">1 orig + 2 copies</span></div>
                                        <label class="upload-zone" id="uz-liv-indigency">
                                            <input type="file" name="liv_doc_indigency[]" multiple required
                                                onchange="fileSelected(this,'uz-liv-indigency')">
                                            <div class="upload-content"><i class="fas fa-file-alt upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="liv_doc_residency" → DB: aliv_cert_residency -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Certificate of
                                            Residency <span class="copy-badge">1 orig + 2 copies</span></div>
                                        <label class="upload-zone" id="uz-liv-residency">
                                            <input type="file" name="liv_doc_residency[]" multiple required
                                                onchange="fileSelected(this,'uz-liv-residency')">
                                            <div class="upload-content"><i class="fas fa-home upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 3 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Training Certificate
                                            <span class="opt-badge">If completed</span>
                                        </div>
                                        <label class="upload-zone" id="uz-liv-training">
                                            <input type="file" name="liv_doc_training"
                                                onchange="fileSelected(this,'uz-liv-training')">
                                            <div class="upload-content"><i
                                                    class="fas fa-certificate upload-icon"></i><span
                                                    class="upload-title">Click to upload</span><span
                                                    class="upload-hint">PDF, JPG, PNG</span></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <button type="button" onclick="switchSub('main')"
                                    class="text-[13px] font-medium text-slate-500 border border-slate-200 rounded-xl px-5 py-2.5 hover:border-navy-400 hover:text-navy-600 transition-all">←
                                    Back to Main Form</button>
                                <button type="button" onclick="saveComplete()"
                                    class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500 transition-all">Save
                                    & Complete ✓</button>
                            </div>
                        </div>
                    </div>

                    <!-- BURIAL -->
                    <div class="screen-panel" id="panel-burial">
                        <div class="max-w-3xl mx-auto space-y-5">
                            <div class="animate-fade-up">
                                <div class="flex items-center gap-2 mb-1"><span class="text-[12px] text-navy-400">AICS
                                        Burial Requirements</span></div>
                                <h1 class="text-xl font-serif text-navy-600">Burial Assistance — Requirements</h1>
                                <p class="text-[13px] text-navy-500 mt-1">Documents for burial assistance require
                                    <strong>1 original + 1 photocopy</strong> only (not 2).
                                </p>
                            </div>
                            <div
                                class="animate-fade-up-1 bg-navy-50 border border-navy-200 rounded-xl px-4 py-3 flex items-start gap-3">
                                <i class="fas fa-info-circle text-navy-400 text-lg mt-0.5"></i>
                                <p class="text-[12px] text-navy-700">Burial assistance has a <strong
                                        class="font-semibold">reduced copy requirement</strong> — only <strong>1
                                        original + 1 photocopy</strong> is needed for each document.</p>
                            </div>
                            <div class="animate-fade-up-2 bg-white rounded-2xl border border-navy-200 overflow-hidden">
                                <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                    <div
                                        class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                        1</div>
                                    <div>
                                        <h2 class="text-[14px] font-semibold text-navy-600">Deceased Information</h2>
                                    </div>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="grid grid-cols-3 gap-4">
                                        <div><label class="field-label req">Name of Deceased</label><input type="text" name="deceased_name"
                                                class="field" placeholder="Full legal name"></div>
                                        <div><label class="field-label req">Date of Death</label><input type="date" name="date_of_death"
                                                class="field"></div>
                                        <div><label class="field-label req">Relationship to Claimant</label><select name="relationship_to_claimant"
                                                class="field">
                                                <option value="">Select</option>
                                                <option>Spouse</option>
                                                <option>Parent</option>
                                                <option>Child</option>
                                                <option>Sibling</option>
                                                <option>Grandparent</option>
                                                <option>Other</option>
                                            </select></div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div><label class="field-label">Funeral Home / Parlor</label><input type="text"
                                                name="funeral_home" class="field" placeholder="Name of funeral establishment"></div>
                                        <div><label class="field-label">Funeral Contract Amount (₱)</label>
                                            <div class="relative"><span
                                                    class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-medium text-[13px]">₱</span><input
                                                    type="number" name="funeral_cost" min="0" step="0.01" class="field pl-7" placeholder="0.00"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="animate-fade-up-3 bg-white rounded-2xl border border-navy-200 overflow-hidden">
                                <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                                    <div
                                        class="w-7 h-7 rounded-full bg-navy-500 flex items-center justify-center text-white text-[11px] font-bold">
                                        2</div>
                                    <div>
                                        <h2 class="text-[14px] font-semibold text-navy-600">Required Documents</h2>
                                        <p class="text-[11px] text-slate-400">Note: Burial only requires 1 original + 1
                                            photocopy each</p>
                                    </div>
                                </div>
                                <div class="p-6 grid grid-cols-2 gap-4">
                                    <!-- name="bur_doc_death" → saveFile('bur_doc_death',...) → DB: ab_death_cert -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Death Certificate
                                            (PSA/LCR) <span class="copy-badge">1 orig + 1 copy</span></div>
                                        <label class="upload-zone" id="uz-bur-death">
                                            <input type="file" name="bur_doc_death[]" multiple required
                                                onchange="fileSelected(this,'uz-bur-death')">
                                            <div class="upload-content"><i class="fas fa-file-alt upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 2 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="bur_doc_contract" → DB: ab_funeral_contract -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Funeral Contract
                                            <span class="copy-badge">1 orig + 1 copy</span>
                                        </div>
                                        <label class="upload-zone" id="uz-bur-contract">
                                            <input type="file" name="bur_doc_contract[]" multiple required
                                                onchange="fileSelected(this,'uz-bur-contract')">
                                            <div class="upload-content"><i
                                                    class="fas fa-file-signature upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 2 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="bur_doc_validid" → DB: ab_valid_id -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Valid ID of Claimant
                                            <span class="copy-badge">1 orig + 1 copy</span>
                                        </div>
                                        <label class="upload-zone" id="uz-bur-id">
                                            <input type="file" name="bur_doc_validid[]" multiple required
                                                onchange="fileSelected(this,'uz-bur-id')">
                                            <div class="upload-content"><i class="fas fa-id-card upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 2 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="bur_doc_indigency" → DB: ab_brgy_indigency -->
                                    <div>
                                        <div class="field-label flex items-center flex-wrap gap-1">Barangay Indigency
                                            <span class="copy-badge">1 orig + 1 copy</span>
                                        </div>
                                        <label class="upload-zone" id="uz-bur-indigency">
                                            <input type="file" name="bur_doc_indigency[]" multiple required
                                                onchange="fileSelected(this,'uz-bur-indigency')">
                                            <div class="upload-content"><i class="fas fa-file-alt upload-icon"></i><span
                                                    class="upload-title">Click to upload (up to 2 files)</span><span
                                                    class="upload-hint">PDF, JPG, PNG — Required</span></div>
                                        </label>
                                    </div>
                                    <!-- name="bur_doc_mayors" → DB: ab_mayors_approval -->
                                    <div class="col-span-2">
                                        <div class="field-label flex items-center flex-wrap gap-1">Mayor's Approval
                                            <span class="opt-badge">LGU AICS only</span>
                                        </div>
                                        <label class="upload-zone" id="uz-bur-mayors">
                                            <input type="file" name="bur_doc_mayors"
                                                onchange="fileSelected(this,'uz-bur-mayors')">
                                            <div class="upload-content"><i class="fas fa-landmark upload-icon"></i><span
                                                    class="upload-title">Click to upload Mayor's Approval</span><span
                                                    class="upload-hint">PDF, JPG, PNG</span></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <button type="button" onclick="switchSub('main')"
                                    class="text-[13px] font-medium text-slate-500 border border-slate-200 rounded-xl px-5 py-2.5 hover:border-navy-400 hover:text-navy-600 transition-all">←
                                    Back to Main Form</button>
                                <button type="button" onclick="saveComplete()"
                                    class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-6 py-2.5 hover:bg-navy-500 transition-all">Save
                                    & Complete ✓</button>
                            </div>
                        </div>
                    </div>

                </form>

            </main>
        </div>

        <footer
            class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <div id="toast"
        class="fixed bottom-6 right-6 bg-navy-600 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 opacity-0 translate-y-4 pointer-events-none transition-all duration-300 z-50">
        <i class="fas fa-check-circle text-emerald-400 text-base"></i><span id="toastMsg">Saved successfully!</span>
    </div>

    <script>
        const EDU_YEAR_OK = <?= $edu_year_ok ? 'true' : 'false' ?>;
        const EDU_YEAR_COUNT = <?= $edu_y_count ?>;
        const EDU_QUARTER = <?= $edu_q['count'] ?>;
        const EDU_WINDOW_END = '<?= $edu_q['window_end'] ? $edu_q['window_end']->format('M j, Y') : '' ?>';
        const EDU_WINDOW_LABEL = '<?= ($edu_q['window_start'] && $edu_q['window_end']) ? '(' . $edu_q['window_start']->format('M j') . ' – ' . $edu_q['window_end']->format('M j, Y') . ')' : '' ?>';
        const BUDGET_OK = <?= $budget_ok ? 'true' : 'false' ?>;

        // (how many times this client has availed each type this year)
        const SUBTYPE_YEAR = {
            medical: <?= $med_y_count ?>,
            financial: <?= $fin_y_count ?>,
            burial: <?= $bur_y_count ?>,
            livelihood: <?= $liv_y_count ?>,
        };

        // (how many in the current 3-month window)
        const SUBTYPE_QUARTER = {
            medical: <?= $med_q['count'] ?>,
            financial: <?= $fin_q['count'] ?>,
            burial: <?= $bur_q['count'] ?>,
            livelihood: <?= $liv_q['count'] ?>,
        };

        // (when each subtype's current window resets)
        const SUBTYPE_WINDOW_END = {
            medical: '<?= $med_q['window_end'] ? $med_q['window_end']->format('M j, Y') : '' ?>',
            financial: '<?= $fin_q['window_end'] ? $fin_q['window_end']->format('M j, Y') : '' ?>',
            burial: '<?= $bur_q['window_end'] ? $bur_q['window_end']->format('M j, Y') : '' ?>',
            livelihood: '<?= $liv_q['window_end'] ? $liv_q['window_end']->format('M j, Y') : '' ?>',
        };

        // Per-subtype window labels (start – end) for the "Availments this quarter" row
        const SUBTYPE_WINDOW_LABEL = {
            medical: '<?= ($med_q['window_start'] && $med_q['window_end']) ? '(' . $med_q['window_start']->format('M j') . ' – ' . $med_q['window_end']->format('M j, Y') . ')' : '' ?>',
            financial: '<?= ($fin_q['window_start'] && $fin_q['window_end']) ? '(' . $fin_q['window_start']->format('M j') . ' – ' . $fin_q['window_end']->format('M j, Y') . ')' : '' ?>',
            burial: '<?= ($bur_q['window_start'] && $bur_q['window_end']) ? '(' . $bur_q['window_start']->format('M j') . ' – ' . $bur_q['window_end']->format('M j, Y') . ')' : '' ?>',
            livelihood: '<?= ($liv_q['window_start'] && $liv_q['window_end']) ? '(' . $liv_q['window_start']->format('M j') . ' – ' . $liv_q['window_end']->format('M j, Y') . ')' : '' ?>',
        };

        // Budget data for both programs
        const BUDGETS = {
            fbml: {
                label: 'AICS FBML',
                annual: <?= $annual ?>,
                spent: <?= $spent ?>,
                remaining: <?= $remaining ?>,
                pct: <?= $pct_used ?>,
                badgeCls: '<?= $badge_cls ?>',
                badgeIcon: '<?= $badge_icon ?>',
                badgeText: '<?= $badge_text ?>',
                barColor: '<?= $bar_color ?>',
                ok: <?= $budget_ok ? 'true' : 'false' ?>,
            },
            edu: {
                label: 'AICS Educational',
                annual: <?= $edu_annual ?>,
                spent: <?= $edu_spent ?>,
                remaining: <?= $edu_remaining ?>,
                pct: <?= $edu_pct_used ?>,
                badgeCls: '<?= $edu_badge_cls ?>',
                badgeIcon: '<?= $edu_badge_icon ?>',
                badgeText: '<?= $edu_badge_text ?>',
                barColor: '<?= $edu_bar_color ?>',
                ok: <?= $edu_budget_ok ? 'true' : 'false' ?>,
            },
        };

        function switchBudgetDisplay(key) {
            const b = BUDGETS[key];
            const fmt = n => '₱' + Math.round(n).toLocaleString();

            document.getElementById('budgetProgramLabel').textContent = b.label;
            document.getElementById('budgetAnnual').textContent = fmt(b.annual);
            document.getElementById('budgetSpent').textContent = fmt(b.spent);

            const remEl = document.getElementById('budgetRemaining');
            remEl.textContent = fmt(b.remaining);
            remEl.className = 'text-[18px] font-bold ' + (b.remaining <= 0 ? 'text-red-500' : 'text-emerald-600');

            const bar = document.getElementById('budgetBar');
            bar.className = 'budget-bar-fill h-2 rounded-full ' + b.barColor;
            bar.style.width = b.pct + '%';
            document.getElementById('budgetPct').textContent = b.pct + '% utilized';

            const budgetRow = document.getElementById('budgetSufficientText');
            budgetRow.textContent = b.ok ? '✓ ' + fmt(b.remaining) + ' available' : '✗ No budget remaining';
            budgetRow.className = 'text-[12px] font-semibold ' + (b.ok ? 'text-emerald-600' : 'text-red-500');
        }

        const subNames = {
            main: 'AICS Availment', medical: 'AICS — Medical', financial: 'AICS — Financial',
            educational: 'AICS — Educational', livelihood: 'AICS — Livelihood', burial: 'AICS — Burial'
        };

        function switchSub(id) {
            document.querySelectorAll('.screen-panel').forEach(p => p.classList.remove('active'));
            document.getElementById('panel-' + id).classList.add('active');
            document.querySelectorAll('.sub-nav').forEach(b => b.classList.remove('active'));
            const navEl = document.getElementById('nav-' + id);
            if (navEl) navEl.classList.add('active');
            document.getElementById('breadcrumbLast').textContent = subNames[id];
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        document.getElementById('proceedToSubtype')?.addEventListener('click', () => {
            const amountInput = document.getElementById('amountField');
            const amountVal = parseFloat(amountInput.value);
            if (!amountInput.value.trim() || isNaN(amountVal) || amountVal < 500 || amountVal > 5000) {
                showToast('Please enter a valid amount (₱500 – ₱5,000).');
                amountInput.focus();
                return;
            }
            const dateAppliedInput = document.getElementById('dateApplied');
            if (!dateAppliedInput.value) {
                showToast('Please select Date Applied.');
                dateAppliedInput.focus();
                return;
            }
            const todayStr = new Date().toISOString().split('T')[0];
            if (dateAppliedInput.value > todayStr) {
                showToast('Date Applied cannot be a future date.');
                dateAppliedInput.focus();
                return;
            }
            if (!currentSubtype) {
                showToast('Please select an assistance type first.');
                return;
            }

            if (currentSubtype === 'educational') {
                if (EDU_QUARTER >= 1) {
                    showToast('Quarter limit reached for educational. Select a different type or wait until the quarter resets.');
                    return;
                }
                if (!EDU_YEAR_OK) {
                    showToast('Educational year limit reached — max 2 per year.');
                    return;
                }
            } else {
                // Check THIS subtype's own quarter limit
                if ((SUBTYPE_QUARTER[currentSubtype] ?? 0) >= 1) {
                    showToast('Quarter limit reached for ' + currentSubtype + '. Select a different type or wait until the quarter resets.');
                    return;
                }
                // Check THIS subtype's own year limit
                if ((SUBTYPE_YEAR[currentSubtype] ?? 0) >= 4) {
                    showToast('Year limit reached for ' + currentSubtype + ' — max 4 per year.');
                    return;
                }
            }

            switchSub(currentSubtype);
        });

        let currentSubtype = '';

        function selectType(card, sub) {
            document.querySelectorAll('.type-card').forEach(c => {
                c.classList.remove('active-card', 'border-navy-600', 'bg-navy-50', 'shadow-md');
                c.classList.add('border-slate-200');
            });
            card.classList.add('active-card', 'border-navy-600', 'bg-navy-50', 'shadow-md');
            card.classList.remove('border-slate-200');
            currentSubtype = sub;

            const isEdu = sub === 'educational';

            // Show/hide the right rows in the limit panel
            document.getElementById('row-quarter').classList.remove('hidden');
            document.getElementById('row-year').classList.remove('hidden');

            // Quarter row: show this type's own count and window (Educational uses the same 1-per-quarter pattern as FBML subtypes)
            const qCount = isEdu ? EDU_QUARTER : (SUBTYPE_QUARTER[sub] ?? 0);
            const qOk = qCount < 1;
            const qLabel = isEdu ? EDU_WINDOW_LABEL : (SUBTYPE_WINDOW_LABEL[sub] || '');
            const qEl = document.getElementById('quarter-status-text');
            qEl.className = 'text-[12px] font-semibold ' + (qOk ? 'text-emerald-600' : 'text-red-500');
            qEl.textContent = (qOk ? '✓' : '✗') + ' ' + qCount + ' of 1 — ' + (qOk ? 'eligible' : 'limit reached');
            document.getElementById('quarter-window-label').textContent = qLabel;

            // Year row: show this type's own count (Educational caps at 2/year, FBML subtypes cap at 4/year)
            const yLimit = isEdu ? 2 : 4;
            const yCount = isEdu ? EDU_YEAR_COUNT : (SUBTYPE_YEAR[sub] ?? 0);
            const yLeft = Math.max(0, yLimit - yCount);
            const yOk = yCount < yLimit;
            const yEl = document.getElementById('fbml-year-text');
            yEl.className = 'text-[12px] font-semibold ' + (yOk ? 'text-emerald-600' : 'text-red-500');
            yEl.textContent = (yOk ? '✓' : '✗') + ' ' + yCount + ' of ' + yLimit + ' — ' + (yOk ? yLeft + ' remaining' : 'limit reached');

            // Block banner: show only if THIS type is at its quarter limit
            const banner = document.getElementById('quarterBlockBanner');
            const msgEl = document.getElementById('quarterBlockMsg');
            if (!qOk) {
                const resetDate = isEdu ? EDU_WINDOW_END : (SUBTYPE_WINDOW_END[sub] || '');
                msgEl.innerHTML = '<?= htmlspecialchars($client_name) ?> has already availed ' + sub + ' assistance once this quarter.'
                    + (resetDate ? ' This window resets on <strong>' + resetDate + '</strong>.' : '')
                    + ' Select a different type or wait until the quarter resets.';
                banner.classList.remove('hidden');
            } else {
                banner.classList.add('hidden');
            }

            switchBudgetDisplay(isEdu ? 'edu' : 'fbml');
        }

        function checkAmount(input) {
            const val = parseFloat(input.value);
            const el = document.getElementById('amountCheck').querySelector('span');
            if (!val || isNaN(val)) {
                el.innerHTML = '— Enter amount above';
                el.className = 'text-[12px] font-semibold text-slate-400';
            } else if (val < 500) {
                el.innerHTML = '<i class="fas fa-times-circle text-red-500 mr-1"></i> Below minimum ₱500';
                el.className = 'text-[12px] font-semibold text-red-500';
            } else if (val > 5000) {
                el.innerHTML = '<i class="fas fa-times-circle text-red-500 mr-1"></i> Exceeds maximum ₱5,000';
                el.className = 'text-[12px] font-semibold text-red-500';
            } else {
                el.innerHTML = `<i class="fas fa-check-circle text-emerald-600 mr-1"></i> ₱${val.toLocaleString()} — within range`;
                el.className = 'text-[12px] font-semibold text-emerald-600';
            }
        }

        // Shows/hides patient fields in Medical panel.
        let patientOn = false;
        function togglePatient() {
            patientOn = !patientOn;
            const track = document.getElementById('patientToggleTrack');
            const thumb = document.getElementById('patientToggleThumb');
            const fields = document.getElementById('patientFields');
            track.classList.toggle('bg-navy-600', patientOn);
            track.classList.toggle('bg-slate-200', !patientOn);
            thumb.style.transform = patientOn ? 'translateX(16px)' : '';
            fields.classList.toggle('hidden', !patientOn);
            const hidden = document.getElementById('patientDifferent');
            if (hidden) hidden.value = patientOn ? '1' : '0';
        }

        function toggleEduPurposeOther(value) {
            const other = document.getElementById('eduPurposeOther');
            if (value === 'Other') {
                other.classList.remove('hidden');
            } else {
                other.classList.add('hidden');
                other.value = '';
            }
        }

        // When a file is chosen, shows the filename in the upload zone.
        function fileSelected(input, zoneId) {
            if (!input.files || !input.files[0]) return;
            const zone = document.getElementById(zoneId);
            const count = input.files.length;
            const name = count === 1 ? input.files[0].name : `${count} files selected`;
            zone.classList.add('has-file');
            zone.classList.remove('border-red-400', 'bg-red-50');
            zone.querySelector('.upload-content').innerHTML =
                `<i class="fas fa-check-circle upload-icon text-navy-600"></i><span class="upload-title">${name}</span><span class="upload-hint">${count > 1 ? count + ' files uploaded' : 'File uploaded'}</span>`;
        }

        function showToast(msg) {
            document.getElementById('toastMsg').textContent = msg;
            const t = document.getElementById('toast');
            t.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
            t.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => {
                t.classList.add('opacity-0', 'translate-y-4');
                t.classList.remove('opacity-100', 'translate-y-0');
            }, 3000);
        }

        function saveComplete() {
            // If Education panel has "Other" purpose selected, the free-text field is required.
            const eduPurposeSelect = document.getElementById('eduPurposeSelect');
            if (currentSubtype === 'educational' && eduPurposeSelect && eduPurposeSelect.value === 'Other') {
                const other = document.getElementById('eduPurposeOther');
                if (!other.value.trim()) {
                    showToast('Please specify the purpose.');
                    other.focus();
                    return;
                }
            }

            // Validate that all required file inputs in the active panel have at least one file - one of the panel's req.
            const activePanel = document.querySelector('.screen-panel.active');
            const requiredFiles = activePanel ? activePanel.querySelectorAll('input[type=file][required]') : [];
            const missing = [];
            requiredFiles.forEach(inp => {
                if (!inp.files || inp.files.length === 0) {
                    // Get the label text for this input
                    const labelEl = inp.closest('div')?.previousElementSibling || inp.closest('div');
                    const labelText = labelEl?.querySelector('.field-label')?.textContent?.trim() || inp.name;
                    missing.push(labelText.replace(/\s*\d+ orig.*$/, '').trim());
                    inp.closest('label')?.classList.add('border-red-400', 'bg-red-50');
                }
            });
            if (missing.length > 0) {
                showToast('Please upload all required documents before submitting.');
                return;
            }

            document.getElementById('hiddenType').value = currentSubtype;
            document.getElementById('hiddenAmount').value = document.getElementById('amountField').value;
            document.getElementById('hiddenApplied').value = document.getElementById('dateApplied').value;
            document.getElementById('hiddenRemarks').value = document.getElementById('remarksInput')?.value || '';
            document.getElementById('aicsForm').submit();
        }

        requestAnimationFrame(() => setTimeout(() => {
            switchBudgetDisplay('fbml');
        }, 400));
    </script>
</body>
