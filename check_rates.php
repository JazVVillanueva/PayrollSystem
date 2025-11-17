<?php
include 'db_connect.php';

$query = "SELECT name, rate FROM employees ORDER BY name ASC";
$result = $conn->query($query);

echo "Employee Rates:\n";
echo str_repeat("=", 50) . "\n";
while($row = $result->fetch_assoc()) {
    echo sprintf("%-30s ₱%s\n", $row['name'], $row['rate']);
}

$conn->close();
?>
