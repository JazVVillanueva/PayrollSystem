<?php
include 'db_connect.php';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="timesheet_export.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Add CSV header
fputcsv($output, ['Date', 'Shift No.', 'Business Unit', 'Name', 'Time IN', 'Time OUT', 'Hours', 'Role', 'Remarks', 'Deductions', 'Bonus']);

// Fetch records from the database
$sql = "SELECT * FROM timesheet";
$result = $conn->query($sql);

// Write data to CSV
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

// Close output stream
fclose($output);
$conn->close();
exit();
?>

