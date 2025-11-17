<?php
require 'db_connect.php';

echo "=== Barnes James - 1/16 entries ===\n";
$r1 = $conn->query("SELECT id, Time_IN, Time_OUT, Hours, Remarks FROM timesheet WHERE Name='Barnes, James' AND Date='2025-01-16'");
while ($row = $r1->fetch_assoc()) {
    echo "ID:" . $row['id'] . " | " . $row['Time_IN'] . "-" . $row['Time_OUT'] . " | Hrs:" . $row['Hours'] . " | " . $row['Remarks'] . "\n";
}

echo "\n=== Barnes James - 1/30 entries ===\n";
$r2 = $conn->query("SELECT id, Time_IN, Time_OUT, Hours, Remarks FROM timesheet WHERE Name='Barnes, James' AND Date='2025-01-30'");
while ($row = $r2->fetch_assoc()) {
    echo "ID:" . $row['id'] . " | " . $row['Time_IN'] . "-" . $row['Time_OUT'] . " | Hrs:" . $row['Hours'] . " | " . $row['Remarks'] . "\n";
}

// Delete the extra entries (keep first, delete second)
echo "\n\nDeleting second entry for each duplicate date...\n";

// Get IDs to delete
$ids_to_delete = [];
$dates = ['2025-01-16', '2025-01-30'];
foreach ($dates as $date) {
    $r = $conn->query("SELECT id FROM timesheet WHERE Name='Barnes, James' AND Date='$date' ORDER BY id LIMIT 1 OFFSET 1");
    if ($row = $r->fetch_assoc()) {
        $ids_to_delete[] = $row['id'];
    }
}

foreach ($ids_to_delete as $id) {
    $conn->query("DELETE FROM timesheet WHERE id=$id");
    echo "Deleted ID: $id\n";
}

// Verify
$barnes_days = $conn->query("SELECT COUNT(DISTINCT Date) as days FROM timesheet WHERE Name='Barnes, James' AND Date BETWEEN '2025-01-03' AND '2025-01-30'");
$bd = $barnes_days->fetch_assoc();
echo "\nBarnes now has " . $bd['days'] . " days (expected 25)\n";

$conn->close();
?>
