<?php
require 'db_connect.php';

$employee = 'Kent, Clark';
$start_date = '2025-01-03';
$end_date = '2025-01-30';

echo "=== KENT CLARK FULL CALCULATION ===\n\n";

$stmt = $conn->prepare("SELECT * FROM timesheet WHERE Name = ? AND Date BETWEEN ? AND ? ORDER BY Date ASC");
$stmt->bind_param("sss", $employee, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$total_days_worked = 0;
$total_overtime_hours = 0;
$total_night_diff = 0;
$total_holiday_premium = 0;
$total_sil_count = 0;
$daily_rate = 520;

$holiday_dates = [
    '2025-01-01' => 1.00,
    '2025-01-05' => 1.00,
    '2025-01-29' => 0.30
];

$processed_dates = [];
$processed_holidays = [];
$seen_rows = [];

while ($row = $result->fetch_assoc()) {
    $date = $row['Date'];
    $hours = (float)$row['Hours'];
    $remarks = $row['Remarks'];
    $sil = $row['Short_Misload_Bonus_SIL'];
    $shift_no = (int)$row['Shift_No'];
    
    $row_key = $date . '|' . $row['Time_IN'] . '|' . $row['Time_OUT'] . '|' . $hours . '|' . $remarks;
    if (in_array($row_key, $seen_rows)) continue;
    $seen_rows[] = $row_key;
    
    if (empty($row['Time_IN']) && empty($row['Time_OUT']) && $hours == 0) continue;
    
    $has_sil = stripos($sil, 'SIL') !== false;
    if (!$has_sil && !in_array($date, $processed_dates)) {
        $total_days_worked++;
        $processed_dates[] = $date;
    }
    
    $is_holiday = array_key_exists($date, $holiday_dates);
    $is_ot = stripos($remarks, 'Overtime') !== false;
    
    if (stripos($remarks, 'Overtime') !== false) {
        $total_overtime_hours += $hours;
    }
    
    if ($shift_no == 3) {
        $total_night_diff += 52;
    }
    
    if (!in_array($date, $processed_holidays) && $is_holiday && !$is_ot) {
        $total_holiday_premium += $daily_rate * $holiday_dates[$date];
        $processed_holidays[] = $date;
    }
    
    $total_sil_count += substr_count($sil, 'SIL');
}

$basic_pay = $total_days_worked * $daily_rate;
$overtime_pay = $total_overtime_hours * ($daily_rate / 8);
$night_diff_pay = $total_night_diff;
$holiday_pay = $total_holiday_premium;
$sil_pay = $total_sil_count * $daily_rate;

$gross_income = $basic_pay + $overtime_pay + $night_diff_pay + $holiday_pay + $sil_pay;

echo "Days Worked (non-SIL): $total_days_worked\n";
echo "Basic Pay: $total_days_worked × $daily_rate = " . number_format($basic_pay, 2) . "\n";
echo "Overtime Hours: $total_overtime_hours\n";
echo "Overtime Pay: " . number_format($overtime_pay, 2) . "\n";
echo "Night Differential: " . number_format($night_diff_pay, 2) . "\n";
echo "Holiday Premium: " . number_format($holiday_pay, 2) . "\n";
echo "SIL Count: $total_sil_count\n";
echo "SIL Pay: $total_sil_count × $daily_rate = " . number_format($sil_pay, 2) . "\n";
echo "\nGROSS INCOME: " . number_format($gross_income, 2) . "\n";
echo "\nExcel Expected: 10,625.00\n";
echo "Difference: " . number_format(10625 - $gross_income, 2) . "\n";
?>
