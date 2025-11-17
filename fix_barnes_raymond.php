<?php
require 'db_connect.php';

echo "=== Fixing Barnes James (remove duplicates) ===\n";

// Get duplicate entries for Barnes
$dups = $conn->query("SELECT Date, id, Time_IN, Time_OUT, Hours FROM timesheet WHERE Name='Barnes, James' AND (Date='2025-01-16' OR Date='2025-01-30') ORDER BY Date, id");

$seen = [];
$to_delete = [];

while ($row = $dups->fetch_assoc()) {
    $key = $row['Date'] . '|' . $row['Time_IN'] . '|' . $row['Time_OUT'] . '|' . $row['Hours'];
    if (isset($seen[$key])) {
        $to_delete[] = $row['id'];
        echo "Will delete duplicate: ID=" . $row['id'] . ", Date=" . $row['Date'] . "\n";
    } else {
        $seen[$key] = true;
    }
}

foreach ($to_delete as $id) {
    $conn->query("DELETE FROM timesheet WHERE id=$id");
}

echo "\nDeleted " . count($to_delete) . " duplicate rows\n";

// Verify
$barnes_days = $conn->query("SELECT COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='Barnes, James' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
$bd = $barnes_days->fetch_assoc();
echo "Barnes now has " . $bd['days'] . " days\n";

echo "\n=== Fixing Raymond Ronnie ===\n";
// Raymond was already fixed above but let me verify
$ray_days = $conn->query("SELECT COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='Raymond, Ronnie' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
$rd = $ray_days->fetch_assoc();
echo "Raymond has " . $rd['days'] . " days (expected 25)\n";

$conn->close();
?>
