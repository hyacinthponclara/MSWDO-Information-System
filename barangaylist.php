<?php
require 'auth.php';
requireRole(['Admin', 'Social Worker', 'Staff']);
require 'db_connect.php';

$stmt = $pdo->prepare("
    SELECT
        b.barangay_id,
        b.barangay_name,
        COUNT(a.availment_id) AS total_availments
    FROM BARANGAY b
    LEFT JOIN CLIENT c  ON c.brgy_id    = b.barangay_id
    LEFT JOIN AVAILMENT a ON a.client_id = c.client_id
    GROUP BY b.barangay_id, b.barangay_name
    ORDER BY b.barangay_name ASC
");
$stmt->execute();

$barangays = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_barangays = count($barangays);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Barangays List – MSWDO San Enrique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans:['DM Sans','sans-serif'], serif:['DM Serif Display','serif'] },
          colors: {
            navy:  { DEFAULT:'#0B2545', 50:'#E8EDF5', 100:'#C5D1E6', 400:'#3A5F93', 500:'#163566', 600:'#0B2545', 700:'#091D38' },
            gold:  { DEFAULT:'#C49A2A', 400:'#C49A2A' },
            slate2:'#F4F7FC',
          },
          keyframes: {
            fadeUp: { '0%':{ opacity:'0', transform:'translateY(10px)' }, '100%':{ opacity:'1', transform:'translateY(0)' } },
            fadeIn: { '0%':{ opacity:'0' }, '100%':{ opacity:'1' } },
          },
          animation: {
            'fade-up'  : 'fadeUp 0.35s ease both',
            'fade-up-1': 'fadeUp 0.35s 0.05s ease both',
            'fade-up-2': 'fadeUp 0.35s 0.10s ease both',
            'fade-up-3': 'fadeUp 0.35s 0.15s ease both',
            'fade-in'  : 'fadeIn 0.2s ease both',
          }
        }
      }
    }
  </script>
  <style>
    body { font-family:'DM Sans',sans-serif; background:#F4F7FC; }
    .sidebar-item { transition:all .15s; }
    .sidebar-item:hover { background:rgba(255,255,255,.07); color:rgba(255,255,255,.95); }
    .sidebar-item.active { background:rgba(29,111,164,.28); border-left-color:#C49A2A; color:#fff; }
    .barangay-row { transition:all .15s ease; }
    .barangay-row:hover { background:#F0F4FA; }
    .barangay-row:hover .row-arrow { opacity:1; transform:translateX(0); }
    .row-arrow { opacity:0; transform:translateX(-4px); transition:all .15s ease; }
    ::-webkit-scrollbar { width:4px; }
    ::-webkit-scrollbar-thumb { background:rgba(255,255,255,.15); border-radius:2px; }
  </style>
</head>
<body class="bg-slate2 min-h-screen flex">

  <?php require 'sidebar.php'; ?>

  <div class="ml-64 flex-1 flex flex-col min-h-screen">

    <header class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 sticky top-0 z-20 animate-fade-up">
      <div class="flex items-center gap-2 text-[13px]">
        <span class="text-navy-600 font-semibold">Barangay Fund Requests</span>
      </div>
    </header>

    <main class="flex-1 p-6 flex flex-col gap-6">
      <div class="animate-fade-up">
        <h1 class="text-2xl font-serif text-navy-600 tracking-tight">Barangay Directory</h1>
        <p class="text-[13px] text-slate-400 mt-0.5">Request activity funds per barangay</p>
      </div>

      <div class="animate-fade-up-1 bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 bg-slate-50/60">
          <p class="text-[13px] font-semibold text-navy-600">
            <i class="fas fa-list-ul mr-2 text-navy-400"></i>All Barangays
          </p>
          <span class="text-[11px] text-slate-400 bg-white/60 px-2 py-0.5 rounded-full">
            <?= $total_barangays ?> records
          </span>
        </div>

        <table class="w-full text-[13px]">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/30">
              <th class="px-5 py-3 text-left">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Barangay ID</span>
              </th>
              <th class="px-5 py-3 text-left">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Barangay Name</span>
              </th>
              <th class="px-5 py-3 text-right">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Availments</span>
              </th>
              <th class="px-5 py-3 text-center">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Request Fund</span>
              </th>
            </tr>
          </thead>
          <tbody>
            <?php
            foreach ($barangays as $row):
                $formatted_count = number_format($row['total_availments']);
            ?>
            <tr class="barangay-row border-b border-slate-100 group">

              <td class="px-5 py-4">
                <span class="text-[12px] font-mono font-medium text-navy-500 bg-navy-50 px-2 py-0.5 rounded-md">
                    BRG-<?= str_pad($row['barangay_id'], 3, '0', STR_PAD_LEFT) ?>
                </span>
              </td>

              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <span class="font-semibold text-navy-700 text-[14px] tracking-tight">
                    <?= htmlspecialchars($row['barangay_name']) ?>
                  </span>
                  <i class="fas fa-arrow-right row-arrow text-navy-400 text-[11px] ml-1"></i>
                </div>
              </td>

              <td class="px-5 py-4 text-right">
                <span class="font-bold text-navy-700 bg-navy-50 px-2.5 py-1 rounded-lg text-[13px] inline-block">
                  <?= $formatted_count ?>
                </span>
              </td>

              <td class="px-5 py-4 text-center">
                <a href="barangayfunds.php?barangay_id=<?= $row['barangay_id'] ?>&barangay_name=<?= urlencode($row['barangay_name']) ?>"
                   class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-white bg-navy-600 rounded-full px-4 py-1.5 hover:bg-navy-500 transition-all shadow-sm hover:shadow-md">
                   <i class="fas fa-plus-circle text-[11px]"></i> Request Fund
                </a>
              </td>

            </tr>
            <?php endforeach; ?>

            <?php if (empty($barangays)): ?>
            <tr>
              <td colspan="4" class="px-5 py-8 text-center text-slate-400 text-[13px]">
                <i class="fas fa-inbox text-2xl mb-2 block"></i>
                No barangays found in the database.
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>

    <footer class="border-t border-slate-200 bg-white px-6 py-3 flex items-center justify-between text-[11px] text-slate-400">
      <span>MSWDO San Enrique Information System</span>
    </footer>
  </div>

</body>
</html>