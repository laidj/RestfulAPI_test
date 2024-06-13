<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jsonData = $_POST['organizations'];
    $organizations = json_decode($jsonData, true);

    function addOrganizations($organizations, $parentId = null) {
        global $pdo;
        foreach ($organizations as $organization) {
            $orgName = $organization['org_name'];
            $stmt = $pdo->prepare("INSERT INTO organizations (name) VALUES (:name) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
            $stmt->execute(['name' => $orgName]);
            $orgId = $pdo->lastInsertId();

            if ($parentId) {
                $stmt = $pdo->prepare("INSERT INTO relationships (parent_id, child_id) VALUES (:parent_id, :child_id)");
                $stmt->execute(['parent_id' => $parentId, 'child_id' => $orgId]);
            }

            if (isset($organization['daughters'])) {
                addOrganizations($organization['daughters'], $orgId);
            }
        }
    }

    addOrganizations($organizations);
    echo "Organizations added successfully!";
}
?>
