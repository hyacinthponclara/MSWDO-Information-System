<?php
require 'auth.php';
requireRole(['Admin','Staff']);

require 'db_connect.php';

$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cl_lastname      = trim($_POST['cl_lastname']      ?? '');
    $cl_firstname     = trim($_POST['cl_firstname']     ?? '');
    $cl_middlename    = trim($_POST['cl_middlename']    ?? '');
    $cl_suffix        = trim($_POST['cl_suffix']        ?? '');
    $cl_birthdate     = trim($_POST['cl_birthdate']     ?? '');
    $cl_sex           = trim($_POST['cl_sex']           ?? '');
    $cl_civilstatus   = trim($_POST['cl_civilstatus']   ?? '');
    $cl_contact_num   = trim($_POST['cl_contact_num']   ?? '');
    $cl_street        = trim($_POST['cl_street']        ?? '');
    $brgy_id          = intval($_POST['brgy_id']        ?? 0);
    $cl_occupation    = trim($_POST['cl_occupation']    ?? '');
    $cl_monthly_income= floatval($_POST['cl_monthly_income'] ?? 0);
    $cl_educ_attain   = trim($_POST['cl_educ_attain']   ?? '');

    $sectors          = $_POST['sectors'] ?? [];
    $cl_is_4ps        = in_array('4ps',        $sectors) ? 1 : 0;
    $cl_is_pwd        = in_array('pwd',        $sectors) ? 1 : 0;
    $cl_is_senior     = in_array('senior',     $sectors) ? 1 : 0;
    $cl_is_soloparent = in_array('soloparent', $sectors) ? 1 : 0;
    $cl_is_indigent   = in_array('indigent',   $sectors) ? 1 : 0;

    // --- validation ---
    if (empty($cl_lastname))    $errors[] = 'Last name is required.';
    if (empty($cl_firstname))   $errors[] = 'First name is required.';
    if (empty($cl_birthdate))   $errors[] = 'Birthdate is required.';
    if (empty($cl_sex))         $errors[] = 'Please select sex.';
    if (empty($cl_civilstatus)) $errors[] = 'Civil status is required.';
    if ($brgy_id === 0)         $errors[] = 'Barangay is required.';

    if (!empty($cl_contact_num) && !preg_match('/^(\+?63|0)9\d{9}$/', $cl_contact_num)) {
        $errors[] = 'Contact number must be a valid format (e.g., 09XX, 639XX, or +639XX).';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $sql = "INSERT INTO client (
                        brgy_id, user_id,
                        cl_lastname, cl_firstname, cl_middlename, cl_suffix,
                        cl_birthdate, cl_sex, cl_civilstatus,
                        cl_occupation, cl_monthly_income, cl_educ_attain,
                        cl_contact_num,
                        cl_is_4ps, cl_is_pwd, cl_is_senior, cl_is_soloparent, cl_is_indigent,
                        cl_region, cl_province, cl_city_municipality, cl_street,
                        cl_date_registered
                    ) VALUES (
                        :brgy_id, :user_id,
                        :cl_lastname, :cl_firstname, :cl_middlename, :cl_suffix,
                        :cl_birthdate, :cl_sex, :cl_civilstatus,
                        :cl_occupation, :cl_monthly_income, :cl_educ_attain,
                        :cl_contact_num,
                        :cl_is_4ps, :cl_is_pwd, :cl_is_senior, :cl_is_soloparent, :cl_is_indigent,
                        'VI', 'Negros Occidental', 'San Enrique', :cl_street,
                        NOW()
                    )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':brgy_id'          => $brgy_id,
                ':user_id'          => $_SESSION['user_id'],
                ':cl_lastname'      => $cl_lastname,
                ':cl_firstname'     => $cl_firstname,
                ':cl_middlename'    => $cl_middlename ?: null,
                ':cl_suffix'        => $cl_suffix     ?: null,
                ':cl_birthdate'     => $cl_birthdate,
                ':cl_sex'           => $cl_sex,
                ':cl_civilstatus'   => $cl_civilstatus,
                ':cl_occupation'    => $cl_occupation    ?: null,
                ':cl_monthly_income'=> $cl_monthly_income,
                ':cl_educ_attain'   => $cl_educ_attain   ?: null,
                ':cl_contact_num'   => $cl_contact_num   ?: null,
                ':cl_is_4ps'        => $cl_is_4ps,
                ':cl_is_pwd'        => $cl_is_pwd,
                ':cl_is_senior'     => $cl_is_senior,
                ':cl_is_soloparent' => $cl_is_soloparent,
                ':cl_is_indigent'   => $cl_is_indigent,
                ':cl_street'        => $cl_street ?: null,
            ]);

            $new_client_id = $pdo->lastInsertId();

            $fam_names       = $_POST['fam_name']       ?? [];
            $fam_relations   = $_POST['fam_relation']   ?? [];
            $fam_ages        = $_POST['fam_age']        ?? [];
            $fam_occupations = $_POST['fam_occupation'] ?? [];
            $fam_incomes     = $_POST['fam_income']     ?? [];

            // build the family array, skip empty rows
            $familyData = [];
            foreach ($fam_names as $i => $fname) {
                if (empty(trim($fname))) continue;
                $familyData[] = [
                    'name'       => trim($fname),
                    'relation'   => trim($fam_relations[$i]   ?? ''),
                    'age'        => intval($fam_ages[$i]       ?? 0),
                    'occupation' => trim($fam_occupations[$i]  ?? ''),
                    'income'     => floatval($fam_incomes[$i]  ?? 0),
                ];
            }

            // only insert a case study record if there are family members to save
            // this avoids an empty record cluttering the CASE_STUDY table
            if (!empty($familyData)) {
                $csSql = "INSERT INTO case_study (
                                client_id, user_id, interview_date,
                                family_composition_json, problem_presented
                            ) VALUES (
                                :client_id, :user_id, CURDATE(),
                                :family_json, 'Initial registration'
                            )";
                $csStmt = $pdo->prepare($csSql);
                $csStmt->execute([
                    ':client_id'   => $new_client_id,
                    ':user_id'     => $_SESSION['user_id'],
                    ':family_json' => json_encode($familyData),
                ]);
            }

            $pdo->commit();
            $success = true;

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Registration error: ' . $e->getMessage());
            $errors[] = 'Something went wrong while saving. Please try again.';
        }
    }
}

// fetch barangays from the BARANGAY table for the dropdown
try {
    $barangays = $pdo->query("SELECT barangay_id, barangay_name FROM barangay ORDER BY barangay_name")->fetchAll();
} catch (PDOException $e) {
    $barangays = [];
    error_log('Could not load barangays: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Client Registration – MSWDO San Enrique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans:  ['DM Sans', 'sans-serif'],
            serif: ['DM Serif Display', 'serif']
          },
          colors: {
            navy: {
              DEFAULT: '#0B2545',
              50:  '#E8EDF5',
              100: '#C5D1E6',
              400: '#3A5F93',
              500: '#163566',
              600: '#0B2545',
              700: '#091D38'
            },
            gold:   { DEFAULT: '#C49A2A', 400: '#C49A2A' },
            slate2: '#F4F7FC',
          }
        }
      }
    }
  </script>

  <style>
    body { font-family: 'DM Sans', sans-serif; }

    .sidebar-item { transition: all .15s ease; }
    .sidebar-item:hover {
      background: rgba(255,255,255,.07);
      color: rgba(255,255,255,.95);
    }
    .sidebar-item.active {
      background: rgba(29,111,164,.28);
      border-left-color: #C49A2A;
      color: #fff;
    }

    .field {
      display: block; width: 100%;
      border-radius: 0.75rem;
      border: 1px solid #94a3b8;
      background: #F8FAFC;
      padding: 10px 14px;
      font-size: 13px; color: #1e293b;
      outline: none;
      transition: all .2s ease;
    }
    .field:focus {
      border-color: #0B2545;
      background: white;
    }
    /* highlight fields with errors */
    .field.is-error { border-color: #EF4444; background: #FFF5F5; }
    .field::placeholder { color: #9CA3AF; }

    select.field {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236B7280' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      background-size: 16px;
      appearance: none;
    }

    .field-label {
      display: block;
      font-size: 11px; font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #64748b;
      margin-bottom: 6px;
    }
    .required-dot::after { content: '*'; color: #EF4444; margin-left: 2px; }

    .check-card { transition: all .15s ease; }
    .check-card:has(input:checked) {
      border-color: #0B2545;
      background: #E8EDF5;
    }
    .check-card:hover { border-color: #9AAECE; }

    .fam-input {
      width: 100%; border: none;
      background: transparent;
      font-size: 12px;
      font-family: 'DM Sans', sans-serif;
      color: #1e293b; outline: none;
      padding: 2px 4px;
    }
    .fam-input:focus { border-bottom: 1.5px solid #3A5F93; }
    .fam-row:hover { background: #F8FAFC; }

    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 2px; }
  </style>
</head>

<body class="bg-slate2 min-h-screen flex">

  <?php include 'sidebar.php'; ?>

  <div class="ml-64 flex-1 flex flex-col min-h-screen">

    <header class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20">
      <div class="flex items-center gap-2 text-[13px]">
        <a href="clients.php" class="text-slate-400 hover:text-navy-600 transition-colors">Clients</a>
        <span class="text-slate-300">/</span>
        <span class="text-navy-600 font-semibold">New Registration</span>
      </div>
      <div class="flex items-center gap-2">
        <a href="clientslist.php"
          class="text-[12px] font-medium text-slate-600 border border-slate-200 rounded-lg px-4 py-1.5 hover:border-navy-400 hover:text-navy-600 transition-all">
          Cancel
        </a>
      </div>
    </header>

    <main class="flex-1 p-6 max-w-4xl w-full mx-auto">

      <div class="mb-6">
        <h1 class="text-xl font-serif text-navy-600">New Client Registration</h1>
        <p class="text-[13px] text-slate-500 mt-1">Complete all required fields.</p>
      </div>

      <?php if ($success): ?>
        <div class="mb-5 bg-emerald-50 border border-emerald-200 rounded-2xl px-5 py-4 flex items-center gap-3">
          <span class="text-emerald-500 text-lg">✓</span>
          <div>
            <p class="text-[13px] font-semibold text-emerald-800">Client registered successfully!</p>
            <p class="text-[11px] text-emerald-600 mt-0.5">Redirecting to client list...</p>
          </div>
        </div>
        <script>
          setTimeout(() => { window.location.href = 'clientslist.php'; }, 2000);
        </script>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="mb-5 bg-red-50 border border-red-200 rounded-2xl px-5 py-4">
          <p class="text-[13px] font-semibold text-red-700 mb-2">Please fix the following:</p>
          <ul class="space-y-1">
            <?php foreach ($errors as $err): ?>
              <li class="text-[12px] text-red-600 flex items-center gap-2">
                <span>•</span> <?= htmlspecialchars($err) ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="" id="clientForm">
        <div class="space-y-6">

          <!-- 1. PERSONAL INFORMATION -->
          <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-up">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
              <div class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0">1</div>
              <div>
                <h2 class="text-[14px] font-semibold text-navy-600">Personal Information</h2>
                <p class="text-[11px] text-slate-400">Basic identifying details of the client</p>
              </div>
            </div>
            <div class="p-6 space-y-5">

              <div class="grid grid-cols-12 gap-4">
                <div class="col-span-4">
                  <label class="field-label required-dot">Last Name</label>
                  <input type="text" name="cl_lastname"
                    class="field <?= in_array('Last name is required.', $errors) ? 'is-error' : '' ?>"
                    placeholder="e.g. Mana-ay"
                    value="<?= htmlspecialchars($_POST['cl_lastname'] ?? '') ?>">
                </div>
                <div class="col-span-4">
                  <label class="field-label required-dot">First Name</label>
                  <input type="text" name="cl_firstname"
                    class="field <?= in_array('First name is required.', $errors) ? 'is-error' : '' ?>"
                    placeholder="e.g. Alexanne"
                    value="<?= htmlspecialchars($_POST['cl_firstname'] ?? '') ?>">
                </div>
                <div class="col-span-3">
                  <label class="field-label">Middle Name</label>
                  <input type="text" name="cl_middlename" class="field" placeholder="Optional"
                    value="<?= htmlspecialchars($_POST['cl_middlename'] ?? '') ?>">
                </div>
                <div class="col-span-1">
                  <label class="field-label">Suffix</label>
                  <select name="cl_suffix" class="field">
                    <option value="">—</option>
                    <?php foreach (['Sr.','Jr.','I','II','III','IV'] as $s): ?>
                      <option <?= ($_POST['cl_suffix'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-4 gap-4">
                <div>
                  <label class="field-label required-dot">Birthdate</label>
                  <!-- cl_age is GENERATED in the DB so we don't need to send it -->
                  <input type="date" id="birthdate" name="cl_birthdate"
                    class="field <?= in_array('Birthdate is required.', $errors) ? 'is-error' : '' ?>"
                    oninput="computeAge()"
                    max="<?= date('Y-m-d') ?>"
                    value="<?= htmlspecialchars($_POST['cl_birthdate'] ?? '') ?>">
                  <p class="text-[10px] text-slate-400 mt-1">Click to select date</p>
                </div>
                <div>
                  <label class="field-label">Age</label>
                  <div class="relative">
                    <!-- age is auto-computed by MySQL (GENERATED column) so this is just for display -->
                    <input type="text" id="ageField" class="field bg-slate-100 cursor-not-allowed"
                      readonly placeholder="Auto">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400">yrs</span>
                  </div>
                </div>
                <div>
                  <label class="field-label required-dot">Sex</label>
                  <div class="flex gap-2 mt-0.5">
                    <?php foreach (['Male','Female'] as $sexOpt): ?>
                      <label class="flex-1 flex items-center justify-center gap-2 border border-slate-200 rounded-xl py-2.5 cursor-pointer hover:border-navy-400 transition-all text-[12px] font-medium text-slate-600 has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50 has-[:checked]:text-navy-700">
                        <!-- name="cl_sex" matches CLIENT.cl_sex ENUM -->
                        <input type="radio" name="cl_sex" value="<?= $sexOpt ?>" class="hidden"
                          <?= ($_POST['cl_sex'] ?? '') === $sexOpt ? 'checked' : '' ?>>
                        <i class="fas fa-<?= $sexOpt === 'Male' ? 'mars' : 'venus' ?>"></i> <?= $sexOpt ?>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </div>
                <div>
                  <label class="field-label required-dot">Civil Status</label>
                  <!-- matches CLIENT.cl_civilstatus ENUM values exactly -->
                  <select name="cl_civilstatus" class="field <?= in_array('Civil status is required.', $errors) ? 'is-error' : '' ?>">
                    <option value="">Select</option>
                    <?php foreach (['Single','Married','Widowed','Separated','Solo Parent'] as $cs): ?>
                      <option <?= ($_POST['cl_civilstatus'] ?? '') === $cs ? 'selected' : '' ?>><?= $cs ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="field-label">Contact Number</label>
                  <input type="text" name="cl_contact_num" maxlength="13" minlength="11"
                    class="field <?= in_array('Contact number must be a valid format (e.g., 09XX, 639XX, or +639XX).', $errors) ? 'is-error' : '' ?>"
                    placeholder="09XX-XXX-XXXX"
                    value="<?= htmlspecialchars($_POST['cl_contact_num'] ?? '') ?>">
                  <p class="text-[10px] text-slate-400 mt-1">Format: Accepts 09XX, 639XX, or +639XX</p>
                </div>
              </div>

            </div>
          </div>

          <!-- 2. ADDRESS -->
          <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-up">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
              <div class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0">2</div>
              <div>
                <h2 class="text-[14px] font-semibold text-navy-600">Address</h2>
                <p class="text-[11px] text-slate-400">Current residential address</p>
              </div>
            </div>
            <div class="p-6 space-y-5">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="field-label">Purok/Street</label>
                  <!-- CLIENT.cl_street -->
                  <input type="text" name="cl_street" class="field" placeholder="e.g. 123 Rizal St."
                    value="<?= htmlspecialchars($_POST['cl_street'] ?? '') ?>">
                </div>
                <div>
                  <label class="field-label required-dot">Barangay</label>
                  <select name="brgy_id" class="field <?= in_array('Barangay is required.', $errors) ? 'is-error' : '' ?>">
                    <option value="">Select Barangay</option>
                    <?php foreach ($barangays as $brgy): ?>
                      <option value="<?= $brgy['barangay_id'] ?>"
                        <?= ($_POST['brgy_id'] ?? '') == $brgy['barangay_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($brgy['barangay_name']) ?>
                      </option>
                    <?php endforeach; ?>
                    <?php if (empty($barangays)): ?>
                      <option disabled>No barangays found in database</option>
                    <?php endif; ?>
                  </select>
                </div>
              </div>

              <!-- these 3 are hardcoded in the INSERT - San Enrique only serves its own municipality -->
              <div class="grid grid-cols-3 gap-4">
                <div>
                  <label class="field-label">Municipality</label>
                  <input type="text" class="field bg-slate-100 cursor-not-allowed" value="San Enrique" readonly>
                </div>
                <div>
                  <label class="field-label">Province</label>
                  <input type="text" class="field bg-slate-100 cursor-not-allowed" value="Negros Occidental" readonly>
                </div>
                <div>
                  <label class="field-label">Region</label>
                  <input type="text" class="field bg-slate-100 cursor-not-allowed" value="NIR" readonly>
                </div>
              </div>

              <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-start gap-3">
                <i class="fas fa-info-circle text-blue-500"></i>
                <p class="text-[12px] text-blue-700">MSWDO services are limited to residents of San Enrique, Negros Occidental.</p>
              </div>
            </div>
          </div>

          <!-- 3. SOCIO-ECONOMIC -->
          <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-up">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
              <div class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0">3</div>
              <div>
                <h2 class="text-[14px] font-semibold text-navy-600">Socio-Economic Information</h2>
                <p class="text-[11px] text-slate-400">Used for indigency assessment and program eligibility</p>
              </div>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-3 gap-4">
                <div>
                  <label class="field-label">Occupation</label>
                  <!-- CLIENT.cl_occupation -->
                  <input type="text" name="cl_occupation" class="field" placeholder="e.g. Farmer, Vendor"
                    value="<?= htmlspecialchars($_POST['cl_occupation'] ?? '') ?>">
                </div>
                <div>
                  <label class="field-label">Monthly Income (₱)</label>
                  <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[13px] font-medium">₱</span>
                    <!-- CLIENT.cl_monthly_income -->
                    <input type="number" min="0" name="cl_monthly_income" class="field pl-7" placeholder="0.00"
                      value="<?= htmlspecialchars($_POST['cl_monthly_income'] ?? '') ?>">
                  </div>
                </div>
                <div>
                  <label class="field-label">Educational Attainment</label>
                  <!-- CLIENT.cl_educ_attain - VARCHAR so any value is fine -->
                  <select name="cl_educ_attain" class="field">
                    <option value="">Select</option>
                    <?php foreach (['No Formal Education','Elementary','High School','Vocational','College','Post Graduate'] as $edu): ?>
                      <option <?= ($_POST['cl_educ_attain'] ?? '') === $edu ? 'selected' : '' ?>><?= $edu ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- 4. SECTORAL CLASSIFICATION -->
          <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-up">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
              <div class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0">4</div>
              <div>
                <h2 class="text-[14px] font-semibold text-navy-600">Sectoral Classification</h2>
                <p class="text-[11px] text-slate-400">Select all that apply — determines program eligibility badges</p>
              </div>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <?php
                $sectorList = [
                  ['4ps',        'fa-home',         '4Ps Member',     'Pantawid Pamilyang Pilipino'],
                  ['pwd',        'fa-wheelchair',   'PWD',            'Person with Disability'],
                  ['senior',     'fa-user-friends', 'Senior Citizen', '60 years old and above'],
                  ['soloparent', 'fa-user',         'Solo Parent',    'RA 8972 qualified'],
                  ['indigent',   'fa-list',         'Indigent',       'DSWD/DOH assessment'],
                ];
                $checkedSectors = $_POST['sectors'] ?? [];
                foreach ($sectorList as [$val, $icon, $label, $sub]):
                ?>
                  <label class="check-card flex items-center gap-3 border-2 border-slate-200 rounded-2xl p-4 cursor-pointer">
                    <input type="checkbox" name="sectors[]" value="<?= $val ?>"
                      class="w-4 h-4 accent-navy-600 flex-shrink-0"
                      <?= in_array($val, $checkedSectors) ? 'checked' : '' ?>>
                    <div>
                      <p class="text-[13px] font-semibold text-slate-700"><i class="fas <?= $icon ?>"></i> <?= $label ?></p>
                      <p class="text-[11px] text-slate-400 mt-0.5"><?= $sub ?></p>
                    </div>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- 5. FAMILY COMPOSITION -->
          <!-- stored as JSON in CASE_STUDY.family_composition_json -->
          <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-fade-up">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
              <div class="w-7 h-7 rounded-full bg-navy-600 flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0">5</div>
              <div>
                <h2 class="text-[14px] font-semibold text-navy-600">Family Composition</h2>
                <p class="text-[11px] text-slate-400">All household members including the client</p>
              </div>
            </div>
            <div class="p-6">
              <div class="rounded-xl border border-slate-200 overflow-hidden mb-3">
                <table class="w-full text-[12px]">
                  <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                      <th class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-8">#</th>
                      <th class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Full Name</th>
                      <th class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-32">Relationship</th>
                      <th class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-16">Age</th>
                      <th class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Occupation</th>
                      <th class="text-left px-3 py-2.5 text-[10px] uppercase tracking-wider text-slate-400 font-semibold w-28">Monthly Income (₱)</th>
                      <th class="w-10"></th>
                    </tr>
                  </thead>
                  <tbody id="famBody">
                    <?php
                    $famNames = $_POST['fam_name'] ?? [''];
                    foreach ($famNames as $i => $fn):
                    ?>
                    <tr class="fam-row border-b border-slate-100">
                      <td class="px-3 py-2.5 text-slate-400 font-medium"><?= $i + 1 ?></td>
                      <td class="px-3 py-2.5"><input class="fam-input" type="text" name="fam_name[]" placeholder="Full name" value="<?= htmlspecialchars($fn) ?>"></td>
                      <td class="px-3 py-2.5"><input class="fam-input" type="text" name="fam_relation[]" placeholder="e.g. Spouse" value="<?= htmlspecialchars($_POST['fam_relation'][$i] ?? '') ?>"></td>
                      <td class="px-3 py-2.5"><input class="fam-input" type="number" name="fam_age[]" placeholder="Age" min="0" value="<?= htmlspecialchars($_POST['fam_age'][$i] ?? '') ?>"></td>
                      <td class="px-3 py-2.5"><input class="fam-input" type="text" name="fam_occupation[]" placeholder="Occupation" value="<?= htmlspecialchars($_POST['fam_occupation'][$i] ?? '') ?>"></td>
                      <td class="px-3 py-2.5"><input class="fam-input" type="number" name="fam_income[]" min="0" step="0.01" placeholder="0.00" value="<?= htmlspecialchars($_POST['fam_income'][$i] ?? '') ?>"></td>
                      <td class="px-3 py-2.5 text-center">
                        <button type="button" onclick="this.closest('tr').remove(); renumber()"
                          class="text-slate-300 hover:text-red-400 transition-colors text-sm">✕</button>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <button type="button" onclick="addFamRow()"
                class="flex items-center gap-2 text-[12px] font-medium text-navy-600 border-2 border-dashed border-navy-200 rounded-xl px-4 py-2.5 hover:border-navy-400 hover:bg-navy-50 transition-all w-full justify-center">
                + Add Member
              </button>
            </div>
          </div>

          <div class="flex items-center justify-end pt-2">
            <button type="submit"
              class="text-[13px] font-semibold text-white bg-navy-600 rounded-xl px-8 py-2.5 hover:bg-navy-500 transition-all">
              Save Client
            </button>
          </div>

        </div>
      </form>

    </main>

    <footer class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
      <span>MSWDO San Enrique Information System</span>
    </footer>
  </div>

  <?php if ($success): ?>
  <div id="toast"
    class="fixed bottom-6 right-6 bg-navy-600 text-white text-[13px] font-medium px-5 py-3.5 rounded-2xl shadow-xl flex items-center gap-3 transition-all duration-300">
    <span class="text-emerald-400 text-base">✓</span>
    <span>Client saved successfully!</span>
  </div>
  <?php endif; ?>

  <script>
    // compute age from birthdate for display only
    function computeAge() {
      const dob = new Date(document.getElementById('birthdate').value);
      if (isNaN(dob)) return;
      const age = Math.floor((new Date() - dob) / (365.25 * 24 * 3600 * 1000));
      document.getElementById('ageField').value = age;
    }

    // run on load in case date was already set (page reload after validation error)
    window.addEventListener('load', computeAge);

    // start count from however many rows PHP already rendered
    let famCount = document.querySelectorAll('#famBody tr').length;

    function addFamRow() {
      famCount++;
      const tr = document.createElement('tr');
      tr.className = 'fam-row border-b border-slate-100';
      tr.innerHTML = `
        <td class="px-3 py-2.5 text-slate-400 font-medium">${famCount}</td>
        <td class="px-3 py-2.5"><input class="fam-input" type="text" name="fam_name[]" placeholder="Full name"></td>
        <td class="px-3 py-2.5"><input class="fam-input" type="text" name="fam_relation[]" placeholder="e.g. Child"></td>
        <td class="px-3 py-2.5"><input class="fam-input" type="number" name="fam_age[]" placeholder="Age" min="0"></td>
        <td class="px-3 py-2.5"><input class="fam-input" type="text" name="fam_occupation[]" placeholder="Occupation"></td>
        <td class="px-3 py-2.5"><input class="fam-input" type="number" name="fam_income[]" min="0" step="0.01" placeholder="0.00"></td>
        <td class="px-3 py-2.5 text-center">
          <button type="button" onclick="this.closest('tr').remove(); renumber()"
            class="text-slate-300 hover:text-red-400 transition-colors text-sm">✕</button>
        </td>
      `;
      document.getElementById('famBody').appendChild(tr);
    }

    function renumber() {
      const rows = document.querySelectorAll('#famBody tr');
      rows.forEach((tr, i) => {
        tr.querySelector('td').textContent = i + 1;
      });
      famCount = rows.length;
    }

    // quick client-side check before submit 
    document.getElementById('clientForm').addEventListener('submit', function(e) {
      const last  = document.querySelector('[name="cl_lastname"]').value.trim();
      const first = document.querySelector('[name="cl_firstname"]').value.trim();
      const bdate = document.querySelector('[name="cl_birthdate"]').value;
      const sex   = document.querySelector('[name="cl_sex"]:checked');
      const brgy  = document.querySelector('[name="brgy_id"]').value;

      if (!last || !first || !bdate || !sex || !brgy) {
        e.preventDefault();
        alert('Please fill in all required fields (marked with *) before saving.');
      }
    });
  </script>

</body>
</html>