<?php
require 'db_connect.php';

// Check absence pattern for employees
$employees = ['Kent, Clark', 'Wayne, Bruce', 'Allen, Barry', 'Hall, Carter'];

// Pay period: Jan 3 to Jan 30 = 28 days
$start = new DateTime('2025-01-03');
$end = new DateTime('2025-01-30');
$interval = new DateInterval('P1D');
$period = new DatePeriod($start, $interval, $end->modify('+1 day'));

$all_dates = [];
foreach ($period as $date) {
    $all_dates[] = $date->format('Y-m-d');
}
$total_days = count($all_dates);

echo "<h3>Absence Analysis (Pay Period: 28 days)</h3>\n";
echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>Employee</th><th>Days Present</th><th>Days Absent</th><th>Calculated Days</th><th>Excel Days</th></tr>\n";

foreach ($employees as $employee) {
    $query = "SELECT DISTINCT Date FROM timesheet 
              WHERE Name = '$employee' 
              AND Date BETWEEN '2025-01-03' AND '2025-01-30'";
    $result = $conn->query($query);
    
    $present_dates = [];
    while ($row = $result->fetch_assoc()) {
        $present_dates[] = $row['Date'];
    }
    
    $days_present = count($present_dates);
    $days_absent = $total_days - $days_present;
    $calculated = $total_days - $days_absent;
    
    echo "<tr>";
    echo "<td>$employee</td>";
    echo "<td>$days_present</td>";
    echo "<td>$days_absent</td>";
    echo "<td><strong>$calculated</strong></td>";
    echo "<td>";
    if ($employee == 'Kent, Clark') echo "27";
    else if ($employee == 'Wayne, Bruce') echo "22";
    else if ($employee == 'Allen, Barry') echo "22";
    else if ($employee == 'Hall, Carter') echo "21";
    echo "</td>";
    echo "</tr>\n";
}

echo "</table>\n";
echo "<p><strong>Note:</strong> Calculated Days = 28 - Days Absent = Days Present</p>\n";
?>
