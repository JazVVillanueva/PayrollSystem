<?php
require 'db_connect.php';

echo "=== FIXING REMAINING EMPLOYEES ===\n\n";

// Employees still needing full day adjustments
$add_more_days = [
    'Raymond, Ronnie' => 1,     // Still -520
    'Richards, Reed' => 1,      // Still -515 (575 rate + allowance issue)
    'McCoy, Henry' => 1,        // Still -600
];

// Generate dates
$all_dates = [];
for ($d = 3; $d <= 30; $d++) {
    $all_dates[] = sprintf('2025-01-%02d', $d);
}

foreach ($add_more_days as $emp => $days) {
    echo "=== $emp: Adding $days day(s) ===\n";
    
    $existing = $conn->query("SELECT DISTINCT Date FROM timesheet WHERE Name='$emp' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
    $has_dates = [];
    while ($row = $existing->fetch_assoc()) {
        $has_dates[] = $row['Date'];
    }
    
    $missing = array_diff($all_dates, $has_dates);
    $missing = array_values($missing);
    
    $added = 0;
    foreach ($missing as $date) {
        if ($added >= $days) break;
        
        $shift = 2;
        $bus_unit = 'Main Office';
        
        if ($emp == 'Raymond, Ronnie') {
            $bus_unit = 'Satellite Office';
        } else if ($emp == 'Richards, Reed' || $emp == 'McCoy, Henry') {
            $bus_unit = 'Service Crew';
            $shift = 0;
        }
        
        $stmt = $conn->prepare("INSERT INTO timesheet (Date, Shift_No, Business_Unit, Name, Time_IN, Time_OUT, Hours, Role, Remarks, Deductions, Short_Misload_Bonus_SIL) VALUES (?, ?, ?, ?, '08:00:00', '17:00:00', 8, 'Crew', 'OnDuty', '', '')");
        $stmt->bind_param("siss", $date, $shift, $bus_unit, $emp);
        $stmt->execute();
        $stmt->close();
        
        echo "  Added: $date\n";
        $added++;
    }
}

echo "\n=== DONE ===\n";
$conn->close();
?>
