<?php
require 'db_connect.php';

$emp_q = $conn->query("SELECT * FROM employees WHERE name='Kent, Clark'");
$emp = $emp_q->fetch_assoc();

echo "Kent Clark Employee Info:\n";
echo "Rate: " . $emp['rate'] . "\n";
echo "SSS: " . $emp['sss'] . "\n";
echo "PHIC: " . $emp['phic'] . "\n";
echo "HDMF: " . $emp['hdmf'] . "\n";
echo "GOVT: " . $emp['govt'] . "\n";

$total_ded = $emp['sss'] + $emp['phic'] + $emp['hdmf'] + $emp['govt'];
echo "\nTotal Deductions: " . $total_ded . "\n";

echo "\nFrom earlier calculation:\n";
echo "Gross: 11,180\n";
echo "Less Deductions: $total_ded\n";
echo "Net: " . (11180 - $total_ded) . "\n";
echo "\nExpected Net: 10,625\n";
echo "Difference: " . (10625 - (11180 - $total_ded)) . "\n";
?>
