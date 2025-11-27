<?php
require_once '../includes/db.php';

$pdo = getDB();

// WHERE does MySQL store THIS database physically?
$path = $pdo->query("SELECT @@datadir")->fetchColumn();

// Which database is THIS CONNECTION actually using?
$db = $pdo->query("SELECT DATABASE()")->fetchColumn();

// Which tables exist in this actual DB?
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>CONNECTED DATABASE INFO</h2>";
echo "<p><strong>Current DB:</strong> $db</p>";
echo "<p><strong>Physical Path:</strong> $path</p>";

echo "<h3>Tables in THIS database:</h3>";
foreach ($tables as $t) {
    echo "$t<br>";
}
?>
