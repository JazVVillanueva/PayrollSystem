<?php
require 'db_connect.php';

// Check employees that are off by exactly -520 (one day)
$off_by_520 = ['Barnes, James', 'Kent, Clark', 'Raymond, Ronnie'];

foreach ($off_by_520 as $emp) {
    echo "\n=== $emp ===\n";
    
    // Count various day types
    $non_sil_q = $conn->query("SELECT COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='$emp' AND Date BETWEEN '2025-01-03' AND '2025-01-30' AND (Short_Misload_Bonus_SIL NOT LIKE '%SIL%' OR Short_Misload_Bonus_SIL = '' OR Short_Misload_Bonus_SIL IS NULL)");
    $non_sil = $non_sil_q->fetch_assoc()['days'];
    
    $all_q = $conn->query("SELECT COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='$emp' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
    $all = $all_q->fetch_assoc()['days'];
    
    $sil_q = $conn->query("SELECT COUNT(*) as cnt FROM timesheet WHERE Name='$emp' AND Date BETWEEN '2025-01-03' AND '2025-01-30' AND Short_Misload_Bonus_SIL LIKE '%SIL%'");
    $sil_count = $sil_q->fetch_assoc()['cnt'];
    
    echo "Non-SIL dates: $non_sil\n";
    echo "All distinct dates: $all\n";
    echo "SIL entries count: $sil_count\n";
    echo "Difference: $all - $non_sil = " . ($all - $non_sil) . "\n";
    echo "\nCurrent code uses non-SIL dates for days_worked: $non_sil\n";
    echo "If we need +1 day, should use: " . ($non_sil + 1) . "\n";
}

// Check one correct employee for comparison
echo "\n\n=== COMPARISON: Allen, Barry (CORRECT) ===\n";
$non_sil_q = $conn->query("SELECT COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='Allen, Barry' AND Date BETWEEN '2025-01-03' AND '2025-01-30' AND (Short_Misload_Bonus_SIL NOT LIKE '%SIL%' OR Short_Misload_Bonus_SIL = '' OR Short_Misload_Bonus_SIL IS NULL)");
$non_sil = $non_sil_q->fetch_assoc()['days'];
echo "Non-SIL dates: $non_sil\n";
echo "This employee is CORRECT, so this is the right day count method\n";
?>
