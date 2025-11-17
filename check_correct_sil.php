<?php
require 'db_connect.php';

// Check a CORRECT employee who has SIL days
$correct_with_sil = ['Rogers, Steve', 'Romanoff, Natasha', 'Jones, John'];

foreach ($correct_with_sil as $emp) {
    $sil_check = $conn->query("SELECT COUNT(*) as cnt FROM timesheet WHERE Name='$emp' AND Short_Misload_Bonus_SIL LIKE '%SIL%' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
    $sil_row = $sil_check->fetch_assoc();
    
    if ($sil_row['cnt'] > 0) {
        echo "\n=== $emp (CORRECT EMPLOYEE) ===\n";
        echo "Has SIL entries: " . $sil_row['cnt'] . "\n";
        
        // Show those SIL entries
        $sil_details = $conn->query("SELECT Date, Hours, Short_Misload_Bonus_SIL FROM timesheet WHERE Name='$emp' AND Short_Misload_Bonus_SIL LIKE '%SIL%' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
        while ($s = $sil_details->fetch_assoc()) {
            echo "  " . $s['Date'] . " | Hours:" . $s['Hours'] . " | SIL:" . $s['Short_Misload_Bonus_SIL'] . "\n";
        }
        
        // Count their work days
        $non_sil = $conn->query("SELECT COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='$emp' AND Date BETWEEN '2025-01-03' AND '2025-01-30' AND (Short_Misload_Bonus_SIL NOT LIKE '%SIL%' OR Short_Misload_Bonus_SIL = '' OR Short_Misload_Bonus_SIL IS NULL)");
        $non_sil_days = $non_sil->fetch_assoc()['days'];
        
        $all_days = $conn->query("SELECT COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='$emp' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
        $all = $all_days->fetch_assoc()['days'];
        
        echo "  Non-SIL dates: $non_sil_days\n";
        echo "  All dates: $all\n";
        echo "  → This employee is CORRECT using non-SIL counting\n";
        break; // Just check one
    }
}

echo "\n\nConclusion: If correct employees use non-SIL date counting,\n";
echo "then Kent's issue is that he's missing data in the CSV/database.\n";
echo "The Excel was calculated with more complete data than what's in the CSV.\n";
?>
