<?php
require 'db_connect.php';

$employee = 'Parker, Peter';
$start_date = '2025-01-03';
$end_date = '2025-01-30';

echo "=== PARKER PETER DETAILED DEBUG ===\n\n";

$stmt = $conn->prepare("SELECT * FROM timesheet WHERE Name = ? AND Date BETWEEN ? AND ? ORDER BY Date ASC");
$stmt->bind_param("sss", $employee, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$days = [];
$dayBuckets = [];
$processed_dates = [];
$seen_rows = [];

while ($row = $result->fetch_assoc()) {
    $date = $row['Date'];
    $hours = (float)$row['Hours'];
    $remarks = $row['Remarks'];
    $sil = $row['Short_Misload_Bonus_SIL'];
    
    // Skip duplicates
    $row_key = $date . '|' . $row['Time_IN'] . '|' . $row['Time_OUT'] . '|' . $hours . '|' . $remarks;
    if (in_array($row_key, $seen_rows)) continue;
    $seen_rows[] = $row_key;
    
    // Skip empty
    if (empty($row['Time_IN']) && empty($row['Time_OUT']) && $hours == 0) continue;
    
    $is_ot = stripos($remarks, 'Overtime') !== false;
    $has_sil = stripos($sil, 'SIL') !== false;
    
    if (!isset($dayBuckets[$date])) {
        $dayBuckets[$date] = ['has_regular_work' => false];
    }
    
    if (!$is_ot && $hours > 0) {
        $dayBuckets[$date]['has_regular_work'] = true;
    }
    
    if (!$has_sil && !in_array($date, $processed_dates)) {
        $processed_dates[] = $date;
    }
    
    $days[] = "$date | Hours:$hours | Remarks:$remarks | SIL:$sil | OT:" . ($is_ot ? 'Yes' : 'No') . " | RegWork:" . ($dayBuckets[$date]['has_regular_work'] ? 'Yes' : 'No');
}

foreach ($days as $d) {
    echo "$d\n";
}

$total_days_worked = count($processed_dates);
$regular_work_days = 0;
foreach ($dayBuckets as $d => $info) {
    if ($info['has_regular_work']) {
        $regular_work_days++;
    }
}

echo "\n\nSUMMARY:\n";
echo "Total days worked (non-SIL): $total_days_worked\n";
echo "Regular work days (for allowance): $regular_work_days\n";
echo "Rate: 550 (>520, so gets allowance)\n";
echo "Expected allowance: " . ($regular_work_days * 20) . "\n";
echo "\nFrom Excel: Expected difference = -70\n";
echo "If Excel uses different day count...\n";
echo "  21 days * 20 = 420\n";
echo "  18 days * 20 = 360\n";
echo "  Difference: 420 - 360 = 60 (close to 70)\n";
?>
