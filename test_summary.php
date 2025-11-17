<?php
// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['start_date'] = '2025-01-03';
$_POST['end_date'] = '2025-01-30';

// Capture output
ob_start();
include 'salary_summary.php';
$output = ob_get_clean();

// Extract employee data from table
preg_match_all('/<tr>\s*<td>([^<]+)<\/td>\s*<td>([\d,\.]+)\s+PHP<\/td>\s*<\/tr>/s', $output, $matches, PREG_SET_ORDER);

echo "Current Net Income Values:\n";
echo str_repeat("=", 50) . "\n";
foreach($matches as $match) {
    $name = trim($match[1]);
    $amount = trim($match[2]);
    if($name !== 'TOTAL') {
        echo sprintf("%-30s ₱%s\n", $name, $amount);
    }
}

// Extract total
if(preg_match('/<th[^>]*>TOTAL<\/th>\s*<th[^>]*>([\d,\.]+)\s+PHP<\/th>/s', $output, $totalMatch)) {
    echo str_repeat("=", 50) . "\n";
    echo sprintf("%-30s ₱%s\n", "TOTAL", $totalMatch[1]);
}
?>
