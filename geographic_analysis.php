<?php
require 'auth.php';
requireRole(['Admin']);
require 'db_connect.php';
require 'export_prepared_by.php';

$brgyStmt = $pdo->prepare("SELECT barangay_id, barangay_name FROM barangay ORDER BY barangay_name");
$brgyStmt->execute();
$barangayRows = $brgyStmt->fetchAll(PDO::FETCH_ASSOC);
$barangayNamesList = array_column($barangayRows, 'barangay_name');

$dataSql = "
    SELECT 'aics_financial' AS prog_key, b.barangay_name, c.client_id,
           CONCAT(c.cl_firstname, ' ', c.cl_lastname) AS full_name, c.cl_age,
           c.cl_is_pwd, c.cl_is_senior, c.cl_is_soloparent, c.cl_is_4ps,
           a.av_amount, a.av_status
    FROM aics_financial af
    JOIN availment a ON a.availment_id = af.availment_id
    JOIN client c ON c.client_id = a.client_id
    JOIN barangay b ON b.barangay_id = c.brgy_id
    WHERE a.av_status IN ('Approved', 'Released')

    UNION ALL

    SELECT 'aics_burial', b.barangay_name, c.client_id,
           CONCAT(c.cl_firstname, ' ', c.cl_lastname), c.cl_age,
           c.cl_is_pwd, c.cl_is_senior, c.cl_is_soloparent, c.cl_is_4ps,
           a.av_amount, a.av_status
    FROM aics_burial ab
    JOIN availment a ON a.availment_id = ab.availment_id
    JOIN client c ON c.client_id = a.client_id
    JOIN barangay b ON b.barangay_id = c.brgy_id
    WHERE a.av_status IN ('Approved', 'Released')

    UNION ALL

    SELECT 'aics_medical', b.barangay_name, c.client_id,
           CONCAT(c.cl_firstname, ' ', c.cl_lastname), c.cl_age,
           c.cl_is_pwd, c.cl_is_senior, c.cl_is_soloparent, c.cl_is_4ps,
           a.av_amount, a.av_status
    FROM aics_medical am
    JOIN availment a ON a.availment_id = am.availment_id
    JOIN client c ON c.client_id = a.client_id
    JOIN barangay b ON b.barangay_id = c.brgy_id
    WHERE a.av_status IN ('Approved', 'Released')

    UNION ALL

    SELECT 'aics_livelihood', b.barangay_name, c.client_id,
           CONCAT(c.cl_firstname, ' ', c.cl_lastname), c.cl_age,
           c.cl_is_pwd, c.cl_is_senior, c.cl_is_soloparent, c.cl_is_4ps,
           a.av_amount, a.av_status
    FROM aics_livelihood al
    JOIN availment a ON a.availment_id = al.availment_id
    JOIN client c ON c.client_id = a.client_id
    JOIN barangay b ON b.barangay_id = c.brgy_id
    WHERE a.av_status IN ('Approved', 'Released')

    UNION ALL

    SELECT 'aics_educational', b.barangay_name, c.client_id,
           CONCAT(c.cl_firstname, ' ', c.cl_lastname), c.cl_age,
           c.cl_is_pwd, c.cl_is_senior, c.cl_is_soloparent, c.cl_is_4ps,
           a.av_amount, a.av_status
    FROM availment a
    JOIN client c ON c.client_id = a.client_id
    JOIN barangay b ON b.barangay_id = c.brgy_id
    WHERE a.program_id = 2 AND a.av_status IN ('Approved', 'Released')

    UNION ALL

    SELECT 'women_child', b.barangay_name, c.client_id,
           CONCAT(c.cl_firstname, ' ', c.cl_lastname), c.cl_age,
           c.cl_is_pwd, c.cl_is_senior, c.cl_is_soloparent, c.cl_is_4ps,
           COALESCE(a.av_amount, 0), COALESCE(a.av_status, wc.wc_status)
    FROM woman_and_children wc
    JOIN client c ON c.client_id = wc.client_id
    JOIN barangay b ON b.barangay_id = c.brgy_id
    LEFT JOIN availment a ON a.availment_id = wc.availment_id
";
$dataStmt = $pdo->prepare($dataSql);
$dataStmt->execute();
$beneficiaryRows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

$leafPrograms = ['aics_financial', 'aics_burial', 'aics_medical', 'aics_livelihood', 'aics_educational', 'women_child'];

function aggregate_program_rows(array $rows, array $barangayNamesList): array
{
    $out = [];
    foreach ($barangayNamesList as $bn) {
        $out[$bn] = [
            'count' => 0,
            'amount' => 0.0,
            'pwd' => 0,
            'senior' => 0,
            'solo' => 0,
            'fourPs' => 0,
            'beneficiaries' => [],
            '_clients' => [],
            '_pwd' => [],
            '_senior' => [],
            '_solo' => [],
            '_fourPs' => [],
        ];
    }

    foreach ($rows as $r) {
        $bn = $r['barangay_name'];
        if (!isset($out[$bn]))
            continue;

        $cid = $r['client_id'];
        $amt = (float) $r['av_amount'];

        $out[$bn]['amount'] += $amt;
        $out[$bn]['beneficiaries'][] = [
            'name' => $r['full_name'],
            'age' => $r['cl_age'] !== null ? (int) $r['cl_age'] : null,
            'amount' => $amt,
            'remarks' => $r['av_status'],
        ];

        $out[$bn]['_clients'][$cid] = true;
        if ($r['cl_is_pwd'])
            $out[$bn]['_pwd'][$cid] = true;
        if ($r['cl_is_senior'])
            $out[$bn]['_senior'][$cid] = true;
        if ($r['cl_is_soloparent'])
            $out[$bn]['_solo'][$cid] = true;
        if ($r['cl_is_4ps'])
            $out[$bn]['_fourPs'][$cid] = true;
    }

    foreach ($barangayNamesList as $bn) {
        $out[$bn]['count'] = count($out[$bn]['_clients']);
        $out[$bn]['pwd'] = count($out[$bn]['_pwd']);
        $out[$bn]['senior'] = count($out[$bn]['_senior']);
        $out[$bn]['solo'] = count($out[$bn]['_solo']);
        $out[$bn]['fourPs'] = count($out[$bn]['_fourPs']);
        unset($out[$bn]['_clients'], $out[$bn]['_pwd'], $out[$bn]['_senior'], $out[$bn]['_solo'], $out[$bn]['_fourPs']);
    }

    return $out;
}

$rowsByProgKey = [];
foreach ($beneficiaryRows as $r) {
    $rowsByProgKey[$r['prog_key']][] = $r;
}

$programData = [];
foreach ($leafPrograms as $pk) {
    $programData[$pk] = aggregate_program_rows($rowsByProgKey[$pk] ?? [], $barangayNamesList);
}

$aicsSubtypes = ['aics_financial', 'aics_burial', 'aics_medical', 'aics_livelihood', 'aics_educational'];
$aicsAllRows = [];
foreach ($aicsSubtypes as $sub) {
    $aicsAllRows = array_merge($aicsAllRows, $rowsByProgKey[$sub] ?? []);
}
$programData['aics_all'] = aggregate_program_rows($aicsAllRows, $barangayNamesList);
foreach ($barangayNamesList as $bn) {
    $programData['aics_all'][$bn]['beneficiaries'] = []; // mixed subtypes; no single drilldown list
}

// combined = distinct clients across AICS (all subtypes) + Women & Children, same reasoning.
$combinedRows = array_merge($aicsAllRows, $rowsByProgKey['women_child'] ?? []);
$programData['combined'] = aggregate_program_rows($combinedRows, $barangayNamesList);
foreach ($barangayNamesList as $bn) {
    $programData['combined'][$bn]['beneficiaries'] = [];
}

$barangayNamesJson = json_encode($barangayNamesList, JSON_UNESCAPED_UNICODE);
$programDataJson = json_encode($programData, JSON_UNESCAPED_UNICODE);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Geographic Analysis – MSWDO San Enrique, Negros Occidental</title>
    <script src="https://cdn.tailwindcss.com">
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Calibri&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js">
    </script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Calibri', 'Arial', 'sans-serif'], serif: ['Calibri', 'Arial', 'sans-serif'] },
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
            font-family: 'Calibri', Arial, sans-serif;
            font-size: 11pt;
            background: #F0F6F2;
        }

        /* Sidebar Styles */
        .sidebar {
            background: linear-gradient(180deg, #0A2517 0%, #103722 50%, #154A2E 100%);
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 256px;
            z-index: 40;
            transition: transform 0.3s ease;
            overflow-y: auto;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-family: 'Calibri', serif;
            color: #fff;
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }

        .sidebar-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.75rem;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.875rem;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }

        .sidebar-item:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .sidebar-item.active {
            background: rgba(255, 255, 255, 0.12);
            border-left-color: #C49A2A;
            color: #fff;
            font-weight: 500;
        }

        .sidebar-item i {
            width: 20px;
            text-align: center;
        }

        /* Mobile sidebar overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: transparent;
            z-index: 35;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* Main Content */
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

        /* Toolbar */
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
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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

        /* Summary Table */
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

        table.summary th:nth-child(3),
        table.summary td:nth-child(3) {
            text-align: right;
        }

        table.summary th:nth-child(2),
        table.summary td:nth-child(2),
        table.summary th:nth-child(4),
        table.summary td:nth-child(4),
        table.summary th:nth-child(5),
        table.summary td:nth-child(5),
        table.summary th:nth-child(6),
        table.summary td:nth-child(6),
        table.summary th:nth-child(7),
        table.summary td:nth-child(7) {
            text-align: center;
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

        /* Legend */
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

        /* Barangay Labels */
        .barangay-label {
            font-family: 'Calibri', Arial, sans-serif;
            font-size: 11pt;
            font-size: 11px;
            font-weight: 500;
            color: #1A5C3A;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(2px);
            padding: 2px 8px;
            border-radius: 12px;
            white-space: nowrap;
            border: 1px solid rgba(26, 92, 58, 0.25);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
            pointer-events: none;
            transition: all 0.2s ease;
            display: inline-block;
            transform-origin: center;
        }

        .barangay-label.highlighted {
            background: rgba(26, 92, 58, 0.15);
            color: #0A2517;
            font-weight: 700;
            border-color: rgba(26, 92, 58, 0.5);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
            transform: scale(1.08);
            z-index: 1000 !important;
            pointer-events: none;
        }

        .barangay-label.active {
            background: rgba(196, 154, 42, 0.25);
            color: #1A5C3A;
            font-weight: 700;
            border-color: #C49A2A;
            box-shadow: 0 2px 8px rgba(196, 154, 42, 0.3);
            transform: scale(1.1);
            z-index: 1000 !important;
            pointer-events: none;
        }

        /* Export dropdown */
        .export-dropdown {
            position: relative;
            display: inline-block;
        }

        .export-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: #fff;
            min-width: 220px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
            border: 1px solid #D4E8DC;
            z-index: 1000;
            overflow: hidden;
        }

        .export-dropdown-content a {
            color: #1A5C3A;
            padding: 0.7rem 1rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.875rem;
            transition: background 0.15s;
            cursor: pointer;
        }

        .export-dropdown-content a:hover {
            background: #EEF6F0;
        }

        .export-dropdown-content a i {
            width: 18px;
            text-align: center;
            font-size: 0.9rem;
        }

        .export-dropdown.active .export-dropdown-content {
            display: block;
        }

        /* Mobile menu button */
        .menu-btn {
            display: none;
            background: none;
            border: none;
            color: #1A5C3A;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
        }

        /* Print letterhead - hidden on screen, shown only when printing */
        #printLetterhead {
            display: none;
        }

        /* PRINT: MAP ONLY */
        @media print {
            @page {
                size: landscape;
                margin: 0;
            }

            html,
            body {
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
                background: #fff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Hide EVERYTHING except the Leaflet map. */
            body * {
                visibility: hidden !important;
            }

            #map,
            #map * {
                visibility: visible !important;
            }

            /* Make the map occupy the entire printed page. */
            #map {
                display: block !important;
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                min-width: 0 !important;
                max-width: none !important;
                min-height: 0 !important;
                max-height: none !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                box-sizing: border-box !important;
                overflow: hidden !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            #map .leaflet-container {
                width: 100% !important;
                height: 100% !important;
                overflow: hidden !important;
            }

            #map .leaflet-map-pane,
            #map .leaflet-tile-pane,
            #map .leaflet-overlay-pane,
            #map .leaflet-shadow-pane,
            #map .leaflet-marker-pane,
            #map .leaflet-tooltip-pane,
            #map .leaflet-popup-pane,
            #map .leaflet-control-container {
                visibility: visible !important;
            }

            /* Keep the map controls, barangay labels, boundaries and legend. */
            #map .leaflet-control,
            #map .leaflet-control-attribution,
            #map .legend {
                visibility: visible !important;
            }

            /* These are intentionally not printed. */
            #printLetterhead,
            #printSignature,
            #printFooter {
                display: none !important;
                visibility: hidden !important;
            }
        }

        /* MOBILE RESPONSIVE STYLES */
        @media (max-width: 1024px) {
            .sidebar {
                left: auto;
                right: 0.75rem;
                top: 3.75rem;
                bottom: auto;
                width: 230px;
                max-height: calc(100vh - 5.5rem);
                border-radius: 0.75rem;
                transform-origin: top right;
                transform: scale(0.95) translateY(-8px);
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition: transform 0.2s ease, opacity 0.2s ease, visibility 0.2s;
            }

            .sidebar.open {
                transform: scale(1) translateY(0);
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
            }

            .ml-64 {
                margin-left: 0 !important;
            }

            .menu-btn {
                display: block;
            }

            .layout {
                grid-template-columns: 1fr;
            }

            #map {
                height: 350px;
            }
        }

        @media (max-width: 768px) {
            .wrap {
                padding: 1rem;
                border-radius: 0.75rem;
            }

            .toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
            }

            .toolbar>div {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .toolbar select {
                flex: 1;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .export-dropdown {
                width: 100%;
            }

            .export-dropdown-content {
                width: 100%;
                right: auto;
                left: 0;
            }

            .header h2 {
                font-size: 1.1rem;
            }

            .header p {
                font-size: 0.8rem;
            }

            #map {
                height: 300px;
            }

            table.summary {
                font-size: 0.75rem;
            }

            table.summary th,
            table.summary td {
                padding: 0.4rem 0.3rem;
                font-size: 0.7rem;
            }

            .spacer {
                display: none;
            }

            .barangay-label {
                font-size: 9px;
                padding: 1px 5px;
            }
        }

        @media (max-width: 480px) {
            main.p-6 {
                padding: 0.5rem;
            }

            .wrap {
                padding: 0.75rem;
                border-radius: 0.5rem;
            }

            .header h2 {
                font-size: 1rem;
            }

            .toolbar {
                padding: 0.5rem 0.75rem;
            }

            .toolbar label {
                font-size: 0.65rem;
            }

            .toolbar select {
                font-size: 0.8rem;
                padding: 0.3rem 0.5rem;
            }

            .btn {
                font-size: 0.8rem;
                padding: 0.35rem 0.75rem;
            }

            #map {
                height: 250px;
            }

            .panel h3 {
                font-size: 0.75rem;
            }

            .top5 li {
                font-size: 0.8rem;
            }

            #drilldown {
                font-size: 0.8rem;
            }

            table.summary th,
            table.summary td {
                padding: 0.3rem 0.2rem;
                font-size: 0.65rem;
            }
        }

        /* Letterhead (screen preview + print) */
        .letterhead {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            text-align: center;
            border-bottom: 1.5px solid #000;
            padding-bottom: 10px;
            margin-bottom: 14px;
            font-family: Calibri, Arial, sans-serif;
            font-size: 11pt;
        }

        .letterhead img {
            width: 64px;
            height: 64px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .letterhead .lh-text {
            flex: 1;
        }

        .letterhead .lh-text .lh-line1 {
            font-size: 12px;
            color: #000;
        }

        .letterhead .lh-text .lh-line2 {
            font-size: 12px;
            font-weight: 700;
            color: #000;
        }

        .letterhead .lh-text .lh-line3 {
            font-size: 12px;
            font-weight: 700;
            color: #000;
        }

        .letterhead .lh-text .lh-line4 {
            font-size: 12px;
            font-weight: 700;
            color: #000;
        }

        .letterhead .lh-text .lh-title {
            font-size: 14px;
            font-weight: 700;
            color: #000;
            margin-top: 8px;
            letter-spacing: 0.01em;
        }

        .letterhead .lh-text .lh-sub {
            font-size: 12px;
            color: #000;
        }

        .letterhead .lh-text .lh-meta {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }

        /* Signature block (print only) */
        #printSignature {
            display: none;
        }

        #printFooter {
            display: none;
        }

        @media print {
            #printSignature {
                display: block !important;
                margin-top: 28px;
                font-family: Calibri, Arial, sans-serif;
                font-size: 11pt;
                font-size: 12px;
                color: #000;
            }

            #printSignature .sig-space {
                height: 34px;
            }

            #printSignature .sig-name {
                font-weight: 700;
                margin-top: 4px;
                border-top: 1px solid #000;
                display: inline-block;
                padding-top: 4px;
            }

            #printFooter {
                margin-top: 18px;
                font-family: Calibri, Arial, sans-serif;
                font-size: 11pt;
                font-size: 9px;
                color: #555;
                text-align: right;
                border-top: 0.5px solid #ccc;
                padding-top: 6px;
            }
        }
    </style>
</head>

<body class="min-h-screen flex">

    <!-- SIDEBAR OVERLAY (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- Sidebar (desktop) -->
    <?php require 'sidebar.php'; ?>
    <!-- Mobile header -->
    <div class="md:hidden bg-forest-600 text-white p-3 flex items-center justify-between">
        <span class="font-serif text-xl">MSWDO</span>
        <button class="text-white"><i class="fas fa-bars text-xl"></i></button>
    </div>

    <!-- MAIN CONTENT -->
    <div class="ml-64 flex-1 flex flex-col min-h-screen">

        <!-- Top Bar -->
        <header class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-4 md:px-6">
            <div class="flex items-center gap-2">
                <span class="text-green-600 font-semibold text-sm md:text-[13px]">Geographic Analysis</span>
            </div>
            <div class="flex items-center gap-2">
                <button class="menu-btn" onclick="toggleSidebar()" aria-label="Toggle menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </header>

        <main class="p-3 md:p-6 overflow-y-auto flex-1">
            <div class="wrap max-w-7xl mx-auto">

                <!-- Letterhead: hidden on screen, shown for Print (see #printLetterhead CSS) -->
                <div class="letterhead" id="printLetterhead">
                    <img src="/logo/municipallogo.png" alt="Municipality of San Enrique logo"
                        onerror="this.onerror=null;this.src='municipallogo.png';this.onerror=function(){this.style.visibility='hidden'};">
                    <div class="lh-text">
                        <div class="lh-line1">Republic of the Philippines</div>
                        <div class="lh-line2">Province of Negros Occidental</div>
                        <div class="lh-line3">Municipality of San Enrique</div>
                        <div class="lh-line4">Municipal Social Welfare and Development Office</div>
                        <div class="lh-title">GEOGRAPHIC ANALYSIS REPORT</div>
                        <div class="lh-sub" id="lhCalendarYear">Calendar Year 2026</div>
                        <div class="lh-meta" id="lhMeta"></div>
                    </div>
                    <img src="/logo/dswdlogo.png" alt="DSWD logo"
                        onerror="this.onerror=null;this.src='dswdlogo.png';this.onerror=function(){this.style.visibility='hidden'};">
                </div>

                <div class="header">
                    <h2><i class="fas fa-map-marked-alt mr-2 text-green-400"></i> Geographic Analysis — San Enrique,
                        Negros Occidental</h2>
                    <p>Choropleth distribution of beneficiaries by barangay. Filter by program, click a barangay for
                        beneficiary breakdown, or export the full table.</p>
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
                            <option value="combined">Combined (All AICS + Women & Children)</option>
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
                    <button class="btn secondary" id="printMapBtn" title="Print Map Only">
                        <i class="fas fa-print"></i> <span class="hidden sm:inline">Print Map</span>
                    </button>
                    <div class="export-dropdown" id="exportDropdownContainer">
                        <button class="btn" id="exportDropdownBtn">
                            <i class="fas fa-download"></i> <span class="hidden sm:inline">Export</span> <i
                                class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="export-dropdown-content">
                            <a id="exportXlsx"><i class="fas fa-file-excel"></i> Microsoft Excel (.xlsx)</a>
                            <a id="exportPdf"><i class="fas fa-file-pdf"></i> PDF Document (.pdf)</a>
                            <a id="exportDocx"><i class="fas fa-file-word"></i> Word Document (.doc)</a>
                        </div>
                    </div>
                </div>

                <div class="layout">
                    <div id="map"></div>

                    <div class="panel">
                        <h3><i class="fas fa-trophy mr-1 text-gold"></i> Top 5 Barangays</h3>
                        <ul class="top5" id="top5List"></ul>

                        <h3 style="margin-top: 1rem;"><i class="fas fa-info-circle mr-1"></i> Barangay Detail</h3>
                        <div id="drilldown">
                            <p class="empty">Click a barangay on the map to see its beneficiary breakdown.</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto -mx-3 md:mx-0">
                    <table class="summary min-w-[700px] md:min-w-0" id="summaryTable">
                        <thead>
                            <tr>
                                <th data-key="name">Barangay</th>
                                <th data-key="count">Beneficiaries</th>
                                <th data-key="amount">Total Amount</th>
                                <th data-key="pwd">PWD</th>
                                <th data-key="senior">Senior</th>
                                <th data-key="solo">Solo Parent</th>
                                <th data-key="fourPs">4Ps</th>
                            </tr>
                        </thead>
                        <tbody id="summaryBody"></tbody>
                    </table>
                </div>

                <div class="note"><i class="fas fa-info-circle mr-1"></i> Boundary source: PSGC-derived barangay
                    polygons | Data as of 2024</div>

                <div id="printSignature">
                    <div>Prepared by:</div>
                    <div class="sig-space">&nbsp;</div>
                    <div class="sig-name">MA. TERESA C. PONCLARA, RSW</div>
                    <div>MSWDO</div>
                </div>

                <div id="printFooter"></div>

            </div>
        </main>

        <footer
            class="border-t border-slate-200 bg-white px-4 py-3 flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-400 gap-1">
            <span>MSWDO San Enrique Information System</span>
        </footer>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js">
    </script>
    <script>
        // MOBILE SIDEBAR TOGGLE
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }

        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('active');
        }

        const barangayNames = <?php echo $barangayNamesJson; ?>;

        const programData = <?php echo $programDataJson; ?>;

        // BARANGAY BOUNDARIES (San Enrique, Negros Occ.)
        const barangayGeoJSON = {
            "type": "FeatureCollection",
            "features": [
                {
                    "type": "Feature", "geometry": {
                        "type": "Polygon", "coordinates": [
                            [
                                [122.881641639, 10.421719385000074],
                                [122.87585988500008, 10.429128715000045],
                                [122.87270090000004, 10.431677552000053],
                                [122.86592689500003, 10.423547726000038],
                                [122.86319109100009, 10.425934129000037],
                                [122.86214026200003, 10.424980367000048],
                                [122.86621318000003, 10.418471095000031],
                                [122.86281631300005, 10.410944085000038],
                                [122.86646064600006, 10.40399369700003],
                                [122.87103449300002, 10.403725221000057],
                                [122.87343545800002, 10.408325988000058],
                                [122.87629102500011, 10.410713364000058],
                                [122.87914988500006, 10.414460277000044],
                                [122.87968737200003, 10.418280885000057],
                                [122.881641639, 10.421719385000074]
                            ]
                        ]
                    }, "properties": { "adm4_en": "Bagonawa" }, "id": 604525001
                },
                {
                    "type": "Feature", "geometry": {
                        "type": "Polygon", "coordinates": [
                            [
                                [122.89970591800011, 10.409855611000069],
                                [122.89538093800002, 10.41003524300004],
                                [122.8915223250001, 10.416179280000051],
                                [122.88272243600012, 10.414561621000074],
                                [122.88090557500004, 10.410047544000063],
                                [122.87629102500011, 10.410713364000058],
                                [122.87343545800002, 10.408325988000058],
                                [122.87103449300002, 10.403725221000057],
                                [122.87238080200008, 10.397625419000066],
                                [122.87129184200013, 10.39370011300002],
                                [122.88175166000008, 10.393504603000054],
                                [122.90253323600007, 10.396358241000028],
                                [122.90094980200001, 10.400340579000042],
                                [122.89970591800011, 10.409855611000069]
                            ]
                        ]
                    }, "properties": { "adm4_en": "Baliwagan" }, "id": 604525002
                },
                {
                    "type": "Feature", "geometry": {
                        "type": "Polygon", "coordinates": [
                            [
                                [122.87270090000004, 10.431677552000053],
                                [122.8772868970001, 10.43231148700005],
                                [122.87567692100004, 10.43718815500006],
                                [122.87622268100006, 10.439852672000029],
                                [122.86228187400002, 10.438167600000043],
                                [122.86217707200002, 10.432813386000023],
                                [122.85978197100007, 10.430738086000076],
                                [122.86319109100009, 10.425934129000037],
                                [122.86592689500003, 10.423547726000038],
                                [122.87270090000004, 10.431677552000053]
                            ]
                        ]
                    }, "properties": { "adm4_en": "Batuan" }, "id": 604525003
                },
                {
                    "type": "Feature", "geometry": {
                        "type": "Polygon", "coordinates": [
                            [
                                [122.89883366200002, 10.431773712000052],
                                [122.8811619600001, 10.43740906000005],
                                [122.87622268100006, 10.439852672000029],
                                [122.87567692100004, 10.43718815500006],
                                [122.8772868970001, 10.43231148700005],
                                [122.87270090000004, 10.431677552000053],
                                [122.87585988500008, 10.429128715000045],
                                [122.881641639, 10.421719385000074],
                                [122.89005119, 10.426185408000036],
                                [122.8924229700001, 10.42612481000003],
                                [122.89509294600009, 10.429487664000023],
                                [122.89883366200002, 10.431773712000052]
                            ]
                        ]
                    }, "properties": { "adm4_en": "Guintorilan" }, "id": 604525004
                },
                {
                    "type": "Feature", "geometry": {
                        "type": "Polygon", "coordinates": [
                            [
                                [122.85774629000004, 10.408596190000024],
                                [122.85657648900006, 10.40993104200004],
                                [122.85201487700012, 10.408880223000038],
                                [122.85426761500004, 10.406704062000074],
                                [122.85702913400007, 10.407238441000061],
                                [122.85774629000004, 10.408596190000024]
                            ]
                        ]
                    }, "properties": { "adm4_en": "Nayon" }, "id": 604525005
                },
                {
                    "type": "Feature", "geometry": {
                        "type": "Polygon", "coordinates": [
                            [
                                [122.86281631300005, 10.410944085000038],
                                [122.86621318000003, 10.418471095000031],
                                [122.86214026200003, 10.424980367000048],
                                [122.84712621400001, 10.423213926000072],
                                [122.84129525600007, 10.417794642000047],
                                [122.85027644100013, 10.408102704000044],
                                [122.85201487700012, 10.408880223000038],
                                [122.85657648900006, 10.40993104200004],
                                [122.85774629000004, 10.408596190000024],
                                [122.86281631300005, 10.410944085000038]
                            ]
                        ]
                    }, "properties": { "adm4_en": "Poblacion" }, "id": 604525006
                },
                {
                    "type": "Feature", "geometry": {
                        "type": "Polygon", "coordinates": [
                            [
                                [122.89970591800011, 10.409855611000069],
                                [122.89735011600008, 10.41863385800002],
                                [122.89883366200002, 10.431773712000052],
                                [122.89509294600009, 10.429487664000023],
                                [122.8924229700001, 10.42612481000003],
                                [122.89005119, 10.426185408000036],
                                [122.881641639, 10.421719385000074],
                                [122.87968737200003, 10.418280885000057],
                                [122.87914988500006, 10.414460277000044],
                                [122.87629102500011, 10.410713364000058],
                                [122.88090557500004, 10.410047544000063],
                                [122.88272243600012, 10.414561621000074],
                                [122.8915223250001, 10.416179280000051],
                                [122.89538093800002, 10.41003524300004],
                                [122.89970591800011, 10.409855611000069]
                            ]
                        ]
                    }, "properties": { "adm4_en": "Sibucao" }, "id": 604525007
                },
                {
                    "type": "Feature", "geometry": {
                        "type": "Polygon", "coordinates": [
                            [
                                [122.84712621400001, 10.423213926000072],
                                [122.83794159200009, 10.435957591000035],
                                [122.83343266100007, 10.432016311000039],
                                [122.8318702270001, 10.429115126000056],
                                [122.84129525600007, 10.417794642000047],
                                [122.84712621400001, 10.423213926000072]
                            ]
                        ]
                    }, "properties": { "adm4_en": "Tabao Baybay" }, "id": 604525008
                },
                {
                    "type": "Feature", "geometry": {
                        "type": "Polygon", "coordinates": [
                            [
                                [122.86228187400002, 10.438167600000043],
                                [122.84052950900002, 10.438168469000061],
                                [122.83794159200009, 10.435957591000035],
                                [122.84712621400001, 10.423213926000072],
                                [122.86214026200003, 10.424980367000048],
                                [122.86319109100009, 10.425934129000037],
                                [122.85978197100007, 10.430738086000076],
                                [122.86217707200002, 10.432813386000023],
                                [122.86228187400002, 10.438167600000043]
                            ]
                        ]
                    }, "properties": { "adm4_en": "Tabao Rizal" }, "id": 604525009
                },
                {
                    "type": "Feature", "geometry": {
                        "type": "Polygon", "coordinates": [
                            [
                                [122.87103449300002, 10.403725221000057],
                                [122.86646064600006, 10.40399369700003],
                                [122.86281631300005, 10.410944085000038],
                                [122.85774629000004, 10.408596190000024],
                                [122.85702913400007, 10.407238441000061],
                                [122.85730578000005, 10.402663620000054],
                                [122.859738557, 10.394461413000045],
                                [122.86197447200006, 10.393816601000028],
                                [122.87129184200013, 10.39370011300002],
                                [122.87238080200008, 10.397625419000066],
                                [122.87103449300002, 10.403725221000057]
                            ]
                        ]
                    }, "properties": { "adm4_en": "Tibsoc" }, "id": 604525010
                }
            ]
        };

        // PROGRAM LABELS
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

        // MAP SETUP
        const map = L.map('map', {
            scrollWheelZoom: false,
            zoomControl: true
        }).setView([10.4135, 122.865], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(map);

        let geoLayer;
        const legend = L.control({ position: 'bottomright' });

        function activeData() { return programData[currentProgram] || {}; }

        function valueOf(b) {
            const data = activeData();
            return (data[b] && typeof data[b][currentMetric] !== 'undefined') ? data[b][currentMetric] : 0;
        }

        function getColor(value, max) {
            if (currentMetric === 'count') {
                if (value >= 81) return '#1b4d3e';
                if (value >= 61) return '#2f6b4f';
                if (value >= 41) return '#5a9279';
                if (value >= 21) return '#9cc2ab';
                return '#dcebe2';
            }
            const t = max > 0 ? value / max : 0;
            if (t > 0.8) return '#1b4d3e';
            if (t > 0.6) return '#2f6b4f';
            if (t > 0.4) return '#5a9279';
            if (t > 0.2) return '#9cc2ab';
            return '#dcebe2';
        }

        const labelMarkers = {};
        let activeLabelName = null;

        function setActiveLabel(name) {
            if (activeLabelName && labelMarkers[activeLabelName]) {
                const prevEl = labelMarkers[activeLabelName].getElement();
                if (prevEl) prevEl.classList.remove('active');
            }
            if (name && labelMarkers[name]) {
                const el = labelMarkers[name].getElement();
                if (el) el.classList.add('active');
            }
            activeLabelName = name;
        }

        function styleFeature(feature) {
            const name = feature.properties.adm4_en;
            const max = Math.max(...barangayNames.map(b => valueOf(b)), 1);
            return {
                fillColor: getColor(valueOf(name), max),
                weight: 2,
                opacity: 1,
                color: '#3a3a32',
                fillOpacity: 0.85
            };
        }

        function onEachFeature(feature, layer) {
            const name = feature.properties.adm4_en;
            const d = activeData()[name] || { count: 0, amount: 0, pwd: 0, senior: 0, solo: 0, fourPs: 0 };

            layer.bindPopup(
                `<h3>Brgy. ${name}</h3>
                    <table>
                    <tr><td><i class="fas fa-layer-group"></i> Program</td><td><b>${programLabels[currentProgram]}</b></td></tr>
                    <tr><td><i class="fas fa-users"></i> Beneficiaries</td><td><b>${d.count}</b></td></tr>
                    <tr><td><i class="fas fa-peso-sign"></i> Total Amount</td><td><b>₱${formatAmount(d.amount)}</b></td></tr>
                    <tr><td><i class="fas fa-wheelchair"></i> PWD</td><td><b>${d.pwd}</b></td></tr>
                    <tr><td><i class="fas fa-user-tie"></i> Senior</td><td><b>${d.senior}</b></td></tr>
                    </table>`
            );

            layer.on({
                mouseover: function (e) {
                    e.target.setStyle({ weight: 3, color: '#c97a3d', fillOpacity: 0.95 });
                    if (labelMarkers[name]) {
                        const el = labelMarkers[name].getElement();
                        if (el) el.classList.add('highlighted');
                    }
                },
                mouseout: function (e) {
                    geoLayer.resetStyle(e.target);
                    if (labelMarkers[name]) {
                        const el = labelMarkers[name].getElement();
                        if (el) el.classList.remove('highlighted');
                    }
                },
                click: function () {
                    showDrilldown(name);
                    setActiveLabel(name);
                }
            });
        }

        function drawLayer() {
            if (geoLayer) {
                geoLayer.eachLayer(layer => layer.off());
                map.removeLayer(geoLayer);
            }
            geoLayer = L.geoJSON(barangayGeoJSON, {
                style: styleFeature,
                onEachFeature: onEachFeature
            }).addTo(map);
        }

        drawLayer();

        const muniBounds = geoLayer.getBounds();
        map.fitBounds(muniBounds, { padding: [10, 10] });
        map.setMaxBounds(muniBounds.pad(0.25));
        map.options.maxBoundsViscosity = 1.0;
        map.setMinZoom(12);
        map.setMaxZoom(17);

        // Municipality mask
        const outerRing = [
            [-85, -360],
            [-85, 360],
            [85, 360],
            [85, -360]
        ];
        const holes = barangayGeoJSON.features.map(f =>
            f.geometry.coordinates[0].map(([lng, lat]) => [lat, lng])
        );
        L.polygon([outerRing, ...holes], {
            stroke: false,
            fillColor: '#cfc9b8',
            fillOpacity: 0.92,
            interactive: false
        }).addTo(map);
        L.polygon(holes, {
            color: '#1f2a24',
            weight: 2.5,
            fill: false,
            interactive: false
        }).addTo(map);

        // Legend
        legend.onAdd = function () {
            const div = L.DomUtil.create('div', 'legend');
            const max = Math.max(...barangayNames.map(b => valueOf(b)), 1);
            let html = `<b>${programLabels[currentProgram]}</b><br>
                        <span>${currentMetric === 'amount' ? 'Total Amount' : 'Beneficiary Count'}</span><br>`;

            if (currentMetric === 'count') {
                const ranges = [
                    { from: 0, to: 20, sample: 10 },
                    { from: 21, to: 40, sample: 30 },
                    { from: 41, to: 60, sample: 50 },
                    { from: 61, to: 80, sample: 70 },
                    { from: 81, to: null, sample: 81 }
                ];
                ranges.forEach(r => {
                    const label = r.to === null ? '81+' : `${r.from}&ndash;${r.to}`;
                    html += `<i style="background:${getColor(r.sample, max)}"></i>${label}<br>`;
                });
            } else {
                const grades = [0, 0.2, 0.4, 0.6, 0.8];
                grades.forEach((g, i) => {
                    const from = Math.round(g * max);
                    const to = i < grades.length - 1 ? Math.round(grades[i + 1] * max) : max;
                    html += `<i style="background:${getColor((g + 0.01) * max, max)}"></i>₱${formatAmount(from)}&ndash;₱${formatAmount(to)}<br>`;
                });
            }
            div.innerHTML = html;
            return div;
        };
        legend.addTo(map);

        // Barangay labels
        barangayGeoJSON.features.forEach(feature => {
            const name = feature.properties.adm4_en;
            const coords = feature.geometry.coordinates[0];
            let latSum = 0,
                lngSum = 0;
            coords.forEach(c => {
                lngSum += c[0];
                latSum += c[1];
            });
            const center = [latSum / coords.length, lngSum / coords.length];

            const labelIcon = L.divIcon({
                className: 'barangay-label',
                html: name,
                iconSize: null,
                iconAnchor: [0, 0]
            });

            const marker = L.marker(center, {
                icon: labelIcon,
                interactive: false
            }).addTo(map);
            labelMarkers[name] = marker;
        });

        // UI RENDERING FUNCTIONS
        function formatAmount(value) {
            return Number(value || 0).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function renderTop5() {
            const ranked = barangayNames
                .map(b => ({ name: b, value: valueOf(b) }))
                .sort((a, b) => b.value - a.value)
                .slice(0, 5);
            const unit = currentMetric === 'amount' ? '₱' : '';
            document.getElementById('top5List').innerHTML = ranked.map((r, i) =>
                `<li><span><span class="rank">${i + 1}.</span>${r.name}</span><b>${unit}${currentMetric === 'amount' ? formatAmount(r.value) : r.value.toLocaleString()}</b></li>`
            ).join('');
        }

        function showDrilldown(name) {
            const d = activeData()[name] || { count: 0, amount: 0, beneficiaries: [] };
            const box = document.getElementById('drilldown');

            if (currentProgram === 'aics_all' || currentProgram === 'combined') {
                box.innerHTML = `
                        <b>Brgy. ${name}</b><br>
                        <span style="color:#777">${programLabels[currentProgram]} total: ${d.count} beneficiaries, ₱${formatAmount(d.amount)}</span><br>
                        <span style="color:#999;font-size:12px;">Select a specific subtype to view individual records.</span>`;
                return;
            }

            let rows = (d.beneficiaries || []).map(b =>
                `<tr><td><i class="fas fa-user text-slate-400 mr-1"></i>${b.name}</td><td>${b.age}</td><td>₱${formatAmount(b.amount)}</td><td><span class="text-xs px-2 py-0.5 rounded-full ${b.remarks === 'Approved' || b.remarks === 'Released' ? 'bg-green-50 text-green-700' : b.remarks === 'Pending' ? 'bg-yellow-50 text-yellow-700' : 'bg-slate-50 text-slate-600'}">${b.remarks}</span></td></tr>`
            ).join('');

            if (!rows) rows =
                `<tr><td colspan="4" style="color:#999;"><i class="fas fa-info-circle mr-1"></i>No beneficiary records for this program.</td></tr>`;

            box.innerHTML = `
                    <b>Brgy. ${name}</b> — ${programLabels[currentProgram]}<br>
                    <span style="color:#777">${d.count} beneficiaries · ₱${formatAmount(d.amount)} total</span>
                    <table><thead><tr><th>Name</th><th>Age</th><th>Amount</th><th>Status</th></tr></thead><tbody>${rows}</tbody></table>`;
        }

        // Summary table
        let sortKey = 'count',
            sortAsc = false;

        function renderSummary() {
            const data = activeData();
            let rows = barangayNames.map(b => ({
                name: b,
                ...(data[b] || { count: 0, amount: 0, pwd: 0, senior: 0, solo: 0, fourPs: 0 })
            }));

            rows.sort((a, b) => {
                const v = sortKey === 'name' ?
                    a.name.localeCompare(b.name) :
                    (a[sortKey] || 0) - (b[sortKey] || 0);
                return sortAsc ? v : -v;
            });

            document.getElementById('summaryBody').innerHTML = rows.map(r =>
                `<tr>
                        <td><i class="fas fa-map-marker-alt mr-2 text-green-500 text-xs"></i>${r.name}</td>
                        <td>${r.count}</td>
                        <td>₱${formatAmount(r.amount)}</td>
                        <td>${r.pwd}</td>
                        <td>${r.senior}</td>
                        <td>${r.solo || 0}</td>
                        <td>${r.fourPs || 0}</td>
                    </tr>`
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

        function refreshAll() {
            setActiveLabel(null);
            drawLayer();
            map.removeControl(legend);
            legend.addTo(map);
            renderTop5();
            renderSummary();
            document.getElementById('drilldown').innerHTML =
                '<p class="empty"><i class="fas fa-hand-pointer mr-1"></i> Click a barangay on the map to see its beneficiary breakdown.</p>';
        }

        refreshAll();

        document.getElementById('program').addEventListener('change', e => {
            currentProgram = e.target.value;
            refreshAll();
        });

        document.getElementById('metric').addEventListener('change', e => {
            currentMetric = e.target.value;
            refreshAll();
        });

        // EXPORT HELPER FUNCTIONS
        const PREPARED_BY_NAME =
    <?= json_encode($preparedByName, JSON_UNESCAPED_UNICODE) ?>;

        const PREPARED_BY_TITLE =
    <?= json_encode($preparedByPosition, JSON_UNESCAPED_UNICODE) ?>;

        function getDateTimeString() {
            const now = new Date();
            return now.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function getDateOnly() {
            return new Date().toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        function getTimeOnly() {
            return new Date().toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        function getFooterTimestamp() {
            return `Generated on ${getDateOnly()} at ${getTimeOnly()}`;
        }

        function getExportData() {
            const data = activeData();
            return barangayNames.map(b => ({
                Barangay: b,
                Beneficiaries: (data[b]?.count) || 0,
                'Total Amount (₱)': (data[b]?.amount) || 0,
                'PWD Count': (data[b]?.pwd) || 0,
                'Senior Count': (data[b]?.senior) || 0,
                'Solo Parent Count': (data[b]?.solo) || 0,
                '4Ps Count': (data[b]?.fourPs) || 0,
            }));
        }

        function getAllProgramsData() {
            const allData = {};
            const programOrder = ['aics_financial', 'aics_burial', 'aics_medical', 'aics_livelihood', 'aics_educational',
                'women_child'
            ];

            programOrder.forEach(progKey => {
                const data = programData[progKey] || {};
                allData[programLabels[progKey]] = barangayNames.map(b => ({
                    Barangay: b,
                    Beneficiaries: (data[b]?.count) || 0,
                    'Total Amount (₱)': (data[b]?.amount) || 0,
                    'PWD Count': (data[b]?.pwd) || 0,
                    'Senior Count': (data[b]?.senior) || 0,
                    'Solo Parent Count': (data[b]?.solo) || 0,
                    '4Ps Count': (data[b]?.fourPs) || 0,
                }));
            });

            return allData;
        }

        async function loadLogoDataUrl(filename) {
            const candidates = [
                `logo/${filename}`,
                `/logo/${filename}`,
                `logo/${filename}`,
                `/logo/${filename}`,
                filename,
                `./${filename}`
            ];
            for (const path of candidates) {
                try {
                    const res = await fetch(path);
                    if (!res.ok) continue;
                    const blob = await res.blob();
                    return await new Promise((resolve, reject) => {
                        const reader = new FileReader();
                        reader.onload = () => resolve(reader.result);
                        reader.onerror = reject;
                        reader.readAsDataURL(blob);
                    });
                } catch (_) { /* try next */ }
            }
            console.warn(`Logo "${filename}" not found in logo/, logo/, or root — exporting without it.`);
            return null;
        }

        async function loadFontBase64(filename) {
            const candidates = [`fonts/${filename}`, `/fonts/${filename}`, filename, `./${filename}`];
            for (const path of candidates) {
                try {
                    const res = await fetch(path);
                    if (!res.ok) continue;
                    const buf = new Uint8Array(await res.arrayBuffer());
                    let bin = '';
                    for (let i = 0; i < buf.length; i += 0x8000) {
                        bin += String.fromCharCode.apply(null, buf.subarray(i, i + 0x8000));
                    }
                    return btoa(bin);
                } catch (_) { /* try next */ }
            }
            console.warn(`Font "${filename}" not found in fonts/ — PDF will use the default font.`);
            return null;
        }

        async function loadBothLogos() {
            const [municipal, dswd] = await Promise.all([
                loadLogoDataUrl('municipallogo.png'),
                loadLogoDataUrl('dswdlogo.png')
            ]);
            return { municipal, dswd };
        }

        // EXCEL EXPORT (ExcelJS - styled report)
        async function exportToXlsx() {
            const wb = new ExcelJS.Workbook();
            wb.creator = 'MSWDO San Enrique Information System';
            wb.created = new Date();

            const { municipal, dswd } = await loadBothLogos();
            const footerStr = getFooterTimestamp();
            const year = new Date().getFullYear();

            const BLACK = 'FF000000';
            const GREY = 'FF666666';
            const BORDER = { style: 'thin', color: { argb: BLACK } };
            const THIN_BORDERS = {
                top: BORDER, left: BORDER, bottom: BORDER, right: BORDER
            };

            const COLS = [
                { header: 'BARANGAY', key: 'Barangay', width: 22 },
                { header: 'BENEFICIARIES', key: 'Beneficiaries', width: 16 },
                { header: 'TOTAL AMOUNT (₱)', key: 'Total Amount (₱)', width: 21 },
                { header: 'PWD COUNT', key: 'PWD Count', width: 14 },
                { header: 'SENIOR COUNT', key: 'Senior Count', width: 15 },
                { header: 'SOLO PARENT COUNT', key: 'Solo Parent Count', width: 20 },
                { header: '4Ps COUNT', key: '4Ps Count', width: 13 }
            ];
            const numCols = COLS.length;

            function mergeTextRow(ws, rowNumber, text, fontSize, bold = false, height = 16) {
                ws.mergeCells(rowNumber, 2, rowNumber, numCols - 1);
                const cell = ws.getCell(rowNumber, 2);
                cell.value = text;
                cell.font = {
                    name: 'Calibri',
                    size: fontSize,
                    bold,
                    color: { argb: BLACK }
                };
                cell.alignment = {
                    horizontal: 'center',
                    vertical: 'middle',
                    wrapText: true
                };
                ws.getRow(rowNumber).height = height;
            }

            function addLetterhead(ws) {
                // Header columns: A = municipal logo, B:F = centered text, G = DSWD logo.
                ws.mergeCells(1, 1, 6, 1);
                ws.mergeCells(1, numCols, 6, numCols);

                const leftCell = ws.getCell(1, 1);
                leftCell.alignment = { horizontal: 'center', vertical: 'middle' };
                const rightCell = ws.getCell(1, numCols);
                rightCell.alignment = { horizontal: 'center', vertical: 'middle' };

                mergeTextRow(ws, 1, 'Republic of the Philippines', 11, false, 16);
                mergeTextRow(ws, 2, 'Province of Negros Occidental', 11, true, 16);
                mergeTextRow(ws, 3, 'Municipality of San Enrique', 11, true, 16);
                mergeTextRow(ws, 4, 'Municipal Social Welfare and Development Office', 11, true, 17);
                mergeTextRow(ws, 5, 'GEOGRAPHIC ANALYSIS REPORT', 11, true, 20);
                mergeTextRow(ws, 6, `Calendar Year ${year}`, 11, false, 15);

                if (municipal) {
                    try {
                        const imgId = wb.addImage({ base64: municipal, extension: 'png' });
                        ws.addImage(imgId, {
                            tl: { col: 0.12, row: 0.20 },
                            ext: { width: 58, height: 58 }
                        });
                    } catch (err) {
                        console.warn('Municipal logo could not be added to Excel:', err);
                    }
                }

                if (dswd) {
                    try {
                        const imgId = wb.addImage({ base64: dswd, extension: 'png' });
                        ws.addImage(imgId, {
                            tl: { col: numCols - 0.88, row: 0.20 },
                            ext: { width: 58, height: 58 }
                        });
                    } catch (err) {
                        console.warn('DSWD logo could not be added to Excel:', err);
                    }
                }

                ws.mergeCells(7, 1, 7, numCols);
                const rule = ws.getCell(7, 1);
                rule.value = '';
                rule.border = { bottom: { style: 'medium', color: { argb: BLACK } } };
                ws.getRow(7).height = 5;

                return 9;
            }

            function addSectionHeading(ws, row, label) {
                ws.mergeCells(row, 1, row, numCols);
                const cell = ws.getCell(row, 1);
                cell.value = label;
                cell.font = {
                    name: 'Calibri', size: 11, bold: true, color: { argb: BLACK }
                };
                cell.alignment = { horizontal: 'left', vertical: 'middle' };
                ws.getRow(row).height = 18;
                return row + 2;
            }

            function writeTable(ws, startRow, rows) {
                const headerRow = ws.getRow(startRow);
                headerRow.height = 24;

                COLS.forEach((c, i) => {
                    const cell = headerRow.getCell(i + 1);
                    cell.value = c.header;
                    cell.font = {
                        bold: true, color: { argb: BLACK }, size: 11, name: 'Calibri'
                    };
                    cell.alignment = {
                        horizontal: c.key === 'Total Amount (₱)' ? 'right' : (i === 0 ? 'left' : 'center'),
                        vertical: 'middle',
                        wrapText: true
                    };
                    cell.border = THIN_BORDERS;
                });

                let r = startRow + 1;
                const totals = {
                    Beneficiaries: 0,
                    'Total Amount (₱)': 0,
                    'PWD Count': 0,
                    'Senior Count': 0,
                    'Solo Parent Count': 0,
                    '4Ps Count': 0
                };

                rows.forEach(rowData => {
                    const row = ws.getRow(r);
                    row.height = 18;
                    COLS.forEach((c, i) => {
                        const cell = row.getCell(i + 1);
                        cell.value = rowData[c.key];
                        cell.border = THIN_BORDERS;
                        cell.alignment = {
                            horizontal: c.key === 'Total Amount (₱)' ? 'right' : (i === 0 ? 'left' : 'center'),
                            vertical: 'middle'
                        };
                        cell.font = {
                            bold: i === 0,
                            color: { argb: BLACK },
                            size: 11,
                            name: 'Calibri'
                        };
                        if (c.key === 'Total Amount (₱)') cell.numFmt = '₱#,##0.00';
                        if (c.key in totals) totals[c.key] += Number(rowData[c.key] || 0);
                    });
                    r++;
                });

                const totalRow = ws.getRow(r);
                totalRow.height = 19;
                totalRow.getCell(1).value = 'TOTAL';
                totalRow.getCell(1).font = {
                    bold: true, color: { argb: BLACK }, size: 11, name: 'Calibri'
                };
                totalRow.getCell(1).border = THIN_BORDERS;
                totalRow.getCell(1).alignment = { horizontal: 'left', vertical: 'middle' };

                COLS.slice(1).forEach((c, i) => {
                    const cell = totalRow.getCell(i + 2);
                    cell.value = totals[c.key];
                    cell.font = {
                        bold: true, color: { argb: BLACK }, size: 11, name: 'Calibri'
                    };
                    cell.border = THIN_BORDERS;
                    cell.alignment = {
                        horizontal: c.key === 'Total Amount (₱)' ? 'right' : 'center',
                        vertical: 'middle'
                    };
                    if (c.key === 'Total Amount (₱)') cell.numFmt = '₱#,##0.00';
                });

                let sigRow = r + 3;
                ws.mergeCells(sigRow, 1, sigRow, 3);
                ws.getCell(sigRow, 1).value = 'Prepared by:';
                ws.getCell(sigRow, 1).font = { size: 11, color: { argb: BLACK }, name: 'Calibri' };

                sigRow += 3;
                ws.mergeCells(sigRow, 1, sigRow, 3);
                const nameCell = ws.getCell(sigRow, 1);
                nameCell.value = PREPARED_BY_NAME;
                nameCell.font = { size: 11, bold: true, color: { argb: BLACK }, name: 'Calibri' };
                nameCell.border = { top: BORDER };

                sigRow += 1;
                ws.mergeCells(sigRow, 1, sigRow, 3);
                ws.getCell(sigRow, 1).value = PREPARED_BY_TITLE;
                ws.getCell(sigRow, 1).font = { size: 11, color: { argb: BLACK }, name: 'Calibri' };

                sigRow += 2;
                ws.mergeCells(sigRow, numCols - 2, sigRow, numCols);
                const footerCell = ws.getCell(sigRow, numCols - 2);
                footerCell.value = footerStr;
                footerCell.font = { size: 11, italic: true, color: { argb: GREY }, name: 'Calibri' };
                footerCell.alignment = { horizontal: 'right', vertical: 'middle' };
            }

            function configurePage(ws) {
                ws.pageSetup = {
                    paperSize: ws.PAPERSIZE_LETTER,
                    orientation: 'landscape',
                    fitToPage: true,
                    fitToWidth: 1,
                    fitToHeight: 0,
                    horizontalCentered: true,
                    margins: {
                        left: 0.25, right: 0.25,
                        top: 0.35, bottom: 0.35,
                        header: 0.1, footer: 0.1
                    }
                };
                ws.pageSetup.printTitlesRow = '1:9';
                ws.views = [{ state: 'normal' }];
                COLS.forEach((c, i) => ws.getColumn(i + 1).width = c.width);
            }

            const wsCurrent = wb.addWorksheet('Report Summary');
            configurePage(wsCurrent);
            const startRow1 = addLetterhead(wsCurrent);
            const headingRow1 = addSectionHeading(
                wsCurrent,
                startRow1,
                `${programLabels[currentProgram]} - ${currentMetric === 'count' ? 'Beneficiary Count' : 'Total Amount'}`
            );
            writeTable(wsCurrent, headingRow1, getExportData());

            const allProgramsData = getAllProgramsData();
            const programOrder = [
                'AICS Financial',
                'AICS Burial',
                'AICS Medical',
                'AICS Livelihood',
                'AICS Educational',
                'Women and Children'
            ];

            programOrder.forEach(progLabel => {
                if (!allProgramsData[progLabel]) return;
                const ws = wb.addWorksheet(progLabel.substring(0, 31));
                configurePage(ws);
                const startRow = addLetterhead(ws);
                const headingRow = addSectionHeading(ws, startRow, progLabel);
                writeTable(ws, headingRow, allProgramsData[progLabel]);
            });

            const buffer = await wb.xlsx.writeBuffer();
            const blob = new Blob([buffer], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'MSWDO_San_Enrique_Geographic_Report.xlsx';
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        }

        // PDF EXPORT 
        async function exportToPdf() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'letter' });

            // ── CURRENTLY LOGGED-IN USER ──
            const PREPARED_BY_NAME =
    <?= json_encode($preparedByName, JSON_UNESCAPED_UNICODE) ?>;

            const PREPARED_BY_TITLE =
    <?= json_encode($preparedByPosition, JSON_UNESCAPED_UNICODE) ?>;

            const [CARLITO_REGULAR, CARLITO_BOLD] = await Promise.all([
                loadFontBase64('Carlito-Regular.ttf'),
                loadFontBase64('Carlito-Bold.ttf')
            ]);
            if (CARLITO_REGULAR && CARLITO_BOLD) {
                doc.addFileToVFS('Carlito-Regular.ttf', CARLITO_REGULAR);
                doc.addFont('Carlito-Regular.ttf', 'Carlito', 'normal');
                doc.addFileToVFS('Carlito-Bold.ttf', CARLITO_BOLD);
                doc.addFont('Carlito-Bold.ttf', 'Carlito', 'bold');
            }

            const { municipal, dswd } = await loadBothLogos();
            const year = new Date().getFullYear();
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const margin = 32;
            const rows = getExportData();

            function drawLetterhead() {
                const y = 22;
                const logoSize = 52;

                if (municipal) {
                    try { doc.addImage(municipal, 'PNG', margin, y, logoSize, logoSize); } catch (_) { }
                }
                if (dswd) {
                    try { doc.addImage(dswd, 'PNG', pageWidth - margin - logoSize, y, logoSize, logoSize); } catch (_) { }
                }

                doc.setTextColor(0, 0, 0);
                doc.setFont('Carlito', 'normal');
                doc.setFontSize(11);
                doc.text('Republic of the Philippines', pageWidth / 2, y + 10, { align: 'center' });
                doc.setFont('Carlito', 'bold');
                doc.text('Province of Negros Occidental', pageWidth / 2, y + 23, { align: 'center' });
                doc.text('Municipality of San Enrique', pageWidth / 2, y + 36, { align: 'center' });
                doc.text('Municipal Social Welfare and Development Office', pageWidth / 2, y + 49, { align: 'center' });
                doc.text('GEOGRAPHIC ANALYSIS REPORT', pageWidth / 2, y + 66, { align: 'center' });
                doc.setFont('Carlito', 'normal');
                doc.text(`Calendar Year ${year}`, pageWidth / 2, y + 80, { align: 'center' });

                doc.setDrawColor(0, 0, 0);
                doc.setLineWidth(0.8);
                doc.line(margin, y + 93, pageWidth - margin, y + 93);
                return y + 111;
            }

            const y = drawLetterhead();
            doc.setFont('Carlito', 'bold');
            doc.setFontSize(11);
            doc.text(programLabels[currentProgram], margin, y);
            doc.setFont('Carlito', 'normal');
            doc.text(
                currentMetric === 'count' ? 'Geographic distribution by beneficiary count' : 'Geographic distribution by total amount',
                margin,
                y + 13
            );

            const body = rows.map(r => [
                r.Barangay,
                String(r.Beneficiaries),
                `₱${formatAmount(Number(r['Total Amount (₱)'] || 0))}`,
                String(r['PWD Count'] || 0),
                String(r['Senior Count'] || 0),
                String(r['Solo Parent Count'] || 0),
                String(r['4Ps Count'] || 0)
            ]);

            const totals = rows.reduce((t, r) => {
                t.b += Number(r.Beneficiaries || 0);
                t.a += Number(r['Total Amount (₱)'] || 0);
                t.p += Number(r['PWD Count'] || 0);
                t.s += Number(r['Senior Count'] || 0);
                t.so += Number(r['Solo Parent Count'] || 0);
                t.f += Number(r['4Ps Count'] || 0);
                return t;
            }, { b: 0, a: 0, p: 0, s: 0, so: 0, f: 0 });

            body.push([
                'TOTAL',
                String(totals.b),
                `₱${formatAmount(totals.a)}`,
                String(totals.p),
                String(totals.s),
                String(totals.so),
                String(totals.f)
            ]);

            const tableWidth = 636;
            const tableLeft = (pageWidth - tableWidth) / 2;

            doc.autoTable({
                head: [['Barangay', 'Beneficiaries', 'Total Amount (₱)', 'PWD', 'Senior', 'Solo Parent', '4Ps']],
                body,
                startY: y + 20,
                theme: 'grid',
                tableWidth,
                margin: { left: tableLeft, right: tableLeft },
                styles: {
                    font: 'Carlito',
                    fontSize: 11,
                    textColor: [0, 0, 0],
                    lineColor: [0, 0, 0],
                    lineWidth: 0.4,
                    cellPadding: 4,
                    valign: 'middle'
                },
                headStyles: {
                    fillColor: [255, 255, 255],
                    textColor: [0, 0, 0],
                    font: 'Carlito',
                    fontStyle: 'bold',
                    fontSize: 11,
                    halign: 'center'
                },
                bodyStyles: { halign: 'center', font: 'Carlito', fontSize: 11 },
                columnStyles: {
                    0: { halign: 'left', fontStyle: 'bold', cellWidth: 150 },
                    1: { cellWidth: 82 },
                    2: { halign: 'right', cellWidth: 135 },
                    3: { cellWidth: 58 },
                    4: { cellWidth: 65 },
                    5: { cellWidth: 88 },
                    6: { cellWidth: 58 }
                },
                didParseCell: data => {
                    if (data.row.raw && data.row.raw[0] === 'TOTAL') {
                        data.cell.styles.font = 'Carlito';
                        data.cell.styles.fontStyle = 'bold';
                        data.cell.styles.fontSize = 11;
                    }
                }
            });

            let sigY = doc.lastAutoTable.finalY + 24;
            if (sigY > pageHeight - 85) sigY = pageHeight - 85;

            doc.setFont('Carlito', 'normal');
            doc.setFontSize(11);
            doc.setTextColor(0, 0, 0);
            doc.text('Prepared by:', margin, sigY);
            sigY += 28;
            doc.setDrawColor(0, 0, 0);
            doc.line(margin, sigY, margin + 190, sigY);
            sigY += 11;
            doc.setFont('Carlito', 'bold');
            doc.text(PREPARED_BY_NAME, margin, sigY);
            sigY += 11;
            doc.setFont('Carlito', 'normal');
            doc.text(PREPARED_BY_TITLE, margin, sigY);

            doc.setFont('Carlito', 'normal');
            doc.setFontSize(11);
            doc.setTextColor(90, 90, 90);
            doc.text(getFooterTimestamp(), pageWidth - margin, pageHeight - 20, { align: 'right' });

            const safeProgram = programLabels[currentProgram].replace(/[^a-z0-9]+/gi, '_');
            doc.save(`MSWDO_San_Enrique_Geographic_Report_${safeProgram}.pdf`);
        }

        // WORD EXPORT 
        async function exportToDocx() {
            const year = new Date().getFullYear();
            const { municipal, dswd } = await loadBothLogos();
            const footerStr = getFooterTimestamp();
            const rows = getExportData();

            const municipalImgTag = municipal
                ? `<img src="${municipal}" width="55" height="55" style="width:55px;height:55px;object-fit:contain;">`
                : `<span style="display:inline-block;width:55px;height:55px;"></span>`;
            const dswdImgTag = dswd
                ? `<img src="${dswd}" width="55" height="55" style="width:55px;height:55px;object-fit:contain;">`
                : `<span style="display:inline-block;width:55px;height:55px;"></span>`;

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            let totals = { b: 0, a: 0, p: 0, s: 0, so: 0, f: 0 };
            let tableRows = '';

            rows.forEach(r => {
                tableRows += `<tr>
                    <td class="left">${escapeHtml(r.Barangay)}</td>
                    <td>${r.Beneficiaries}</td>
                    <td style="text-align:right;">₱${formatAmount(Number(r['Total Amount (₱)'] || 0))}</td>
                    <td>${r['PWD Count'] || 0}</td>
                    <td>${r['Senior Count'] || 0}</td>
                    <td>${r['Solo Parent Count'] || 0}</td>
                    <td>${r['4Ps Count'] || 0}</td>
                </tr>`;
                totals.b += Number(r.Beneficiaries || 0);
                totals.a += Number(r['Total Amount (₱)'] || 0);
                totals.p += Number(r['PWD Count'] || 0);
                totals.s += Number(r['Senior Count'] || 0);
                totals.so += Number(r['Solo Parent Count'] || 0);
                totals.f += Number(r['4Ps Count'] || 0);
            });

            tableRows += `<tr class="total-row">
                <td class="left">TOTAL</td>
                <td>${totals.b}</td>
                <td style="text-align:right;">₱${formatAmount(totals.a)}</td>
                <td>${totals.p}</td>
                <td>${totals.s}</td>
                <td>${totals.so}</td>
                <td>${totals.f}</td>
            </tr>`;

            const html = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office"
                      xmlns:w="urn:schemas-microsoft-com:office:word"
                      xmlns="http://www.w3.org/TR/REC-html40">
                <head>
                    <meta charset="UTF-8">
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                    <!--[if gte mso 9]><xml>
                    <w:WordDocument>
                        <w:View>Print</w:View>
                        <w:Zoom>100</w:Zoom>
                        <w:DoNotOptimizeForBrowser/>
                    </w:WordDocument>
                    </xml><![endif]-->
                    <style>
                        @page Section1 {
                            size: 11in 8.5in;
                            mso-page-orientation: landscape;
                            margin: 0.35in 0.45in 0.35in 0.45in;
                        }
                        div.Section1 { page: Section1; }
                        body {
                            font-family: Calibri, Arial, sans-serif;
            font-size: 11pt;
                            font-size: 11pt;
                            color: #000;
                            margin: 0;
                            padding: 0;
                        }
                        table.letterhead-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin: 0 0 4px 0;
                        }
                        table.letterhead-table td {
                            border: none;
                            vertical-align: middle;
                            padding: 0;
                        }
                        .logo-cell {
                            width: 65px;
                            text-align: center;
                        }
                        .text-cell {
                            text-align: center;
                            line-height: 1.05;
                        }
                        .lh-line, .lh-bold {
                            font-size: 11pt;
                            margin: 0;
                        }
                        .lh-bold { font-weight: bold; }
                        .office {
                            font-size: 11pt;
                            font-weight: bold;
                            margin: 2px 0 0;
                        }
                        .date {
                            font-size: 11pt;
                            margin: 1px 0 0;
                        }
                        .rule {
                            border-bottom: 1px solid #000;
                            margin-bottom: 6px;
                        }
                        h2 {
                            font-size: 11pt;
                            font-weight: bold;
                            color: #000;
                            margin: 0 0 4px 0;
                        }
                        .subtitle {
                            font-size: 11pt;
                            margin: 0 0 5px 0;
                        }
                        table.data {
                            width: 100%;
                            border-collapse: collapse;
                            margin: 0;
                            table-layout: fixed;
                            font-size: 11pt;
                        }
                        table.data th {
                            background: #FFF;
                            color: #000;
                            padding: 3px 2px;
                            text-align: center;
                            font-weight: bold;
                            border: 1px solid #000;
                            font-size: 11pt;
                            line-height: 1.0;
                        }
                        table.data td {
                            padding: 3px 2px;
                            text-align: center;
                            border: 1px solid #000;
                            color: #000;
                            line-height: 1.0;
                        }
                        table.data td.left {
                            text-align: left;
                            font-weight: bold;
                        }
                        .total-row td { font-weight: bold; }
                        .signature {
                            margin-top: 8px;
                            font-size: 11pt;
                            color: #000;
                        }
                        .signature p { margin: 1px 0; }
                        .sig-space { height: 15px; }
                        .sig-name {
                            font-weight: bold;
                            border-top: 1px solid #000;
                            display: inline-block;
                            padding-top: 2px;
                            width: 215px;
                        }
                        .footer-ts {
                            margin-top: 7px;
                            font-size: 11pt;
                            color: #777;
                            text-align: right;
                        }
                    </style>
                </head>
                <body>
                <div class="Section1">
                    <table class="letterhead-table"><tr>
                        <td class="logo-cell">${municipalImgTag}</td>
                        <td class="text-cell">
                            <p class="lh-line">Republic of the Philippines</p>
                            <p class="lh-bold">Province of Negros Occidental</p>
                            <p class="lh-bold">Municipality of San Enrique</p>
                            <p class="lh-bold">Municipal Social Welfare and Development Office</p>
                            <p class="office">GEOGRAPHIC ANALYSIS REPORT</p>
                            <p class="date">Calendar Year ${year}</p>
                        </td>
                        <td class="logo-cell">${dswdImgTag}</td>
                    </tr></table>
                    <div class="rule"></div>

                    <h2>${escapeHtml(programLabels[currentProgram])}</h2>
                    <p class="subtitle">Geographic distribution by ${currentMetric === 'count' ? 'beneficiary count' : 'total amount'}.</p>

                    <table class="data">
                        <colgroup>
                            <col style="width:24%;">
                            <col style="width:13%;">
                            <col style="width:18%;">
                            <col style="width:10%;">
                            <col style="width:10%;">
                            <col style="width:15%;">
                            <col style="width:10%;">
                        </colgroup>
                        <thead><tr>
                            <th>Barangay</th>
                            <th>Beneficiaries</th>
                            <th style="text-align:right;">Total Amount (₱)</th>
                            <th>PWD</th>
                            <th>Senior</th>
                            <th>Solo Parent</th>
                            <th>4Ps</th>
                        </tr></thead>
                        <tbody>${tableRows}</tbody>
                    </table>

                    <div class="signature">
                        <p>Prepared by:</p>
                        <div class="sig-space">&nbsp;</div>
                        <p class="sig-name">${PREPARED_BY_NAME}</p>
                        <p>${PREPARED_BY_TITLE}</p>
                    </div>

                    <div class="footer-ts">${footerStr}</div>
                </div>
                </body></html>`;

            const blob = new Blob(['\ufeff' + html], { type: 'application/msword' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            const safeProgram = programLabels[currentProgram].replace(/[^a-z0-9]+/gi, '_');
            a.download = `MSWDO_San_Enrique_Geographic_Report_${safeProgram}.doc`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        }

        (function initLetterheadMeta() {
            const year = new Date().getFullYear();
            document.getElementById('lhCalendarYear').textContent = `Calendar Year ${year}`;
            document.getElementById('lhMeta').textContent = 'Generated: ' + getDateOnly();
        })();

        let printTimer = null;

        window.addEventListener('beforeprint', () => {
            document.getElementById('printFooter').textContent = getFooterTimestamp();

            if (map && typeof map.invalidateSize === 'function') {
                map.invalidateSize({ pan: false, animate: false });
                requestAnimationFrame(() => {
                    map.invalidateSize({ pan: false, animate: false });
                });
            }
        });

        window.addEventListener('afterprint', () => {
            if (printTimer) clearTimeout(printTimer);
            printTimer = setTimeout(() => {
                if (map && typeof map.invalidateSize === 'function') {
                    map.invalidateSize({ pan: false, animate: false });
                }
            }, 100);
        });

        document.getElementById('exportXlsx').addEventListener('click', async (e) => {
            e.preventDefault();
            document.getElementById('exportDropdownContainer').classList.remove('active');
            await exportToXlsx();
        });

        document.getElementById('exportPdf').addEventListener('click', async (e) => {
            e.preventDefault();
            document.getElementById('exportDropdownContainer').classList.remove('active');
            await exportToPdf();
        });

        document.getElementById('exportDocx').addEventListener('click', async (e) => {
            e.preventDefault();
            document.getElementById('exportDropdownContainer').classList.remove('active');
            await exportToDocx();
        });

        window.addEventListener('beforeprint', () => {
            if (map && typeof map.invalidateSize === 'function') {
                setTimeout(() => {
                    map.invalidateSize({ pan: false, animate: false });
                }, 50);
            }
        });

        window.addEventListener('afterprint', () => {
            if (map && typeof map.invalidateSize === 'function') {
                setTimeout(() => {
                    map.invalidateSize({ pan: false, animate: false });
                }, 50);
            }
        });

        document.getElementById('printMapBtn').addEventListener('click', () => {
            // Wait briefly so Leaflet finishes any current map updates, then print.
            if (map && typeof map.invalidateSize === 'function') {
                map.invalidateSize({ pan: false, animate: false });
            }

            setTimeout(() => {
                window.print();
            }, 150);
        });

        document.getElementById('exportDropdownBtn').addEventListener('click', (e) => {
            e.stopPropagation();
            document.getElementById('exportDropdownContainer').classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!document.getElementById('exportDropdownContainer').contains(e.target)) {
                document.getElementById('exportDropdownContainer').classList.remove('active');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeSidebar();
        });

        console.log(' MSWDO Geographic Analysis Dashboard Ready');
        console.log(' Live data loaded from database');
        console.log(' Map initialized with choropleth visualization');
        console.log(' Mobile responsive layout active');
        console.log(' Export functions: Excel (summary + all programs), PDF/Word (current program only)');
    </script>

</body>

</html>