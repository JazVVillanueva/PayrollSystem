<?php
include 'db_connect.php';
$r = $conn->query('SHOW COLUMNS FROM timesheet');
echo "Timesheet Columns:\n";
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
