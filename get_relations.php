<?php
// Include the database connection
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['org_name'])) {
    $orgName = $_GET['org_name'];

    // Get the organization's ID
    $stmt = $pdo->prepare("SELECT id FROM organizations WHERE name = :name");
    $stmt->execute(['name' => $orgName]);
    $orgId = $stmt->fetchColumn();

    if ($orgId) {
        // Get parents
        $stmt = $pdo->prepare("SELECT DISTINCT o2.name AS org_name, 'parent' AS relationship_type 
                                FROM relationships r 
                                JOIN organizations o1 ON r.child_id = o1.id 
                                JOIN organizations o2 ON r.parent_id = o2.id 
                                WHERE o1.id = :org_id");
        $stmt->execute(['org_id' => $orgId]);
        $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get daughters
        $stmt = $pdo->prepare("SELECT DISTINCT o2.name AS org_name, 'daughter' AS relationship_type 
                                FROM relationships r 
                                JOIN organizations o1 ON r.parent_id = o1.id 
                                JOIN organizations o2 ON r.child_id = o2.id 
                                WHERE o1.id = :org_id");
        $stmt->execute(['org_id' => $orgId]);
        $daughters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get sisters
        $stmt = $pdo->prepare("SELECT DISTINCT o3.name AS org_name, 'sister' AS relationship_type 
                                FROM relationships r1 
                                JOIN organizations o1 ON r1.child_id = o1.id 
                                JOIN relationships r2 ON r1.parent_id = r2.parent_id 
                                JOIN organizations o3 ON r2.child_id = o3.id 
                                WHERE o1.id = :org_id AND o3.id != :org_id");
        $stmt->execute(['org_id' => $orgId]);
        $sisters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Merge results
        $relations = array_merge($parents, $daughters, $sisters);

        // Remove duplicates by converting to unique associative array
        $unique_relations = [];
        foreach ($relations as $relation) {
            $key = $relation['org_name'] . '_' . $relation['relationship_type'];
            $unique_relations[$key] = $relation;
        }

        // Convert back to indexed array
        $unique_relations = array_values($unique_relations);

        // Output the results in pretty print
        header('Content-Type: application/json');
        echo json_encode($unique_relations, JSON_PRETTY_PRINT);
    } else {
        echo json_encode([]);
    }
}
?>
