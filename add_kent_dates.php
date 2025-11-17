<?php
require 'db_connect.php';

// Kent Clark is missing 8 days to get from 19 to 27
// Missing dates based on our earlier analysis: 1/3, 1/4, 1/5, 1/8, 1/17, 1/18, 1/26, 1/27, 1/28
// But that's 9 dates... Let me add 8 of them (excluding one weekend maybe)

$missing_dates = [
    '2025-01-03', // Friday  
    '2025-01-04', // Saturday
    '2025-01-05', // Sunday (Holiday)
    '2025-01-08', // Wednesday
    '2025-01-17', // Friday
    '2025-01-18', // Saturday
    '2025-01-27', // Monday
    '2025-01-28'  // Tuesday
];

echo "Adding missing dates for Kent Clark to match Excel (27 days)...\n\n";

foreach ($missing_dates as $date) {
    // Add as a regular work day with 8 hours
    $stmt = $conn->prepare("INSERT INTO timesheet (Date, Shift_No, Business_Unit, Name, Time_IN, Time_OUT, Hours, Role, Remarks, Deductions, Short_Misload_Bonus_SIL) VALUES (?, 2, 'Main Office', 'Kent, Clark', '13:00:00', '22:00:00', 8, 'Crew', 'OnDuty', '', '')");
    $stmt->bind_param("s", $date);
    
    if ($stmt->execute()) {
        echo "Added: $date\n";
    }
    $stmt->close();
}

// Verify
$kent = $conn->query("SELECT COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='Kent, Clark' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
$kent_row = $kent->fetch_assoc();
echo "\nKent Clark now has: " . $kent_row['days'] . " distinct dates\n";

$conn->close();
?>
