<?php
require 'db_connect.php';

$name = 'Hall, Carter';
echo "=== Detailed Breakdown for $name ===\n\n";

// Get all records
$q = $conn->query("SELECT * FROM timesheet WHERE Name='$name' AND Date BETWEEN '2025-01-03' AND '2025-01-30' ORDER BY Date, Time_In");

$total_crew_hours = 0;

while ($row = $q->fetch_assoc()) {
    if ($row['Role'] == 'Crew') {
        $total_crew_hours += $row['Hours'];
        echo $row['Date'] . " | Crew | " . $row['Hours'] . "hrs | " . $row['Remarks'] . "\n";
    }
}

echo "\nTotal Crew Hours: $total_crew_hours\n";
echo "Potential Crew Bonus (40 * floor($total_crew_hours/8)): " . (40 * floor($total_crew_hours/8)) . " php\n";
echo "Actual difference: -20 php\n";

$conn->close();
?>
