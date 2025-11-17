<?php
include 'db_connect.php';

$employee = 'Kent, Clark';
$start_date = '2025-01-03';
$end_date = '2025-01-30';

$stmt = $conn->prepare("SELECT * FROM timesheet WHERE Name = ? AND Date BETWEEN ? AND ? ORDER BY Date ASC");
$stmt->bind_param("sss", $employee, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

echo "=== Kent, Clark Analysis ===\n\n";

$dates = [];
$night_shift_count = 0;
$sil_count = 0;
$seen_rows = [];

while ($row = $result->fetch_assoc()) {
    $row_key = $row['Date'] . '|' . $row['Time_IN'] . '|' . $row['Time_OUT'] . '|' . $row['Hours'] . '|' . $row['Remarks'];
    if (in_array($row_key, $seen_rows)) {
        continue;
    }
    $seen_rows[] = $row_key;
    
    $is_ot = stripos($row['Remarks'], 'Overtime') !== false;
    $has_sil = stripos($row['Short_Misload_Bonus_SIL'], 'SIL') !== false;
    $shift_no = isset($row['Shift_No']) ? (int)$row['Shift_No'] : 0;
    
    echo "{$row['Date']}: Shift={$shift_no}, Hours={$row['Hours']}, Remarks={$row['Remarks']}, SIL=" . ($has_sil ? 'YES' : 'NO') . "\n";
    
    if (!$has_sil) {
        $dates[] = $row['Date'];
    }
    
    if ($shift_no == 3) {
        $night_shift_count++;
    }
    
    if ($has_sil) {
        $sil_count++;
    }
}

$unique_dates = array_unique($dates);

echo "\nSummary:\n";
echo "Unique non-SIL dates: " . count($unique_dates) . "\n";
echo "Night shifts (Shift_No=3): $night_shift_count\n";
echo "SIL count: $sil_count\n";
echo "Expected days from Excel: 27\n";
echo "Expected night diff from Excel: 624 ÷ 52 = " . (624/52) . " nights\n";

$stmt->close();
$conn->close();
?>
