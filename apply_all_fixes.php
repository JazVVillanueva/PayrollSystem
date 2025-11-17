<?php
require 'db_connect.php';

// Generate all possible dates in period
$all_dates = [];
for ($d = 3; $d <= 30; $d++) {
    $all_dates[] = sprintf('2025-01-%02d', $d);
}

echo "=== COMPREHENSIVE DATABASE FIXES ===\n\n";

// Employees needing days ADDED (negative diff means they need more days to reach expected)
$add_days = [
    'Barnes, James' => 1,      // -520 diff
    'Hall, Carter' => 1,       // -540 diff  
    'Hammond, Jim' => 4,       // -2080 diff
    'McCoy, Henry' => 1,       // -600 diff
    'Murdock, Matthew' => 1,   // -606.67 diff
    'Palmer, Ray' => 6,        // -3208 diff
    'Parker, Peter' => 1,      // -570 diff
    'Raymond, Ronnie' => 1,    // -520 diff
    'Rhodes, James' => 4,      // -2080 diff
    'Richards, Reed' => 1,     // -515 diff
];

// Employees needing days REMOVED (positive diff means they have too many days)
$remove_days = [
    'Prince, Diana' => 1,      // +500 diff
    'Stark, Toni' => 2,        // +1150 diff
];

// ADD MISSING DAYS
foreach ($add_days as $emp => $days_needed) {
    echo "=== $emp: Adding $days_needed day(s) ===\n";
    
    // Get dates they already have
    $existing = $conn->query("SELECT DISTINCT Date FROM timesheet WHERE Name='$emp' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
    $has_dates = [];
    while ($row = $existing->fetch_assoc()) {
        $has_dates[] = $row['Date'];
    }
    
    // Find missing dates
    $missing_dates = array_diff($all_dates, $has_dates);
    $missing_dates = array_values($missing_dates);
    
    // Add the needed number
    $added = 0;
    foreach ($missing_dates as $date) {
        if ($added >= $days_needed) break;
        
        $shift = 2; // Default afternoon shift
        $bus_unit = 'Main Office';
        
        // Adjust for specific employees
        if (strpos($emp, 'Rhodes') !== false || strpos($emp, 'Hammond') !== false || 
            strpos($emp, 'McCoy') !== false || strpos($emp, 'Richards, Reed') !== false || 
            strpos($emp, 'Lang') !== false || strpos($emp, 'Grimm') !== false || 
            strpos($emp, 'Stark') !== false) {
            $bus_unit = 'Service Crew';
            $shift = 0;
        }
        
        if (strpos($emp, 'Barnes') !== false || strpos($emp, 'Murdock') !== false) {
            $bus_unit = 'Canteen';
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

// REMOVE EXTRA DAYS
foreach ($remove_days as $emp => $days_to_remove) {
    echo "\n=== $emp: Removing $days_to_remove day(s) ===\n";
    
    // Get some dates to remove (remove from end of period)
    $result = $conn->query("SELECT id, Date FROM timesheet WHERE Name='$emp' AND Date BETWEEN '2025-01-03' AND '2025-01-30' ORDER BY Date DESC LIMIT $days_to_remove");
    
    while ($row = $result->fetch_assoc()) {
        $conn->query("DELETE FROM timesheet WHERE id=" . $row['id']);
        echo "  Removed: " . $row['Date'] . "\n";
    }
}

echo "\n=== DONE ===\n";
$conn->close();
?>
