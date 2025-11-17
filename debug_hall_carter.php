<?php
require 'db_connect.php';

$name = 'Hall, Carter';
$startDate = '2025-01-03';
$endDate = '2025-01-30';

echo "=== Hall Carter Detailed Breakdown ===\n\n";

// Get all cashier entries
$q = $conn->query("SELECT Date, Role, Hours, CAST(Bonus AS DECIMAL(10,2)) as Bonus FROM timesheet 
                   WHERE Name='$name' AND Date BETWEEN '$startDate' AND '$endDate' ORDER BY Date");

$total_cashier_hours = 0;
$total_bonus_from_db = 0;

echo "Date       | Role      | Hours | Bonus\n";
echo "-----------|-----------|-------|-------\n";

while ($row = $q->fetch_assoc()) {
    if ($row['Role'] == 'Cashier') {
        $total_cashier_hours += $row['Hours'];
    }
    $total_bonus_from_db += $row['Bonus'];
    echo str_pad($row['Date'], 10) . " | " . str_pad($row['Role'], 9) . " | " . str_pad($row['Hours'], 5) . " | " . $row['Bonus'] . "\n";
}

echo "\n--- Summary ---\n";
echo "Total Cashier Hours: $total_cashier_hours\n";
echo "Expected Cashier Bonus (floor(172/8)*40): " . (floor($total_cashier_hours / 8) * 40) . " php\n";
echo "Total Bonus from DB: $total_bonus_from_db php\n";
echo "Difference: -20 php (from comparison report)\n";

$conn->close();
?>
