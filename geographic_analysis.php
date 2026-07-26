<?php
require 'auth.php';
requireRole(['Admin']); 
require 'db_connect.php';

$barangayNames = ['Bagonawa', 'Baliwagan', 'Batuan', 'Guintorilan', 'Nayon',
                   'Poblacion', 'Sibucao', 'Tabao Baybay', 'Tabao Rizal', 'Tibsoc'];

function emptyBrgyRecord() {
    return ['count' => 0, 'amount' => 0.0, 'pwd' => 0, 'senior' => 0,
            'solo' => 0, 'fourPs' => 0, 'beneficiaries' => []];
}

function countByBarangay(PDO $pdo, string $table, string $availmentFk = 'availment_id') {
    $sql = "
        SELECT b.barangay_name AS barangay, COUNT(*) AS cnt
        FROM $table t
        JOIN availment a ON a.availment_id = t.$availmentFk
        JOIN client c ON c.client_id = a.client_id
        JOIN barangay b ON b.barangay_id = c.brgy_id
        GROUP BY b.barangay_name
    ";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
    return $rows; // ['Poblacion' => 15, ...]
}

$pwdByBrgy    = countByBarangay($pdo, 'PWD');
$seniorByBrgy = countByBarangay($pdo, 'SENIOR');
$soloByBrgy   = countByBarangay($pdo, 'SOLO_PARENT');
$fourPsByBrgy = countByBarangay($pdo, 'FOUR_PS');

function applyProfileCounts(array &$record, string $brgy, array $pwd, array $senior, array $solo, array $fourPs) {
    $record['pwd']    = (int)($pwd[$brgy]    ?? 0);
    $record['senior'] = (int)($senior[$brgy] ?? 0);
    $record['solo']   = (int)($solo[$brgy]   ?? 0);
    $record['fourPs'] = (int)($fourPs[$brgy] ?? 0);
}

// ── AICS SUBTYPES ───────────────────────────────────────────────────────
$aicsSubtypes = [
    'aics_financial'   => ['table' => 'AICS_FINANCIAL',   'label' => 'Financial'],
    'aics_burial'      => ['table' => 'AICS_BURIAL',      'label' => 'Burial'],
    'aics_medical'     => ['table' => 'AICS_MEDICAL',     'label' => 'Medical'],
    'aics_livelihood'  => ['table' => 'AICS_LIVELIHOOD',  'label' => 'Livelihood'],
    'aics_educational' => ['table' => 'AICS_EDUCATIONAL', 'label' => 'Educational'],
];

$programData = [];

foreach ($aicsSubtypes as $key => $meta) {
    // Seed every barangay with a zeroed record so the map always has all 10
    $data = [];
    foreach ($barangayNames as $b) $data[$b] = emptyBrgyRecord();

    $stmt = $pdo->prepare("
        SELECT
            b.barangay_name AS barangay,
            c.cl_firstname, c.cl_lastname, c.cl_age,
            a.av_amount, a.av_status
        FROM {$meta['table']} t
        JOIN availment a ON a.availment_id = t.availment_id
        JOIN client c ON c.client_id = a.client_id
        JOIN barangay b ON b.barangay_id = c.brgy_id
    ");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $b = $row['barangay'];
        if (!isset($data[$b])) $data[$b] = emptyBrgyRecord(); // barangay outside the 10 mapped ones
        $data[$b]['count']++;
        $data[$b]['amount'] += (float)$row['av_amount'];
        $data[$b]['beneficiaries'][] = [
            'name'    => trim($row['cl_firstname'] . ' ' . $row['cl_lastname']),
            'age'     => (int)$row['cl_age'],
            'remarks' => $row['av_status'],
        ];
    }
    foreach ($barangayNames as $b) {
        applyProfileCounts($data[$b], $b, $pwdByBrgy, $seniorByBrgy, $soloByBrgy, $fourPsByBrgy);
    }
    $programData[$key] = $data;
}

// ── WOMEN AND CHILDREN ──────────────────────────────────────────────────
$women = [];
foreach ($barangayNames as $b) $women[$b] = emptyBrgyRecord();

$wcStmt = $pdo->query("
    SELECT
        b.barangay_name AS barangay,
        c.cl_firstname, c.cl_lastname, c.cl_age,
        a.av_amount, wc.wc_status
    FROM women_and_children wc
    JOIN availment a ON a.availment_id = wc.availment_id
    JOIN client c ON c.client_id = a.client_id
    JOIN barangay b ON b.barangay_id = c.brgy_id
");
foreach ($wcStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $b = $row['barangay'];
    if (!isset($women[$b])) $women[$b] = emptyBrgyRecord();
    $women[$b]['count']++;
    $women[$b]['amount'] += (float)$row['av_amount'];
    $women[$b]['beneficiaries'][] = [
        'name'    => trim($row['cl_firstname'] . ' ' . $row['cl_lastname']),
        'age'     => (int)$row['cl_age'],
        'remarks' => $row['wc_status'],
    ];
}
foreach ($barangayNames as $b) {
    applyProfileCounts($women[$b], $b, $pwdByBrgy, $seniorByBrgy, $soloByBrgy, $fourPsByBrgy);
}
$programData['women_child'] = $women;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Geographic Analysis – MSWDO San Enrique, Negros Occidental</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['DM Sans', 'sans-serif'], serif: ['DM Serif Display', 'serif'] },
                    colors: {
                        green: {
                            DEFAULT: '#1A5C3A',
                            50: '#EEF6F0',
                            100: '#D4E8DC',
                            200: '#A8D0B8',
                            300: '#7DB895',
                            400: '#52A071',
                            500: '#1A5C3A',
                            600: '#154A2E',
                            700: '#103722',
                            800: '#0A2517',
                            900: '#05120B'
                        },
                        gold: { DEFAULT: '#C49A2A', 400: '#C49A2A' },
                        sage: '#F0F6F2',
                    },
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'DM Sans', sans-serif;
            background: #F0F6F2;
        }
        .sidebar-item {
            transition: all .15s;
        }
        .sidebar-item:hover {
            background: rgba(26, 92, 58, .08);
            color: #1A5C3A;
        }
        .sidebar-item.active {
            background: rgba(26, 92, 58, .12);
            border-left-color: #C49A2A;
            color: #1A5C3A;
        }

        /* Geographic analysis custom styles */
        .wrap {
            background: #fff;
            border-radius: 1rem;
            border: 1px solid #D4E8DC;
            padding: 1.5rem;
        }
        .header {
            margin-bottom: 1rem;
        }
        .header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1A5C3A;
        }
        .header p {
            font-size: 0.875rem;
            color: #4A7A5A;
        }
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            background: #EEF6F0;
            border: 1px solid #D4E8DC;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
        }
        .toolbar label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #4A7A5A;
            margin-right: 0.25rem;
        }
        .toolbar select {
            padding: 0.4rem 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #D4E8DC;
            background: #fff;
            font-size: 0.875rem;
            color: #1A5C3A;
        }
        .btn {
            padding: 0.4rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid #1A5C3A;
            background: #1A5C3A;
            color: #fff;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .2s;
        }
        .btn:hover {
            background: #154A2E;
        }
        .btn.secondary {
            background: #fff;
            color: #1A5C3A;
            border-color: #1A5C3A;
        }
        .btn.secondary:hover {
            background: #EEF6F0;
        }
        .layout {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 1rem;
            align-items: start;
        }
        @media (max-width: 880px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }
        #map {
            height: 460px;
            border-radius: 0.75rem;
            border: 1px solid #D4E8DC;
            background: #eae6da;
        }
        .panel {
            background: #fff;
            border: 1px solid #D4E8DC;
            border-radius: 0.75rem;
            padding: 1rem;
        }
        .panel h3 {
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #4A7A5A;
            margin: 0 0 0.5rem;
        }
        .top5 {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .top5 li {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            padding: 0.4rem 0;
            border-bottom: 1px dashed #D4E8DC;
        }
        .top5 li:last-child {
            border-bottom: none;
        }
        .top5 .rank {
            display: inline-block;
            width: 1.5rem;
            color: #C49A2A;
            font-weight: 700;
        }
        #drilldown {
            margin-top: 0.75rem;
            font-size: 0.875rem;
        }
        #drilldown .empty {
            color: #94A3B8;
            font-style: italic;
        }
        #drilldown table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
        }
        #drilldown th,
        #drilldown td {
            text-align: left;
            padding: 0.3rem 0.4rem;
            font-size: 0.8rem;
            border-bottom: 1px solid #E2E8F0;
        }
        table.summary {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: #fff;
            border: 1px solid #D4E8DC;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        table.summary th,
        table.summary td {
            padding: 0.6rem 0.75rem;
            font-size: 0.875rem;
            text-align: left;
            border-bottom: 1px solid #D4E8DC;
        }
        table.summary th {
            background: #EEF6F0;
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
            color: #1A5C3A;
            font-weight: 600;
        }
        table.summary th:after {
            content: " ⇅";
            color: #94A3B8;
            font-size: 0.7rem;
        }
        table.summary tbody tr:hover {
            background: #F0F6F2;
        }

        /* Compact legend */
        .legend {
            background: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #D4E8DC;
            font-size: 10px;
            line-height: 1.3;
            max-width: 140px;
        }
        .legend i {
            width: 12px;
            height: 12px;
            display: inline-block;
            margin-right: 4px;
            border-radius: 2px;
            vertical-align: middle;
        }
        .legend b {
            font-size: 10px;
            display: block;
        }
        .legend span {
            font-size: 9px;
        }
        .leaflet-popup-content h3 {
            margin: 0 0 0.25rem;
            font-size: 1rem;
            color: #1A5C3A;
        }
        .leaflet-popup-content table {
            font-size: 0.8rem;
            border-collapse: collapse;
        }
        .leaflet-popup-content td {
            padding: 0.1rem 0.5rem 0.1rem 0;
            color: #334155;
        }
        .note {
            font-size: 0.8rem;
            color: #94A3B8;
            margin-top: 1rem;
        }

        /* ---- ENHANCED BARANGAY LABEL STYLES ---- */
        .barangay-label {
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            font-weight: 500;
            color: #1A5C3A;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(2px);
            padding: 2px 8px;
            border-radius: 12px;
            white-space: nowrap;
            border: 1px solid rgba(26, 92, 58, 0.25);
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            pointer-events: none;           /* clicks pass through to polygon */
            transition: all 0.2s ease;      /* animate hover/active changes */
            display: inline-block;          /* needed for transform */
            transform-origin: center;       /* scale from center */
        }

        /* Hover state (when mouse is over the polygon) */
        .barangay-label.highlighted {
            background: rgba(26, 92, 58, 0.15);
            color: #0A2517;
            font-weight: 700;
            border-color: rgba(26, 92, 58, 0.5);
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
            transform: scale(1.08);
            z-index: 1000 !important;       /* bring to front */
            pointer-events: none;
        }

        /* Active/clicked state (stays until another click) */
        .barangay-label.active {
            background: rgba(196, 154, 42, 0.25);   /* gold tint */
            color: #1A5C3A;
            font-weight: 700;
            border-color: #C49A2A;
            box-shadow: 0 2px 8px rgba(196, 154, 42, 0.3);
            transform: scale(1.1);
            z-index: 1000 !important;
            pointer-events: none;
        }
    </style>
</head>

<body class="min-h-screen flex">

    <!-- SIDEBAR (PHP include) -->
    <?php require 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="ml-64 flex-1 flex flex-col min-h-screen">

        <!-- Top Bar (sticky removed) -->
        <header class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6">
            <div class="flex items-center gap-2 text-[13px]">
                <span class="text-green-600 font-semibold">Geographic Analysis</span>
            </div>
        </header>

        <main class="p-6 overflow-y-auto flex-1">
            <div class="wrap max-w-7xl mx-auto">

                <div class="header">
                    <h2><i class="fas fa-map-marked-alt mr-2 text-green-400"></i> Geographic Analysis — San Enrique, Negros Occidental</h2>
                    <p>Choropleth distribution of beneficiaries by barangay. Filter by program, click a barangay for beneficiary breakdown, or export the full table.</p>
                </div>

                <div class="toolbar">
                    <div>
                        <label for="program">Program:</label>
                        <select id="program">
                            <option value="aics_all">AICS (All)</option>
                            <option value="aics_financial">AICS Financial</option>
                            <option value="aics_burial">AICS Burial</option>
                            <option value="aics_medical">AICS Medical</option>
                            <option value="aics_livelihood">AICS Livelihood</option>
                            <option value="aics_educational">AICS Educational</option>
                            <option value="women_child">Women and Children</option>
                            <option value="combined">Combined (All AICS + Women &amp; Children)</option>
                        </select>
                    </div>
                    <div>
                        <label for="metric">Show:</label>
                        <select id="metric">
                            <option value="count">Beneficiary Count</option>
                            <option value="amount">Total Amount (₱)</option>
                        </select>
                    </div>
                    <div class="spacer flex-1"></div>
                    <button class="btn secondary" id="exportBtn"><i class="fas fa-file-csv mr-1"></i> Export CSV</button>
                </div>

                <div class="layout">
                    <div id="map"></div>

                    <div class="panel">
                        <h3>Top 5 Barangays</h3>
                        <ul class="top5" id="top5List"></ul>

                        <h3 style="margin-top: 1rem;">Barangay Detail</h3>
                        <div id="drilldown">
                            <p class="empty">Click a barangay on the map to see its beneficiary breakdown.</p>
                        </div>
                    </div>
                </div>

                <table class="summary" id="summaryTable">
                    <thead>
                        <tr>
                            <th data-key="name">Barangay</th>
                            <th data-key="count">Beneficiaries</th>
                            <th data-key="amount">Total Amount</th>
                            <th data-key="pwd">PWD Count</th>
                            <th data-key="senior">Senior Count</th>
                            <th data-key="solo">Solo Parent Count</th>
                            <th data-key="4ps">4Ps Count</th>
                        </tr>
                    </thead>
                    <tbody id="summaryBody"></tbody>
                </table>

                <div class="note"><i class="fas fa-info-circle mr-1"></i> Boundary source: PSGC-derived barangay polygons</div>

            </div>
        </main>

        <footer class="border-t border-slate-200 bg-white px-6 py-3 text-[11px] text-slate-400">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <script>
        // ---- REAL BARANGAY BOUNDARIES (San Enrique, Negros Occidental) ----
        const barangayGeoJSON = { "type": "FeatureCollection", "features": [{ "type": "Feature", "geometry": { "type": "Polygon", "coordinates": [[[122.881641639, 10.421719385000074], [122.87585988500008, 10.429128715000045], [122.87270090000004, 10.431677552000053], [122.86592689500003, 10.423547726000038], [122.86319109100009, 10.425934129000037], [122.86214026200003, 10.424980367000048], [122.86621318000003, 10.418471095000031], [122.86281631300005, 10.410944085000038], [122.86646064600006, 10.40399369700003], [122.87103449300002, 10.403725221000057], [122.87343545800002, 10.408325988000058], [122.87629102500011, 10.410713364000058], [122.87914988500006, 10.414460277000044], [122.87968737200003, 10.418280885000057], [122.881641639, 10.421719385000074]]] }, "properties": { "adm4_en": "Bagonawa" }, "id": 604525001 }, { "type": "Feature", "geometry": { "type": "Polygon", "coordinates": [[[122.89970591800011, 10.409855611000069], [122.89538093800002, 10.41003524300004], [122.8915223250001, 10.416179280000051], [122.88272243600012, 10.414561621000074], [122.88090557500004, 10.410047544000063], [122.87629102500011, 10.410713364000058], [122.87343545800002, 10.408325988000058], [122.87103449300002, 10.403725221000057], [122.87238080200008, 10.397625419000066], [122.87129184200013, 10.39370011300002], [122.88175166000008, 10.393504603000054], [122.90253323600007, 10.396358241000028], [122.90094980200001, 10.400340579000042], [122.89970591800011, 10.409855611000069]]] }, "properties": { "adm4_en": "Baliwagan" }, "id": 604525002 }, { "type": "Feature", "geometry": { "type": "Polygon", "coordinates": [[[122.87270090000004, 10.431677552000053], [122.8772868970001, 10.43231148700005], [122.87567692100004, 10.43718815500006], [122.87622268100006, 10.439852672000029], [122.86228187400002, 10.438167600000043], [122.86217707200002, 10.432813386000023], [122.85978197100007, 10.430738086000076], [122.86319109100009, 10.425934129000037], [122.86592689500003, 10.423547726000038], [122.87270090000004, 10.431677552000053]]] }, "properties": { "adm4_en": "Batuan" }, "id": 604525003 }, { "type": "Feature", "geometry": { "type": "Polygon", "coordinates": [[[122.89883366200002, 10.431773712000052], [122.8811619600001, 10.43740906000005], [122.87622268100006, 10.439852672000029], [122.87567692100004, 10.43718815500006], [122.8772868970001, 10.43231148700005], [122.87270090000004, 10.431677552000053], [122.87585988500008, 10.429128715000045], [122.881641639, 10.421719385000074], [122.89005119, 10.426185408000036], [122.8924229700001, 10.42612481000003], [122.89509294600009, 10.429487664000023], [122.89883366200002, 10.431773712000052]]] }, "properties": { "adm4_en": "Guintorilan" }, "id": 604525004 }, { "type": "Feature", "geometry": { "type": "Polygon", "coordinates": [[[122.85774629000004, 10.408596190000024], [122.85657648900006, 10.40993104200004], [122.85201487700012, 10.408880223000038], [122.85426761500004, 10.406704062000074], [122.85702913400007, 10.407238441000061], [122.85774629000004, 10.408596190000024]]] }, "properties": { "adm4_en": "Nayon" }, "id": 604525005 }, { "type": "Feature", "geometry": { "type": "Polygon", "coordinates": [[[122.86281631300005, 10.410944085000038], [122.86621318000003, 10.418471095000031], [122.86214026200003, 10.424980367000048], [122.84712621400001, 10.423213926000072], [122.84129525600007, 10.417794642000047], [122.85027644100013, 10.408102704000044], [122.85201487700012, 10.408880223000038], [122.85657648900006, 10.40993104200004], [122.85774629000004, 10.408596190000024], [122.86281631300005, 10.410944085000038]]] }, "properties": { "adm4_en": "Poblacion" }, "id": 604525006 }, { "type": "Feature", "geometry": { "type": "Polygon", "coordinates": [[[122.89970591800011, 10.409855611000069], [122.89735011600008, 10.41863385800002], [122.89883366200002, 10.431773712000052], [122.89509294600009, 10.429487664000023], [122.8924229700001, 10.42612481000003], [122.89005119, 10.426185408000036], [122.881641639, 10.421719385000074], [122.87968737200003, 10.418280885000057], [122.87914988500006, 10.414460277000044], [122.87629102500011, 10.410713364000058], [122.88090557500004, 10.410047544000063], [122.88272243600012, 10.414561621000074], [122.8915223250001, 10.416179280000051], [122.89538093800002, 10.41003524300004], [122.89970591800011, 10.409855611000069]]] }, "properties": { "adm4_en": "Sibucao" }, "id": 604525007 }, { "type": "Feature", "geometry": { "type": "Polygon", "coordinates": [[[122.84712621400001, 10.423213926000072], [122.83794159200009, 10.435957591000035], [122.83343266100007, 10.432016311000039], [122.8318702270001, 10.429115126000056], [122.84129525600007, 10.417794642000047], [122.84712621400001, 10.423213926000072]]] }, "properties": { "adm4_en": "Tabao Baybay" }, "id": 604525008 }, { "type": "Feature", "geometry": { "type": "Polygon", "coordinates": [[[122.86228187400002, 10.438167600000043], [122.84052950900002, 10.438168469000061], [122.83794159200009, 10.435957591000035], [122.84712621400001, 10.423213926000072], [122.86214026200003, 10.424980367000048], [122.86319109100009, 10.425934129000037], [122.85978197100007, 10.430738086000076], [122.86217707200002, 10.432813386000023], [122.86228187400002, 10.438167600000043]]] }, "properties": { "adm4_en": "Tabao Rizal" }, "id": 604525009 }, { "type": "Feature", "geometry": { "type": "Polygon", "coordinates": [[[122.87103449300002, 10.403725221000057], [122.86646064600006, 10.40399369700003], [122.86281631300005, 10.410944085000038], [122.85774629000004, 10.408596190000024], [122.85702913400007, 10.407238441000061], [122.85730578000005, 10.402663620000054], [122.859738557, 10.394461413000045], [122.86197447200006, 10.393816601000028], [122.87129184200013, 10.39370011300002], [122.87238080200008, 10.397625419000066], [122.87103449300002, 10.403725221000057]]] }, "properties": { "adm4_en": "Tibsoc" }, "id": 604525010 }] };

        const barangayNames = barangayGeoJSON.features.map(f => f.properties.adm4_en);

        // ---- PROGRAM DATA (from database) ----
        const programData = <?= json_encode($programData) ?>;

        programData.aics_all = {};
        barangayNames.forEach(b => {
            let count = 0, amount = 0;
            ['financial', 'burial', 'medical', 'livelihood', 'educational'].forEach(sub => {
                const key = 'aics_' + sub;
                if (programData[key] && programData[key][b]) {
                    count += programData[key][b].count;
                    amount += programData[key][b].amount;
                }
            });
            programData.aics_all[b] = {
                count: count,
                amount: amount,
                pwd: programData.aics_financial[b].pwd,
                senior: programData.aics_financial[b].senior,
                solo: programData.aics_financial[b].solo,
                fourPs: programData.aics_financial[b].fourPs,
                beneficiaries: []
            };
        });

        programData.combined = {};
        barangayNames.forEach(b => {
            const aicsAll = programData.aics_all[b];
            const women = programData.women_child[b];
            programData.combined[b] = {
                count: aicsAll.count + women.count,
                amount: aicsAll.amount + women.amount,
                pwd: aicsAll.pwd,
                senior: aicsAll.senior,
                solo: aicsAll.solo,
                fourPs: aicsAll.fourPs,
                beneficiaries: []
            };
        });

        const programLabels = {
            aics_all: "AICS (All)",
            aics_financial: "AICS Financial",
            aics_burial: "AICS Burial",
            aics_medical: "AICS Medical",
            aics_livelihood: "AICS Livelihood",
            aics_educational: "AICS Educational",
            women_child: "Women and Children",
            combined: "Combined (All AICS + Women & Children)"
        };

        let currentProgram = 'aics_all';
        let currentMetric = 'count';

        // ---- MAP SETUP ----
        const map = L.map('map', { scrollWheelZoom: false }).setView([10.4135, 122.865], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors', maxZoom: 18
        }).addTo(map);

        let geoLayer;
        const legend = L.control({ position: 'bottomright' });

        function activeData() { return programData[currentProgram]; }
        function valueOf(b) { return activeData()[b][currentMetric]; }

        function getColor(value, max) {
            const t = max ? value / max : 0;
            return t > 0.8 ? '#1b4d3e' : t > 0.6 ? '#2f6b4f' : t > 0.4 ? '#5a9279' : t > 0.2 ? '#9cc2ab' : '#dcebe2';
        }

        // Store references to label markers
        const labelMarkers = {};

        // Keep track of the currently active label (so we can remove its class)
        let activeLabelName = null;

        function setActiveLabel(name) {
            // Remove active class from previous label
            if (activeLabelName && labelMarkers[activeLabelName]) {
                const prevEl = labelMarkers[activeLabelName].getElement();
                if (prevEl) prevEl.classList.remove('active');
            }
            // Add active class to new label
            if (name && labelMarkers[name]) {
                const el = labelMarkers[name].getElement();
                if (el) el.classList.add('active');
            }
            activeLabelName = name;
        }

        function styleFeature(feature) {
            const name = feature.properties.adm4_en;
            const max = Math.max(...barangayNames.map(b => valueOf(b)), 1);
            return { fillColor: getColor(valueOf(name), max), weight: 2, opacity: 1, color: '#3a3a32', fillOpacity: 0.85 };
        }

        function onEachFeature(feature, layer) {
            const name = feature.properties.adm4_en;
            const d = activeData()[name];
            layer.bindPopup(
                `<h3>Brgy. ${name}</h3><table>
                <tr><td>Program</td><td><b>${programLabels[currentProgram]}</b></td></tr>
                <tr><td>Beneficiaries</td><td><b>${d.count}</b></td></tr>
                <tr><td>Total Amount</td><td><b>₱${d.amount.toLocaleString()}</b></td></tr>
                <tr><td>PWD Count</td><td><b>${d.pwd}</b></td></tr>
                <tr><td>Senior Count</td><td><b>${d.senior}</b></td></tr>
                </table>`
            );
            layer.on({
                mouseover: function(e) {
                    e.target.setStyle({ weight: 3, color: '#c97a3d', fillOpacity: 0.95 });
                    // Highlight corresponding label
                    if (labelMarkers[name]) {
                        const el = labelMarkers[name].getElement();
                        if (el) el.classList.add('highlighted');
                    }
                },
                mouseout: function(e) {
                    geoLayer.resetStyle(e.target);
                    // Remove hover highlight
                    if (labelMarkers[name]) {
                        const el = labelMarkers[name].getElement();
                        if (el) el.classList.remove('highlighted');
                    }
                },
                click: function() {
                    showDrilldown(name);
                    // Set this label as the active one
                    setActiveLabel(name);
                }
            });
        }

        function drawLayer() {
            if (geoLayer) map.removeLayer(geoLayer);
            geoLayer = L.geoJSON(barangayGeoJSON, { style: styleFeature, onEachFeature }).addTo(map);
        }
        drawLayer();
        const muniBounds = geoLayer.getBounds();
        map.fitBounds(muniBounds, { padding: [10, 10] });

        // Lock view + mask outside municipality
        map.setMaxBounds(muniBounds.pad(0.25));
        map.options.maxBoundsViscosity = 1.0;
        map.setMinZoom(12);
        map.setMaxZoom(17);
        const outerRing = [[-85, -360], [-85, 360], [85, 360], [85, -360]];
        const holes = barangayGeoJSON.features.map(f => f.geometry.coordinates[0].map(([lng, lat]) => [lat, lng]));
        L.polygon([outerRing, ...holes], { stroke: false, fillColor: '#cfc9b8', fillOpacity: 0.92, interactive: false }).addTo(map);
        L.polygon(holes, { color: '#1f2a24', weight: 2.5, fill: false, interactive: false }).addTo(map);

        legend.onAdd = function () {
            const div = L.DomUtil.create('div', 'legend');
            const max = Math.max(...barangayNames.map(b => valueOf(b)), 1);
            const grades = [0, 0.2, 0.4, 0.6, 0.8];
            const unit = currentMetric === 'amount' ? '₱' : '';
            let html = `<b>${programLabels[currentProgram]}</b><br><span>${currentMetric === 'amount' ? 'Total Amount' : 'Beneficiary Count'}</span><br>`;
            grades.forEach((g, i) => {
                const from = Math.round(g * max);
                const to = i < grades.length - 1 ? Math.round(grades[i + 1] * max) : max;
                html += `<i style="background:${getColor((g + 0.01) * max, max)}"></i>${unit}${from.toLocaleString()}&ndash;${unit}${to.toLocaleString()}<br>`;
            });
            div.innerHTML = html;
            return div;
        };
        legend.addTo(map);

        // ---- CREATE BEAUTIFUL PERMANENT BARANGAY LABELS ----
        barangayGeoJSON.features.forEach(feature => {
            const name = feature.properties.adm4_en;
            const coords = feature.geometry.coordinates[0];
            let latSum = 0, lngSum = 0;
            coords.forEach(c => { lngSum += c[0]; latSum += c[1]; });
            const center = [latSum / coords.length, lngSum / coords.length];

            const labelIcon = L.divIcon({
                className: 'barangay-label',
                html: name,
                iconSize: null,
                iconAnchor: [0, 0]
            });

            const marker = L.marker(center, { icon: labelIcon, interactive: false }).addTo(map);
            labelMarkers[name] = marker;
        });

        // ---- TOP 5 PANEL ----
        function renderTop5() {
            const ranked = barangayNames.map(b => ({ name: b, value: valueOf(b) })).sort((a, b) => b.value - a.value).slice(0, 5);
            const unit = currentMetric === 'amount' ? '₱' : '';
            document.getElementById('top5List').innerHTML = ranked.map((r, i) =>
                `<li><span><span class="rank">${i + 1}.</span>${r.name}</span><b>${unit}${r.value.toLocaleString()}</b></li>`
            ).join('');
        }

        // ---- DRILLDOWN PANEL ----
        function showDrilldown(name) {
            const d = activeData()[name];
            const box = document.getElementById('drilldown');
            if (currentProgram === 'aics_all' || currentProgram === 'combined') {
                box.innerHTML = `<b>Brgy. ${name}</b><br><span style="color:#777">${programLabels[currentProgram]} total: ${d.count} beneficiaries, ₱${d.amount.toLocaleString()}</span><br><span style="color:#999;font-size:12px;">Select a specific subtype to view individual records.</span>`;
                return;
            }
            let rows = d.beneficiaries.map(b => `<tr><td>${b.name}</td><td>${b.age}</td><td>${b.remarks}</td></tr>`).join('');
            if (!rows) rows = `<tr><td colspan="3" style="color:#999;">No beneficiary records for this program.</td></tr>`;
            box.innerHTML = `<b>Brgy. ${name}</b> — ${programLabels[currentProgram]}<br>
            <span style="color:#777">${d.count} beneficiaries · ₱${d.amount.toLocaleString()} total</span>
            <table><thead><tr><th>Name</th><th>Age</th><th>Status</th></tr></thead><tbody>${rows}</tbody></table>`;
        }

        // ---- SUMMARY TABLE ----
        let sortKey = 'count', sortAsc = false;

        function renderSummary() {
            const data = activeData();
            let rows = barangayNames.map(b => ({ name: b, ...data[b] }));
            rows.sort((a, b) => {
                const v = sortKey === 'name' ? a.name.localeCompare(b.name) : a[sortKey] - b[sortKey];
                return sortAsc ? v : -v;
            });
            document.getElementById('summaryBody').innerHTML = rows.map(r =>
                `<tr><td>${r.name}</td><td>${r.count}</td><td>₱${r.amount.toLocaleString()}</td><td>${r.pwd}</td><td>${r.senior}</td><td>${r.solo || 0}</td><td>${r.fourPs || 0}</td></tr>`
            ).join('');
        }
        document.querySelectorAll('#summaryTable th').forEach(th => {
            th.addEventListener('click', () => {
                const key = th.dataset.key;
                sortAsc = (sortKey === key) ? !sortAsc : false;
                sortKey = key;
                renderSummary();
            });
        });

        // ---- REFRESH ALL ----
        function refreshAll() {
            // Clear any active label highlight
            setActiveLabel(null);
            drawLayer();
            map.removeControl(legend);
            legend.addTo(map);
            renderTop5();
            renderSummary();
            document.getElementById('drilldown').innerHTML = '<p class="empty">Click a barangay on the map to see its beneficiary breakdown.</p>';
        }
        refreshAll();

        document.getElementById('program').addEventListener('change', e => { currentProgram = e.target.value; refreshAll(); });
        document.getElementById('metric').addEventListener('change', e => { currentMetric = e.target.value; refreshAll(); });

        // ---- CSV EXPORT ----
        document.getElementById('exportBtn').addEventListener('click', () => {
            const data = activeData();
            let csv = "Barangay,Beneficiaries,Total Amount,PWD Count,Senior Count,Solo Parent Count,4Ps Count\n";
            barangayNames.forEach(b => {
                const r = data[b];
                csv += `${b},${r.count},${r.amount},${r.pwd},${r.senior},${r.solo||0},${r.fourPs||0}\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `san_enrique_${currentProgram}_report.csv`;
            a.click();
            URL.revokeObjectURL(url);
        });
    </script>

</body>

</html>