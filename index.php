<!DOCTYPE html>
<html>
<head>
    <title>Test API</title>
</head>
<body>
    <h1>Test Add Organizations</h1>
    <form action="add_organizations.php" method="POST">
        <textarea name="organizations" rows="10" cols="50"></textarea><br>
        <input type="submit" value="Submit">
    </form>

    <h1>Test Get Relations</h1>
    <form action="get_relations.php" method="GET">
        <input type="text" name="org_name" placeholder="Organization Name"><br>
        <input type="submit" value="Submit">
    </form>
</body>
</html>
