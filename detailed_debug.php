<?php
include 'db_connect.php';

$test_employees = ['Grimm, Ben', 'Lang, Scott', 'Curry, Arthur', 'Palmer, Ray', 'Stark, Toni'];
$start_date = '2025-01-03';
$end_date = '2025-01-30';

foreach ($test_employees as $employee) {
    echo "\n=== $employee ===\n";
    
    $rate_query = $conn->query("SELECT rate FROM employees WHERE name = '$employee'");
    $rate_row = $rate_query->fetch_assoc();
    $daily_rate = (float)$rate_row['rate'];
    
    $stmt = $conn->prepare("SELECT Date, Time_IN, Time_OUT, Hours, Shift_No, Role, Remarks, Short_Misload_Bonus_SIL, Business_Unit FROM timesheet WHERE Name = ? AND Date BETWEEN ? AND ? ORDER BY Date ASC");
    $stmt->bind_param("sss", $employee, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dates_with_regular_work = [];
    $dates_with_ot_only = [];
    $seen_rows = [];
    
    while ($row = $result->fetch_assoc()) {
        $row_key = $row['Date'] . '|' . $row['Time_IN'] . '|' . $row['Time_OUT'] . '|' . $row['Hours'] . '|' . $row['Remarks'];
        if (in_array($row_key, $seen_rows)) {
            continue;
        }
        $seen_rows[] = $row_key;
        
        $is_ot = stripos($row['Remarks'], 'Overtime') !== false;
        $has_sil = stripos($row['Short_Misload_Bonus_SIL'], 'SIL') !== false;
        
        if (!$is_ot && !$has_sil) {
            $dates_with_regular_work[] = $row['Date'];
        } elseif ($is_ot) {
            $dates_with_ot_only[] = $row['Date'];
        }
    }
    
    $unique_regular_dates = array_unique($dates_with_regular_work);
    $unique_ot_dates = array_unique($dates_with_ot_only);
    $ot_only_dates = array_diff($unique_ot_dates, $unique_regular_dates);
    
    echo "Rate: $daily_rate\n";
    echo "Regular work days: " . count($unique_regular_dates) . "\n";
    echo "OT-only days: " . count($ot_only_dates) . " - " . implode(', ', $ot_only_dates) . "\n";
    echo "Allowance eligible: " . ($daily_rate > 520 ? 'YES' : 'NO') . "\n";
    echo "Expected allowance days: " . count($unique_regular_dates) . "\n";
    
    $stmt->close();
}

$conn->close();
?>
