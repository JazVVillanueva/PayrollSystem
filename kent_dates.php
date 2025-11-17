<?php
require 'db_connect.php';

echo "Kent Clark - All Dates Analysis:\n\n";

$query = "SELECT Date, Time_IN, Time_OUT, Hours, Shift_No, Remarks, Short_Misload_Bonus_SIL 
          FROM timesheet 
          WHERE Name = 'Kent, Clark' 
          AND Date BETWEEN '2025-01-03' AND '2025-01-30' 
          ORDER BY Date, Time_IN";
$result = $conn->query($query);

$date_count = [];
while ($row = $result->fetch_assoc()) {
    $date = $row['Date'];
    if (!isset($date_count[$date])) {
        $date_count[$date] = 0;
    }
    $date_count[$date]++;
    
    echo "$date | Shift: {$row['Shift_No']} | Hours: {$row['Hours']} | Remarks: {$row['Remarks']} | SIL: {$row['Short_Misload_Bonus_SIL']}\n";
}

echo "\n\nDate Summary:\n";
foreach ($date_count as $date => $count) {
    echo "$date: $count row(s)\n";
}

echo "\n\nTotal DISTINCT dates: " . count($date_count) . "\n";
echo "Excel shows: 27 days\n";
echo "Difference: " . (27 - count($date_count)) . " days\n";
?>
