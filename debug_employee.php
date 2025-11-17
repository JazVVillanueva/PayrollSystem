<?php
include 'db_connect.php';

$employee = 'Curry, Arthur';
$start_date = '2025-01-03';
$end_date = '2025-01-30';

echo "Detailed Breakdown for: $employee\n";
echo str_repeat("=", 70) . "\n\n";

// Get employee rate
$rate_query = $conn->query("SELECT rate FROM employees WHERE name = '$employee'");
$rate_row = $rate_query->fetch_assoc();
$daily_rate = (float)$rate_row['rate'];
echo "Daily Rate: ₱$daily_rate\n\n";

// Get timesheet data
$stmt = $conn->prepare("SELECT * FROM timesheet WHERE Name = ? AND Date BETWEEN ? AND ? ORDER BY Date ASC");
$stmt->bind_param("sss", $employee, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$total_days = 0;
$total_ot_hours = 0;
$total_ot_pay = 0;
$night_diff_count = 0;
$holiday_premium = 0;
$sil_count = 0;
$processed_dates = [];
$processed_holidays = [];

$holiday_dates = [
    '2025-01-01' => 1.00,
    '2025-01-05' => 1.00,
    '2025-01-29' => 0.30,
];

echo "Timesheet Entries:\n";
echo str_repeat("-", 70) . "\n";

while ($row = $result->fetch_assoc()) {
    $date = $row['Date'];
    $hours = (float)$row['Hours'];
    $remarks = $row['Remarks'];
    $sil = $row['Short_Misload_Bonus_SIL'];
    $time_in = strtotime($row['Time_IN']);
    $time_in_hour = (int)date('H', $time_in);
    $shift_no = isset($row['Shift_No']) ? (int)$row['Shift_No'] : 0;
    
    $is_ot = stripos($remarks, 'Overtime') !== false;
    $has_sil = stripos($sil, 'SIL') !== false;
    $is_holiday = array_key_exists($date, $holiday_dates);
    
    echo "$date | Hours: $hours | $remarks | SIL: " . ($has_sil ? 'YES' : 'NO') . " | OT: " . ($is_ot ? 'YES' : 'NO') . " | Holiday: " . ($is_holiday ? 'YES' : 'NO') . " | Shift: $shift_no | Time In: " . date('H:i', $time_in) . "\n";
    
    // Count days
    if (!$has_sil && !in_array($date, $processed_dates)) {
        $total_days++;
        $processed_dates[] = $date;
    }
    
    // Count OT
    if ($is_ot) {
        $ot_rate = $daily_rate / 8;
        $total_ot_hours += $hours;
        $total_ot_pay += $hours * $ot_rate;
        
        if ($is_holiday) {
            $holiday_premium += $hours * $ot_rate * $holiday_dates[$date];
        }
    } else if ($is_holiday) {
        // Regular work on holiday
        if (!in_array($date, $processed_holidays)) {
            $holiday_premium += $daily_rate * $holiday_dates[$date];
            $processed_holidays[] = $date;
        }
    }
    
    // Night differential - only shift 3
    if ($shift_no == 3) {
        $night_diff_count++;
    }
    
    // SIL count
    $sil_count += substr_count($sil, 'SIL');
}

echo "\n" . str_repeat("=", 70) . "\n\n";
echo "Summary:\n";
echo "Days Worked: $total_days\n";
echo "OT Hours: $total_ot_hours\n";
echo "OT Pay: ₱" . number_format($total_ot_pay, 2) . "\n";
echo "Night Diff Count: $night_diff_count\n";
echo "Night Diff Pay: ₱" . number_format($night_diff_count * 52, 2) . "\n";
echo "SIL Count: $sil_count\n";
echo "SIL Pay: ₱" . number_format($sil_count * $daily_rate, 2) . "\n";
echo "Holiday Premium: ₱" . number_format($holiday_premium, 2) . "\n";

$allowance = ($daily_rate > 520) ? (20 * $total_days) : 0;
echo "Allowance: ₱" . number_format($allowance, 2) . "\n\n";

$basic_pay = $total_days * $daily_rate;
$gross = $basic_pay + $total_ot_pay + ($night_diff_count * 52) + $holiday_premium + ($sil_count * $daily_rate) + $allowance;

echo "Basic Pay: ₱" . number_format($basic_pay, 2) . "\n";
echo "GROSS INCOME: ₱" . number_format($gross, 2) . "\n\n";

// Get deductions
$ded_query = $conn->query("SELECT sss, phic, hdmf, govt FROM employees WHERE name = '$employee'");
$ded_row = $ded_query->fetch_assoc();
$sss = (float)$ded_row['sss'];
$phic = (float)$ded_row['phic'];
$hdmf = (float)$ded_row['hdmf'];
$govt = (float)$ded_row['govt'];

echo "Deductions:\n";
echo "SSS: ₱" . number_format($sss, 2) . "\n";
echo "PHIC: ₱" . number_format($phic, 2) . "\n";
echo "HDMF: ₱" . number_format($hdmf, 2) . "\n";
echo "Govt: ₱" . number_format($govt, 2) . "\n";

$total_deductions = $sss + $phic + $hdmf + $govt;
echo "Total Deductions: ₱" . number_format($total_deductions, 2) . "\n\n";

$net_income = $gross - $total_deductions;
echo "NET INCOME: ₱" . number_format($net_income, 2) . "\n";
echo "EXPECTED: ₱13,717.89\n";
echo "DIFFERENCE: ₱" . number_format(13717.89 - $net_income, 2) . "\n";

$conn->close();
?>
