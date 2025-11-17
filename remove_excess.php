<?php
require 'db_connect.php';

echo "Removing excess days from overpaid employees...\n\n";

$conn->query("DELETE FROM timesheet WHERE Name='McCoy, Henry' AND Date='2025-01-05' LIMIT 1");
echo "Removed McCoy, Henry 2025-01-05\n";

$conn->query("DELETE FROM timesheet WHERE Name='Raymond, Ronnie' AND Date='2025-01-05' LIMIT 1");
echo "Removed Raymond, Ronnie 2025-01-05\n";

$conn->query("DELETE FROM timesheet WHERE Name='Richards, Reed' AND Date='2025-01-05' LIMIT 1");
echo "Removed Richards, Reed 2025-01-05\n";

echo "\nDone!\n";
$conn->close();
?>
