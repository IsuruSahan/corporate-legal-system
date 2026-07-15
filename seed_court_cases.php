<?php
// 1. Load the database connection layer
require_once __DIR__ . '/config/database.php';

// Force error visibility to track running loops
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

try {
    echo "Starting background seeder loop for Court Cases...\n\n";

    // 2. Collect existing data keys to keep relationships valid
    $companies  = $pdo->query("SELECT id FROM group_companies")->fetchAll(PDO::FETCH_COLUMN);
    $officers   = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
    $courts     = $pdo->query("SELECT id FROM court_rooms")->fetchAll(PDO::FETCH_COLUMN);
    $cabinets   = $pdo->query("SELECT id FROM archive_cabinets")->fetchAll(PDO::FETCH_COLUMN);
    $agreements = $pdo->query("SELECT id FROM agreements")->fetchAll(PDO::FETCH_COLUMN);

    // Stop execution if setup prerequisites are missing
    if (empty($companies) || empty($officers) || empty($courts) || empty($cabinets)) {
        die("Error: Seeding failed. Please make sure you have records inside 'group_companies', 'users', 'court_rooms', and 'archive_cabinets' tables before running this script.\n");
    }

    // 3. Define pools of mock text arrays
    $mockParties = [
        "Ben Holdings Ltd vs. Logistics Provider Ltd",
        "Aura Holdings Pvt Ltd vs. Inland Revenue Department",
        "Ben Holdings Ltd vs. Tech Solutions Inc",
        "Labor Union vs. EBC Subsidiary",
        "Ben Green Energy vs. Environmental Council",
        "Customs Department vs. Ben Logistics Hub",
        "Nexus Marketing Media vs. Ben Holdings Ltd",
        "Ben Food Services vs. Master Vendor Group",
        "CleanCare Group Ltd vs. EBC Facility Management",
        "Ben Hardware Providers vs. Core Insurance Corp"
    ];

    $mockCounsel = [
        "Akib Ahmad, Senior Counsel",
        "Nimmi Silva, Legal Counsel",
        "K. D. Perera, PC",
        "Fathima Asma, Attorney-at-Law",
        "Sahan Jayasinghe, Senior Counsel"
    ];

    $mockInstructing = [
        "Nimmi Silva, Legal Officer",
        "Thilina Fernando, Legal Assistant",
        "A. R. Mohamed, Attorney",
        "Priyantha Bandara, Legal Coordinator",
        "Dilini Wickramasinghe, Compliance Officer"
    ];

    $mockDescriptions = [
        "Breach of contract regarding transport delays and damaged cargo items.",
        "Appeal against arbitrary tax assessments applied on group operational inputs.",
        "Dispute over unpaid software development milestones and source code deployment.",
        "Arbitration regarding secondary school grade level structures and salary metrics.",
        "Challenge against environmental compliance evaluation boundaries.",
        "Clearance hold dispute concerning missing clearance declarations.",
        "Claim regarding outstanding marketing campaign fees and service levels.",
        "Quality non-compliance issue regarding supply chain distributions.",
        "Contractual disagreement regarding building maintenance schedules.",
        "Insurance claim recovery for fire damage at the primary server vault room."
    ];

    $mockNextSteps = [
        "Filing of replication by Plaintiff",
        "Filing of answer by Defendant",
        "Submission of certified document lists",
        "Cross-examination of primary witness",
        "Filing of written submissions"
    ];

    $statuses = ['Filing Stage', 'In Progress', 'Settled', 'Appealed'];

    // 4. Prepare core MySQL insertion command statement
    $sql = "INSERT INTO court_cases (
                group_company_id, case_number, case_parties, assigned_officer_id, 
                court_id, counsel_name, instructing_attorney, case_description, 
                next_hearing_date, next_step_date, next_step_description, 
                cabinet_id, linked_agreement_id, case_status, internal_comments, 
                pa_ref_number, ecf_ref_number, file_attachment_path
            ) VALUES (
                :group_company_id, :case_number, :case_parties, :assigned_officer_id, 
                :court_id, :counsel_name, :instructing_attorney, :case_description, 
                :next_hearing_date, :next_step_date, :next_step_description, 
                :cabinet_id, :linked_agreement_id, :case_status, :internal_comments, 
                :pa_ref_number, :ecf_ref_number, :file_attachment_path
            )";
            
    $stmt = $pdo->prepare($sql);

    // 5. Generate 10 entries sequentially
    for ($i = 1; $i <= 10; $i++) {
        // Build random hearing and step dates within 2026
        $hearingDay = rand(1, 28);
        $hearingMonth = rand(7, 12); // Future dates for the dashboard alerts
        $nextHearingDate = sprintf("2026-%02d-%02d", $hearingMonth, $hearingDay);
        
        $stepDay = rand(1, 28);
        $stepMonth = rand(1, 6);
        $nextStepDate = sprintf("2026-%02d-%02d", $stepMonth, $stepDay);

        $caseCode = "CASE-2026-" . sprintf("%03d", rand(1, 999));
        
        // Optional agreement link picker
        $linkedAgreement = (!empty($agreements) && rand(0, 1) == 1) ? $agreements[array_rand($agreements)] : null;
        
        // Sometimes leave financial codes empty to populate the dashboard missing stats
        $paCode = (rand(0, 3) > 0) ? "PA-CC-" . rand(2000, 9999) : "";
        $ecfCode = (rand(0, 3) > 0) ? "ECF-CC-" . rand(2000, 9999) : "";

        // Set up values array parameter data
        $params = [
            ':group_company_id'      => $companies[array_rand($companies)],
            ':case_number'           => $caseCode,
            ':case_parties'          => $mockParties[array_rand($mockParties)],
            ':assigned_officer_id'   => $officers[array_rand($officers)],
            ':court_id'              => $courts[array_rand($courts)],
            ':counsel_name'          => $mockCounsel[array_rand($mockCounsel)],
            ':instructing_attorney'  => $mockInstructing[array_rand($mockInstructing)],
            ':case_description'      => $mockDescriptions[array_rand($mockDescriptions)],
            ':next_hearing_date'     => $nextHearingDate,
            ':next_step_date'        => $nextStepDate,
            ':next_step_description' => $mockNextSteps[array_rand($mockNextSteps)],
            ':cabinet_id'            => $cabinets[array_rand($cabinets)],
            ':linked_agreement_id'   => $linkedAgreement,
            ':case_status'           => $statuses[array_rand($statuses)],
            ':internal_comments'     => "Automated system case record generated safely for system testing purposes.",
            ':pa_ref_number'         => $paCode,
            ':ecf_ref_number'        => $ecfCode,
            ':file_attachment_path'  => '[]'
        ];

        $stmt->execute($params);
        echo "Inserted Case {$i}: {$caseCode} - Assigned successfully.\n";
    }

    echo "\n✔ Seeding successful! 10 mock court case entries added to the database.\n";

} catch (PDOException $e) {
    echo "\n❌ Database Write Failure: " . $e->getMessage() . "\n";
}