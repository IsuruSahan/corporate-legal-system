<?php
// 1. Load the database connection layer
require_once __DIR__ . '/config/database.php';

// Force error visibility to track running loops
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

try {
    echo "Starting background seeder loop...\n\n";

    // 2. Collect existing data keys to keep relationships valid
    $companies  = $pdo->query("SELECT id FROM group_companies")->fetchAll(PDO::FETCH_COLUMN);
    $officers   = $pdo->query("SELECT id FROM users WHERE role IN ('Admin', 'Staff')")->fetchAll(PDO::FETCH_COLUMN);
    $categories = $pdo->query("SELECT id FROM agreement_categories")->fetchAll(PDO::FETCH_COLUMN);
    $cabinets   = $pdo->query("SELECT id FROM archive_cabinets")->fetchAll(PDO::FETCH_COLUMN);

    // Stop execution if setup prerequisites are missing
    if (empty($companies) || empty($officers) || empty($categories) || empty($cabinets)) {
        die("Error: Seeding failed. Please make sure you have records inside 'group_companies', 'users', 'agreement_categories', and 'archive_cabinets' tables before running this script.\n");
    }

    // 3. Define pools of mock text arrays
    $mockTitles = [
        "Office Space Lease Extension",
        "Master Marketing Service Retainer",
        "Non-Disclosure Mutual Agreement",
        "Software Licences Supply Procurement",
        "Corporate Catering Vendor Contract",
        "Cloud Hosting Infrastructure Agreement",
        "External Auditing Services Framework",
        "Logistics Handling Agreement",
        "Janitorial Facility Maintenance Contract",
        "Hardware Equipment Procurement Lease"
    ];

    $mockPartiesB = [
        "Aura Holdings Pvt Ltd",
        "Nexus Marketing Media",
        "Vertex Global Solutions",
        "Colombo Tech Labs",
        "Island Food Services",
        "Apex Systems International",
        "Matrix Audit Partners",
        "Swift Logistics Hub",
        "CleanCare Group Ltd",
        "Cyber Hardware Providers"
    ];

    $statuses = ['Active', 'Pending', 'Renewing'];

    // 4. Prepare core MySQL insertion command statement
    $sql = "INSERT INTO agreements (
                group_company_id, title, party_b, assigned_officer_id, 
                category_id, physical_ref_no, cabinet_id, effective_date, 
                expiry_date, initial_status, internal_comments, pa_ref_number, 
                ecf_ref_number, file_attachment_path
            ) VALUES (
                :group_company_id, :title, :party_b, :assigned_officer_id, 
                :category_id, :physical_ref_no, :cabinet_id, :effective_date, 
                :expiry_date, :initial_status, :internal_comments, :pa_ref_number, 
                :ecf_ref_number, :file_attachment_path
            )";
            
    $stmt = $pdo->prepare($sql);

    // 5. Generate 10 entries sequentially
    for ($i = 1; $i <= 10; $i++) {
        // Build random dates within the year 2026
        $startDay = rand(1, 28);
        $startMonth = rand(1, 6);
        $effectiveDate = sprintf("2026-%02d-%02d", $startMonth, $startDay);
        $expiryDate = sprintf("2027-%02d-%02d", $startMonth, $startDay); // 1 year lease lifecycle

        // Pick unique string fragments
        $randomTitle = $mockTitles[array_rand($mockTitles)] . " (Batch " . rand(10, 99) . ")";
        $randomPartyB = $mockPartiesB[array_rand($mockPartiesB)];
        $systemUniqueCode = "AGR-2026-MOCK" . rand(1000, 9999);
        
        // Sometimes leave financial codes empty to match dynamic dashboard alerts
        $paCode = (rand(0, 3) > 0) ? "PA-" . rand(2000, 9999) : "";
        $ecfCode = (rand(0, 3) > 0) ? "ECF-" . rand(2000, 9999) : "";

        // Set up values array parameter data
        $params = [
            ':group_company_id'     => $companies[array_rand($companies)],
            ':title'                => $randomTitle,
            ':party_b'              => $randomPartyB,
            ':assigned_officer_id'  => $officers[array_rand($officers)],
            ':category_id'          => $categories[array_rand($categories)],
            ':physical_ref_no'      => $systemUniqueCode,
            ':cabinet_id'           => $cabinets[array_rand($cabinets)],
            ':effective_date'       => $effectiveDate,
            ':expiry_date'          => $expiryDate,
            ':initial_status'       => $statuses[array_rand($statuses)],
            ':internal_comments'    => "Automated system test data record entry generated safely for verification purposes.",
            ':pa_ref_number'        => $paCode,
            ':ecf_ref_number'       => $ecfCode,
            ':file_attachment_path' => '[]' // Plain array syntax structure string
        ];

        $stmt->execute($params);
        echo "Inserted Record {$i}: {$systemUniqueCode} - {$randomTitle}\n";
    }

    echo "\n✔ Seeding successful! 10 mock agreement entries added to the database.\n";

} catch (PDOException $e) {
    echo "\n❌ Database Write Failure: " . $e->getMessage() . "\n";
}