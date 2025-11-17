<?php
include 'db_connect.php';

$employee = 'Barnes, James';
$start_date = '2025-01-03';
$end_date = '2025-01-30';

$rate_query = $conn->query("SELECT rate FROM employees WHERE name = '$employee'");
$rate_row = $rate_query->fetch_assoc();
$daily_rate = (float)$rate_row['rate'];

echo "=== Barnes, James Analysis ===\n";
echo "Rate: $daily_rate\n\n";

$stmt = $conn->prepare("SELECT Date, Time_IN, Time_OUT, Hours, Shift_No, Business_Unit, Role, Remarks, Short_Misload_Bonus_SIL, Deductions FROM timesheet WHERE Name = ? AND Date BETWEEN ? AND ? ORDER BY Date ASC");
$stmt->bind_param("sss", $employee, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$dates_with_regular_work = [];
$dates_with_ot_only = [];
$all_dates = [];
$seen_rows = [];

echo "Entries:\n";
while ($row = $result->fetch_assoc()) {
    $row_key = $row['Date'] . '|' . $row['Time_IN'] . '|' . $row['Time_OUT'] . '|' . $row['Hours'] . '|' . $row['Remarks'];
    if (in_array($row_key, $seen_rows)) {
        echo "  [DUPLICATE SKIPPED] {$row['Date']}\n";
        continue;
    }
    $seen_rows[] = $row_key;
    
    $is_ot = stripos($row['Remarks'], 'Overtime') !== false;
    $has_sil = stripos($row['Short_Misload_Bonus_SIL'], 'SIL') !== false;
    $is_canteen = stripos($row['Business_Unit'], 'Canteen') !== false;
    
    echo "  {$row['Date']}: {$row['Hours']}h, {$row['Remarks']}, " . ($is_canteen ? 'CANTEEN' : 'Regular') . ", " . ($is_ot ? 'OT' : 'REGULAR') . "\n";
    
    if (!$has_sil) {
        $all_dates[] = $row['Date'];
        if (!$is_ot) {
            $dates_with_regular_work[] = $row['Date'];
        } elseif ($is_ot) {
            $dates_with_ot_only[] = $row['Date'];
        }
    }
}

$unique_all_dates = array_unique($all_dates);
$unique_regular_dates = array_unique($dates_with_regular_work);
$unique_ot_dates = array_unique($dates_with_ot_only);
$ot_only_dates = array_diff($unique_ot_dates, $unique_regular_dates);

echo "\nSummary:\n";
echo "Total unique dates (all): " . count($unique_all_dates) . "\n";
echo "Regular work dates: " . count($unique_regular_dates) . "\n";
echo "OT-only dates: " . count($ot_only_dates) . " - " . implode(', ', $ot_only_dates) . "\n";
echo "\nCurrent logic:\n";
echo "  Days counted (all dates): " . count($unique_all_dates) . "\n";
echo "  Days for allowance (regular only): " . count($unique_regular_dates) . "\n";
echo "  Basic pay: " . count($unique_all_dates) . " × $daily_rate = " . (count($unique_all_dates) * $daily_rate) . "\n";
echo "  Allowance: " . ($daily_rate > 520 ? (20 * count($unique_regular_dates)) : 0) . "\n";

$stmt->close();
$conn->close();
?>
