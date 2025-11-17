<?php
require 'db_connect.php';

echo "Kent's entries on 1/10-1/12 (SIL dates + 1/12 regular work):\n\n";

$r = $conn->query("SELECT Date, Shift_No, Hours, Remarks, Short_Misload_Bonus_SIL FROM timesheet WHERE Name='Kent, Clark' AND Date BETWEEN '2025-01-10' AND '2025-01-12' ORDER BY Date, Shift_No");

while ($row = $r->fetch_assoc()) {
    echo $row['Date'] . " | Shift:" . $row['Shift_No'] . " | Hrs:" . $row['Hours'] . " | Remarks:" . $row['Remarks'] . " | SIL:" . $row['Short_Misload_Bonus_SIL'] . "\n";
}

echo "\n\nHypothesis: Maybe one of the SIL days (with 0 hours) should STILL count as a work day for basic pay?\n";
echo "Or maybe 1/12 which has BOTH SIL and regular work should count as 2 days?\n";
echo "\nLet me check if we should count 'all distinct dates' instead of 'non-SIL dates':\n\n";

$all_dates = $conn->query("SELECT COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='Kent, Clark' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
$all_row = $all_dates->fetch_assoc();
echo "All distinct dates: " . $all_row['days'] . "\n";

$non_sil = $conn->query("SELECT COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='Kent, Clark' AND Date BETWEEN '2025-01-03' AND '2025-01-30' AND (Short_Misload_Bonus_SIL NOT LIKE '%SIL%' OR Short_Misload_Bonus_SIL = '' OR Short_Misload_Bonus_SIL IS NULL)");
$non_sil_row = $non_sil->fetch_assoc();
echo "Non-SIL dates: " . $non_sil_row['days'] . "\n";

echo "\nCurrent: Using " . $non_sil_row['days'] . " days → Net = 10,105\n";
echo "Need: " . ($non_sil_row['days'] + 1) . " days → Net = 10,625\n";
echo "If use all dates: " . $all_row['days'] . " days → Net = " . (10105 + ($all_row['days'] - $non_sil_row['days']) * 520) . "\n";
?>
