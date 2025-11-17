<?php
require 'db_connect.php';

// Analyze days of work for multiple employees
$employees = ['Kent, Clark', 'Wayne, Bruce', 'Allen, Barry', 'Jordan, Hal', 'Curry, Arthur', 'Barnes, James'];
$expected_days = [27, 22, 22, 22, 22, 25]; // From Excel screenshot and previous info

echo "<h3>Days of Work Analysis</h3>\n";
echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>Employee</th><th>Expected Days (Excel)</th><th>Dates with Entries</th><th>Non-SIL Dates</th><th>SIL Days</th><th>Difference</th></tr>\n";

for ($i = 0; $i < count($employees); $i++) {
    $employee = $employees[$i];
    $expected = $expected_days[$i];
    
    $query = "SELECT Date, Hours, Remarks, Short_Misload_Bonus_SIL 
              FROM timesheet 
              WHERE Name = '$employee' 
              AND Date BETWEEN '2025-01-03' AND '2025-01-30' 
              ORDER BY Date";
    $result = $conn->query($query);
    
    $all_dates = [];
    $non_sil_dates = [];
    $sil_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $date = $row['Date'];
        $sil = $row['Short_Misload_Bonus_SIL'];
        $has_sil = stripos($sil, 'SIL') !== false;
        
        if (!in_array($date, $all_dates)) {
            $all_dates[] = $date;
        }
        
        if ($has_sil) {
            $sil_count++;
        } else {
            if (!in_array($date, $non_sil_dates)) {
                $non_sil_dates[] = $date;
            }
        }
    }
    
    $total_entries = count($all_dates);
    $non_sil = count($non_sil_dates);
    $diff = $expected - $total_entries;
    
    echo "<tr>";
    echo "<td>$employee</td>";
    echo "<td>$expected</td>";
    echo "<td>$total_entries</td>";
    echo "<td>$non_sil</td>";
    echo "<td>$sil_count</td>";
    echo "<td style='color:" . ($diff > 0 ? 'red' : 'green') . "'>$diff</td>";
    echo "</tr>\n";
}

echo "</table>\n";

// Check pay period
echo "<p><strong>Pay Period:</strong> 2025-01-03 to 2025-01-30 = 28 calendar days</p>\n";
echo "<p><strong>Hypothesis:</strong> If Excel uses (28 - absent_days) or some other calendar-based calculation...</p>\n";
?>
