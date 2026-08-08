<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Account – MSWDO San Enrique</title>

  <script src="https://cdn.tailwindcss.com"></script>

  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['DM Sans', 'sans-serif'],
            serif: ['DM Serif Display', 'serif']
          },
          colors: {
            forest: {
              DEFAULT: '#0F3D2E',
              50: '#EAF4EE',
              100: '#CFE8DA',
              200: '#A3D2B6',
              300: '#78BC93',
              400: '#3F9A68',
              500: '#1F7A4D',
              600: '#0F3D2E',
              700: '#0C3125',
              800: '#082019',
              900: '#04120E'
            },
            lime: {
              DEFAULT: '#8A9A3A',
              50: '#F6F8EC',
              100: '#E9EECB',
              200: '#D3DE9C',
              300: '#BCC96D',
              400: '#A5B44A',
              500: '#8A9A3A',
              600: '#6D7A2D'
            },
            slate2: '#F2F8F4'
          },
          keyframes: {
            fadeUp: {
              '0%': {
                opacity: '0',
                transform: 'translateY(10px)'
              },
              '100%': {
                opacity: '1',
                transform: 'translateY(0)'
              }
            }
          },
          animation: {
            'fade-up': 'fadeUp .4s ease both',
            'fade-up-1': 'fadeUp .4s ease .05s both',
            'fade-up-2': 'fadeUp .4s ease .1s both'
          }
        }
      }
    }
  </script>

  <style>
    body {
      font-family: 'DM Sans', sans-serif;
      background: #F2F8F4;
    }

    .info-card {
      transition: transform .2s ease, box-shadow .2s ease;
    }

    .info-card:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(15, 61, 46, .08);
    }

    .field-row {
      border-bottom: 1px solid #E8EEE9;
    }

    .field-row:last-child {
      border-bottom: none;
    }

    .profile-avatar {
      background: linear-gradient(135deg, #0F3D2E, #1F7A4D);
    }

    .sidebar-item {
      transition: all .15s ease;
    }

    .sidebar-item:hover {
      background: rgba(255, 255, 255, .07);
    }

    ::-webkit-scrollbar {
      width: 5px;
    }

    ::-webkit-scrollbar-thumb {
      background: #CFE8DA;
      border-radius: 999px;
    }
  </style>
</head>

<body class="bg-slate2 min-h-screen flex flex-col md:flex-row">

  <!-- Sidebar -->
  <?php require 'sidebar.php'; ?>

  <!-- Mobile Header -->
  <div class="md:hidden bg-forest-600 text-white px-4 py-3 flex items-center justify-between">
    <span class="font-serif text-xl">MSWDO</span>
    <button class="text-white" onclick="toggleMobileMenu()">
      <i class="fas fa-bars text-xl"></i>
    </button>
  </div>

  <!-- Main -->
  <div class="md:ml-64 flex-1 flex flex-col min-h-screen w-full">

    <!-- Top Bar -->
    <header class="bg-white border-b border-slate-200 h-14 px-4 md:px-6 flex items-center justify-between sticky top-0 z-20">
      <div class="flex items-center gap-2">
        <i class="fas fa-user-circle text-forest-500 text-sm"></i>
        <span class="text-[13px] font-semibold text-forest-600">
          My Account
        </span>
      </div>

      <span class="hidden sm:block text-[11px] text-slate-400" id="currentDate"></span>
    </header>

    <!-- Content -->
    <main class="flex-1 p-4 md:p-6 space-y-4 md:space-y-5 overflow-y-auto">

      <!-- Page Header -->
      <section class="animate-fade-up">
        <h1 class="text-xl md:text-2xl font-serif text-forest-600">
          My Account
        </h1>
        <p class="text-[12px] text-slate-500 mt-1">
          View and review your account information.
        </p>
      </section>

      <!-- Profile Overview -->
      <section class="info-card animate-fade-up-1 bg-white rounded-2xl border border-slate-200 overflow-hidden">

        <div class="h-2 bg-forest-600"></div>

        <div class="p-5 md:p-6">

          <div class="flex flex-col sm:flex-row sm:items-center gap-4">

            <!-- Avatar -->
            <div class="profile-avatar w-20 h-20 rounded-2xl flex items-center justify-center text-white flex-shrink-0 shadow-sm">
              <i class="fas fa-user text-3xl"></i>
            </div>

            <!-- Name -->
            <div class="flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-lg md:text-xl font-semibold text-forest-600">
                  Juan Dela Cruz
                </h2>

                <span class="text-[10px] font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">
                  Active
                </span>
              </div>

              <p class="text-[12px] text-slate-500 mt-1">
                Social Welfare and Development Office
              </p>

              <p class="text-[11px] text-slate-400 mt-1">
                San Enrique, Negros Occidental
              </p>
            </div>

            <!-- Role -->
            <div class="sm:text-right">
              <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Account Role
              </p>

              <p class="text-[13px] font-semibold text-forest-600 mt-1">
                Staff
              </p>
            </div>

          </div>

        </div>
      </section>

      <!-- Account Information -->
      <section class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5">

        <!-- Personal Information -->
        <div class="info-card animate-fade-up-1 bg-white rounded-2xl border border-slate-200">

          <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
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

          <div class="px-5">

            <div class="field-row py-3 flex items-start justify-between gap-4">
              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  Full Name
                </p>
              </div>

              <p class="text-[12px] font-medium text-slate-700 text-right">
                Juan Dela Cruz
              </p>
            </div>

            <div class="field-row py-3 flex items-start justify-between gap-4">
              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  First Name
                </p>
              </div>

              <p class="text-[12px] text-slate-700 text-right">
                Juan
              </p>
            </div>

            <div class="field-row py-3 flex items-start justify-between gap-4">
              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  Middle Name
                </p>
              </div>

              <p class="text-[12px] text-slate-700 text-right">
                Santos
              </p>
            </div>

            <div class="field-row py-3 flex items-start justify-between gap-4">
              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  Last Name
                </p>
              </div>

              <p class="text-[12px] text-slate-700 text-right">
                Dela Cruz
              </p>
            </div>

          </div>
        </div>

        <!-- Contact Information -->
        <div class="info-card animate-fade-up-1 bg-white rounded-2xl border border-slate-200">

          <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
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

          <div class="px-5">

            <div class="field-row py-3 flex items-start justify-between gap-4">
              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  Email Address
                </p>
              </div>

              <p class="text-[12px] text-slate-700 text-right break-all">
                staff@gmail.com
              </p>
            </div>

            <div class="field-row py-3 flex items-start justify-between gap-4">
              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  Contact Number
                </p>
              </div>

              <p class="text-[12px] text-slate-700 text-right">
                0912 345 6789
              </p>
            </div>

            <div class="field-row py-3 flex items-start justify-between gap-4">
              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  Office
                </p>
              </div>

              <p class="text-[12px] text-slate-700 text-right">
                MSWDO San Enrique
              </p>
            </div>

            <div class="field-row py-3 flex items-start justify-between gap-4">
              <div>
                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                  Municipality
                </p>
              </div>

              <p class="text-[12px] text-slate-700 text-right">
                San Enrique, Negros Occidental
              </p>
            </div>

          </div>
        </div>

      </section>

      <!-- Position & Employment Information -->
      <section class="info-card animate-fade-up-2 bg-white rounded-2xl border border-slate-200">

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

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">

          <div class="p-5 border-b sm:border-r border-slate-100">
            <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
              Position
            </p>

            <p class="text-[13px] font-semibold text-forest-600 mt-1">
              Social Welfare Assistant
            </p>
          </div>

          <div class="p-5 border-b lg:border-r border-slate-100">
            <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
              Office
            </p>

            <p class="text-[13px] font-medium text-slate-700 mt-1">
              MSWDO
            </p>
          </div>

          <div class="p-5 border-b sm:border-r lg:border-b-0 border-slate-100">
            <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
              Municipality
            </p>

            <p class="text-[13px] font-medium text-slate-700 mt-1">
              San Enrique
            </p>
          </div>

          <div class="p-5">
            <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
              Province
            </p>

            <p class="text-[13px] font-medium text-slate-700 mt-1">
              Negros Occidental
            </p>
          </div>

        </div>
      </section>

      <!-- Account Information -->
      <section class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5">

        <!-- Login Information -->
        <div class="info-card bg-white rounded-2xl border border-slate-200">

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
                MSWDO-STAFF-001
              </span>
            </div>

            <div class="field-row py-3 flex items-center justify-between gap-4">
              <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Username
              </span>

              <span class="text-[12px] font-medium text-slate-700">
                staff
              </span>
            </div>

            <div class="field-row py-3 flex items-center justify-between gap-4">
              <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Role
              </span>

              <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">
                Staff
              </span>
            </div>

            <div class="field-row py-3 flex items-center justify-between gap-4">
              <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Account Status
              </span>

              <span class="flex items-center gap-1.5 text-[11px] font-semibold text-emerald-600">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Active
              </span>
            </div>

          </div>
        </div>

        <!-- Account Activity -->
        <div class="info-card bg-white rounded-2xl border border-slate-200">

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

              <span class="text-[12px] text-slate-700">
                January 15, 2026
              </span>
            </div>

            <div class="field-row py-3 flex items-center justify-between gap-4">
              <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Last Login
              </span>

              <span class="text-[12px] text-slate-700">
                August 9, 2026 · 8:42 AM
              </span>
            </div>

            <div class="field-row py-3 flex items-center justify-between gap-4">
              <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                Password
              </span>

              <span class="text-[12px] text-slate-500">
                Last changed 30 days ago
              </span>
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

      <!-- Security Notice -->
      <section class="bg-forest-50 border border-forest-100 rounded-2xl p-4 flex items-start gap-3">

        <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-forest-600 flex-shrink-0">
          <i class="fas fa-shield-halved text-sm"></i>
        </div>

        <div>
          <h3 class="text-[12px] font-semibold text-forest-700">
            Keep your account information updated
          </h3>

          <p class="text-[11px] text-forest-600/70 mt-0.5 leading-relaxed">
            If any of your personal, contact, or position information is incorrect,
            please report the issue to the system administrator for verification
            and updating.
          </p>
        </div>

      </section>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-white px-4 md:px-6 py-3 flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-400 gap-1">
      <span>MSWDO San Enrique Information System</span>
    </footer>

  </div>

  <script>
    const dateSpan = document.getElementById('currentDate');

    if (dateSpan) {
      const today = new Date();

      dateSpan.textContent = today.toLocaleDateString('en-PH', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    }

    function toggleMobileMenu() {
    }
  </script>

</body>
</html>