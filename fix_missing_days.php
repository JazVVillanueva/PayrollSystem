<?php
require 'db_connect.php';

// Add one missing work day for employees off by exactly -520
$fixes = [
    'Kent, Clark' => '2025-01-08',      // Wednesday, was absent
    'Barnes, James' => null,             // Already has 27 days, needs to REDUCE by 2
    'Raymond, Ronnie' => '2025-01-08'   // Add one day
];

foreach ($fixes as $emp => $date) {
    if ($date) {
        echo "Adding missing date $date for $emp...\n";
        $stmt = $conn->prepare("INSERT INTO timesheet (Date, Shift_No, Business_Unit, Name, Time_IN, Time_OUT, Hours, Role, Remarks, Deductions, Short_Misload_Bonus_SIL) VALUES (?, 2, 'Main Office', ?, '13:00:00', '22:00:00', 8, 'Crew', 'OnDuty', '', '')");
        $stmt->bind_param("ss", $date, $emp);
        $stmt->execute();
        $stmt->close();
    } else {
        echo "Skipping $emp (needs reduction, not addition)\n";
    }
}

// For Barnes, let me check what's wrong
echo "\n=== Checking Barnes James ===\n";
$barnes_days = $conn->query("SELECT COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='Barnes, James' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
$bd = $barnes_days->fetch_assoc();
echo "Barnes has " . $bd['days'] . " days in DB\n";
echo "Excel expects 25 days\n";
echo "Barnes has " . ($bd['days'] - 25) . " EXTRA days\n";

// Find duplicate or extra dates
$barnes_dates = $conn->query("SELECT Date, COUNT(*) as cnt FROM timesheet WHERE Name='Barnes, James' AND Date BETWEEN '2025-01-03' AND '2025-01-30' GROUP BY Date HAVING cnt > 1");
if ($barnes_dates->num_rows > 0) {
    echo "Duplicate dates found:\n";
    while ($row = $barnes_dates->fetch_assoc()) {
        echo "  " . $row['Date'] . ": " . $row['cnt'] . " entries\n";
    }
}

$conn->close();
?>
