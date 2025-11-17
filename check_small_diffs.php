<?php
require 'db_connect.php';

$small_diff = ['Curry, Arthur', 'Hall, Carter', 'Hall, Shiera', 'Jordan, Hal', 'Maximoff, Wanda', 
               'Murdock, Matthew', 'Palmer, Ray', 'Prince, Diana', 'Wayne, Bruce', 'Stark, Toni'];

foreach ($small_diff as $emp) {
    echo "\n=== $emp ===\n";
    
    // Check if they have cashier role
    $cashier_q = $conn->query("SELECT SUM(Hours) as hours FROM timesheet WHERE Name='$emp' AND Role='Cashier' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
    $cashier_hours = $cashier_q->fetch_assoc()['hours'] ?? 0;
    
    // Check business unit
    $unit_q = $conn->query("SELECT Business_Unit, COUNT(*) as cnt FROM timesheet WHERE Name='$emp' AND Date BETWEEN '2025-01-03' AND '2025-01-30' GROUP BY Business_Unit ORDER BY cnt DESC LIMIT 1");
    $unit = $unit_q->fetch_assoc()['Business_Unit'] ?? 'Unknown';
    
    // Check rate
    $rate_q = $conn->query("SELECT rate FROM employees WHERE name='$emp'");
    $rate = $rate_q->fetch_assoc()['rate'];
    
    echo "Business Unit: $unit\n";
    echo "Rate: $rate\n";
    echo "Cashier Hours: $cashier_hours\n";
    echo "Expected Cashier Bonus: " . (floor($cashier_hours / 8) * 40) . " php\n";
}

$conn->close();
?>
