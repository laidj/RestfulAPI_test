<?php
header("Content-Type: application/json");
$host = '127.0.0.1';
$db = 'organization_db';
$user = 'root';
$pass = 'jakob';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST' && $_SERVER['REQUEST_URI'] == '/addOrganizations') {
    $data = json_decode(file_get_contents('php://input'), true);
    addOrganizations($pdo, $data);
} elseif ($method == 'GET' && isset($_GET['org_name'])) {
    $orgName = $_GET['org_name'];
    getRelations($pdo, $orgName);
} else {
    echo json_encode(['error' => 'Invalid request']);
}

function addOrganizations($pdo, $data) {
    $orgs = [];

    function insertOrganization($pdo, $org, &$orgs) {
        if (!isset($orgs[$org['org_name']])) {
            $stmt = $pdo->prepare("INSERT INTO organizations (org_name) VALUES (:org_name)");
            $stmt->execute(['org_name' => $org['org_name']]);
            $orgs[$org['org_name']] = $pdo->lastInsertId();
        }

        if (isset($org['daughters'])) {
            foreach ($org['daughters'] as $daughter) {
                insertOrganization($pdo, $daughter, $orgs);
                $stmt = $pdo->prepare("INSERT INTO relations (parent_id, child_id) VALUES (:parent_id, :child_id)");
                $stmt->execute(['parent_id' => $orgs[$org['org_name']], 'child_id' => $orgs[$daughter['org_name']]]);
            }
        }
    }

    foreach ($data as $org) {
        insertOrganization($pdo, $org, $orgs);
    }

    echo json_encode(['status' => 'success']);
}

function getRelations($pdo, $orgName) {
    $stmt = $pdo->prepare("SELECT id FROM organizations WHERE org_name = :org_name");
    $stmt->execute(['org_name' => $orgName]);
    $org = $stmt->fetch();

    if (!$org) {
        echo json_encode(['error' => 'Organization not found']);
        return;
    }

    $orgId = $org['id'];

    $stmt = $pdo->prepare("
        SELECT 'parent' AS relationship_type, o2.org_name
        FROM relations r
        JOIN organizations o1 ON r.child_id = o1.id
        JOIN organizations o2 ON r.parent_id = o2.id
        WHERE o1.id = :org_id
        UNION
        SELECT 'daughter' AS relationship_type, o2.org_name
        FROM relations r
        JOIN organizations o1 ON r.parent_id = o1.id
        JOIN organizations o2 ON r.child_id = o2.id
        WHERE o1.id = :org_id
        UNION
        SELECT 'sister' AS relationship_type, o3.org_name
        FROM relations r1
        JOIN relations r2 ON r1.parent_id = r2.parent_id AND r1.child_id != r2.child_id
        JOIN organizations o1 ON r1.child_id = o1.id
        JOIN organizations o2 ON r2.child_id = o2.id
        JOIN organizations o3 ON r2.child_id = o3.id
        WHERE o1.id = :org_id
    ");
    $stmt->execute(['org_id' => $orgId]);
    $relations = $stmt->fetchAll();

    echo json_encode($relations);
}
?>
