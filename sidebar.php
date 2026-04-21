<?php
$role = $_SESSION['user_role'];
$current_page = basename($_SERVER['PHP_SELF']);
?>

<html>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['DM Sans', 'sans-serif'],
                    serif: ['DM Serif Display', 'serif'],
                },
                colors: {
                    navy: {
                        DEFAULT: '#0B2545',
                        50: '#E8EDF5',
                        100: '#C5D1E6',
                        200: '#9AAECE',
                        300: '#6F8BB5',
                        400: '#3A5F93',
                        500: '#163566',
                        600: '#0B2545',
                        700: '#091D38',
                        800: '#06142A',
                        900: '#030A15',
                    },
                    gold: {
                        DEFAULT: '#C49A2A',
                        50: '#FBF5E6',
                        100: '#F5E4B3',
                        200: '#EDD07A',
                        300: '#E4BC3F',
                        400: '#C49A2A',
                        500: '#9E7A1F',
                        600: '#795C16',
                    },
                    slate2: '#F4F7FC',
                },
                keyframes: {
                    fadeUp: { '0%': { opacity: '0', transform: 'translateY(12px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    pulse2: { '0%,100%': { opacity: '1' }, '50%': { opacity: '.5' } },
                },
                animation: {
                    'fade-up': 'fadeUp 0.4s ease both',
                    'fade-up-1': 'fadeUp 0.4s ease 0.05s both',
                    'fade-up-2': 'fadeUp 0.4s ease 0.1s both',
                    'fade-up-3': 'fadeUp 0.4s ease 0.15s both',
                    'fade-up-4': 'fadeUp 0.4s ease 0.2s both',
                    'fade-up-5': 'fadeUp 0.4s ease 0.25s both',
                    'pulse2': 'pulse2 2s ease-in-out infinite',
                }
            }
        }
    }
</script>

</html>

<aside id="sidebar" class="w-64 min-h-screen flex flex-col" style="background: #0B2545;">

    <!-- Logo -->
    <div class="px-5 pt-5 pb-4 border-b border-white/10">
        <div
            class="w-9 h-9 rounded-full bg-gold-400 flex items-center justify-center text-navy-600 text-lg font-serif font-bold mb-3">
            M</div>
        <p class="font-serif text-white text-sm leading-snug">MSWDO San Enrique</p>
        <p class="text-white/40 text-[10px] mt-0.5 tracking-wide">Information System</p>
    </div>


    <!-- acces by admin -->
    <?php if ($role === 'Admin'): ?>
        <a href="admindashboard.php"
            class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/70 text-sm <?= $current_page == 'admindashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie text-sm"></i> Dashboard
        </a>

        <a href="clientslist.html"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-users text-sm"></i> Clients
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-list text-sm"></i> Availments
        </a>

        <p class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-widest text-white/30 font-medium">Programs</p>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-capsules text-sm"></i> AICS
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-home text-sm"></i> 4Ps
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-briefcase text-sm"></i> SLP
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-utensils text-sm"></i> SFP
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-school text-sm"></i> Day Care
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-user-friends text-sm"></i> Senior Citizen
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-wheelchair text-sm"></i> PWD
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-user text-sm"></i> Solo Parent
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-lock text-sm"></i> Women &amp; Child
        </a>

        <p class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-widest text-white/30 font-medium">Analytics</p>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-file-alt text-sm"></i> Reports
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-map-marked-alt text-sm"></i> Geographic Analysis
        </a>
        <a href="#"
            class="sidebar-item flex items-center justify-between px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <span class="flex items-center gap-2.5"><i class="fas fa-money-bill-wave text-sm"></i> Budget Monitoring</span>
            <span
                class="relative bg-red-500 text-white text-[10px] font-semibold px-1.5 py-0.5 rounded-full leading-none badge-pulse">2</span>
        </a>

        <p class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-widest text-white/30 font-medium">Administration</p>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-user-gear text-sm"></i> User Management
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-gear text-sm"></i> System Config
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-file-alt text-sm"></i> Activity Logs
        </a>
        </nav>

        <!-- User footer -->
        <div class="px-4 py-3 border-t border-white/10 flex items-center gap-3">
            <div
                class="w-8 h-8 rounded-full bg-gold-400 flex items-center justify-center text-navy-600 text-xs font-bold flex-shrink-0">
                JD</div>
            <div class="min-w-0">
                <p class="text-white text-xs font-medium truncate">Juan Dela Cruz</p>
                <p class="text-white/40 text-[10px]">Administrator</p>
            </div>
            <a href="index.html" class="ml-auto text-white/30 hover:text-white/70 text-sm transition-colors" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    <?php endif; ?>


    <!-- access by social worker -->
    <?php if ($role === 'Social Worker'): ?>
        <a href="mswdohead.php"
            class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/70 text-sm <?= $current_page == 'mswdohead.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie text-sm"></i> Dashboard
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-users text-sm"></i> Clients
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-list text-sm"></i> Availments
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-book text-sm"></i> Case Study
        </a>

        <!-- Confidential section — violet tinted -->
        <p class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-widest text-violet-300/60 font-medium">Confidential
        </p>
        <a href="#" class="sidebar-conf active-conf flex items-center justify-between px-3 py-2 rounded text-[13px]">
            <span class="flex items-center gap-2.5"><i class="fas fa-lock text-sm"></i> Confidential Cases</span>
            <span
                class="badge-ring relative bg-red-500 text-white text-[10px] font-semibold px-1.5 py-0.5 rounded-full leading-none">3</span>
        </a>
        <a href="#" class="sidebar-conf flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-violet-200/50">
            <i class="fas fa-search text-sm"></i> Case Search
        </a>

        <p class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-widest text-white/30 font-medium">Programs</p>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-capsules text-sm"></i> AICS</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-home text-sm"></i> 4Ps</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-briefcase text-sm"></i> SLP</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-utensils text-sm"></i> SFP</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-school text-sm"></i> Day Care</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-user-friends text-sm"></i> Senior Citizen</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-wheelchair text-sm"></i> PWD</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-user text-sm"></i> Solo Parent</a>

        <p class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-widest text-white/30 font-medium">Reports</p>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-file-alt text-sm"></i> Reports</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-map-marked-alt text-sm"></i> Geographic</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-money-bill text-sm"></i> Budget Monitor</a>
        </nav>

        <!-- User footer -->
        <div class="px-4 py-3 border-t border-white/10 flex items-center gap-3">
            <div
                class="w-8 h-8 rounded-full bg-violet-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                MP</div>
            <div class="min-w-0">
                <p class="text-white text-xs font-medium truncate">Ma. Teresa C. Ponclara </p>
                <p class="text-violet-300/60 text-[10px]">MSWDO Head</p>
            </div>
            <a href="logout.php" class="ml-auto text-white/30 hover:text-white/70 text-sm transition-colors" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>

    <?php endif; ?>

    <!-- access by staff -->
    <?php if ($role === 'Staff'): ?>
        <a href="staffdashboard.php"
            class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/70 text-sm <?= $current_page == 'staffdashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie text-sm"></i> Dashboard
        </a>
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-users text-sm"></i> Clients
        </a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent">
            <i class="fas fa-list text-sm"></i> Availments
        </a>

        <p class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-widest text-white/30 font-medium">Programs</p>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-capsules text-sm"></i> AICS</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-home text-sm"></i> 4Ps</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-briefcase text-sm"></i> SLP</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-utensils text-sm"></i> SFP</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-school text-sm"></i> Day Care</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-user-friends text-sm"></i> Senior Citizen</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-wheelchair text-sm"></i> PWD</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-user text-sm"></i> Solo Parent</a>

        <p class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-widest text-white/30 font-medium">Reports</p>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-file-alt text-sm"></i> Reports</a>
        <a href="#"
            class="sidebar-item flex items-center gap-2.5 px-3 py-2 rounded text-[13px] text-white/60 border-l-[3px] border-transparent"><i
                class="fas fa-money-bill text-sm"></i> Budget Monitor</a>
        </nav>

        <!-- User footer -->
        <div class="px-4 py-3 border-t border-white/10 flex items-center gap-3">
            <div
                class="w-8 h-8 rounded-full bg-teal-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                AR</div>
            <div class="min-w-0">
                <p class="text-white text-xs font-medium truncate">Ana Reyes</p>
                <p class="text-white/40 text-[10px]">Staff</p>
            </div>
            <a href="logout.php" class="ml-auto text-white/30 hover:text-white/70 text-sm transition-colors" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    <?php endif; ?>


    </nav>

</aside>