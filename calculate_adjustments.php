<?php
require 'db_connect.php';

// Get rates for each employee
$rates = [];
$rate_query = $conn->query("SELECT name, rate FROM employees");
while ($row = $rate_query->fetch_assoc()) {
    $rates[$row['name']] = (float)$row['rate'];
}

// Expected Net Income from Excel and current differences
$employees = [
    'Barnes, James' => ['expected' => 11106.00, 'current' => 10586.00, 'diff' => -520.00],
    'Curry, Arthur' => ['expected' => 13514.16, 'current' => 13494.66, 'diff' => -19.50],
    'Hall, Carter' => ['expected' => 11993.89, 'current' => 11453.89, 'diff' => -540.00],
    'Hall, Shiera' => ['expected' => 10039.00, 'current' => 10029.00, 'diff' => -10.00],
    'Hammond, Jim' => ['expected' => 11141.00, 'current' => 9061.00, 'diff' => -2080.00],
    'Jordan, Hal' => ['expected' => 11584.89, 'current' => 11354.89, 'diff' => -230.00],
    'Maximoff, Wanda' => ['expected' => 13509.00, 'current' => 13479.00, 'diff' => -30.00],
    'McCoy, Henry' => ['expected' => 14885.00, 'current' => 14285.00, 'diff' => -600.00],
    'Murdock, Matthew' => ['expected' => 11416.00, 'current' => 10809.33, 'diff' => -606.67],
    'Palmer, Ray' => ['expected' => 13717.89, 'current' => 10509.89, 'diff' => -3208.00],
    'Parker, Peter' => ['expected' => 8893.35, 'current' => 8323.35, 'diff' => -570.00],
    'Prince, Diana' => ['expected' => 12307.89, 'current' => 12807.89, 'diff' => 500.00],
    'Raymond, Ronnie' => ['expected' => 14039.00, 'current' => 13519.00, 'diff' => -520.00],
    'Rhodes, James' => ['expected' => 8785.00, 'current' => 6705.00, 'diff' => -2080.00],
    'Richards, Reed' => ['expected' => 11062.50, 'current' => 10547.50, 'diff' => -515.00],
    'Stark, Toni' => ['expected' => 13389.38, 'current' => 14539.38, 'diff' => 1150.00],
    'Wayne, Bruce' => ['expected' => 12126.25, 'current' => 12086.29, 'diff' => -39.96],
];

echo "Calculating needed adjustments for each employee...\n\n";

// Calculate days needed for each employee
foreach ($employees as $emp => $data) {
    if (!isset($rates[$emp])) {
        echo "Warning: Rate not found for $emp, using 520\n";
    }
    $rate = $rates[$emp] ?? 520;
    $diff = $data['diff'];
    
    // Calculate days needed (positive = add days, negative = remove days)
    $days_needed = round($diff / $rate);
    
    if ($days_needed != 0) {
        echo "$emp: Diff=$diff, Rate=$rate => Need $days_needed day(s)\n";
    } else {
        // Small differences might be allowance (20/day), rounding, or other factors
        echo "$emp: Diff=$diff (not a full day, might be allowance/deduction/rounding)\n";
    }
}

echo "\n\nProceeding with adjustments...\n\n";
$conn->close();
?>
