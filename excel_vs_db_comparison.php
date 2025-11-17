<?php
require 'db_connect.php';

// Based on Excel screenshot, these are the expected "Days of Work" values
$expected_days = [
    'Kent, Clark' => 27,
    'Wayne, Bruce' => 22,
    'Prince, Diana' => 22,
    'Allen, Barry' => 22,
    'Jordan, Hal' => 22,
    'Curry, Arthur' => 22,
    'Jones, John' => 22,
    'Queen, Oliver' => 22,
    'Palmer, Ray' => 22,
    'Hall, Carter' => 21,
    'Barnes, James' => 25,  // From earlier analysis
    'Hammond, Jim' => 26    // Guessing based on pattern
];

echo "=== COMPARISON: Excel Days vs Database Days ===\n\n";
echo str_pad("Employee", 25) . " | " . str_pad("DB Days", 10) . " | " . str_pad("Excel Days", 10) . " | Difference\n";
echo str_repeat("-", 70) . "\n";

foreach ($expected_days as $name => $excel_days) {
    // Count distinct dates in database
    $query = $conn->query("SELECT COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='$name' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
    $row = $query->fetch_assoc();
    $db_days = $row['days'];
    
    $diff = $db_days - $excel_days;
    $status = ($diff == 0) ? "✓ MATCH" : "✗ OFF by $diff";
    
    echo str_pad($name, 25) . " | " . str_pad($db_days, 10) . " | " . str_pad($excel_days, 10) . " | $status\n";
}

echo "\n\n=== HYPOTHESIS CHECK ===\n";
echo "If Excel 'Days of Work' is NOT from timesheet database,\n";
echo "then maybe it's calculated as: 28 total days - absent days\n";
echo "OR it's manually entered from another source\n\n";

// Check if any employee has exactly 27 or 28 days
$all_employees = $conn->query("SELECT Name, COUNT(DISTINCT Date) as days FROM timesheet WHERE Date BETWEEN '2025-01-03' AND '2025-01-30' GROUP BY Name HAVING days >= 25 ORDER BY days DESC");
echo "Employees with 25+ days in database:\n";
while ($row = $all_employees->fetch_assoc()) {
    echo "  " . $row['Name'] . ": " . $row['days'] . " days\n";
}
?>
