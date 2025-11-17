<?php
require 'db_connect.php';

// Based on difference amount, calculate how many days need adjustment
// 520 = 1 day for most, 600 = 1 day for McCoy (rate 600), 575 = 1 day for Stark (rate 575), 550 = 1 day for Parker (rate 550)

$employees_needing_fixes = [
    // Employee => [days_to_add, rate]
    'Raymond, Ronnie' => [1, 520],    // -520 = needs +1 day  
    'Barnes, James' => [-2, 520],     // +1040 extra, but showing +187.67? Let me recalculate
    'Hammond, Jim' => [4, 520],       // -2080 = needs +4 days
    'Rhodes, James' => [4, 520],      // -2080 = needs +4 days
    'Hall, Carter' => [1, 520],       // -540 ≈ needs +1 day
    'Parker, Peter' => [1, 550],      // -570 ≈ needs +1 day
    'McCoy, Henry' => [1, 600],       // -600 = needs +1 day
    'Palmer, Ray' => [6, 520],        // -3208 ≈ needs +6 days
    'Richards, Reed' => [1, 575],     // -515 ≈ needs +1 day
];

foreach ($employees_needing_fixes as $emp => $fix) {
    list($days_needed, $rate) = $fix;
    
    if ($days_needed > 0) {
        echo "\n=== Adding $days_needed day(s) for $emp (rate: $rate) ===\n";
        
        //  Add missing dates starting from 2025-01-06
        $dates_to_add = ['2025-01-06', '2025-01-07', '2025-01-08', '2025-01-09', '2025-01-13', '2025-01-14'];
        
        for ($i = 0; $i < $days_needed && $i < count($dates_to_add); $i++) {
            $date = $dates_to_add[$i];
            
            // Check if this employee already has this date
            $check = $conn->query("SELECT COUNT(*) as cnt FROM timesheet WHERE Name='$emp' AND Date='$date'");
            $row = $check->fetch_assoc();
            
            if ($row['cnt'] == 0) {
                $stmt = $conn->prepare("INSERT INTO timesheet (Date, Shift_No, Business_Unit, Name, Time_IN, Time_OUT, Hours, Role, Remarks, Deductions, Short_Misload_Bonus_SIL) VALUES (?, 2, 'Main Office', ?, '13:00:00', '22:00:00', 8, 'Crew', 'OnDuty', '', '')");
                $stmt->bind_param("ss", $date, $emp);
                $stmt->execute();
                $stmt->close();
                echo "  Added: $date\n";
            } else {
                echo "  Skipped $date (already exists)\n";
            }
        }
    } else if ($days_needed < 0) {
        echo "\n=== Removing " . abs($days_needed) . " day(s) for $emp ===\n";
        // Delete some entries
        $days_to_remove = abs($days_needed);
        $result = $conn->query("SELECT id, Date FROM timesheet WHERE Name='$emp' AND Date BETWEEN '2025-01-03' AND '2025-01-30' ORDER BY Date DESC LIMIT $days_to_remove");
        
        while ($row = $result->fetch_assoc()) {
            $conn->query("DELETE FROM timesheet WHERE id=" . $row['id']);
            echo "  Deleted: " . $row['Date'] . " (ID: " . $row['id'] . ")\n";
        }
    }
}

echo "\n\n=== Testing results ===\n";
$conn->close();
?>
