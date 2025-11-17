<?php
// Run salary_summary.php logic for just Rhodes James
$_POST['start_date'] = '2025-01-03';
$_POST['end_date'] = '2025-01-30';
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
include 'salary_summary.php';
$output = ob_get_clean();

// Parse the output to find Rhodes James
preg_match_all('/<td>(.*?)<\/td>/s', $output, $matches);
$data = $matches[1];

// Find Rhodes James row
for ($i = 0; $i < count($data); $i++) {
    if (trim($data[$i]) === 'Rhodes, James') {
        echo "Rhodes, James calculated net income: " . trim($data[$i+1]) . "\n";
        break;
    }
}

// Also run a detailed calculation
include 'db_connect.php';

$employee = 'Rhodes, James';
$start_date = '2025-01-03';
$end_date = '2025-01-30';

$empRate = [];
if ($qr = $conn->query("SELECT name, rate FROM employees")) {
    while ($r = $qr->fetch_assoc()) {
        $empRate[$r['name']] = (float)$r['rate'];
    }
}

$stmt = $conn->prepare("SELECT * FROM timesheet WHERE Name = ? AND Date BETWEEN ? AND ? ORDER BY Date ASC");
$stmt->bind_param("sss", $employee, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$total_days_worked = 0;
$total_overtime_pay = 0.0;
$total_night_diff = 0;
$total_holiday_premium = 0;
$total_sil_count = 0;
$total_cashier_bonus = 0;
$daily_rate = $empRate[$employee] ?? 520;

$processed_dates = [];
$processed_holidays = [];
$late_absent = 0;

$holiday_dates = [
    '2025-01-06' => 1.00,
    '2025-01-29' => 0.30
];

echo "\n=== DETAILED CALCULATION ===\n";
echo "Daily Rate: $daily_rate\n\n";

while ($row = $result->fetch_assoc()) {
    $date = $row['Date'];
    $hours = (float)$row['Hours'];
    $remarks = $row['Remarks'];
    $sil_col = $row['Short_Misload_Bonus_SIL'];
    $shift_no = isset($row['Shift_No']) ? (int)$row['Shift_No'] : 0;
    $time_in = $row['Time_IN'];
    $time_out = $row['Time_OUT'];
    
    $has_sil = stripos($sil_col, 'SIL') !== false;
    
    // Days worked
    if (!$has_sil && !in_array($date, $processed_dates)) {
        $total_days_worked++;
        $processed_dates[] = $date;
    }
    
    // OT
    $is_ot = (stripos($remarks, 'Overtime') !== false);
    if ($is_ot && $hours > 0) {
        $ot_rate = $daily_rate / 8;
        $ot_pay = $hours * $ot_rate;
        $total_overtime_pay += $ot_pay;
    }
    
    // Night diff
    if (!empty($time_in) && !empty($time_out)) {
        $time_in_24 = date('H:i', strtotime($time_in));
        $time_out_24 = date('H:i', strtotime($time_out));
        $is_night = ($time_in_24 >= '20:00' || $time_in_24 < '07:00') || ($time_out_24 >= '20:00' && $time_out_24 <= '23:59') || ($time_out_24 >= '00:00' && $time_out_24 < '07:00');
        if ($is_night) {
            $total_night_diff += 52;
        }
    }
    
    // Holiday
    $is_holiday = array_key_exists($date, $holiday_dates);
    if (!in_array($date, $processed_holidays) && $is_holiday) {
        $holiday_multiplier = $holiday_dates[$date];
        $holiday_premium = $daily_rate * $holiday_multiplier;
        $total_holiday_premium += $holiday_premium;
        $processed_holidays[] = $date;
    }
    
    // SIL count
    $total_sil_count += substr_count($sil_col, 'SIL');
    
    // Late
    if (stripos($remarks, 'Late') !== false) {
        $late_absent += 150;
    }
}

$total_night_diff += $total_sil_count * 52;

$basic_pay = $total_days_worked * $daily_rate;
$sil_pay = $total_sil_count * $daily_rate;
$sss = 562.5;
$phic = 312.5;
$hdmf = 200;

echo "Days Worked (non-SIL): $total_days_worked\n";
echo "Basic Pay: $basic_pay\n";
echo "SIL Count: $total_sil_count\n";
echo "SIL Pay: $sil_pay\n";
echo "Overtime Pay: $total_overtime_pay\n";
echo "Night Diff: $total_night_diff\n";
echo "Holiday Premium: $total_holiday_premium\n";
echo "Cashier Bonus: $total_cashier_bonus\n";
echo "\n";
echo "Gross Income: " . ($basic_pay + $total_overtime_pay + $total_night_diff + $total_holiday_premium + $sil_pay + $total_cashier_bonus) . "\n";
echo "Late: $late_absent\n";
echo "SSS: $sss\n";
echo "PHIC: $phic\n";
echo "HDMF: $hdmf\n";
echo "Total Deductions: " . ($sss + $phic + $hdmf + $late_absent) . "\n";
echo "\nNet Income: " . ($basic_pay + $total_overtime_pay + $total_night_diff + $total_holiday_premium + $sil_pay + $total_cashier_bonus - $sss - $phic - $hdmf - $late_absent) . "\n";
echo "Expected: 8,785.00\n";

$stmt->close();
$conn->close();
?>
