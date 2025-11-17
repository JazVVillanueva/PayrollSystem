<?php
require 'db_connect.php';

$csv_file = 'c:\xampp\htdocs\LBYCPG2\lab5\Payroll Testing Data.csv';

if (!file_exists($csv_file)) {
    die("CSV file not found: $csv_file\n");
}

echo "Importing data from CSV...\n";

$handle = fopen($csv_file, 'r');
$header = fgetcsv($handle); // Skip header row

$count = 0;
$errors = 0;

while (($data = fgetcsv($handle)) !== FALSE) {
    if (count($data) < 11) continue; // Skip incomplete rows
    
    $date = $data[0];
    $shift_no = $data[1];
    $business_unit = $data[2];
    $name = trim($data[3], '"'); // Remove quotes
    $time_in = $data[4];
    $time_out = $data[5];
    $hours = $data[6];
    $role = $data[7];
    $remarks = $data[8];
    $deductions = $data[9];
    $sil = $data[10];
    
    // Only import data within our date range
    if ($date < '2025-01-03' || $date > '2025-01-30') continue;
    
    $stmt = $conn->prepare("INSERT INTO timesheet (Date, Shift_No, Business_Unit, Name, Time_IN, Time_OUT, Hours, Role, Remarks, Deductions, Short_Misload_Bonus_SIL) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("sisssssssss", $date, $shift_no, $business_unit, $name, $time_in, $time_out, $hours, $role, $remarks, $deductions, $sil);
        
        if ($stmt->execute()) {
            $count++;
        } else {
            $errors++;
            echo "Error inserting row: " . $stmt->error . "\n";
        }
        $stmt->close();
    }
}

fclose($handle);

echo "\n=== IMPORT COMPLETE ===\n";
echo "Rows imported: $count\n";
echo "Errors: $errors\n";

// Verify Kent Clark
$kent = $conn->query("SELECT COUNT(*) as cnt FROM timesheet WHERE Name='Kent, Clark' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
$kent_row = $kent->fetch_assoc();
echo "\nKent Clark rows in database: " . $kent_row['cnt'] . "\n";

// Check total employees
$emp_count = $conn->query("SELECT COUNT(DISTINCT Name) as cnt FROM timesheet WHERE Date BETWEEN '2025-01-03' AND '2025-01-30'");
$emp_row = $emp_count->fetch_assoc();
echo "Total employees: " . $emp_row['cnt'] . "\n";

$conn->close();
?>
