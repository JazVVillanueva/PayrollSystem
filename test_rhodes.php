<?php
include 'db_connect.php';

// Query Rhodes James specific data
$employee = 'Rhodes, James';
$start_date = '2025-01-03';
$end_date = '2025-01-30';

echo "=== RHODES JAMES ANALYSIS ===\n\n";

// Get employee rate
$rate_query = $conn->query("SELECT rate FROM employees WHERE name = 'Rhodes, James'");
$rate_row = $rate_query->fetch_assoc();
$daily_rate = $rate_row['rate'];
echo "Daily Rate: $daily_rate\n\n";

// Get all timesheet entries
$stmt = $conn->prepare("SELECT Date, Hours, Role, Remarks, Short_Misload_Bonus_SIL FROM timesheet WHERE Name = ? AND Date BETWEEN ? AND ? ORDER BY Date ASC");
$stmt->bind_param("sss", $employee, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

echo "Date\t\tHours\tRole\t\tRemarks\t\tDeductions\tSIL\n";
echo str_repeat("-", 90) . "\n";

$total_days = 0;
$total_sil = 0;
$worked_dates = [];
$has_deductions = false;

while ($row = $result->fetch_assoc()) {
    $date = $row['Date'];
    $hours = $row['Hours'];
    $role = $row['Role'];
    $remarks = $row['Remarks'];
    $sil = $row['Short_Misload_Bonus_SIL'];
    $deduct = $row['Deductions'] ??  '';
    
    if (!empty($deduct)) {
        $has_deductions = true;
    }
    
    echo "$date\t$hours\t$role\t$remarks\t\t$deduct\t\t$sil\n";
    
    if (!in_array($date, $worked_dates)) {
        $worked_dates[] = $date;
        $total_days++;
    }
    
    if (stripos($sil, 'SIL') !== false) {
        $total_sil++;
    }
}

echo "\n";
echo "Total unique dates: " . count($worked_dates) . "\n";
echo "Total SIL count: $total_sil\n";
echo "Days worked (non-SIL): " . (count($worked_dates) - $total_sil) . "\n";

// Calculate expected values
echo "\n=== EXPECTED CALCULATIONS ===\n";
echo "Basic Pay (days without SIL): " . ((count($worked_dates) - $total_sil) * $daily_rate) . "\n";
echo "SIL Pay: " . ($total_sil * $daily_rate) . "\n";
echo "Three Kings Day (1/6): " . ($daily_rate * 1.00) . "\n";
echo "Chinese New Year (1/29): " . ($daily_rate * 0.30) . "\n";
echo "Night Diff from SIL: " . ($total_sil * 52) . "\n";

$stmt->close();
$conn->close();
?>
