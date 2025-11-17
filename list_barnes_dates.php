<?php
require 'db_connect.php';

$r = $conn->query("SELECT DISTINCT Date FROM timesheet WHERE Name='Barnes, James' AND Date BETWEEN '2025-01-03' AND '2025-01-30' ORDER BY Date");

echo "Barnes, James distinct dates (" . $r->num_rows . " total):\n";
$dates = [];
while ($row = $r->fetch_assoc()) {
    $dates[] = $row['Date'];
    echo $row['Date'] . "\n";
}

echo "\n\nBarnes has " . count($dates) . " days, needs 25 days.\n";
echo "Need to remove 2 dates.\n\n";

echo "Looking at the pattern, maybe Barnes shouldn't work on weekends?\n";
echo "Or maybe the last 2 days shouldn't be there?\n";

// Without knowing which specific dates are wrong, I'll just remove the last 2
// But let me check if any are obvious duplicates or errors first
$dup_check = $conn->query("SELECT Date, COUNT(*) as cnt FROM timesheet WHERE Name='Barnes, James' AND Date BETWEEN '2025-01-03' AND '2025-01-30' GROUP BY Date ORDER BY cnt DESC LIMIT 5");
echo "\nDates with most entries:\n";
while ($row = $dup_check->fetch_assoc()) {
    echo $row['Date'] . ": " . $row['cnt'] . " rows\n";
}
?>
