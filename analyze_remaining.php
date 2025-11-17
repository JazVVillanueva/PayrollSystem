<?php
require 'db_connect.php';

echo "=== ANALYZING REMAINING 13 EMPLOYEES ===\n\n";

$remaining = [
    'Curry, Arthur' => -19.50,
    'Hall, Carter' => -20.00,
    'Hall, Shiera' => -10.00,
    'Jordan, Hal' => -230.00,
    'Maximoff, Wanda' => -30.00,
    'McCoy, Henry' => 620.00,
    'Murdock, Matthew' => -86.67,
    'Palmer, Ray' => -88.00,
    'Prince, Diana' => -20.00,
    'Raymond, Ronnie' => 520.00,
    'Richards, Reed' => 655.00,
    'Stark, Toni' => -212.50,
    'Wayne, Bruce' => -39.96,
];

foreach ($remaining as $emp => $diff) {
    echo "\n=== $emp (Diff: $diff) ===\n";
    
    // Get rate and current days
    $rate_q = $conn->query("SELECT rate FROM employees WHERE name='$emp'");
    $rate = $rate_q->fetch_assoc()['rate'];
    
    $days_q = $conn->query("SELECT COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='$emp' AND Date BETWEEN '2025-01-03' AND '2025-01-30' AND (Short_Misload_Bonus_SIL NOT LIKE '%SIL%' OR Short_Misload_Bonus_SIL = '' OR Short_Misload_Bonus_SIL IS NULL)");
    $days = $days_q->fetch_assoc()['days'];
    
    $gets_allowance = $rate > 520;
    $allowance_per_day = $gets_allowance ? 20 : 0;
    $total_per_day = $rate + $allowance_per_day;
    
    echo "Rate: $rate | Days: $days | Allowance/day: $allowance_per_day | Total/day: $total_per_day\n";
    
    // Analyze what's needed
    if ($diff > 0) {
        echo "STATUS: Getting TOO MUCH (overpaid by $diff)\n";
        $days_to_remove = round($diff / $total_per_day);
        echo "ACTION: Remove ~$days_to_remove day(s)\n";
    } else if ($diff < 0) {
        $abs_diff = abs($diff);
        echo "STATUS: Getting TOO LITTLE (underpaid by $abs_diff)\n";
        
        if ($abs_diff < $total_per_day) {
            echo "ACTION: Small difference - might be deduction, rounding, or partial calculation\n";
            // Check for specific issues
            if ($abs_diff >= 10 && $abs_diff <= 30) {
                echo "  → Possibly 1-2 days of allowance (20/day) or cashier bonus issue\n";
            }
        } else {
            $days_to_add = round($abs_diff / $total_per_day);
            echo "ACTION: Add ~$days_to_add day(s)\n";
        }
    }
}

$conn->close();
?>
