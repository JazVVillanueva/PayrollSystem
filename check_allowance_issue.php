<?php
require 'db_connect.php';

$overshot = ['Hammond, Jim', 'McCoy, Henry', 'Raymond, Ronnie', 'Rhodes, James', 'Palmer, Ray', 'Richards, Reed'];

foreach ($overshot as $emp) {
    $rate_q = $conn->query("SELECT rate FROM employees WHERE name='$emp'");
    $rate = $rate_q->fetch_assoc()['rate'];
    
    echo "\n=== $emp ===\n";
    echo "Rate: $rate\n";
    echo "Gets allowance: " . ($rate > 520 ? "YES (20/day)" : "NO") . "\n";
    
    // Count their work days now
    $days_q = $conn->query("SELECT COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='$emp' AND Date BETWEEN '2025-01-03' AND '2025-01-30' AND (Short_Misload_Bonus_SIL NOT LIKE '%SIL%' OR Short_Misload_Bonus_SIL = '' OR Short_Misload_Bonus_SIL IS NULL)");
    $days = $days_q->fetch_assoc()['days'];
    echo "Work days: $days\n";
    
    // If they get allowance, adding 1 day gives them: rate + 20 allowance
    if ($rate > 520) {
        echo "Adding 1 day gives: $rate (pay) + 20 (allowance) = " . ($rate + 20) . "\n";
    } else {
        echo "Adding 1 day gives: $rate (pay only)\n";
    }
}

echo "\n\nConclusion: Employees with rate > 520 get extra allowance when we add days!\n";
echo "So they overshoot. We need to account for this or adjust the allowance calculation.\n";
?>
