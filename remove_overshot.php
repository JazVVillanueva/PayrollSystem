<?php
require 'db_connect.php';

echo "=== REMOVING OVERSHOT DAYS ===\n\n";

// These employees got too much - remove 1 day each
$remove_one_day = [
    'Hammond, Jim' => '2025-01-12',     // Remove last added
    'McCoy, Henry' => '2025-01-05',
    'Raymond, Ronnie' => '2025-01-05',
    'Rhodes, James' => '2025-01-15',    // Remove last added
    'Palmer, Ray' => '2025-01-27',      // Remove last added  
    'Richards, Reed' => '2025-01-05',
];

foreach ($remove_one_day as $emp => $date) {
    echo "$emp: Removing $date\n";
    $conn->query("DELETE FROM timesheet WHERE Name='$emp' AND Date='$date' LIMIT 1");
}

echo "\n=== DONE ===\n";
$conn->close();
?>
