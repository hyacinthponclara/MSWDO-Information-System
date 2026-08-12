<?php
require 'auth.php';
require 'db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: index.html?error=unauthorized');
    exit;
}

/*
|--------------------------------------------------------------------------
| Account Details actions
|--------------------------------------------------------------------------
| Users can update their own:
| - first name
| - middle name
| - last name
| - email
| - contact number
| - password
|
| Role, position, employment status, office, municipality, and province
| are read-only here and are managed by Admin through User Management.
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'] ?? '';

    try {

        if ($action === 'update_personal') {

            $firstName  = trim($_POST['firstName'] ?? '');
            $middleName = trim($_POST['middleName'] ?? '') ?: null;
            $lastName   = trim($_POST['lastName'] ?? '');

            if ($firstName === '' || $lastName === '') {
                throw new Exception('First name and last name are required.');
            }

            $stmt = $pdo->prepare("
                UPDATE MSWDO_USER
                SET
                    user_firstname = ?,
                    user_middlename = ?,
                    user_lastname = ?
                WHERE user_id = ?
            ");

            $stmt->execute([
                $firstName,
                $middleName,
                $lastName,
                $userId
            ]);

            // Keep the current session name synchronized.
            $_SESSION['user_firstname'] = $firstName;
            $_SESSION['user_lastname']  = $lastName;

            echo json_encode([
                'success' => true,
                'message' => 'Personal information updated successfully.'
            ]);
            exit;
        }


        if ($action === 'update_contact') {

            $email   = trim($_POST['email'] ?? '');
            $contact = trim($_POST['contact'] ?? '');

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address.');
            }

            $normalizedContact = preg_replace('/[\s-]/', '', $contact);

            if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $normalizedContact)) {
                throw new Exception('Please enter a valid Philippine mobile number.');
            }

            $stmt = $pdo->prepare("
                SELECT user_id
                FROM MSWDO_USER
                WHERE user_email = ?
                  AND user_id <> ?
                LIMIT 1
            ");

            $stmt->execute([
                $email,
                $userId
            ]);

            if ($stmt->fetchColumn()) {
                throw new Exception('That email address is already being used by another account.');
            }

            $stmt = $pdo->prepare("
                UPDATE MSWDO_USER
                SET
                    user_email = ?,
                    user_contactnum = ?
                WHERE user_id = ?
            ");

            $stmt->execute([
                $email,
                $contact,
                $userId
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Contact information updated successfully.'
            ]);
            exit;
        }


        if ($action === 'change_password') {

            $currentPassword = $_POST['currentPassword'] ?? '';
            $newPassword     = $_POST['newPassword'] ?? '';
            $confirmPassword = $_POST['confirmPassword'] ?? '';

            if ($currentPassword === '') {
                throw new Exception('Please enter your current password.');
            }

            if (strlen($newPassword) < 8) {
                throw new Exception('Your new password must contain at least 8 characters.');
            }

            if ($newPassword !== $confirmPassword) {
                throw new Exception('The new passwords do not match.');
            }

            $stmt = $pdo->prepare("
                SELECT user_password
                FROM MSWDO_USER
                WHERE user_id = ?
                LIMIT 1
            ");

            $stmt->execute([$userId]);

            $currentHash = $stmt->fetchColumn();

            if (!$currentHash || !password_verify($currentPassword, $currentHash)) {
                throw new Exception('Your current password is incorrect.');
            }

            if (password_verify($newPassword, $currentHash)) {
                throw new Exception('Your new password must be different from your current password.');
            }

            $newHash = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                UPDATE MSWDO_USER
                SET user_password = ?
                WHERE user_id = ?
            ");

            $stmt->execute([
                $newHash,
                $userId
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Password updated successfully.'
            ]);
            exit;
        }


        throw new Exception('Unknown account action.');

    } catch (PDOException $e) {

        // Do not expose database details to the user.
        echo json_encode([
            'success' => false,
            'message' => 'The update could not be saved. Please check the information and try again.'
        ]);
        exit;

    } catch (Exception $e) {

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Load the currently logged-in user's information
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        user_id,
        username,
        user_firstname,
        user_middlename,
        user_lastname,
        user_role,
        user_position,
        user_employment_status,
        user_office,
        user_municipality,
        user_province,
        user_contactnum,
        user_email,
        user_isactive,
        user_date_joined,
        user_last_login
    FROM MSWDO_USER
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: index.html?error=unauthorized');
    exit;
}

$fullName = trim(
    $user['user_firstname'] . ' ' .
    ($user['user_middlename'] ?? '') . ' ' .
    $user['user_lastname']
);

$accountId =
    'MSWDO-USER-' .
    str_pad(
        (string) $user['user_id'],
        3,
        '0',
        STR_PAD_LEFT
    );

$dateJoined = !empty($user['user_date_joined'])
    ? date('F j, Y', strtotime($user['user_date_joined']))
    : '—';

$lastLogin = !empty($user['user_last_login'])
    ? date('F j, Y · g:i A', strtotime($user['user_last_login']))
    : 'Never';

$officeDisplay = $user['user_office'] ?: '—';
$municipalityDisplay = $user['user_municipality'] ?: '—';
$provinceDisplay = $user['user_province'] ?: '—';

$locationDisplay = trim(
    $municipalityDisplay .
    ($provinceDisplay !== '—' ? ', ' . $provinceDisplay : '')
);

$active = (int) $user['user_isactive'] === 1;
$role = $user['user_role'] ?: '—';
$position = $user['user_position'] ?: '—';
$employmentStatus = $user['user_employment_status'] ?: '—';
$email = $user['user_email'] ?: '—';
$contact = $user['user_contactnum'] ?: '—';

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>My Account - MSWDO San Enrique</title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap"
    rel="stylesheet">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            forest: {
              50: '#f1f7f3',
              100: '#dceee2',
              200: '#b9dcc5',
              300: '#8fc4a0',
              400: '#5fa67a',
              500: '#3d875e',
              600: '#2f6f4e',
              700: '#285b42',
              800: '#234a37',
              900: '#1d3d2f'
            }
          },
          fontFamily: {
            sans: ['DM Sans', 'sans-serif'],
            serif: ['Playfair Display', 'serif']
          }
        }
      }
    }
  </script>

  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: #f8faf9;
      color: #334155;
    }

    .info-card {
      transition: box-shadow 0.2s ease, transform 0.2s ease;
    }

    .info-card:hover {
      box-shadow: 0 8px 25px rgba(15, 23, 42, 0.05);
    }

    .field-row {
      border-bottom: 1px solid #f1f5f9;
    }

    .field-row:last-child {
      border-bottom: none;
    }

    .profile-avatar {
      background: linear-gradient(135deg,
          #2f6f4e,
          #3d875e);
    }

    .animate-fade-up {
      animation: fadeUp 0.45s ease both;
    }

    .animate-fade-up-1 {
      animation: fadeUp 0.45s ease 0.08s both;
    }

    .animate-fade-up-2 {
      animation: fadeUp 0.45s ease 0.16s both;
    }

    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(8px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .modal-backdrop {
      animation: modalFade 0.2s ease both;
    }

    .modal-box {
      animation: modalSlide 0.25s ease both;
    }

    @keyframes modalFade {
      from {
        opacity: 0;
      }

      to {
        opacity: 1;
      }
    }

    @keyframes modalSlide {
      from {
        opacity: 0;
        transform: translateY(12px) scale(0.98);
      }

      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .input-field {
      transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease;
    }

    .input-field:focus {
      outline: none;
      border-color: #2f6f4e;
      box-shadow: 0 0 0 3px rgba(47, 111, 78, 0.1);
    }

    .readonly-field {
      background: #f8fafc;
    }

    @media (max-width: 640px) {
      .field-value {
        max-width: 55%;
      }
    }
  </style>
</head>


<body class="bg-slate2 min-h-screen flex flex-col md:flex-row">
  <!-- Sidebar (desktop) -->
  <?php require 'sidebar.php'; ?>
  <!-- Mobile header -->
  <div class="md:hidden bg-forest-600 text-white p-3 flex items-center justify-between">
    <span class="font-serif text-xl">MSWDO</span>
    <button class="text-white"><i class="fas fa-bars text-xl"></i></button>
  </div>
  <!-- ═══════════ MAIN ═══════════ -->
  <div class="md:ml-64 flex-1 flex flex-col min-h-screen w-full">
    <header
      class="bg-white border-b border-slate-200 h-14 px-4 md:px-6 flex items-center justify-between sticky top-0 z-20">
      <div class="flex items-center gap-2">
        <i class="fas fa-user-circle text-forest-500 text-sm"></i>

        <span class="text-[13px] font-semibold text-forest-600">
          My Account
        </span>
      </div>

      <span class="hidden sm:block text-[11px] text-slate-400" id="currentDate"></span>
    </header>

    <main class="flex-1 p-4 md:p-6 space-y-4 md:space-y-5 overflow-y-auto">

      <section class="animate-fade-up">

        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">

          <div>
            <h1 class="text-xl md:text-2xl font-serif text-forest-600">
              My Account
            </h1>

            <p class="text-[12px] text-slate-500 mt-1">
              View and manage your account information.
            </p>
          </div>

        </div>

      </section>


      <!-- PROFILE OVERVIEW -->
      <section class="info-card animate-fade-up-1 bg-white rounded-2xl border border-slate-200 overflow-hidden">

        <div class="h-2 bg-forest-600"></div>

        <div class="p-5 md:p-6">

          <div class="flex flex-col sm:flex-row sm:items-center gap-4">

            <div
              class="profile-avatar w-20 h-20 rounded-2xl flex items-center justify-center text-white flex-shrink-0 shadow-sm">
              <i class="fas fa-user text-3xl"></i>
            </div>

            <div class="flex-1 min-w-0">

              <div class="flex flex-wrap items-center gap-2">

                <h2 id="profileFullName" class="text-lg md:text-xl font-semibold text-forest-600">
                  <?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>
                </h2>

                <span class="text-[10px] font-semibold px-2.5 py-1 rounded-full <?= $active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' ?>">
                  <?= $active ? 'Active' : 'Inactive' ?>
                </span>

              </div>

              <p class="text-[12px] text-slate-500 mt-1">
                Social Welfare and Development Office
              </p>

              <p class="text-[11px] text-slate-400 mt-1">
                <?= htmlspecialchars($locationDisplay, ENT_QUOTES, 'UTF-8') ?>
              </p>

            </div>

            <div class="sm:text-right">

              <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Account Role
              </p>

              <p class="text-[13px] font-semibold text-forest-600 mt-1">
                <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>
              </p>

            </div>

          </div>

        </div>
      </section>
      <section class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5">

        <div class="info-card animate-fade-up-1 bg-white rounded-2xl border border-slate-200 overflow-hidden">

          <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">

            <div class="flex items-center gap-3">

              <div class="w-8 h-8 rounded-lg bg-forest-50 flex items-center justify-center text-forest-600">
                <i class="fas fa-id-card text-sm"></i>
              </div>

              <div>
                <h2 class="text-[13px] font-semibold text-forest-600">
                  Personal Information
                </h2>

                <p class="text-[10px] text-slate-400">
                  Your registered personal details
                </p>
              </div>

            </div>

            <button type="button" onclick="openPersonalEdit()"
              class="flex items-center gap-1.5 text-[11px] font-semibold text-forest-600 hover:text-forest-700 transition">
              <i class="fas fa-pen text-[9px]"></i>
              Edit
            </button>

          </div>


          <div class="px-5">

            <div class="field-row py-3 flex items-start justify-between gap-4">

              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  Full Name
                </p>
              </div>

              <p id="displayFullName" class="field-value text-[12px] font-medium text-slate-700 text-right">
                <?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>
              </p>

            </div>

            <div class="field-row py-3 flex items-start justify-between gap-4">

              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  First Name
                </p>
              </div>

              <p id="displayFirstName" class="field-value text-[12px] text-slate-700 text-right">
                <?= htmlspecialchars($user['user_firstname'], ENT_QUOTES, 'UTF-8') ?>
              </p>

            </div>

            <div class="field-row py-3 flex items-start justify-between gap-4">

              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  Middle Name
                </p>
              </div>

              <p id="displayMiddleName" class="field-value text-[12px] text-slate-700 text-right">
                <?= htmlspecialchars($user['user_middlename'] ?: '—', ENT_QUOTES, 'UTF-8') ?>
              </p>

            </div>


            <div class="field-row py-3 flex items-start justify-between gap-4">

              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  Last Name
                </p>
              </div>

              <p id="displayLastName" class="field-value text-[12px] text-slate-700 text-right">
                <?= htmlspecialchars($user['user_lastname'], ENT_QUOTES, 'UTF-8') ?>
              </p>

            </div>

          </div>
        </div>


        <div class="info-card animate-fade-up-1 bg-white rounded-2xl border border-slate-200 overflow-hidden">

          <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">

            <div class="flex items-center gap-3">

              <div class="w-8 h-8 rounded-lg bg-forest-50 flex items-center justify-center text-forest-600">
                <i class="fas fa-address-book text-sm"></i>
              </div>

              <div>
                <h2 class="text-[13px] font-semibold text-forest-600">
                  Contact Information
                </h2>

                <p class="text-[10px] text-slate-400">
                  Your registered contact details
                </p>
              </div>

            </div>

            <button type="button" onclick="openContactEdit()"
              class="flex items-center gap-1.5 text-[11px] font-semibold text-forest-600 hover:text-forest-700 transition">
              <i class="fas fa-pen text-[9px]"></i>
              Edit
            </button>

          </div>

          <div class="px-5">

            <div class="field-row py-3 flex items-start justify-between gap-4">

              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  Email Address
                </p>
              </div>

              <p id="displayEmail" class="field-value text-[12px] text-slate-700 text-right break-all">
                <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
              </p>

            </div>
            <div class="field-row py-3 flex items-start justify-between gap-4">

              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  Contact Number
                </p>
              </div>

              <p id="displayContact" class="field-value text-[12px] text-slate-700 text-right">
                <?= htmlspecialchars($contact, ENT_QUOTES, 'UTF-8') ?>
              </p>

            </div>

            <div class="field-row py-3 flex items-start justify-between gap-4">

              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  Office
                </p>
              </div>

              <div class="flex items-center gap-2">

                <p class="field-value text-[12px] text-slate-700 text-right">
                  <?= htmlspecialchars($officeDisplay, ENT_QUOTES, 'UTF-8') ?>
                </p>

              </div>

            </div>


            <div class="field-row py-3 flex items-start justify-between gap-4">

              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  Municipality
                </p>
              </div>

              <div class="flex items-center gap-2">

                <p class="field-value text-[12px] text-slate-700 text-right">
                  <?= htmlspecialchars($locationDisplay, ENT_QUOTES, 'UTF-8') ?>
                </p>


              </div>

            </div>

          </div>
        </div>

      </section>

      <section class="info-card animate-fade-up-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">

        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">

          <div class="w-8 h-8 rounded-lg bg-lime-50 flex items-center justify-center text-lime-600">
            <i class="fas fa-briefcase text-sm"></i>
          </div>

          <div>

            <h2 class="text-[13px] font-semibold text-forest-600">
              Position & Office Information
            </h2>

            <p class="text-[10px] text-slate-400">
              Your current position and office assignment
            </p>

          </div>

        </div>


        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5">

          <div class="p-5 border-b sm:border-r border-slate-100">

            <div class="flex items-center justify-between gap-2">

              <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Position
              </p>

            </div>

            <p class="text-[13px] font-semibold text-forest-600 mt-1">
              <?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?>
            </p>

          </div>

          <div class="p-5 border-b sm:border-r border-slate-100">

            <div class="flex items-center justify-between gap-2">

              <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Employment Status
              </p>

            </div>

            <p class="text-[13px] font-semibold text-forest-600 mt-1">
              <?= htmlspecialchars($employmentStatus, ENT_QUOTES, 'UTF-8') ?>
            </p>

          </div>


          <div class="p-5 border-b lg:border-r border-slate-100">

            <div class="flex items-center justify-between gap-2">

              <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Office
              </p>

            </div>

            <p class="text-[13px] font-medium text-slate-700 mt-1">
              <?= htmlspecialchars($officeDisplay, ENT_QUOTES, 'UTF-8') ?>
            </p>

          </div>


          <div class="p-5 border-b sm:border-r lg:border-b-0 border-slate-100">

            <div class="flex items-center justify-between gap-2">

              <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Municipality
              </p>

            </div>

            <p class="text-[13px] font-medium text-slate-700 mt-1">
              <?= htmlspecialchars($municipalityDisplay, ENT_QUOTES, 'UTF-8') ?>
            </p>

          </div>


          <div class="p-5">

            <div class="flex items-center justify-between gap-2">

              <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Province
              </p>

            </div>

            <p class="text-[13px] font-medium text-slate-700 mt-1">
              <?= htmlspecialchars($provinceDisplay, ENT_QUOTES, 'UTF-8') ?>
            </p>

          </div>

        </div>
      </section>
      <section class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5">

        <div class="info-card bg-white rounded-2xl border border-slate-200 overflow-hidden">

          <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">

            <div class="w-8 h-8 rounded-lg bg-forest-50 flex items-center justify-center text-forest-600">
              <i class="fas fa-user-lock text-sm"></i>
            </div>

            <div>

              <h2 class="text-[13px] font-semibold text-forest-600">
                Account Information
              </h2>

              <p class="text-[10px] text-slate-400">
                Basic information about this account
              </p>

            </div>

          </div>


          <div class="px-5">

            <div class="field-row py-3 flex items-center justify-between gap-4">

              <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Account ID
              </span>

              <span class="text-[12px] font-medium text-slate-700">
                <?= htmlspecialchars($accountId, ENT_QUOTES, 'UTF-8') ?>
              </span>

            </div>


            <div class="field-row py-3 flex items-center justify-between gap-4">

              <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Username
              </span>

              <span class="text-[12px] font-medium text-slate-700">
                <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>
              </span>

            </div>


            <div class="field-row py-3 flex items-center justify-between gap-4">

              <div class="flex items-center gap-2">

                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  Role
                </span>

                <i class="fas fa-lock text-[9px] text-slate-300" title="Role cannot be changed by the user"></i>

              </div>

              <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">
                <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>
              </span>

            </div>


            <div class="field-row py-3 flex items-center justify-between gap-4">

              <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Account Status
              </span>

              <span class="flex items-center gap-1.5 text-[11px] font-semibold <?= $active ? 'text-emerald-600' : 'text-slate-500' ?>">

                <span class="w-1.5 h-1.5 rounded-full <?= $active ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>

                <?= $active ? 'Active' : 'Inactive' ?>

              </span>

            </div>

          </div>
        </div>


        <div class="info-card bg-white rounded-2xl border border-slate-200 overflow-hidden">

          <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">

            <div class="w-8 h-8 rounded-lg bg-lime-50 flex items-center justify-center text-lime-600">
              <i class="fas fa-clock-rotate-left text-sm"></i>
            </div>

            <div>

              <h2 class="text-[13px] font-semibold text-forest-600">
                Account Activity
              </h2>

              <p class="text-[10px] text-slate-400">
                Recent account activity
              </p>

            </div>

          </div>


          <div class="px-5">

            <div class="field-row py-3 flex items-center justify-between gap-4">

              <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Date Joined
              </span>

              <span class="text-[12px] text-slate-700 text-right">
                <?= htmlspecialchars($dateJoined, ENT_QUOTES, 'UTF-8') ?>
              </span>

            </div>


            <div class="field-row py-3 flex items-center justify-between gap-4">

              <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Last Login
              </span>

              <span class="text-[12px] text-slate-700 text-right">
                <?= htmlspecialchars($lastLogin, ENT_QUOTES, 'UTF-8') ?>
              </span>

            </div>


            <!-- Password -->
            <div class="field-row py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">

              <div>

                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  Password
                </span>

                <p id="passwordStatus" class="text-[11px] text-slate-500 mt-0.5">
                  Password is securely stored.
                </p>

              </div>


              <button type="button" onclick="openPasswordModal()"
                class="self-start sm:self-auto inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg border border-forest-200 bg-forest-50 text-[11px] font-semibold text-forest-600 hover:bg-forest-100 transition whitespace-nowrap">
                <i class="fas fa-key text-[9px]"></i>
                Change Password
              </button>

            </div>


            <div class="py-3 flex items-center justify-between gap-4">

              <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Login Status
              </span>

              <span class="text-[11px] font-semibold text-emerald-600">

                <i class="fas fa-circle text-[6px] mr-1"></i>
                Secure

              </span>

            </div>

          </div>
        </div>

      </section>
      <section class="bg-forest-50 border border-forest-100 rounded-2xl p-4 flex items-start gap-3">

        <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-forest-600 flex-shrink-0">
          <i class="fas fa-shield-halved text-sm"></i>
        </div>

        <div>

          <h3 class="text-[12px] font-semibold text-forest-700">
            Keep your account information updated
          </h3>

          <p class="text-[11px] text-forest-600/70 mt-0.5 leading-relaxed">
            You can update your personal and contact information
            and change your password from this page. Your account
            role, position, employment status, and office assignment
            are managed by an Administrator.
          </p>

        </div>

      </section>

    </main>


    <footer
      class="border-t border-slate-200 bg-white px-4 md:px-6 py-3 flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-400 gap-1">

      <span>
        MSWDO San Enrique Information System
      </span>

    </footer>

    <div id="personalModal" class="hidden fixed inset-0 z-50 modal-backdrop bg-slate-900/40 px-4 py-6 overflow-y-auto">

      <div class="min-h-full flex items-center justify-center">

        <div class="modal-box bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

          <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">

            <div>

              <h2 class="text-[14px] font-semibold text-forest-600">
                Edit Personal Information
              </h2>

              <p class="text-[10px] text-slate-400 mt-0.5">
                Update your registered name
              </p>

            </div>


            <button type="button" onclick="closePersonalEdit()"
              class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
              <i class="fas fa-xmark text-sm"></i>
            </button>

          </div>

          <form id="personalForm" onsubmit="savePersonalInformation(event)">

            <div class="p-5 space-y-4">

              <div>

                <label for="editFirstName"
                  class="block text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-1.5">
                  First Name
                </label>

                <input type="text" id="editFirstName" value="<?= htmlspecialchars($user["user_firstname"], ENT_QUOTES, "UTF-8") ?>"
                  class="input-field w-full border border-slate-200 rounded-lg px-3 py-2.5 text-[12px] text-slate-700"
                  required>

              </div>


              <div>

                <label for="editMiddleName"
                  class="block text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-1.5">
                  Middle Name
                </label>

                <input type="text" id="editMiddleName" value="<?= htmlspecialchars($user["user_middlename"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                  class="input-field w-full border border-slate-200 rounded-lg px-3 py-2.5 text-[12px] text-slate-700">

              </div>


              <div>

                <label for="editLastName"
                  class="block text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-1.5">
                  Last Name
                </label>

                <input type="text" id="editLastName" value="<?= htmlspecialchars($user["user_lastname"], ENT_QUOTES, "UTF-8") ?>"
                  class="input-field w-full border border-slate-200 rounded-lg px-3 py-2.5 text-[12px] text-slate-700"
                  required>

              </div>

            </div>


            <div
              class="px-5 py-4 bg-slate-50 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">

              <button type="button" onclick="closePersonalEdit()"
                class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-[11px] font-semibold text-slate-600 hover:bg-slate-50 transition">
                Cancel
              </button>

              <button type="submit"
                class="px-4 py-2 rounded-lg bg-[#2f6f4e] text-white text-[11px] font-semibold hover:bg-[#255a3f] transition cursor-pointer">
                <i class="fas fa-check mr-1"></i>
                Save Changes
              </button>

            </div>

          </form>

        </div>

      </div>

    </div>


    <div id="contactModal" class="hidden fixed inset-0 z-50 modal-backdrop bg-slate-900/40 px-4 py-6 overflow-y-auto">

      <div class="min-h-full flex items-center justify-center">

        <div class="modal-box bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

          <!-- Modal Header -->
          <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">

            <div>

              <h2 class="text-[14px] font-semibold text-forest-600">
                Edit Contact Information
              </h2>

              <p class="text-[10px] text-slate-400 mt-0.5">
                Update your contact details
              </p>

            </div>


            <button type="button" onclick="closeContactEdit()"
              class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
              <i class="fas fa-xmark text-sm"></i>
            </button>

          </div>


          <form id="contactForm" onsubmit="saveContactInformation(event)">

            <div class="p-5 space-y-4">

              <div>

                <label for="editEmail"
                  class="block text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-1.5">
                  Email Address
                </label>

                <input type="email" id="editEmail" value="<?= htmlspecialchars($user["user_email"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                  class="input-field w-full border border-slate-200 rounded-lg px-3 py-2.5 text-[12px] text-slate-700"
                  required>

              </div>


              <div>

                <label for="editContact"
                  class="block text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-1.5">
                  Contact Number
                </label>

                <input type="tel" id="editContact" value="<?= htmlspecialchars($user["user_contactnum"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                  class="input-field w-full border border-slate-200 rounded-lg px-3 py-2.5 text-[12px] text-slate-700"
                  required>

              </div>


              <div>

                <label class="block text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-1.5">
                  Office
                </label>

                <div
                  class="readonly-field w-full border border-slate-200 rounded-lg px-3 py-2.5 text-[12px] text-slate-500 flex items-center justify-between">

                  <span>
                    MSWDO San Enrique
                  </span>

                  <i class="fas fa-lock text-[9px] text-slate-300"></i>

                </div>

              </div>

            </div>


            <div
              class="px-5 py-4 bg-slate-50 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">

              <button type="button" onclick="closeContactEdit()"
                class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-[11px] font-semibold text-slate-600 hover:bg-slate-50 transition">
                Cancel
              </button>

              <button type="submit"
                class="px-4 py-2 rounded-lg bg-[#2f6f4e] text-white text-[11px] font-semibold hover:bg-[#255a3f] transition cursor-pointer">
                <i class="fas fa-check mr-1"></i>
                Save Changes
              </button>

            </div>

          </form>

        </div>

      </div>

    </div>


    <div id="passwordModal" class="hidden fixed inset-0 z-50 modal-backdrop bg-slate-900/40 px-4 py-6 overflow-y-auto">

      <div class="min-h-full flex items-center justify-center">

        <div class="modal-box bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

          <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">

            <div>

              <h2 class="text-[14px] font-semibold text-forest-600">
                Change Password
              </h2>

              <p class="text-[10px] text-slate-400 mt-0.5">
                Update your account password
              </p>

            </div>


            <button type="button" onclick="closePasswordModal()"
              class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
              <i class="fas fa-xmark text-sm"></i>
            </button>

          </div>


          <form id="passwordForm" onsubmit="changePassword(event)">

            <div class="p-5 space-y-4">

              <div>

                <label for="currentPassword"
                  class="block text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-1.5">
                  Current Password
                </label>

                <div class="relative">

                  <input type="password" id="currentPassword"
                    class="input-field w-full border border-slate-200 rounded-lg px-3 py-2.5 pr-10 text-[12px] text-slate-700"
                    required>

                  <button type="button" onclick="togglePassword('currentPassword', this)"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-forest-600">
                    <i class="fas fa-eye text-[11px]"></i>
                  </button>

                </div>

              </div>


              <div>

                <label for="newPassword"
                  class="block text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-1.5">
                  New Password
                </label>

                <div class="relative">

                  <input type="password" id="newPassword" minlength="8"
                    class="input-field w-full border border-slate-200 rounded-lg px-3 py-2.5 pr-10 text-[12px] text-slate-700"
                    required>

                  <button type="button" onclick="togglePassword('newPassword', this)"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-forest-600">
                    <i class="fas fa-eye text-[11px]"></i>
                  </button>

                </div>

                <p class="text-[10px] text-slate-400 mt-1">
                  Password must contain at least 8 characters.
                </p>

              </div>


              <div>

                <label for="confirmPassword"
                  class="block text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-1.5">
                  Confirm New Password
                </label>

                <div class="relative">

                  <input type="password" id="confirmPassword" minlength="8"
                    class="input-field w-full border border-slate-200 rounded-lg px-3 py-2.5 pr-10 text-[12px] text-slate-700"
                    required>

                  <button type="button" onclick="togglePassword('confirmPassword', this)"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-forest-600">
                    <i class="fas fa-eye text-[11px]"></i>
                  </button>

                </div>

              </div>


              <div class="bg-forest-50 border border-forest-100 rounded-lg p-3 flex items-start gap-2.5">

                <i class="fas fa-shield-halved text-forest-600 text-sm mt-0.5"></i>

                <p class="text-[10px] leading-relaxed text-forest-600">
                  Choose a password that is difficult for others to
                  guess. Do not share your password with anyone.
                </p>

              </div>

            </div>

            <div
              class="px-5 py-4 bg-slate-50 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">

              <button type="button" onclick="closePasswordModal()"
                class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-[11px] font-semibold text-slate-600 hover:bg-slate-50 transition">
                Cancel
              </button>

              <button type="submit"
                class="px-4 py-2 rounded-lg bg-[#2f6f4e] text-white text-[11px] font-semibold hover:bg-[#255a3f] transition cursor-pointer">
                <i class="fas fa-key mr-1"></i>
                Update Password
              </button>

            </div>

          </form>

        </div>

      </div>

    </div>

    <script>


      function updateCurrentDate() {

        const now = new Date();

        const formattedDate = now.toLocaleDateString(
          'en-PH',
          {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
          }
        );

        const dateElement =
          document.getElementById('currentDate');

        if (dateElement) {
          dateElement.textContent = formattedDate;
        }

      }

      updateCurrentDate();



      function openPersonalEdit() {

        document.getElementById('editFirstName').value =
          document.getElementById('displayFirstName').textContent.trim();

        document.getElementById('editMiddleName').value =
          document.getElementById('displayMiddleName').textContent.trim();

        document.getElementById('editLastName').value =
          document.getElementById('displayLastName').textContent.trim();

        document
          .getElementById('personalModal')
          .classList.remove('hidden');

        document.body.classList.add('overflow-hidden');

      }


      function closePersonalEdit() {

        document
          .getElementById('personalModal')
          .classList.add('hidden');

        document.body.classList.remove('overflow-hidden');

      }


      async function savePersonalInformation(event) {

        event.preventDefault();

        const firstName =
          document.getElementById('editFirstName').value.trim();

        const middleName =
          document.getElementById('editMiddleName').value.trim();

        const lastName =
          document.getElementById('editLastName').value.trim();

        if (!firstName || !lastName) {
          alert('Please enter your first name and last name.');
          return;
        }

        const formData = new FormData();
        formData.append('action', 'update_personal');
        formData.append('firstName', firstName);
        formData.append('middleName', middleName);
        formData.append('lastName', lastName);

        try {

          const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
          });

          const result = await response.json();

          if (!result.success) {
            alert(result.message);
            return;
          }

          document.getElementById('displayFirstName').textContent =
            firstName;

          document.getElementById('displayMiddleName').textContent =
            middleName || '—';

          document.getElementById('displayLastName').textContent =
            lastName;

          const fullName =
            [firstName, middleName, lastName]
              .filter(Boolean)
              .join(' ');

          document.getElementById('displayFullName').textContent =
            fullName;

          document.getElementById('profileFullName').textContent =
            fullName;

          closePersonalEdit();

          showToast(result.message);

        } catch (error) {

          console.error(error);
          alert('The update could not be saved. Please try again.');

        }
      }


      function openContactEdit() {

        document.getElementById('editEmail').value =
          document.getElementById('displayEmail').textContent.trim();

        document.getElementById('editContact').value =
          document.getElementById('displayContact').textContent.trim();

        document
          .getElementById('contactModal')
          .classList.remove('hidden');

        document.body.classList.add('overflow-hidden');

      }


      function closeContactEdit() {

        document
          .getElementById('contactModal')
          .classList.add('hidden');

        document.body.classList.remove('overflow-hidden');

      }


      async function saveContactInformation(event) {

        event.preventDefault();

        const email =
          document.getElementById('editEmail').value.trim();

        const contact =
          document.getElementById('editContact').value.trim();

        if (!email || !contact) {
          alert('Please complete your contact information.');
          return;
        }

        const formData = new FormData();
        formData.append('action', 'update_contact');
        formData.append('email', email);
        formData.append('contact', contact);

        try {

          const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
          });

          const result = await response.json();

          if (!result.success) {
            alert(result.message);
            return;
          }

          document.getElementById('displayEmail').textContent =
            email;

          document.getElementById('displayContact').textContent =
            contact;

          closeContactEdit();

          showToast(result.message);

        } catch (error) {

          console.error(error);
          alert('The update could not be saved. Please try again.');

        }
      }


      function openPasswordModal() {

        document
          .getElementById('passwordModal')
          .classList.remove('hidden');

        document.body.classList.add('overflow-hidden');

      }


      function closePasswordModal() {

        document
          .getElementById('passwordModal')
          .classList.add('hidden');

        document.body.classList.remove('overflow-hidden');

        document
          .getElementById('passwordForm')
          .reset();

      }


      async function changePassword(event) {

        event.preventDefault();

        const currentPassword =
          document.getElementById('currentPassword').value;

        const newPassword =
          document.getElementById('newPassword').value;

        const confirmPassword =
          document.getElementById('confirmPassword').value;

        if (!currentPassword) {
          alert('Please enter your current password.');
          return;
        }

        if (newPassword.length < 8) {
          alert('Your new password must contain at least 8 characters.');
          return;
        }

        if (newPassword !== confirmPassword) {
          alert('The new passwords do not match.');
          return;
        }

        const formData = new FormData();
        formData.append('action', 'change_password');
        formData.append('currentPassword', currentPassword);
        formData.append('newPassword', newPassword);
        formData.append('confirmPassword', confirmPassword);

        try {

          const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
          });

          const result = await response.json();

          if (!result.success) {
            alert(result.message);
            return;
          }

          document.getElementById('passwordStatus').textContent =
            'Password is securely stored.';

          closePasswordModal();

          showToast(result.message);

        } catch (error) {

          console.error(error);
          alert('The password could not be changed. Please try again.');

        }
      }


      function togglePassword(inputId, button) {

        const input =
          document.getElementById(inputId);

        const icon =
          button.querySelector('i');


        if (input.type === 'password') {

          input.type = 'text';

          icon.classList.remove('fa-eye');
          icon.classList.add('fa-eye-slash');

        } else {

          input.type = 'password';

          icon.classList.remove('fa-eye-slash');
          icon.classList.add('fa-eye');

        }

      }

      function showToast(message) {

        const existingToast =
          document.getElementById('accountToast');

        if (existingToast) {
          existingToast.remove();
        }


        const toast =
          document.createElement('div');

        toast.id = 'accountToast';

        toast.className =
          'fixed bottom-5 right-5 z-[70] bg-forest-700 text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-2 text-[11px] font-medium';


        toast.innerHTML = `
        <div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center">
          <i class="fas fa-check text-[9px]"></i>
        </div>

        <span>${message}</span>
      `;


        document.body.appendChild(toast);


        setTimeout(() => {

          toast.style.opacity = '0';
          toast.style.transform = 'translateY(8px)';
          toast.style.transition =
            'all 0.25s ease';

          setTimeout(() => {
            toast.remove();
          }, 250);

        }, 2500);

      }


      document
        .getElementById('personalModal')
        .addEventListener('click', function (event) {

          if (event.target === this) {
            closePersonalEdit();
          }

        });


      document
        .getElementById('contactModal')
        .addEventListener('click', function (event) {

          if (event.target === this) {
            closeContactEdit();
          }

        });


      document
        .getElementById('passwordModal')
        .addEventListener('click', function (event) {

          if (event.target === this) {
            closePasswordModal();
          }

        });


      document.addEventListener(
        'keydown',
        function (event) {

          if (event.key !== 'Escape') {
            return;
          }

          closePersonalEdit();
          closeContactEdit();
          closePasswordModal();

        }
      );

    </script>

</body>

</html>