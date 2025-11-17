<?php
require 'db_connect.php';

echo "=== CLEANING DATABASE ===\n\n";

// Delete ALL timesheet data first
$conn->query('TRUNCATE TABLE timesheet');
echo "Cleared all timesheet data\n\n";

$csv_file = 'c:\xampp\htdocs\LBYCPG2\lab5\Payroll Testing Data.csv';

if (!file_exists($csv_file)) {
    die("CSV file not found: $csv_file\n");
}

echo "Importing fresh data from CSV...\n";

$handle = fopen($csv_file, 'r');
$header = fgetcsv($handle); // Skip header

$count = 0;
$errors = 0;

while (($data = fgetcsv($handle)) !== FALSE) {
    if (count($data) < 11) continue;
    
    $date = $data[0];
    $shift_no = $data[1];
    $business_unit = $data[2];
    $name = str_replace('"', '', $data[3]); // Remove quotes
    $time_in = $data[4];
    $time_out = $data[5];
    $hours = $data[6];
    $role = $data[7];
    $remarks = $data[8];
    $deductions = $data[9];
    $sil = $data[10];
    
    $stmt = $conn->prepare("INSERT INTO timesheet (Date, Shift_No, Business_Unit, Name, Time_IN, Time_OUT, Hours, Role, Remarks, Deductions, Short_Misload_Bonus_SIL) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("sisssssssss", $date, $shift_no, $business_unit, $name, $time_in, $time_out, $hours, $role, $remarks, $deductions, $sil);
        
        if ($stmt->execute()) {
            $count++;
        } else {
            $errors++;
        }
        $stmt->close();
    }
}

fclose($handle);

echo "\n=== IMPORT COMPLETE ===\n";
echo "Total rows imported: $count\n";
echo "Errors: $errors\n\n";

// Verify data
$kent = $conn->query("SELECT COUNT(*) as cnt, COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='Kent, Clark' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
$kent_row = $kent->fetch_assoc();
echo "Kent Clark: " . $kent_row['cnt'] . " rows, " . $kent_row['days'] . " distinct dates\n";

$total = $conn->query("SELECT COUNT(*) as cnt FROM timesheet WHERE Date BETWEEN '2025-01-03' AND '2025-01-30'");
$total_row = $total->fetch_assoc();
echo "Total rows in pay period: " . $total_row['cnt'] . "\n";

$conn->close();
?>
