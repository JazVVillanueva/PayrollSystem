<?php
require 'db_connect.php';

$employees = ['Hall, Carter', 'Hall, Shiera', 'Prince, Diana', 'Maximoff, Wanda', 'Curry, Arthur'];

foreach ($employees as $name) {
    $crew_q = $conn->query("SELECT Date, Hours, Remarks FROM timesheet 
                           WHERE Name='$name' AND Role='Crew' AND Date BETWEEN '2025-01-03' AND '2025-01-30'
                           ORDER BY Date");
    
    $crew_hours = 0;
    $has_crew = false;
    
    echo "\n=== $name ===\n";
    while ($row = $crew_q->fetch_assoc()) {
        $has_crew = true;
        $crew_hours += $row['Hours'];
        echo "  " . $row['Date'] . " | " . $row['Hours'] . "hrs | " . $row['Remarks'] . "\n";
    }
    
    if ($has_crew) {
        echo "Total Crew Hours: $crew_hours\n";
        echo "Crew Bonus if calculated (40 * floor($crew_hours/8)): " . (40 * floor($crew_hours/8)) . " php\n";
    } else {
        echo "No Crew records in database\n";
    }
}

$conn->close();
?>
