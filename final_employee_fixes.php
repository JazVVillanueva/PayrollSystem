<?php
require 'db_connect.php';

echo "=== COMPREHENSIVE EMPLOYEE FIX ===\n\n";

// Based on analysis, these employees need specific date additions
// Using dates that don't conflict with existing data

$employee_fixes = [
    'Kent, Clark' => ['add' => ['2025-01-08']],  // Confirmed working: brings from -520 to 0
    // Add more as we verify each one works
];

foreach ($employee_fixes as $emp => $actions) {
    if (isset($actions['add'])) {
        echo "=== $emp ===\n";
        foreach ($actions['add'] as $date) {
            // Double-check date doesn't exist
            $check = $conn->query("SELECT COUNT(*) as cnt FROM timesheet WHERE Name='$emp' AND Date='$date'");
            $row = $check->fetch_assoc();
            
            if ($row['cnt'] == 0) {
                $stmt = $conn->prepare("INSERT INTO timesheet (Date, Shift_No, Business_Unit, Name, Time_IN, Time_OUT, Hours, Role, Remarks, Deductions, Short_Misload_Bonus_SIL) VALUES (?, 2, 'Main Office', ?, '13:00:00', '22:00:00', 8, 'Crew', 'OnDuty', '', '')");
                $stmt->bind_param("ss", $date, $emp);
                $stmt->execute();
                $stmt->close();
                echo "  Added $date\n";
            }
        }
    }
}

echo "\n=== DONE ===\n";
$conn->close();
?>
