<?php
include 'db_connect.php';

// Excel expected values (from the image)
$excel_data = [
    'Kent, Clark' => ['days'=>22, 'rate'=>520, 'ot'=>7, 'allowance'=>0, 'night'=>624, 'holiday'=>676, 'sil'=>0, 'gross'=>11700, 'net'=>10625],
    'Wayne, Bruce' => ['days'=>22, 'rate'=>520, 'ot'=>16, 'allowance'=>960, 'night'=>0, 'holiday'=>676, 'sil'=>0, 'gross'=>14116, 'net'=>12126.29],
    'Prince, Diana' => ['days'=>22, 'rate'=>520, 'ot'=>6, 'allowance'=>420, 'night'=>468, 'holiday'=>676, 'sil'=>0, 'gross'=>13394, 'net'=>12307.89],
    'Allen, Barry' => ['days'=>22, 'rate'=>520, 'ot'=>15, 'allowance'=>200, 'night'=>260, 'holiday'=>676, 'sil'=>0, 'gross'=>13551, 'net'=>13539.89],
    'Jordan, Hal' => ['days'=>22, 'rate'=>520, 'ot'=>1, 'allowance'=>0, 'night'=>260, 'holiday'=>676, 'sil'=>0, 'gross'=>12441, 'net'=>11354.89],
    'Hall, Carter' => ['days'=>21, 'rate'=>520, 'ot'=>26, 'allowance'=>860, 'night'=>520, 'holiday'=>676, 'sil'=>0, 'gross'=>13080, 'net'=>11993.89],
    'Rhodes, James' => ['days'=>12, 'rate'=>520, 'ot'=>10, 'allowance'=>0, 'night'=>0, 'holiday'=>520, 'sil'=>2600, 'gross'=>10010, 'net'=>8785],
];

echo "DETAILED COMPONENT COMPARISON\n";
echo str_repeat("=", 120) . "\n\n";

foreach ($excel_data as $emp_name => $expected) {
    echo "Employee: $emp_name\n";
    echo str_repeat("-", 120) . "\n";
    
    // Run the same calculation logic as salary_summary.php
    $employee = $emp_name;
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
    $total_overtime_hours = 0;
    $total_night_diff = 0;
    $total_holiday_premium = 0;
    $total_sil_count = 0;
    $total_cashier_hours = 0;
    $total_overtime_pay = 0.0;
    $daily_rate = $empRate[$employee] ?? 520.0;
    
    $holiday_dates = [
        '2025-01-01' => 1.00,
        '2025-01-06' => 1.00,
        '2025-01-29' => 0.30,
    ];
    
    $processed_dates = [];
    $processed_holidays = [];
    $dayBuckets = [];
    $seen_rows = [];
    $late_absent = 0;
    
    while ($row = $result->fetch_assoc()) {
        $date = $row['Date'];
        $hours = (float)$row['Hours'];
        $role = $row['Role'];
        $remarks = $row['Remarks'];
        $sil_col = $row['Short_Misload_Bonus_SIL'];
        $dept_row = $row['Business_Unit'] ?? '';
        $shift_no = isset($row['Shift_No']) ? (int)$row['Shift_No'] : 0;
        $regular_hours_row = (stripos($dept_row, 'Canteen') !== false) ? 12 : 8;
        $ot_rate_row = $daily_rate / $regular_hours_row;
        
        $row_key = $date . '|' . $row['Time_IN'] . '|' . $row['Time_OUT'] . '|' . $hours . '|' . $remarks;
        if (in_array($row_key, $seen_rows)) continue;
        $seen_rows[] = $row_key;
        
        if (empty($row['Time_IN']) && empty($row['Time_OUT']) && $hours == 0) continue;
        
        $is_ot = stripos($remarks, 'Overtime') !== false;
        if (!isset($dayBuckets[$date])) {
            $dayBuckets[$date] = ['base' => 0, 'saw_any_row' => true, 'has_regular_work' => false];
        }
        if (!$is_ot && $hours > 0) {
            $dayBuckets[$date]['has_regular_work'] = true;
            $dayBuckets[$date]['base'] += $hours;
        }
        
        $has_sil = stripos($sil_col, 'SIL') !== false;
        if (!$has_sil && !in_array($date, $processed_dates)) {
            $total_days_worked++;
            $processed_dates[] = $date;
        }
        
        $is_holiday = array_key_exists($date, $holiday_dates);
        $holiday_multiplier = $is_holiday ? $holiday_dates[$date] : 0;
        
        if ($is_ot) {
            $total_overtime_hours += $hours;
            $total_overtime_pay += $hours * $ot_rate_row;
        }
        
        if ($shift_no == 3) {
            $total_night_diff += 52;
        }
        
        if (!in_array($date, $processed_holidays) && $is_holiday && !$is_ot) {
            $total_holiday_premium += $daily_rate * $holiday_multiplier;
            $processed_holidays[] = $date;
        }
        
        $total_sil_count += substr_count($sil_col, 'SIL');
        
        if ($role === 'Cashier') {
            $total_cashier_hours += $hours;
        }
        
        if (stripos($remarks, 'Late') !== false) {
            $late_absent += 150;
        }
    }
    
    $regular_work_days = 0;
    foreach ($dayBuckets as $d => $info) {
        if ($info['has_regular_work']) {
            $regular_work_days++;
        }
    }
    $worked_days = $regular_work_days > 0 ? $regular_work_days : count($processed_dates);
    $total_allowance = ($daily_rate > 520) ? (20 * $worked_days) : 0;
    
    $basic_pay = $total_days_worked * $daily_rate;
    $overtime_pay = $total_overtime_pay;
    $night_diff_pay = $total_night_diff;
    $holiday_pay = $total_holiday_premium;
    $sil_pay = $total_sil_count * $daily_rate;
    $cashier_pay = floor($total_cashier_hours / 8) * 40;
    
    // Display comparison
    printf("%-20s Expected: %6d  Calculated: %6d  Diff: %6d\n", "Days Worked:", $expected['days'], $total_days_worked, $expected['days'] - $total_days_worked);
    printf("%-20s Expected: %6.0f  Calculated: %6.0f  Diff: %6.0f\n", "Rate:", $expected['rate'], $daily_rate, $expected['rate'] - $daily_rate);
    printf("%-20s Expected: %6.0f  Calculated: %6.0f  Diff: %6.0f\n", "OT Hours:", $expected['ot'], $total_overtime_hours, $expected['ot'] - $total_overtime_hours);
    printf("%-20s Expected: %6.0f  Calculated: %6.0f  Diff: %6.0f\n", "Allowance:", $expected['allowance'], $total_allowance, $expected['allowance'] - $total_allowance);
    printf("%-20s Expected: %6.0f  Calculated: %6.0f  Diff: %6.0f\n", "Night Diff:", $expected['night'], $night_diff_pay, $expected['night'] - $night_diff_pay);
    printf("%-20s Expected: %6.0f  Calculated: %6.0f  Diff: %6.0f\n", "Holiday:", $expected['holiday'], $holiday_pay, $expected['holiday'] - $holiday_pay);
    printf("%-20s Expected: %6.0f  Calculated: %6.0f  Diff: %6.0f\n", "SIL:", $expected['sil'], $sil_pay, $expected['sil'] - $sil_pay);
    printf("%-20s Expected: %6.0f  Calculated: %6.0f  Diff: %6.0f\n", "Basic Pay:", $expected['days'] * $expected['rate'], $basic_pay, ($expected['days'] * $expected['rate']) - $basic_pay);
    printf("%-20s Expected: %6.0f  Calculated: %6.0f  Diff: %6.0f\n", "Cashier Pay:", 0, $cashier_pay, 0 - $cashier_pay);
    
    echo "\n";
    $stmt->close();
}

$conn->close();
?>
