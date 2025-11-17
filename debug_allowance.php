<?php
include 'db_connect.php';

$_POST['start_date'] = '2025-01-03';
$_POST['end_date'] = '2025-01-30';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Test employees
$test_employees = ['Wayne, Bruce', 'Kent, Clark', 'McCoy, Henry', 'Hammond, Jim', 'Murdock, Matthew'];

echo "DETAILED CALCULATION BREAKDOWN\n";
echo str_repeat("=", 100) . "\n\n";

foreach ($test_employees as $employee) {
    echo "EMPLOYEE: $employee\n";
    echo str_repeat("-", 100) . "\n";
    
    // Get rate from database
    $rate_query = $conn->query("SELECT rate FROM employees WHERE name = '$employee'");
    $rate_row = $rate_query->fetch_assoc();
    $rate = $rate_row ? $rate_row['rate'] : 520;
    
    // Get all records
    $query = "SELECT * FROM timesheet WHERE Name = '$employee' AND Date BETWEEN '2025-01-03' AND '2025-01-30' ORDER BY Date ASC";
    $result = $conn->query($query);
    
    $days_worked = 0;
    $ot_hours = 0;
    $dates = [];
    $rows_count = 0;
    $days_with_ot = 0;
    $dates_with_ot = [];
    
    while ($row = $result->fetch_assoc()) {
        $date = $row['Date'];
        $hours = $row['Hours'];
        $remarks = $row['Remarks'];
        $sil = $row['Short_Misload_Bonus_SIL'];
        
        if (!in_array($date, $dates) && stripos($sil, 'SIL') === false) {
            $days_worked++;
            $dates[] = $date;
        }
        
        if (stripos($remarks, 'Overtime') !== false) {
            $ot_hours += $hours;
            if (!in_array($date, $dates_with_ot)) {
                $days_with_ot++;
                $dates_with_ot[] = $date;
            }
        }
        
        $rows_count++;
    }
    
    $ot_rate = $rate / 8;
    
    echo "Rate: $rate\n";
    echo "Days Worked: $days_worked\n";
    echo "Days with OT: $days_with_ot\n";
    echo "OT Hours: $ot_hours\n";
    echo "OT Rate: $ot_rate\n";
    echo "Total Rows: $rows_count\n";
    
    // Calculate various possibilities
    $allowance_calc1 = ($rate > 520) ? (20 * $days_worked) : 0;
    echo "Allowance (if rate > 520, 20 per day worked): $allowance_calc1\n";
    
    $allowance_calc2 = ($rate > 520) ? (20 * $days_worked) : (20 * $ot_hours);
    echo "Allowance (rate > 520: 20/day, else: 20/OT hour): $allowance_calc2\n";
    
    $allowance_calc3 = ($rate > 520) ? (20 * $days_worked) : (20 * $days_with_ot);
    echo "Allowance (rate > 520: 20/day, else: 20/day with OT): $allowance_calc3\n";
    
    $allowance_calc4 = ($rate > 520) ? (20 * $days_worked) : (20 * ($days_worked + $days_with_ot));
    echo "Allowance (rate > 520: 20/day, else: 20*(days + OT days)): $allowance_calc4\n";
    
    echo "\n";
}

$conn->close();
?>
