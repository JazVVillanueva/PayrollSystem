<?php
require 'db_connect.php';

$employees = [
    ['name' => 'Curry, Arthur', 'diff' => -19.50],
    ['name' => 'Hall, Carter', 'diff' => -20.00],
    ['name' => 'Hall, Shiera', 'diff' => -10.00],
    ['name' => 'Maximoff, Wanda', 'diff' => -30.00],
    ['name' => 'Prince, Diana', 'diff' => -20.00],
];

foreach ($employees as $emp) {
    $name = $emp['name'];
    $diff = $emp['diff'];
    
    echo "\n=== $name (Diff: $diff) ===\n";
    
    // Get rate
    $rate_q = $conn->query("SELECT rate FROM employees WHERE name='$name'");
    $rate = $rate_q->fetch_assoc()['rate'];
    echo "Rate: $rate\n";
    
    // Count days with regular work (not just OT)
    $days_q = $conn->query("SELECT Date, GROUP_CONCAT(Remarks) as all_remarks 
                           FROM timesheet 
                           WHERE Name='$name' AND Date BETWEEN '2025-01-03' AND '2025-01-30'
                           GROUP BY Date");
    
    $regular_work_days = 0;
    $ot_only_days = 0;
    
    while ($row = $days_q->fetch_assoc()) {
        $remarks = $row['all_remarks'];
        $has_regular = (stripos($remarks, 'OnDuty') !== false || stripos($remarks, 'Regular') !== false);
        $has_ot = (stripos($remarks, 'Overtime') !== false);
        
        if ($has_regular) {
            $regular_work_days++;
        } elseif ($has_ot) {
            $ot_only_days++;
        }
    }
    
    echo "Regular Work Days: $regular_work_days\n";
    echo "OT-only Days: $ot_only_days\n";
    
    if ($rate > 520) {
        $expected_allowance = $regular_work_days * 20;
        echo "Expected Allowance (rate>520): $expected_allowance php\n";
        
        // Check how many days difference is
        $days_diff = abs($diff) / 20;
        echo "Difference in days (diff/20): $days_diff days\n";
    } else {
        echo "No allowance (rate=520)\n";
    }
}

$conn->close();
?>
