<?php
// Include the database connection
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the JSON data from the form
    $jsonData = $_POST['organizations'];
    $organizations = json_decode($jsonData, true);

    function addOrganizations($organizations, $parentId = null) {
        global $pdo;
        foreach ($organizations as $organization) {
            $orgName = $organization['org_name'];
            // Insert the organization into the database
            $stmt = $pdo->prepare("INSERT INTO organizations (name) VALUES (:name) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
            $stmt->execute(['name' => $orgName]);
            $orgId = $pdo->lastInsertId();

            // If there's a parent ID, create a parent-child relationship
            if ($parentId) {
                $stmt = $pdo->prepare("INSERT INTO relationships (parent_id, child_id) VALUES (:parent_id, :child_id)");
                $stmt->execute(['parent_id' => $parentId, 'child_id' => $orgId]);
            }

            // Recursively add daughters
            if (isset($organization['daughters'])) {
                addOrganizations($organization['daughters'], $orgId);
            }
        }
    }

    // Call the function to add organizations
    addOrganizations($organizations);
    echo "Organizations added successfully!";
}
?>
