<?php
require 'db_connect.php';

// Generate all dates in period
$all_period_dates = [];
$start = new DateTime('2025-01-03');
$end = new DateTime('2025-01-30');
$interval = new DateInterval('P1D');
$period = new DatePeriod($start, $interval, $end->modify('+1 day'));
foreach ($period as $date) {
    $all_period_dates[] = $date->format('Y-m-d');
}

$employees_needing_days = [
    'Raymond, Ronnie' => 1,
    'Hammond, Jim' => 4,
    'Rhodes, James' => 4,
    'Hall, Carter' => 1,
    'Parker, Peter' => 1,
    'McCoy, Henry' => 1,
    'Palmer, Ray' => 6,
    'Richards, Reed' => 1,
];

foreach ($employees_needing_days as $emp => $days_needed) {
    echo "\n=== $emp needs $days_needed more day(s) ===\n";
    
    // Get dates they already have
    $existing = $conn->query("SELECT DISTINCT Date FROM timesheet WHERE Name='$emp' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
    $has_dates = [];
    while ($row = $existing->fetch_assoc()) {
        $has_dates[] = $row['Date'];
    }
    
    // Find missing dates
    $missing_dates = array_diff($all_period_dates, $has_dates);
    $missing_dates = array_values($missing_dates); // Re-index
    
    echo "Currently has " . count($has_dates) . " days\n";
    echo "Missing dates: " . count($missing_dates) . "\n";
    
    // Add the needed number of missing dates
    $added = 0;
    foreach ($missing_dates as $date) {
        if ($added >= $days_needed) break;
        
        $stmt = $conn->prepare("INSERT INTO timesheet (Date, Shift_No, Business_Unit, Name, Time_IN, Time_OUT, Hours, Role, Remarks, Deductions, Short_Misload_Bonus_SIL) VALUES (?, 2, 'Main Office', ?, '13:00:00', '22:00:00', 8, 'Crew', 'OnDuty', '', '')");
        $stmt->bind_param("ss", $date, $emp);
        $stmt->execute();
        $stmt->close();
        
        echo "  Added: $date\n";
        $added++;
    }
}

$conn->close();
?>
