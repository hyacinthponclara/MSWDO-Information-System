<?php

function getAllProgramBudgets(PDO $pdo): array //returns each program's details
{
    $stmt = $pdo->query("
        SELECT
            p.program_id,
            p.program_name,
            p.prog_funding_source,
            p.prog_period,
            p.prog_annual_budget AS total,

            COALESCE(av.spent_availment, 0) AS spent_availment,
            COALESCE(pp.spent_proposals, 0) AS spent_proposals,

            COALESCE(av.spent_availment, 0)
                + COALESCE(pp.spent_proposals, 0) AS spent,

            p.prog_annual_budget
                - COALESCE(av.spent_availment, 0)
                - COALESCE(pp.spent_proposals, 0) AS remaining

        FROM PROGRAM p

        LEFT JOIN (
            SELECT
                program_id,
                SUM(av_amount) AS spent_availment
            FROM AVAILMENT
            WHERE av_status = 'Released'
            GROUP BY program_id
        ) av
            ON av.program_id = p.program_id

        LEFT JOIN (
            SELECT
                program_id,
                SUM(pp_budget) AS spent_proposals
            FROM PROJECT_PROPOSAL
            GROUP BY program_id
        ) pp
            ON pp.program_id = p.program_id

        ORDER BY p.program_name ASC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {

        $total = (float) $r['total'];

        $r['total']           = $total;
        $r['spent_availment'] = (float) $r['spent_availment'];
        $r['spent_proposals'] = (float) $r['spent_proposals'];
        $r['spent']           = (float) $r['spent'];
        $r['remaining']       = (float) $r['remaining'];

        $r['pct_used'] = $total > 0
            ? round(($r['spent'] / $total) * 100)
            : 0;
    }

    unset($r);

    return $rows;
}


function getProgramBudget(PDO $pdo, array $programNames): array    // combines selected programs
{
    if (empty($programNames)) {
        return [
            'total'           => 0,
            'spent_availment' => 0,
            'spent_proposals' => 0,
            'spent'           => 0,
            'remaining'       => 0,
            'pct_used'        => 0,
        ];
    }

    $placeholders = implode(
        ',',
        array_fill(0, count($programNames), '?')
    );

    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(p.prog_annual_budget), 0) AS total,

            COALESCE(av.spent_availment, 0)
                AS spent_availment,

            COALESCE(pp.spent_proposals, 0)
                AS spent_proposals

        FROM PROGRAM p

        LEFT JOIN (
            SELECT
                a.program_id,
                SUM(a.av_amount) AS spent_availment

            FROM AVAILMENT a

            JOIN PROGRAM p2
                ON p2.program_id = a.program_id

            WHERE a.av_status = 'Released'
              AND p2.program_name IN ($placeholders)

            GROUP BY a.program_id

        ) av
            ON av.program_id = p.program_id

        LEFT JOIN (
            SELECT
                pp.program_id,
                SUM(pp.pp_budget) AS spent_proposals

            FROM PROJECT_PROPOSAL pp

            JOIN PROGRAM p3
                ON p3.program_id = pp.program_id

            WHERE p3.program_name IN ($placeholders)

            GROUP BY pp.program_id

        ) pp
            ON pp.program_id = p.program_id

        WHERE p.program_name IN ($placeholders)
    ");

    /*
     * There are three separate IN (...) sections:
     *
     * 1. AVAILMENT
     * 2. PROJECT_PROPOSAL
     * 3. PROGRAM
     *
     * Therefore the program names are bound three times.
     */
    $stmt->execute(
        array_merge(
            $programNames,
            $programNames,
            $programNames
        )
    );

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = 0.0;
    $spentAvailment = 0.0;
    $spentProposals = 0.0;

    foreach ($rows as $r) {

        $total += (float) $r['total'];

        $spentAvailment +=
            (float) $r['spent_availment'];

        $spentProposals +=
            (float) $r['spent_proposals'];
    }

    $spent = $spentAvailment + $spentProposals;

    $remaining = $total - $spent;

    return [
        'total'           => $total,
        'spent_availment' => $spentAvailment,
        'spent_proposals' => $spentProposals,
        'spent'           => $spent,
        'remaining'       => $remaining,
        'pct_used'        => $total > 0
            ? round(($spent / $total) * 100)
            : 0,
    ];
}


/**
 * Get the program_id for a given program_name.
 * Returns null if the program does not exist.
 */
function getProgramId(
    PDO $pdo,
    string $programName
): ?int {

    $stmt = $pdo->prepare("
        SELECT program_id
        FROM PROGRAM
        WHERE program_name = ?
        LIMIT 1
    ");

    $stmt->execute([$programName]);

    $id = $stmt->fetchColumn();

    return $id !== false
        ? (int) $id
        : null;
}