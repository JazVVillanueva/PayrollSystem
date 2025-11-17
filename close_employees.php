<?php
require 'db_connect.php';

$close_employees = ['Hall, Shiera', 'Curry, Arthur', 'Maximoff, Wanda', 'Wayne, Bruce', 'Parker, Peter'];

foreach ($close_employees as $emp) {
    echo "\n=== $emp ===\n";
    
    // Get rate
    $rate_q = $conn->query("SELECT rate FROM employees WHERE name='$emp'");
    $rate_row = $rate_q->fetch_assoc();
    $rate = $rate_row['rate'];
    
    // Count days and roles
    $days_q = $conn->query("SELECT COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='$emp' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
    $days_row = $days_q->fetch_assoc();
    $days = $days_row['days'];
    
    // Check if cashier
    $cash_q = $conn->query("SELECT Role, Hours FROM timesheet WHERE Name='$emp' AND Role='Cashier' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
    $cashier_hours = 0;
    while ($c = $cash_q->fetch_assoc()) {
        $cashier_hours += (float)$c['Hours'];
    }
    
    echo "Rate: $rate\n";
    echo "Days: $days\n";
    echo "Cashier Hours: $cashier_hours\n";
    echo "Expected Allowance (if rate>520): " . ($rate > 520 ? ($days * 20) : 0) . "\n";
    echo "Expected Cashier Bonus: " . (floor($cashier_hours / 8) * 40) . "\n";
}
?>
