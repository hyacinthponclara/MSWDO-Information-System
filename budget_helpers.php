<?php

/*
| Budget rule:
|
| Approved  = awaiting release = NOT deducted
| Released  = spent = deducted
|
|--------------------------------------------------------------------------
*/

function getAllProgramBudgets(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
            p.program_id,
            p.program_name,
            p.prog_funding_source,
            p.prog_period,
            p.prog_annual_budget AS total,

            COALESCE(av.spent_availment, 0)
                AS spent_availment,

            COALESCE(pp.spent_proposals, 0)
                AS spent_proposals,

            COALESCE(av.spent_availment, 0)
                + COALESCE(pp.spent_proposals, 0)
                AS spent,

            p.prog_annual_budget
                - COALESCE(av.spent_availment, 0)
                - COALESCE(pp.spent_proposals, 0)
                AS remaining

        FROM program p


        /*
        |--------------------------------------------------------------------------
        | AVAILMENTS
        |--------------------------------------------------------------------------
        |
        | ONLY Released availments are counted as spent.
        |
        */

        LEFT JOIN (

            SELECT
                program_id,

                SUM(av_amount)
                    AS spent_availment

            FROM availment

            WHERE av_status = 'Released'

            GROUP BY program_id

        ) av

            ON av.program_id = p.program_id


        /*
        |--------------------------------------------------------------------------
        | PROJECT PROPOSALS
        |--------------------------------------------------------------------------
        |
        | ONLY Released project proposals are counted as spent.
        |
        */

        LEFT JOIN (

            SELECT
                program_id,

                SUM(pp_budget)
                    AS spent_proposals

            FROM project_proposal

            WHERE pp_status = 'Released'

            GROUP BY program_id

        ) pp

            ON pp.program_id = p.program_id


        ORDER BY p.program_name ASC
    ");


    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


    foreach ($rows as &$r) {

        $total =
            (float) $r['total'];


        $r['total'] =
            $total;


        $r['spent_availment'] =
            (float) $r['spent_availment'];


        $r['spent_proposals'] =
            (float) $r['spent_proposals'];


        $r['spent'] =
            (float) $r['spent'];


        $r['remaining'] =
            (float) $r['remaining'];


        $r['pct_used'] =
            $total > 0
                ? round(
                    ($r['spent'] / $total) * 100
                )
                : 0;
    }


    unset($r);


    return $rows;
}


/*
|--------------------------------------------------------------------------
| GET BUDGET FOR SELECTED PROGRAMS
|--------------------------------------------------------------------------
|
| Used by pages that need the combined budget of one or more programs.
|
| IMPORTANT:
|
| Only Released amounts are deducted.
|
|--------------------------------------------------------------------------
*/

function getProgramBudget(
    PDO $pdo,
    array $programNames
): array {

    if (empty($programNames)) {

        return [

            'total' =>
                0,

            'spent_availment' =>
                0,

            'spent_proposals' =>
                0,

            'spent' =>
                0,

            'remaining' =>
                0,

            'pct_used' =>
                0,

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Create ?,?,? placeholders
    |--------------------------------------------------------------------------
    */

    $placeholders =
        implode(
            ',',
            array_fill(
                0,
                count($programNames),
                '?'
            )
        );


    $stmt = $pdo->prepare("

        SELECT

            COALESCE(
                SUM(p.prog_annual_budget),
                0
            ) AS total,


            COALESCE(
                av.spent_availment,
                0
            ) AS spent_availment,


            COALESCE(
                pp.spent_proposals,
                0
            ) AS spent_proposals


        FROM program p


        /*
        |--------------------------------------------------------------------------
        | RELEASED AVAILMENTS ONLY
        |--------------------------------------------------------------------------
        */

        LEFT JOIN (

            SELECT

                a.program_id,

                SUM(a.av_amount)
                    AS spent_availment


            FROM availment a


            JOIN program p2

                ON p2.program_id =
                   a.program_id


            WHERE a.av_status = 'Released'

              AND p2.program_name
                  IN ($placeholders)


            GROUP BY a.program_id

        ) av

            ON av.program_id =
               p.program_id


        /*
        |--------------------------------------------------------------------------
        | RELEASED PROJECT PROPOSALS ONLY
        |--------------------------------------------------------------------------
        */

        LEFT JOIN (

            SELECT

                pp.program_id,

                SUM(pp.pp_budget)
                    AS spent_proposals


            FROM project_proposal pp


            JOIN program p3

                ON p3.program_id =
                   pp.program_id


            WHERE pp.pp_status = 'Released'

              AND p3.program_name
                  IN ($placeholders)


            GROUP BY pp.program_id

        ) pp

            ON pp.program_id =
               p.program_id


        /*
        |--------------------------------------------------------------------------
        | SELECT REQUESTED PROGRAMS
        |--------------------------------------------------------------------------
        */

        WHERE p.program_name
              IN ($placeholders)

    ");


    /*
    |--------------------------------------------------------------------------
    | There are 3 IN (...) sections:
    |
    | 1. AVAILMENT
    | 2. PROJECT_PROPOSAL
    | 3. PROGRAM
    |
    | Therefore the program names are bound 3 times.
    |--------------------------------------------------------------------------
    */

    $stmt->execute(

        array_merge(

            $programNames,

            $programNames,

            $programNames

        )

    );


    $rows =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    $total =
        0.0;


    $spentAvailment =
        0.0;


    $spentProposals =
        0.0;


    foreach ($rows as $r) {

        $total +=
            (float) $r['total'];


        $spentAvailment +=
            (float) $r['spent_availment'];


        $spentProposals +=
            (float) $r['spent_proposals'];
    }


    /*
    |--------------------------------------------------------------------------
    | Calculate final totals
    |--------------------------------------------------------------------------
    */

    $spent =
        $spentAvailment
        + $spentProposals;


    $remaining =
        $total
        - $spent;


    return [

        'total' =>
            $total,

        'spent_availment' =>
            $spentAvailment,

        'spent_proposals' =>
            $spentProposals,

        'spent' =>
            $spent,

        'remaining' =>
            $remaining,

        'pct_used' =>
            $total > 0
                ? round(
                    ($spent / $total) * 100
                )
                : 0,

    ];
}


/*
|--------------------------------------------------------------------------
| GET PROGRAM ID
|--------------------------------------------------------------------------
|
| Finds the program_id using the program name.
|
|--------------------------------------------------------------------------
*/

function getProgramId(
    PDO $pdo,
    string $programName
): ?int {

    $stmt = $pdo->prepare("

        SELECT program_id

        FROM program

        WHERE program_name = ?

        LIMIT 1

    ");


    $stmt->execute([
        $programName
    ]);


    $id =
        $stmt->fetchColumn();


    return $id !== false
        ? (int) $id
        : null;
}


/*
|--------------------------------------------------------------------------
| GET PROJECT PROPOSALS FOR A PROGRAM
|--------------------------------------------------------------------------
|
| Used by:
|
| funds_4ps.php
| funds_slp.php
| funds_sfp.php
| funds_daycare.php
| funds_wac.php
| funds_senior.php
| funds_pwd.php
| funds_soloparents.php
|
|--------------------------------------------------------------------------
*/

function getFundRequests(
    PDO $pdo,
    string $programName
): array {

    $stmt = $pdo->prepare("

        SELECT

            pp.proposal_id,

            pp.pp_title,

            pp.pp_date_from,

            pp.pp_date_to,

            pp.pp_venue,

            pp.pp_num_participants,

            pp.pp_participant_desc,

            pp.pp_budget,

            pp.pp_fund_source,

            pp.pp_date_submitted,

            pp.pp_status,

            pp.pp_date_released

        FROM project_proposal pp

        JOIN program p

            ON p.program_id =
               pp.program_id

        WHERE p.program_name = ?

        ORDER BY
            pp.pp_date_submitted DESC

    ");


    $stmt->execute([
        $programName
    ]);


    $rows = [];


    foreach (
        $stmt->fetchAll(PDO::FETCH_ASSOC)
        as $r
    ) {

        $from =
            new DateTime(
                $r['pp_date_from']
            );


        $to =
            new DateTime(
                $r['pp_date_to']
            );


        /*
        |--------------------------------------------------------------------------
        | +1 because both the start and end
        | dates are included.
        |--------------------------------------------------------------------------
        */

        $days =
            $from->diff($to)->days + 1;


        $rows[] = [

            'id' =>
                (int) $r['proposal_id'],

            'title' =>
                $r['pp_title'],

            'duration' =>
                $days
                . ' '
                . (
                    $days === 1
                        ? 'day'
                        : 'days'
                ),

            'venue' =>
                $r['pp_venue'],

            'participants' =>
                trim(
                    $r['pp_num_participants']
                    . ' '
                    . $r['pp_participant_desc']
                ),

            'budget' =>
                (float) $r['pp_budget'],

            'fundSource' =>
                $r['pp_fund_source'],

            'date' =>
                (
                    new DateTime(
                        $r['pp_date_submitted']
                    )
                )->format('Y-m-d'),

            'status' =>
                $r['pp_status'] ?? 'Approved',

            'dateReleased' =>
                !empty($r['pp_date_released'])
                    ? (
                        new DateTime(
                            $r['pp_date_released']
                        )
                    )->format('Y-m-d')
                    : null,

        ];
    }


    return $rows;
}