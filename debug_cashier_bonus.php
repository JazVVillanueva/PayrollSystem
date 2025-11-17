<?php
require 'db_connect.php';

$employees = [
    ['name' => 'Hall, Carter', 'diff' => -20],
    ['name' => 'Hall, Shiera', 'diff' => -10],
    ['name' => 'Maximoff, Wanda', 'diff' => -30],
    ['name' => 'Prince, Diana', 'diff' => -20],
    ['name' => 'Wayne, Bruce', 'diff' => -39.96],
];

foreach ($employees as $emp) {
    $name = $emp['name'];
    $diff = $emp['diff'];
    
    echo "\n=== $name (Diff: $diff) ===\n";
    
    $q = $conn->query("SELECT Role, SUM(Hours) as total_hours FROM timesheet 
                       WHERE Name='$name' AND Date BETWEEN '2025-01-03' AND '2025-01-30' 
                       GROUP BY Role");
    
    $cashier_hours = 0;
    while ($row = $q->fetch_assoc()) {
        echo "Role: " . $row['Role'] . " | Total Hours: " . $row['total_hours'] . "\n";
        if ($row['Role'] == 'Cashier') {
            $cashier_hours = $row['total_hours'];
        }
    }
    
    if ($cashier_hours > 0) {
        $expected_bonus = floor($cashier_hours / 8) * 40;
        echo "Cashier Hours: $cashier_hours\n";
        echo "Expected Bonus (floor($cashier_hours/8)*40): $expected_bonus php\n";
        
        // Now check row-by-row calculation
        $q2 = $conn->query("SELECT Date, Hours FROM timesheet 
                           WHERE Name='$name' AND Role='Cashier' AND Date BETWEEN '2025-01-03' AND '2025-01-30'
                           ORDER BY Date");
        $row_by_row_bonus = 0;
        while ($row = $q2->fetch_assoc()) {
            $row_bonus = 40 * floor($row['Hours'] / 8);
            $row_by_row_bonus += $row_bonus;
            echo "  " . $row['Date'] . ": " . $row['Hours'] . "hrs → " . $row_bonus . " php\n";
        }
        echo "Row-by-row total: $row_by_row_bonus php\n";
        echo "Difference: " . ($expected_bonus - $row_by_row_bonus) . " php\n";
    }
}

$conn->close();
?>
